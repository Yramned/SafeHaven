<?php
/**
 * SafeHaven - Capacity Controller
 * Manages evacuation center capacity
 */

require_once MODEL_PATH . 'EvacuationCenterModel.php';
require_once MODEL_PATH . 'CapacityModel.php';
require_once MODEL_PATH . 'EvacuationModel.php';

class CapacityController {
    
    /**
     * Show capacity management page
     */
    public static function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        // Check if user is admin
        if (strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }
        
        $pageTitle = 'Capacity Management - SafeHaven';
        $activePage = 'capacity';
        $extraCss = ['assets/css/safehaven-system.css','assets/css/Capacity.css'];
        $extraJs = ['assets/js/capacity.js'];
        
        // Get centers, stats, and recent evacuation requests
        try {
            $centers        = EvacuationCenterModel::getAll();
            $statistics     = EvacuationCenterModel::getStatistics();
            $recentRequests = EvacuationModel::getAll(20);
        } catch (Exception $e) {
            error_log('[CapacityController] index: ' . $e->getMessage());
            $centers        = [];
            $statistics     = [];
            $recentRequests = [];
        }
        
        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/capacity.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }
    
    /**
     * Update center capacity (AJAX endpoint)
     */
    public static function updateOccupancy() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
            exit;
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['center_id']) || !isset($data['occupancy'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
            exit;
        }
        
        try {
            $centerId = intval($data['center_id']);
            $occupancy = intval($data['occupancy']);
            
            $center = EvacuationCenterModel::getById($centerId);
            if (!$center) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Center not found'
                ]);
                exit;
            }
            
            // Update occupancy
            $updated = EvacuationCenterModel::updateOccupancy($centerId, $occupancy);
            
            if ($updated) {
                // Log the change
                CapacityModel::logChange(
                    $centerId,
                    $occupancy,
                    $center['capacity'],
                    'manual-update',
                    $_SESSION['user_id'],
                    'Manual occupancy update by admin'
                );
                
                $updatedCenter = EvacuationCenterModel::getById($centerId);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Occupancy updated successfully',
                    'center' => $updatedCenter
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update occupancy'
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Capacity Update Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred'
            ]);
        }
        exit;
    }
    
    /**
     * Approve or deny an evacuation request (AJAX endpoint)
     */
    public static function updateRequestStatus() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = intval($input['request_id'] ?? 0);
        $action    = $input['action'] ?? '';

        if (!$requestId || !in_array($action, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        try {
            // Get the request so we know the family count and center
            $req = EvacuationModel::getById($requestId);

            if (!$req) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                exit;
            }

            // If approving, increment center occupancy
            if ($action === 'approved' && !empty($req['center_id'])) {
                $familyMembers = (int)($req['family_members'] ?? 1);
                EvacuationCenterModel::incrementOccupancy($req['center_id'], $familyMembers);

                $updatedCenter = EvacuationCenterModel::getById($req['center_id']);
                CapacityModel::logChange(
                    $req['center_id'],
                    $updatedCenter['current_occupancy'],
                    $updatedCenter['capacity'],
                    'evacuation-request',
                    $_SESSION['user_id'],
                    "Request #{$requestId} approved by admin – {$familyMembers} evacuees"
                );
            }

            EvacuationModel::updateStatus($requestId, $action);

            // ── Send SMS on approval ─────────────────────────────────────────
            if ($action === 'approved') {
                try {
                    require_once ROOT_PATH . 'services/PhilSmsService.php';
                    require_once MODEL_PATH . 'UserModel.php';
                    $userRow = UserModel::getById($req['user_id']);
                    $primaryPhone = trim($userRow['phone_number'] ?? '');

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

                    if (!empty($recipients)) {
                        $centerRow = EvacuationCenterModel::getById($req['center_id']);
                        $smsText   = PhilSmsService::buildApprovalMessage($req, $centerRow ?: []);
                        PhilSmsService::send(array_unique($recipients), $smsText);
                    }
                } catch (Exception $smsEx) {
                    error_log('[PhilSMS] Approval SMS error: ' . $smsEx->getMessage());
                }
            }
            // ─────────────────────────────────────────────────────────────────

            echo json_encode([
                'success' => true,
                'message' => 'Request ' . $action,
                'request_id' => $requestId,
                'new_status' => $action,
            ]);

        } catch (Exception $e) {
            error_log("Request Action Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An error occurred']);
        }
        exit;
    }

    /**
     * Get capacity data (AJAX endpoint)
     */
    public static function getData() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized'
            ]);
            exit;
        }
        
        try {
            $centers = EvacuationCenterModel::getAll();
            $statistics = EvacuationCenterModel::getStatistics();
            
            echo json_encode([
                'success' => true,
                'centers' => $centers,
                'statistics' => $statistics
            ]);
            
        } catch (Exception $e) {
            error_log("Get Capacity Data Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Failed to retrieve data'
            ]);
        }
        exit;
    }

    /**
     * Update family_members count on an evacuation request + adjust center occupancy
     */
    public static function updateRequestFamily() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input     = json_decode(file_get_contents('php://input'), true);
        $requestId = intval($input['request_id'] ?? 0);
        $newCount  = intval($input['family_members'] ?? 0);

        if (!$requestId || $newCount < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        try {
            // Get current request
            $req = EvacuationModel::getById($requestId);

            if (!$req || empty($req['id'])) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                exit;
            }

            $oldCount  = (int)($req['family_members'] ?? 1);
            $diff      = $newCount - $oldCount;   // positive = more people, negative = fewer
            $centerId  = $req['center_id'] ?? null;

            // Detect if family_members column exists before updating
            $db   = Database::getInstance()->getConnection();
            $cols = [];
            $res  = $db->query("SHOW COLUMNS FROM evacuation_requests");
            foreach ($res->fetchAll() as $row) { $cols[] = $row['Field']; }

            if (!in_array('family_members', $cols)) {
                echo json_encode(['success' => false, 'message' => 'family_members column does not exist in your database. Please run the ALTER TABLE migration.']);
                exit;
            }

            // Update the request row
            $updSql = in_array('updated_at', $cols)
                ? "UPDATE evacuation_requests SET family_members = ?, updated_at = NOW() WHERE id = ?"
                : "UPDATE evacuation_requests SET family_members = ? WHERE id = ?";
            $stmt = $db->prepare($updSql);
            $stmt->execute([$newCount, $requestId]);

            // Adjust center occupancy by the difference (only for approved requests)
            $status = $req['status'] ?? '';
            if ($centerId && $diff !== 0 && in_array($status, ['approved', 'completed'])) {
                $center = EvacuationCenterModel::getById($centerId);
                if ($center) {
                    $newOccupancy = max(0, (int)$center['current_occupancy'] + $diff);
                    EvacuationCenterModel::updateOccupancy($centerId, $newOccupancy);

                    $updatedCenter = EvacuationCenterModel::getById($centerId);
                    CapacityModel::logChange(
                        $centerId,
                        $updatedCenter['current_occupancy'],
                        $updatedCenter['capacity'],
                        'manual-update',
                        $_SESSION['user_id'],
                        "Request #{$requestId} family count updated: {$oldCount} → {$newCount}"
                    );
                }
            }

            echo json_encode([
                'success'        => true,
                'message'        => 'Family members updated successfully',
                'old_count'      => $oldCount,
                'new_count'      => $newCount,
                'diff'           => $diff,
            ]);

        } catch (Exception $e) {
            error_log("updateRequestFamily Error: " . $e->getMessage());
            $msg = defined('IS_LOCAL') && IS_LOCAL ? $e->getMessage() : 'An error occurred.';
            echo json_encode(['success' => false, 'message' => $msg]);
        }
        exit;
    }

    /**
     * Delete an evacuation request (AJAX endpoint)
     */
    public static function deleteRequest() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input     = json_decode(file_get_contents('php://input'), true);
        $requestId = intval($input['request_id'] ?? 0);

        if (!$requestId) {
            echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
            exit;
        }

        try {
            $db  = Database::getInstance()->getConnection();

            // Get request first so we can reverse occupancy if needed
            $req = EvacuationModel::getById($requestId);

            // If it was approved, subtract the people from center occupancy
            if ($req && !empty($req['center_id']) && in_array($req['status'] ?? '', ['approved', 'completed'])) {
                $familyMembers = (int)($req['family_members'] ?? 1);
                EvacuationCenterModel::decrementOccupancy($req['center_id'], $familyMembers);

                $updatedCenter = EvacuationCenterModel::getById($req['center_id']);
                CapacityModel::logChange(
                    $req['center_id'],
                    $updatedCenter['current_occupancy'] ?? 0,
                    $updatedCenter['capacity']          ?? 0,
                    'manual-update',
                    $_SESSION['user_id'],
                    "Request #{$requestId} deleted — {$familyMembers} evacuees removed"
                );
            }

            $stmt = $db->prepare("DELETE FROM evacuation_requests WHERE id = ?");
            $stmt->execute([$requestId]);

            echo json_encode(['success' => true, 'message' => 'Request deleted', 'request_id' => $requestId]);

        } catch (Exception $e) {
            error_log("deleteRequest Error: " . $e->getMessage());
            $msg = defined('IS_LOCAL') && IS_LOCAL ? $e->getMessage() : 'An error occurred.';
            echo json_encode(['success' => false, 'message' => $msg]);
        }
        exit;
    }

    /**
     * Add a new evacuation center (AJAX endpoint)
     */
    public static function addCenter() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $name     = trim($input['name']           ?? '');
        $barangay = trim($input['barangay']        ?? '');
        $address  = trim($input['address']         ?? '');
        $capacity = (int)($input['capacity']       ?? 0);
        $contact  = trim($input['contact_number']  ?? '');
        $facilities = trim($input['facilities']    ?? '');
        $lat      = $input['latitude']             ?? null;
        $lng      = $input['longitude']            ?? null;

        if (empty($name) || $capacity < 1) {
            echo json_encode(['success' => false, 'message' => 'Name and capacity are required.']);
            exit;
        }

        $ok = EvacuationCenterModel::create([
            'name'              => $name,
            'barangay'          => $barangay ?: null,
            'address'           => $address  ?: null,
            'latitude'          => $lat      ?: null,
            'longitude'         => $lng      ?: null,
            'capacity'          => $capacity,
            'current_occupancy' => 0,
            'status'            => 'accepting',
            'contact_number'    => $contact    ?: null,
            'facilities'        => $facilities ?: null,
        ]);

        if ($ok) {
            $centers    = EvacuationCenterModel::getAll();
            $statistics = EvacuationCenterModel::getStatistics();
            echo json_encode([
                'success'    => true,
                'message'    => 'Evacuation center added successfully.',
                'centers'    => $centers,
                'statistics' => $statistics,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add center. Check database columns.']);
        }
        exit;
    }

    /**
     * Edit an existing evacuation center (AJAX endpoint)
     */
    public static function editCenter() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $centerId = (int)($input['center_id'] ?? 0);

        if (!$centerId) {
            echo json_encode(['success' => false, 'message' => 'Invalid center ID']);
            exit;
        }

        $center = EvacuationCenterModel::getById($centerId);
        if (!$center) {
            echo json_encode(['success' => false, 'message' => 'Center not found']);
            exit;
        }

        $updateData = [];
        if (!empty($input['name']))           $updateData['name']           = trim($input['name']);
        if (!empty($input['barangay']))       $updateData['barangay']       = trim($input['barangay']);
        if (!empty($input['address']))        $updateData['address']        = trim($input['address']);
        if (!empty($input['capacity']))       $updateData['capacity']       = (int)$input['capacity'];
        if (!empty($input['contact_number'])) $updateData['contact_number'] = trim($input['contact_number']);
        if (isset($input['facilities']))      $updateData['facilities']     = trim($input['facilities']);
        if (!empty($input['latitude']))       $updateData['latitude']       = $input['latitude'];
        if (!empty($input['longitude']))      $updateData['longitude']      = $input['longitude'];

        if (empty($updateData)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            exit;
        }

        $ok = EvacuationCenterModel::update($centerId, $updateData);
        if ($ok) {
            // Recalculate status if capacity changed
            if (isset($updateData['capacity'])) {
                EvacuationCenterModel::updateOccupancy($centerId, (int)($center['current_occupancy'] ?? 0));
            }
            $updated    = EvacuationCenterModel::getById($centerId);
            $statistics = EvacuationCenterModel::getStatistics();
            echo json_encode([
                'success'    => true,
                'message'    => 'Center updated successfully.',
                'center'     => $updated,
                'statistics' => $statistics,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update center.']);
        }
        exit;
    }

    /**
     * Delete an evacuation center (AJAX endpoint)
     */
    public static function deleteCenter() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $centerId = (int)($input['center_id'] ?? 0);

        if (!$centerId) {
            echo json_encode(['success' => false, 'message' => 'Invalid center ID']);
            exit;
        }

        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("DELETE FROM evacuation_centers WHERE id = ?");
            $stmt->execute([$centerId]);
            $statistics = EvacuationCenterModel::getStatistics();
            echo json_encode(['success' => true, 'message' => 'Center deleted.', 'statistics' => $statistics]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Could not delete: ' . $e->getMessage()]);
        }
        exit;
    }
}
