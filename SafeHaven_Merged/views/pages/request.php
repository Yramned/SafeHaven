 * SafeHaven – evacuation-request.php
 * Evacuation Request form  →  Success confirmation screen
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: auth.php?action=login');
    exit;
}

// ── CSRF token ─────────────────────────────────────────────────
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$pageTitle  = 'SafeHaven – Evacuation Request';
$activePage = 'evacuation-request';

$extraCss = ['assets/css/evacuation-request.css'];
$extraJs  = ['assets/js/evacuation-request.js'];

require_once VIEW_PATH . 'shared/dashboard-header.php';
?>

<div class="evac-page">
<main class="evac-main">
<div class="evac-container">

<!-- ─── Page header ────────────────────────────── -->
<div class="evac-page-head">
    <div class="evac-page-head-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9"  x2="12"    y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
    </div>
    <div>
        <h2>Evacuation Request</h2>
        <p>Emergency Assistance System</p>
    </div>
</div>

<!-- ════════════════════════════════════════════════
     FORM SCREEN
     ════════════════════════════════════════════════ -->
<form id="evacuationFormScreen" class="evac-form-screen"
      method="post" action="index.php?action=evacuation-request">

    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Location -->
    <div class="loc-card">
        <div class="loc-card-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>Your Location</span>
        </div>
        <p id="locationDisplay">Street, Barangay area</p>
        <a href="#" id="updateLocationBtn">Update Location</a>
    </div>

    <!-- Priority -->
    <p class="evac-label" id="priorityLabel">Priority Classification</p>
    <div class="priority-grid" id="priorityGrid" role="group" aria-labelledby="priorityLabel">
        <div class="pri-pill"        data-val="elderly"       role="button" tabindex="0" aria-pressed="false">Elderly (60+)</div>
        <div class="pri-pill active" data-val="unaccompanied" role="button" tabindex="0" aria-pressed="true" >Unaccompanied Minor</div>
        <div class="pri-pill"        data-val="pregnant"      role="button" tabindex="0" aria-pressed="false">Pregnant</div>
        <div class="pri-pill"        data-val="medical"       role="button" tabindex="0" aria-pressed="false">Medical Emergency</div>
        <div class="pri-pill full-row" data-val="disability"  role="button" tabindex="0" aria-pressed="false">Person with Disability</div>
    </div>

    <!-- Family -->
    <p class="evac-label">Family Members</p>
    <div class="family-box">
        <div class="fam-btns">
            <button class="fam-btn" id="famMinus" type="button" aria-label="Remove a family member">−</button>
            <div>
                <div class="fam-count" id="famCount">1</div>
                <div class="fam-sub">People</div>
            </div>
            <button class="fam-btn" id="famPlus" type="button" aria-label="Add a family member">+</button>
        </div>
    </div>

    <!-- Needs -->
    <p class="evac-label" id="needsLabel">Special Needs</p>
    <div class="needs-grid" id="needsGrid" role="group" aria-labelledby="needsLabel">
        <div class="need-pill"        data-val="medication" role="button" tabindex="0" aria-pressed="false">Medication</div>
        <div class="need-pill active" data-val="wheelchair" role="button" tabindex="0" aria-pressed="true" >Wheelchair</div>
        <div class="need-pill"        data-val="infant"     role="button" tabindex="0" aria-pressed="false">Infant Supplies</div>
        <div class="need-pill"        data-val="personal"   role="button" tabindex="0" aria-pressed="false">Personal Supplies</div>
        <div class="need-pill"        data-val="oxygen"     role="button" tabindex="0" aria-pressed="false">Oxygen Tank</div>
        <div class="need-pill"        data-val="other"      role="button" tabindex="0" aria-pressed="false">Other</div>
    </div>

    <!-- Submit -->
    <button class="btn-request-evac" id="btnRequestEvac" type="button">
        Request Evacuation
    </button>
</form>

<!-- ════════════════════════════════════════════════
     SUCCESS SCREEN  (hidden until JS toggles it)
     ════════════════════════════════════════════════ -->
<div id="evacuationSuccessScreen" class="success-screen">

    <!-- Hero checkmark + title -->
    <div class="success-hero">
        <div class="success-check">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <h2>Request Sent Successfully!</h2>
        <p>Your evacuation request has been approved</p>
        <p class="sub-note">Confirmation sent via SMS and App notification</p>
    </div>

    <!-- Assigned evacuation center -->
    <div class="assigned-card">
        <div class="assigned-card-head">
            <h3>Assigned Evacuation Center</h3>
            <span class="badge badge-blue">Confirmed via SMS</span>
        </div>

        <!-- Center name -->
        <div class="center-name-row">
            <div class="center-pin">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
                <div class="center-name">Barangay 18 – High School Evacuation Center</div>
                <div class="center-address">Barangay 18, Bacolod City</div>
            </div>
        </div>

        <!-- Distance / travel time -->
        <div class="center-stats">
            <div class="cstat">
                <div class="cstat-label">Distance</div>
                <div class="cstat-val">2.3 km</div>
            </div>
            <div class="cstat">
                <div class="cstat-label">Est. Travel Time</div>
                <div class="cstat-val">15 minutes</div>
            </div>
        </div>
    </div>

    <!-- Confirmation code -->
    <div class="conf-code-box">
        <div class="conf-code-label">Confirmation Code</div>
        <div class="conf-code" id="successConfCode">EVAC – 2026 – QAR2MDB</div>
        <div class="conf-code-note">Present this code upon arrival</div>
    </div>

    <!-- Summary rows -->
    <div class="summary-row">
        <span class="sr-label">Family Members</span>
        <span class="sr-value" id="successFamCount" data-count="1">1 person</span>
    </div>
    <div class="summary-row">
        <span class="sr-label">Special Needs</span>
        <span class="sr-value" id="successNeeds">
            <span class="badge badge-blue">Wheelchair</span>
        </span>
    </div>

    <!-- Capacity bar -->
    <div class="capacity-box">
        <div class="capacity-head">
            <span class="sr-label">Center Capacity</span>
            <span class="sr-value" id="capBarLabel">62%</span>
        </div>
        <div class="cap-bar-bg">
            <div class="cap-bar-fill" id="capBarFill" data-capacity="62" style="width:62%;"></div>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="action-btns">
        <button class="btn-directions" type="button">Get Directions</button>
        <button class="btn-call" type="button">Call Center</button>
    </div>

    <!-- Submit another -->
    <div class="another-link">
        <button id="btnAnother" type="button">Submit another request</button>
    </div>
</div>

</div><!-- /evac-container -->
</main><!-- /evac-main -->
</div><!-- /evac-page -->

<!-- ════════════════════════════════════════════════
     LOCATION UPDATE MODAL
     ════════════════════════════════════════════════ -->
<div id="locationModal" class="location-modal">
    <div class="location-modal-content">
        <div class="location-modal-header">
            <h3>Update Your Location</h3>
            <button class="location-modal-close" id="closeLocationModal" aria-label="Close modal">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        
        <div class="location-modal-body">
            <div class="location-input-group">
                <label for="streetInput">Street Address</label>
                <input type="text" id="streetInput" placeholder="Enter street address" value="Street">
            </div>
            
            <div class="location-input-group">
                <label for="barangayInput">Barangay</label>
                <select id="barangayInput">
                    <option value="">Select Barangay</option>
                    <option value="Barangay 1">Barangay 1</option>
                    <option value="Barangay 2">Barangay 2</option>
                    <option value="Barangay 3">Barangay 3</option>
                    <option value="Barangay 4">Barangay 4</option>
                    <option value="Barangay 5">Barangay 5</option>
                    <option value="Barangay 6">Barangay 6</option>
                    <option value="Barangay 7">Barangay 7</option>
                    <option value="Barangay 8">Barangay 8</option>
                    <option value="Barangay 9">Barangay 9</option>
                    <option value="Barangay 10">Barangay 10</option>
                    <option value="Barangay 11">Barangay 11</option>
                    <option value="Barangay 12">Barangay 12</option>
                    <option value="Barangay 13">Barangay 13</option>
                    <option value="Barangay 14">Barangay 14</option>
                    <option value="Barangay 15">Barangay 15</option>
                    <option value="Barangay 16">Barangay 16</option>
                    <option value="Barangay 17">Barangay 17</option>
                    <option value="Barangay 18">Barangay 18</option>
                    <option value="Barangay 19">Barangay 19</option>
                    <option value="Barangay 20">Barangay 20</option>
                </select>
            </div>

            <div class="location-input-group">
                <label for="cityInput">City</label>
                <input type="text" id="cityInput" placeholder="Enter city" value="Bacolod City">
            </div>

            <button class="btn-use-gps" id="useGpsBtn" type="button">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="10" r="3"/>
                    <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41m11.32-11.32l1.41-1.41"/>
                </svg>
                Use My Current GPS Location
            </button>
        </div>

        <div class="location-modal-footer">
            <button class="btn-cancel" id="cancelLocationBtn" type="button">Cancel</button>
            <button class="btn-save-location" id="saveLocationBtn" type="button">Save Location</button>
        </div>
    </div>
</div>

<?php require_once VIEW_PATH . 'shared/footer.php'; ?>