<?php
/**
 * SafeHaven - Evacuation Controller
 * Handles evacuation requests and centers
 */

require_once MODEL_PATH . 'EvacuationModel.php';
require_once MODEL_PATH . 'EvacuationCenterModel.php';
require_once MODEL_PATH . 'CapacityModel.php';

class EvacuationController {
    
    /**
     * Show evacuation request form
     */
    public static function requestForm() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        // CSRF token — set here so the view can use $csrfToken directly
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $csrfToken = $_SESSION['csrf_token'];
        
        $pageTitle  = 'Evacuation Request - SafeHaven';
        $activePage = 'evacuation-request';
        $extraCss   = ['assets/css/safehaven-system.css','assets/css/evacuation-request.css'];
        $extraJs    = ['assets/js/evacuation-request.js'];
        
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/evacuation-request.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }
    
    /**
     * Submit evacuation request (AJAX endpoint)
     */
    public static function submitRequest() {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized. Please log in.'
            ]);
            exit;
        }
        
        // Check request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);
            exit;
        }
        
        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            // Fallback to regular POST
            $data = $_POST;
        }
        
        // Validate CSRF token
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid security token. Please refresh the page.'
            ]);
            exit;
        }
        
        try {
            // Find best available evacuation center
            $center = EvacuationCenterModel::findBestAvailable();

            if (!$center) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No evacuation centers are available right now. Please try again later.'
                ]);
                exit;
            }

            // Parse special needs
            $specialNeeds = [];
            if (isset($data['special_needs'])) {
                if (is_array($data['special_needs'])) {
                    $specialNeeds = $data['special_needs'];
                } elseif (is_string($data['special_needs']) && !empty($data['special_needs'])) {
                    $specialNeeds = array_filter(explode(',', $data['special_needs']));
                }
            }

            $familyMembers = max(1, intval($data['family_members'] ?? 1));

            // Build request data - only pass what we have
            $requestData = [
                'user_id'            => $_SESSION['user_id'],
                'center_id'          => $center['id'],
                'location_street'    => $data['location_street']    ?? '',
                'location_barangay'  => $data['location_barangay']  ?? '',
                'location_city'      => $data['location_city']       ?? 'Bacolod City',
                'location_latitude'  => $data['location_latitude']  ?? null,
                'location_longitude' => $data['location_longitude'] ?? null,
                'priority'           => $data['priority']            ?? 'unaccompanied',
                'family_members'     => $familyMembers,
                'special_needs'      => $specialNeeds,
                'status'             => 'pending',
                'notes'              => null,
            ];

            // Save to DB (create() auto-detects which columns exist)
            $request = EvacuationModel::create($requestData);

            // ── Send SMS confirmation to the user ────────────────────────────
            try {
                require_once ROOT_PATH . 'services/PhilSmsService.php';
                require_once MODEL_PATH . 'UserModel.php';
                $userRow = UserModel::getById($_SESSION['user_id']);
                $primaryPhone = trim($userRow['phone_number'] ?? '');

                // Collect recipients: user + family members
                $recipients = [];
                if ($primaryPhone) {
                    $recipients[] = PhilSmsService::formatNumber($primaryPhone);
                }
                $familyJson = $userRow['family_numbers'] ?? '';
                if ($familyJson) {
                    $familyNums = json_decode($familyJson, true);
                    if (is_array($familyNums)) {
                        foreach ($familyNums as $fn) {
                            $fn = trim($fn);
                            if ($fn) $recipients[] = PhilSmsService::formatNumber($fn);
                        }
                    }
                }

                if (!empty($recipients) && $request) {
                    $smsText = PhilSmsService::buildEvacuationMessage($request, $center);
                    PhilSmsService::send(array_unique($recipients), $smsText);
                }
            } catch (Exception $smsEx) {
                error_log('[PhilSMS] Evacuation submit error: ' . $smsEx->getMessage());
            }
            // ─────────────────────────────────────────────────────────────────

            // Do NOT increment occupancy here — admin must approve first
            $updatedCenter = EvacuationCenterModel::getById($center['id']);
            $requestId = $request['id'] ?? 'N/A';

            // Pull values from saved request if available, otherwise use what we know
            $confirmationCode = $request['confirmation_code']
                ?? ('EVAC-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 7)));
            $savedFamily      = $request['family_members'] ?? $familyMembers;
            $savedNeeds       = isset($request['special_needs']) && is_array($request['special_needs'])
                ? $request['special_needs']
                : $specialNeeds;

            $capPct = ($updatedCenter['capacity'] > 0)
                ? round(($updatedCenter['current_occupancy'] / $updatedCenter['capacity']) * 100)
                : 0;

            echo json_encode([
                'success' => true,
                'message' => 'Evacuation request submitted! Waiting for admin approval.',
                'status'  => 'pending',
                'request' => [
                    'id'                => $requestId,
                    'confirmation_code' => $confirmationCode,
                    'family_members'    => $savedFamily,
                    'special_needs'     => $savedNeeds,
                ],
                'center' => [
                    'name'                => $center['name'],
                    'address'             => $center['address'],
                    'barangay'            => $center['barangay'],
                    'contact_number'      => $center['contact_number'],
                    'latitude'            => $center['latitude']  ?? null,
                    'longitude'           => $center['longitude'] ?? null,
                    'distance'            => '—',
                    'travel_time'         => '—',
                    'current_occupancy'   => $updatedCenter['current_occupancy'],
                    'capacity'            => $updatedCenter['capacity'],
                    'occupancy_percentage'=> $capPct,
                ]
            ]);

        } catch (Exception $e) {
            error_log("Evacuation Request Error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            $errMsg = IS_LOCAL
                ? 'Error: ' . $e->getMessage()
                : 'An error occurred while processing your request. Please try again.';
            echo json_encode(['success' => false, 'message' => $errMsg]);
        }
        exit;
    }
    
    /**
     * Show evacuation centers page
     */
    public static function centers() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        $pageTitle = 'Evacuation Centers - SafeHaven';
        $activePage = 'evacuation-centers';
        $extraCss = ['assets/css/safehaven-system.css','assets/css/centers.css'];
        $extraJs  = [];
        
        // Get evacuation centers from database
        $evacuationCenters = EvacuationCenterModel::getAll();
        
        // Get statistics and user role
        $statistics = EvacuationCenterModel::getStatistics();
        
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/centers.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }
    
    /**
     * Show evacuation result page
     */
    public static function result() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        $isApproved = isset($_GET['status']) && $_GET['status'] === 'approved';
        
        $pageTitle = $isApproved ? 'Request Approved - SafeHaven' : 'Center at Capacity - SafeHaven';
        $activePage = 'evacuation-request';
        $extraCss = ['assets/css/EvacuationResult.css'];
        $extraJs = [];
        
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/result.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }

    /**
     * Admin: manage all evacuation requests (separate page)
     */
    public static function adminRequests() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }
        $pageTitle  = 'Evacuation Requests â SafeHaven';
        $activePage = 'admin-evacuation';
        $extraCss   = ['assets/css/safehaven-system.css','assets/css/Capacity.css'];
        $extraJs    = ['assets/js/capacity.js'];
        $allRequests     = EvacuationModel::getAll(200);
        $pendingRequests = array_values(array_filter($allRequests, fn($r) => ($r['status'] ?? '') === 'pending'));
        $otherRequests   = array_values(array_filter($allRequests, fn($r) => ($r['status'] ?? '') !== 'pending'));
        $statistics      = EvacuationCenterModel::getStatistics();
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/admin-evacuation.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }
}
