 * SafeHaven – EvacuationResult.php
 * Unified page showing both approval and decline scenarios
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?action=login');
    exit;
}

// Demo: Randomly decide approval vs decline (50/50 chance)
// In production, this would check actual evacuation center capacity
$isApproved = isset($_GET['status']) ? ($_GET['status'] === 'approved') : (rand(0, 1) === 1);

// Approval data
$approvalData = [
    'confirmation_code' => 'EVAC-2026-QAR2MD8',
    'center_name' => 'Bacolod Central School',
    'center_address' => 'Barangay 18, Bacolod City',
    'distance' => '2.3 km',
    'travel_time' => '15 minutes',
    'capacity_percent' => 62
];

// Decline data
$declineData = [
    'original_center' => 'Barangay 18 - High School Evacuation Center',
    'alternative_centers' => [
        [
            'name' => 'Toyota Bacolod Center',
            'address' => 'Barangay 22, Bacolod City',
            'distance' => '3.1 km away',
            'travel_time' => '18 minutes',
            'capacity' => 45
        ],
        [
            'name' => 'San Sebastian Center',
            'address' => 'Barangay 25, Bacolod City',
            'distance' => '4.5 km away',
            'travel_time' => '22 minutes',
            'capacity' => 38
        ]
    ]
];

$pageTitle  = $isApproved ? 'SafeHaven – Request Approved' : 'SafeHaven – Center at Capacity';
$activePage = 'evacuation-request';
$extraCss   = ['assets/css/EvacuationResult.css'];
$extraJs    = [];

require_once VIEW_PATH . 'shared/dashboard-header.php';
?>

<div class="result-page">
<main class="result-main">
<div class="result-container">

    <?php if ($isApproved): ?>
        <!-- ═══════ APPROVED STATE ═══════ -->
        
        <!-- Success Hero -->
        <div class="result-hero approved">
            <div class="result-icon icon-approved">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <h1>Request Approved!</h1>
            <p class="result-subtitle">Your evacuation request has been received</p>
            <p class="result-note">Confirmation sent via SMS and App notification</p>
        </div>

        <!-- Assigned Center Card -->
        <div class="center-card">
            <div class="center-card-header">
                <h2>Assigned Evacuation Center</h2>
                <span class="badge badge-success">Confirmed via SMS</span>
            </div>

            <div class="center-info">
                <div class="center-icon-wrapper">
                    <div class="center-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                </div>
                <div class="center-details">
                    <h3><?php echo htmlspecialchars($approvalData['center_name']); ?></h3>
                    <p><?php echo htmlspecialchars($approvalData['center_address']); ?></p>
                </div>
            </div>

            <div class="center-stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Distance</div>
                    <div class="stat-value"><?php echo htmlspecialchars($approvalData['distance']); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Est. Travel Time</div>
                    <div class="stat-value"><?php echo htmlspecialchars($approvalData['travel_time']); ?></div>
                </div>
            </div>
        </div>

        <!-- Confirmation Code -->
        <div class="confirmation-card">
            <div class="conf-label">Confirmation Code</div>
            <div class="conf-code"><?php echo htmlspecialchars($approvalData['confirmation_code']); ?></div>
            <div class="conf-note">Present this code upon arrival</div>
        </div>

        <!-- Summary Details -->
        <div class="summary-card">
            <div class="summary-row">
                <span class="summary-label">Priority Classification</span>
                <span class="summary-value" data-priority></span>
            </div>

            <div class="summary-row">
                <span class="summary-label">Family Members</span>
                <span class="summary-value" data-family>1 person</span>
            </div>
            
            <div class="summary-row">
                <span class="summary-label">Special Needs</span>
                <span class="summary-value" data-needs>
                    <span class="badge badge-special">Wheelchair</span>
                </span>
            </div>

            <div class="summary-row">
                <span class="summary-label">Center Capacity</span>
                <span class="summary-value capacity-value"><?php echo $approvalData['capacity_percent']; ?>%</span>
            </div>
            <div class="capacity-bar-wrapper">
                <div class="capacity-bar-bg">
                    <div class="capacity-bar-fill" style="width: <?php echo $approvalData['capacity_percent']; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-primary" type="button" onclick="alert('Opening directions...')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                </svg>
                Get Directions
            </button>
            <button class="btn-success" type="button" onclick="alert('Calling center...')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                Call Center
            </button>
        </div>

    <?php else: ?>
        <!-- ═══════ DECLINED STATE ═══════ -->
        
        <!-- Decline Hero -->
        <div class="result-hero declined">
            <div class="result-icon icon-declined">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </div>
            <h1>Center at Capacity</h1>
            <p class="result-subtitle">Don't worry, we found alternatives for you</p>
        </div>

        <!-- Original Request Info -->
        <div class="original-request-card">
            <div class="original-header">
                <h2>Your Request Details</h2>
            </div>
            <div class="summary-row">
                <span class="summary-label">Priority Classification</span>
                <span class="summary-value" data-priority></span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Family Members</span>
                <span class="summary-value" data-family>1 person</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Special Needs</span>
                <span class="summary-value" data-needs>
                    <span class="badge badge-special">Wheelchair</span>
                </span>
            </div>
        </div>

        <!-- Decline Reason Card -->
        <div class="original-request-card">
            <div class="original-header">
                <h2>Assigned Evacuation Center</h2>
            </div>
            <div class="original-info">
                <p class="original-center-name"><?php echo htmlspecialchars($declineData['original_center']); ?></p>
                <p class="original-reason">Your request has been automatically redirected to nearby centers with available space.</p>
            </div>
        </div>

        <!-- Alternative Centers -->
        <div class="alternatives-section">
            <h2 class="alternatives-heading">Alternative Centers Available</h2>
            
            <div class="alternatives-grid">
                <?php foreach ($declineData['alternative_centers'] as $center): ?>
                <div class="alternative-card">
                    <div class="alt-card-header">
                        <div class="alt-card-title">
                            <div class="alt-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                            </div>
                            <div>
                                <h3><?php echo htmlspecialchars($center['name']); ?></h3>
                                <p class="alt-address"><?php echo htmlspecialchars($center['address']); ?></p>
                            </div>
                        </div>
                        <span class="badge badge-available">Available</span>
                    </div>

                    <div class="alt-stats-grid">
                        <div class="alt-stat">
                            <div class="alt-stat-label">Distance (VMAP)</div>
                            <div class="alt-stat-value"><?php echo htmlspecialchars($center['distance']); ?></div>
                        </div>
                        <div class="alt-stat">
                            <div class="alt-stat-label">Est. Travel</div>
                            <div class="alt-stat-value"><?php echo htmlspecialchars($center['travel_time']); ?></div>
                        </div>
                    </div>

                    <div class="alt-capacity">
                        <div class="alt-capacity-label">
                            <span>Capacity</span>
                            <span class="alt-capacity-percent"><?php echo $center['capacity']; ?>%</span>
                        </div>
                        <div class="capacity-bar-bg">
                            <div class="capacity-bar-fill" style="width: <?php echo $center['capacity']; ?>%"></div>
                        </div>
                    </div>

                    <button class="btn-select-center" type="button" onclick="selectCenter('<?php echo htmlspecialchars($center['name'], ENT_QUOTES); ?>')">
                        Select This Center
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Back to Request -->
        <div class="back-action">
            <a href="evacuation-request.php" class="btn-back">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                Submit New Request to Alternative Center
            </a>
        </div>

    <?php endif; ?>

    <!-- Submit Another Link (for both states) -->
    <div class="another-request">
        <a href="evacuation-request.php">Submit another request</a>
    </div>

</div>
</main>
</div>

<script>
// Read request data from sessionStorage
const requestData = {
    priority: sessionStorage.getItem('evac_priority') || 'Unaccompanied Minor',
    familyCount: parseInt(sessionStorage.getItem('evac_family_count') || '1'),
    specialNeeds: JSON.parse(sessionStorage.getItem('evac_special_needs') || '["Wheelchair"]')
};

// Update family count display
document.addEventListener('DOMContentLoaded', () => {
    const familyEl = document.querySelector('.summary-value[data-family]');
    if (familyEl && requestData.familyCount) {
        const plural = requestData.familyCount === 1 ? 'person' : 'people';
        familyEl.textContent = `${requestData.familyCount} ${plural}`;
    }

    // Update special needs badges
    const needsEl = document.querySelector('.summary-value[data-needs]');
    if (needsEl && requestData.specialNeeds) {
        needsEl.innerHTML = requestData.specialNeeds.length > 0
            ? requestData.specialNeeds.map(n => `<span class="badge badge-special">${n}</span>`).join(' ')
            : '<span class="badge badge-special">None</span>';
    }

    // Update priority display if it exists
    const priorityEl = document.querySelector('.summary-value[data-priority]');
    if (priorityEl && requestData.priority) {
        priorityEl.innerHTML = `<span class="badge badge-special">${requestData.priority}</span>`;
    }
});

function selectCenter(centerName) {
    alert('Selecting ' + centerName + '...');
    setTimeout(() => {
        window.location.href = 'evacuation-request.php';
    }, 500);
}
</script>

<?php require_once VIEW_PATH . 'shared/footer.php'; ?>