<?php

class CapacityModel {
    private $dataFile;
    
    public function __construct() {
        $this->dataFile = STORAGE_PATH . 'capacity_data.json';
        $this->initializeData();
    }
    
    private function initializeData() {
        if (!file_exists($this->dataFile)) {
            $initialData = [
                'capacity' => [
                    'current' => 204,
                    'max' => 300
                ],
                'requests' => [
                    [
                        'id' => 'REQ-001',
                        'name' => 'Maria Santos',
                        'family_size' => 4,
                        'location' => 'Barangay 12',
                        'notes' => 'Has medical needs',
                        'priority' => 'high',
                        'time_ago' => '15 mins ago',
                        'status' => 'pending'
                    ],
                    [
                        'id' => 'REQ-002',
                        'name' => 'Juan Dela Cruz',
                        'family_size' => 6,
                        'location' => 'Barangay 8',
                        'notes' => 'With children',
                        'priority' => 'medium',
                        'time_ago' => '28 mins ago',
                        'status' => 'pending'
                    ],
                    [
                        'id' => 'REQ-003',
                        'name' => 'Rosa Reyes',
                        'family_size' => 3,
                        'location' => 'Barangay 5',
                        'notes' => 'House flooded',
                        'priority' => 'normal',
                        'time_ago' => '45 mins ago',
                        'status' => 'pending'
                    ]
                ]
            ];
            file_put_contents($this->dataFile, json_encode($initialData, JSON_PRETTY_PRINT));
        }
    }
    
    public function getData() {
        return json_decode(file_get_contents($this->dataFile), true);
    }
    
    public function getStats() {
        $data = $this->getData();
        $current = $data['capacity']['current'];
        $max = $data['capacity']['max'];
        $available = $max - $current;
        $percentage = round(($current / $max) * 100);
        $pending = count(array_filter($data['requests'], fn($r) => $r['status'] === 'pending'));
        
        return [
            'current' => $current,
            'max' => $max,
            'available' => $available,
            'percentage' => $percentage,
            'pending' => $pending
        ];
    }
    
    public function getPendingRequests() {
        $data = $this->getData();
        return array_filter($data['requests'], fn($r) => $r['status'] === 'pending');
    }
    
    public function approveRequest($requestId) {
        $data = $this->getData();
        
        foreach ($data['requests'] as $key => &$request) {
            if ($request['id'] === $requestId && $request['status'] === 'pending') {
                $familySize = $request['family_size'];
                
                if ($data['capacity']['current'] + $familySize > $data['capacity']['max']) {
                    return ['success' => false, 'message' => 'Not enough capacity'];
                }
                
                $request['status'] = 'approved';
                $data['capacity']['current'] += $familySize;
                
                $this->saveData($data);
                
                return ['success' => true, 'message' => 'Request approved'];
            }
        }
        
        return ['success' => false, 'message' => 'Request not found'];
    }
    
    public function denyRequest($requestId) {
        $data = $this->getData();
        
        foreach ($data['requests'] as $key => &$request) {
            if ($request['id'] === $requestId && $request['status'] === 'pending') {
                $request['status'] = 'denied';
                
                $this->saveData($data);
                
                return ['success' => true, 'message' => 'Request denied'];
            }
        }
        
        return ['success' => false, 'message' => 'Request not found'];
    }
    
    private function saveData($data) {
        file_put_contents($this->dataFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}

// ═══════════════════════════════════════════════════════
// CONTROLLER - Handle AJAX Requests
// ═══════════════════════════════════════════════════════

$model = new CapacityModel();
$action = $_POST['action'] ?? $_GET['action'] ?? 'view';

$model = new CapacityModel();
$action = $_GET['action'] ?? 'view';

if ($action === 'approve') {
    header('Content-Type: application/json');
    echo json_encode($model->approveRequest($_POST['request_id'] ?? ''));
    exit;
}

if ($action === 'deny') {
    header('Content-Type: application/json');
    echo json_encode($model->denyRequest($_POST['request_id'] ?? ''));
    exit;
}

$stats = $model->getStats();
$requests = $model->getPendingRequests();
$pageTitle  = 'SafeHaven – Capacity Management';
$activePage = 'sms-notifications'; 
$extraCss   = ['assets/css/Capacity.css'];
$extraJs    = ['assets/js/capacity.js'];

require_once VIEW_PATH . 'shared/dashboard-header.php'; 
?>

<div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon purple">
                    <span>👥</span>
                </div>
                <div class="stat-label">CURRENT OCCUPANCY</div>
                <div class="stat-value"><?php echo $stats['percentage']; ?>%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['percentage']; ?>%"></div>
                </div>
                <div class="stat-text"><?php echo $stats['current']; ?> / <?php echo $stats['max']; ?> beds occupied</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <span>✓</span>
                </div>
                <div class="stat-label">AVAILABLE BEDS</div>
                <div class="stat-value"><?php echo $stats['available']; ?></div>
                <div class="stat-text"><?php echo round(($stats['available'] / $stats['max']) * 100); ?>% capacity remaining</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue">
                    <span>🏠</span>
                </div>
                <div class="stat-label">TOTAL CAPACITY</div>
                <div class="stat-value"><?php echo $stats['max']; ?></div>
                <div class="stat-text">Maximum bed capacity</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink">
                    <span>⏳</span>
                </div>
                <div class="stat-label">PENDING REQUESTS</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-text">Awaiting approval</div>
            </div>
        </div>

        <div class="main-section">
            <div class="section">
                <h2>Pending Evacuation Requests</h2>
                
                <div class="requests-list" id="requestsList">
                    <?php if (empty($requests)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">✓</div>
                            <p>No pending requests</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requests as $request): ?>
                        <div class="request-card" data-id="<?php echo $request['id']; ?>">
                            <div class="request-header">
                                <div class="request-name"><?php echo htmlspecialchars($request['name']); ?></div>
                                <span class="priority-badge priority-<?php echo $request['priority']; ?>">
                                    <?php echo ucfirst($request['priority']); ?> Priority
                                </span>
                            </div>
                            
                            <div class="request-details">
                                <div class="detail-item">
                                    <span class="icon">👨‍👩‍👧‍👦</span>
                                    <span>Family of <?php echo $request['family_size']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="icon">📍</span>
                                    <span><?php echo htmlspecialchars($request['location']); ?></span>
                                </div>
                            </div>
                            
                            <div class="request-details">
                                <div class="detail-item">
                                    <span class="icon">🏥</span>
                                    <span><?php echo htmlspecialchars($request['notes']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="icon">🕒</span>
                                    <span><?php echo $request['time_ago']; ?></span>
                                </div>
                            </div>
                            
                            <div class="request-actions">
                                <button class="btn btn-approve" onclick="approveRequest('<?php echo $request['id']; ?>')">
                                    ✓ Approve
                                </button>
                                <button class="btn btn-deny" onclick="denyRequest('<?php echo $request['id']; ?>')">
                                    ✗ Deny
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="toast" id="toast">
            <span id="toastMessage"></span>
        </div>
    </div>

<?php require_once VIEW_PATH . 'shared/footer.php'; ?>
