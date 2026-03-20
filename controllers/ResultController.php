<?php
/**
 * SafeHaven - Result Controller
 * Shows real request data from DB based on confirmation_code or request_id.
 */

require_once MODEL_PATH . 'EvacuationModel.php';
require_once MODEL_PATH . 'EvacuationCenterModel.php';

class ResultController {

    public static function show() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        $request     = null;
        $isApproved  = false;
        $approvalData = [];
        $declineData  = [];

        // ── Try to load real request ──────────────────────────────────────────
        $code      = $_GET['code']       ?? '';
        $requestId = (int)($_GET['request_id'] ?? 0);

        if ($code) {
            $request = EvacuationModel::getByConfirmationCode($code);
        } elseif ($requestId) {
            $request = EvacuationModel::getById($requestId);
        }

        // ── If no real request, use status from query string (fallback) ───────
        if (!$request || empty($request['id'])) {
            $isApproved = isset($_GET['status']) && $_GET['status'] === 'approved';
        } else {
            $isApproved = in_array($request['status'] ?? '', ['approved', 'completed']);
        }

        // ── Build approval data ───────────────────────────────────────────────
        if ($request && !empty($request['id'])) {
            $center = $request['center_id']
                ? EvacuationCenterModel::getById($request['center_id'])
                : null;

            $cap = (int)($center['capacity']          ?? 0);
            $occ = (int)($center['current_occupancy'] ?? 0);
            $capPct = $cap > 0 ? round(($occ / $cap) * 100) : 0;

            $approvalData = [
                'confirmation_code' => $request['confirmation_code'] ?? ('EVAC-' . date('Y') . '-UNKNOWN'),
                'center_name'       => $request['center_name']    ?? ($center['name']    ?? 'N/A'),
                'center_address'    => $request['center_address'] ?? ($center['address'] ?? 'N/A'),
                'distance'          => '—',
                'travel_time'       => '—',
                'capacity_percent'  => $capPct,
                'family_members'    => $request['family_members'] ?? 1,
                'priority'          => $request['priority']       ?? 'N/A',
                'special_needs'     => is_array($request['special_needs'])
                    ? $request['special_needs']
                    : (json_decode($request['special_needs'] ?? '[]', true) ?: []),
                'contact_number'    => $request['center_contact'] ?? ($center['contact_number'] ?? ''),
                'status'            => $request['status']         ?? 'pending',
            ];

            // Alternative centers for declined
            if (!$isApproved) {
                $altCenters = EvacuationCenterModel::getAvailable();
                // Exclude the original center
                $altCenters = array_filter($altCenters, fn($c) => (int)$c['id'] !== (int)($request['center_id'] ?? 0));
                $altCenters = array_values(array_slice($altCenters, 0, 3));

                $declineData = [
                    'original_center'     => $request['center_name'] ?? 'Selected Center',
                    'alternative_centers' => array_map(function($c) {
                        $cap = (int)($c['capacity']          ?? 0);
                        $occ = (int)($c['current_occupancy'] ?? 0);
                        $pct = $cap > 0 ? round(($occ / $cap) * 100) : 0;
                        return [
                            'name'        => $c['name'],
                            'address'     => $c['address'],
                            'distance'    => '—',
                            'travel_time' => '—',
                            'capacity'    => $pct,
                            'status'      => $c['status'],
                            'contact'     => $c['contact_number'] ?? '',
                        ];
                    }, $altCenters),
                ];
            }
        } else {
            // Fallback static demo data
            $approvalData = [
                'confirmation_code' => 'EVAC-2026-DEMO001',
                'center_name'       => 'Barangay 18 High School Evacuation Center',
                'center_address'    => 'Barangay 18, Bacolod City',
                'distance'          => '2.3 km',
                'travel_time'       => '15 minutes',
                'capacity_percent'  => 62,
                'family_members'    => 1,
                'priority'          => 'unaccompanied',
                'special_needs'     => [],
                'contact_number'    => '+63 912 111 2222',
                'status'            => $isApproved ? 'approved' : 'rejected',
            ];
            $declineData = [
                'original_center'     => 'Barangay 18 High School Evacuation Center',
                'alternative_centers' => [],
            ];
        }

        $pageTitle  = $isApproved ? 'SafeHaven – Request Approved' : 'SafeHaven – Center at Capacity';
        $activePage = 'evacuation-request';
        $extraCss   = ['assets/css/EvacuationResult.css'];
        $extraJs    = ['assets/js/result.js'];

        require_once VIEW_PATH . 'shared/dashboard-header.php';
        require_once VIEW_PATH . 'pages/result.php';
        require_once VIEW_PATH . 'shared/footer.php';
    }
}
