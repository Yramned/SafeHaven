<?php
/**
 * SafeHaven - DRRM Situational/Incident Report PDF Generator
 * Generates a downloadable FPDF report for admin use.
 *
 * Accessible at: index.php?page=drrm-report
 * Authorization: Admin only
 *
 * Data sources:
 *   - EvacuationCenterModel::getStatistics()
 *   - EvacuationCenterModel::getAll()
 *   - EvacuationModel::getAll()
 *   - AlertModel::getAll()
 */

require_once CONFIG_PATH . 'database.php';
require_once MODEL_PATH . 'EvacuationCenterModel.php';
require_once MODEL_PATH . 'EvacuationModel.php';
require_once MODEL_PATH . 'AlertModel.php';
require_once ROOT_PATH . 'lib/fpdf/fpdf.php';

// ─── Auth Guard ──────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'index.php?page=login');
    exit;
}

// ─── Fetch Data ───────────────────────────────────────────────────────────────
$stats    = EvacuationCenterModel::getStatistics();
$centers  = EvacuationCenterModel::getAll();
$requests = EvacuationModel::getAll();
$alerts   = AlertModel::getAll();

// Aggregate request counts
$pendingCount  = 0;
$approvedCount = 0;
$rejectedCount = 0;
$totalFamilies = 0;
$totalPersons  = 0;

foreach ($requests as $req) {
    $status = strtolower($req['status'] ?? 'pending');
    $fm = (int)($req['family_members'] ?? 1);
    if ($status === 'pending')           $pendingCount++;
    elseif ($status === 'approved')      { $approvedCount++; $totalFamilies++; $totalPersons += $fm; }
    elseif (in_array($status, ['rejected', 'denied'])) $rejectedCount++;
}

// Active alerts summary
$criticalAlerts  = 0;
$warningAlerts   = 0;
$evacuationAlerts = 0;
$infoAlerts      = 0;
$activeAlerts    = [];
foreach ($alerts as $alert) {
    $sev = strtolower($alert['severity'] ?? 'info');
    if ($sev === 'critical')   $criticalAlerts++;
    elseif ($sev === 'warning') $warningAlerts++;
    elseif ($sev === 'evacuation') $evacuationAlerts++;
    else $infoAlerts++;
    $activeAlerts[] = $alert;
}

// Generation meta
$generatedAt   = date('F d, Y  h:i A');
$reportDate    = date('Y-m-d');
$reportTime    = date('H:i:s');
$adminName     = $_SESSION['user_name'] ?? 'Administrator';
$reportRefNo   = 'SH-RPT-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

// ─── Custom FPDF class ────────────────────────────────────────────────────────
class DrrmReport extends FPDF {

    public $reportTitle    = 'SITUATIONAL / INCIDENT REPORT';
    public $generatedAt    = '';
    public $reportRefNo    = '';

    // Color palette
    private $colorNavy   = [13,  71, 161];   // header bg
    private $colorBlue   = [21, 101, 192];   // section header bg
    private $colorLight  = [227, 242, 253];  // alternating row
    private $colorWhite  = [255, 255, 255];
    private $colorGray   = [245, 245, 245];
    private $colorText   = [33,  33,  33];
    private $colorMuted  = [100, 100, 100];
    private $colorBorder = [189, 189, 189];

    function Header() {
        // Navy banner
        $this->SetFillColor(...$this->colorNavy);
        $this->Rect(0, 0, 210, 28, 'F');

        // Shield icon area (left accent)
        $this->SetFillColor(255, 193, 7); // amber
        $this->Rect(0, 0, 6, 28, 'F');

        // Organisation + title
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 15);
        $this->SetXY(10, 5);
        $this->Cell(0, 7, 'SAFEHAVEN EMERGENCY EVACUATION SYSTEM', 0, 1, 'L');

        $this->SetFont('Arial', '', 9);
        $this->SetX(10);
        $this->Cell(0, 5, 'Disaster Risk Reduction and Management Office  |  Cebu City, Philippines', 0, 1, 'L');

        $this->SetFont('Arial', 'B', 10);
        $this->SetX(10);
        $this->Cell(0, 6, strtoupper($this->reportTitle), 0, 1, 'L');

        // Ref + date top-right
        $this->SetFont('Arial', '', 7.5);
        $this->SetXY(130, 5);
        $this->Cell(70, 4, 'Ref No: ' . $this->reportRefNo, 0, 1, 'R');
        $this->SetX(130);
        $this->Cell(70, 4, 'Generated: ' . $this->generatedAt, 0, 1, 'R');

        $this->SetTextColor(...$this->colorText);
        $this->SetY(32);
    }

    function Footer() {
        $this->SetY(-14);
        $this->SetFillColor(...$this->colorNavy);
        $this->Rect(0, $this->GetY(), 210, 14, 'F');

        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(200, 200, 200);
        $this->SetX(6);
        $this->Cell(100, 14, 'SafeHaven  |  DRRM Office  |  CONFIDENTIAL - FOR OFFICIAL USE ONLY', 0, 0, 'L');
        $this->Cell(95, 14, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');
        $this->SetTextColor(...$this->colorText);
    }

    /** Utility: Draw a filled section header */
    function SectionHeader($title, $subtitle = '') {
        $this->Ln(4);
        $this->SetFillColor(...$this->colorBlue);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 9.5);
        $this->SetX(6);
        $this->Cell(198, 7, '  ' . strtoupper($title), 0, 1, 'L', true);
        if ($subtitle) {
            $this->SetFillColor(...$this->colorLight);
            $this->SetTextColor(...$this->colorMuted);
            $this->SetFont('Arial', 'I', 8);
            $this->SetX(6);
            $this->Cell(198, 5, '  ' . $subtitle, 0, 1, 'L', true);
        }
        $this->SetTextColor(...$this->colorText);
        $this->Ln(1);
    }

    /** Utility: Draw a stat box (label + big value + sub) */
    function StatBox($x, $y, $w, $h, $label, $value, $sub, $fillR, $fillG, $fillB) {
        $this->SetXY($x, $y);
        $this->SetFillColor($fillR, $fillG, $fillB);
        $this->SetDrawColor(...$this->colorBorder);
        $this->RoundedRect($x, $y, $w, $h, 2, 'FD');

        // Label
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(80, 80, 80);
        $this->SetXY($x + 2, $y + 2);
        $this->Cell($w - 4, 5, strtoupper($label), 0, 1, 'C');

        // Value
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(...$this->colorNavy);
        $this->SetX($x + 2);
        $this->Cell($w - 4, 11, $value, 0, 1, 'C');

        // Sub
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(100, 100, 100);
        $this->SetX($x + 2);
        $this->Cell($w - 4, 4, $sub, 0, 1, 'C');

        $this->SetTextColor(...$this->colorText);
        $this->SetDrawColor(0, 0, 0);
    }

    /** Utility: Rounded rectangle */
    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k  = $this->k;
        $hp = $this->h;
        if ($style == 'F') $op = 'f';
        elseif ($style == 'FD' || $style == 'DF') $op = 'B';
        else $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);
        $xc = $x + $w - $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r; $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);
        $xc = $x + $r; $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));
        $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k,
            $x3 * $this->k, ($h - $y3) * $this->k
        ));
    }

    /** Severity badge chip */
    function SeverityBadge($x, $y, $text) {
        $text = strtolower($text);
        switch ($text) {
            case 'critical':   $r=183; $g=28;  $b=28;  break;
            case 'evacuation': $r=230; $g=81;  $b=0;   break;
            case 'warning':    $r=245; $g=127; $b=23;  break;
            default:           $r=21;  $g=101; $b=192; break;
        }
        $this->SetFillColor($r, $g, $b);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 7);
        $this->SetXY($x, $y);
        $this->Cell(22, 5, strtoupper($text), 0, 0, 'C', true);
        $this->SetTextColor(...$this->colorText);
        $this->SetFillColor(255, 255, 255);
    }

    /** Center status badge */
    function StatusBadge($x, $y, $text) {
        $text = strtolower($text);
        switch ($text) {
            case 'accepting': $r=27;  $g=94;  $b=32;  $label='ACCEPTING'; break;
            case 'limited':   $r=230; $g=81;  $b=0;   $label='LIMITED';   break;
            case 'full':      $r=183; $g=28;  $b=28;  $label='FULL';      break;
            default:          $r=100; $g=100; $b=100; $label=strtoupper($text); break;
        }
        $this->SetFillColor($r, $g, $b);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 7);
        $this->SetXY($x, $y);
        $this->Cell(22, 5, $label, 0, 0, 'C', true);
        $this->SetTextColor(...$this->colorText);
        $this->SetFillColor(255, 255, 255);
    }

    /** Horizontal divider */
    function Divider() {
        $this->Ln(2);
        $this->SetDrawColor(...$this->colorBorder);
        $this->SetLineWidth(0.3);
        $this->Line(6, $this->GetY(), 204, $this->GetY());
        $this->SetLineWidth(0.2);
        $this->Ln(2);
    }
}

// ─── Build PDF ────────────────────────────────────────────────────────────────
$pdf = new DrrmReport('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->generatedAt = $generatedAt;
$pdf->reportRefNo = $reportRefNo;
$pdf->SetMargins(6, 32, 6);
$pdf->SetAutoPageBreak(true, 16);
$pdf->AddPage();

// ════════════════════════════════════════════════════════
// SECTION 1 — REPORT SUMMARY HEADER BOX
// ════════════════════════════════════════════════════════
$pdf->SetFillColor(250, 250, 250);
$pdf->SetDrawColor(189, 189, 189);
$pdf->SetLineWidth(0.3);
$pdf->SetX(6);
$pdf->RoundedRect(6, $pdf->GetY(), 198, 18, 2, 'FD');

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(50, 50, 50);
$pdf->SetX(10);
$yMeta = $pdf->GetY();
$pdf->SetY($yMeta + 2);

// Left col
$pdf->SetX(10);
$pdf->Cell(90, 4.5, 'Report Reference No.: ' . $reportRefNo, 0, 1);
$pdf->SetX(10);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(90, 4.5, 'Date Generated: ' . $generatedAt, 0, 1);
$pdf->SetX(10);
$pdf->Cell(90, 4.5, 'Prepared By: ' . $adminName . '  (DRRM Administrator)', 0, 1);

// Right col
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetXY(108, $yMeta + 2);
$pdf->Cell(90, 4.5, 'Classification: FOR OFFICIAL USE ONLY', 0, 1);
$pdf->SetX(108);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(90, 4.5, 'Report Type: Situational / Incident Report', 0, 1);
$pdf->SetX(108);
$pdf->Cell(90, 4.5, 'Jurisdiction: Cebu City, Philippines', 0, 1);

$pdf->SetTextColor(33, 33, 33);
$pdf->Ln(6);

// ════════════════════════════════════════════════════════
// SECTION 2 — KEY STATISTICS (stat boxes)
// ════════════════════════════════════════════════════════
$pdf->SectionHeader('Overall Situation Summary', 'Real-time aggregate data from all evacuation centers');

$yStats = $pdf->GetY();
$boxW   = 45;
$boxH   = 26;
$gapX   = 4;
$startX = 8;

$statBoxes = [
    ['Total Centers',   (string)($stats['total_centers']  ?? 0), 'Registered centers',  [227,242,253]],
    ['Total Capacity',  (string)($stats['total_capacity'] ?? 0), 'Max persons',         [232,245,233]],
    ['Total Evacuees',  (string)($stats['total_evacuees'] ?? 0), 'Currently displaced', [255,243,224]],
    ['Occupancy Rate',  ($stats['occupancy_rate'] ?? 0) . '%',   'System-wide',         [252,228,236]],
];

foreach ($statBoxes as $i => $box) {
    $bx = $startX + $i * ($boxW + $gapX);
    $pdf->StatBox($bx, $yStats, $boxW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
}
$pdf->SetY($yStats + $boxH + 3);

// Sub-row: center status breakdown
$subBoxes = [
    ['Accepting',    (string)($stats['accepting']     ?? 0), 'Centers open',       [232,245,233]],
    ['Limited',      (string)($stats['limited']       ?? 0), 'Nearing capacity',   [255,243,224]],
    ['Full',         (string)($stats['full']          ?? 0), 'At capacity',        [252,228,236]],
    ['Avail. Beds',  (string)($stats['available_beds']?? 0), 'Remaining capacity', [227,242,253]],
];

$yStats2 = $pdf->GetY();
foreach ($subBoxes as $i => $box) {
    $bx = $startX + $i * ($boxW + $gapX);
    $pdf->StatBox($bx, $yStats2, $boxW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
}
$pdf->SetY($yStats2 + $boxH + 4);

// ════════════════════════════════════════════════════════
// SECTION 3 — ACTIVE ALERTS SUMMARY
// ════════════════════════════════════════════════════════
$pdf->SectionHeader('Active Situational Alerts', 'Current alerts as of ' . $generatedAt);

// Alert counts row
$alertBoxes = [
    ['Critical',    (string)$criticalAlerts,   'Critical/Evacuation', [252,228,236]],
    ['Warning',     (string)$warningAlerts,    'Warning level',       [255,243,224]],
    ['Info',        (string)$infoAlerts,       'Info level',          [227,242,253]],
    ['Total Alerts',(string)count($activeAlerts), 'All active',        [245,245,245]],
];

$yAlerts = $pdf->GetY();
foreach ($alertBoxes as $i => $box) {
    $bx = $startX + $i * ($boxW + $gapX);
    $pdf->StatBox($bx, $yAlerts, $boxW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
}
$pdf->SetY($yAlerts + $boxH + 4);

// Alerts table (last 10)
if (!empty($activeAlerts)) {
    $recentAlerts = array_slice($activeAlerts, 0, 10);

    // Table header
    $colWidths  = [8, 40, 88, 26, 30];
    $colHeaders = ['#', 'Severity', 'Title / Description', 'Location', 'Date & Time'];
    $colAligns  = ['C', 'C', 'L', 'L', 'C'];

    $pdf->SetFillColor(21, 101, 192);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetX(6);
    foreach ($colHeaders as $idx => $hdr) {
        $pdf->Cell($colWidths[$idx], 7, $hdr, 0, 0, $colAligns[$idx], true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 8);
    $odd = true;
    foreach ($recentAlerts as $i => $alert) {
        if ($pdf->GetY() > 260) { $pdf->AddPage(); }

        $fillBg = $odd ? [255,255,255] : [240,248,255];
        $pdf->SetFillColor(...$fillBg);
        $pdf->SetTextColor(33, 33, 33);

        $rowY = $pdf->GetY();
        $pdf->SetX(6);

        $pdf->Cell($colWidths[0], 6, (string)($i + 1), 0, 0, 'C', true);

        // Severity badge cell (colored text)
        $sev = strtolower($alert['severity'] ?? 'info');
        switch ($sev) {
            case 'critical':    $pdf->SetTextColor(183, 28, 28); break;
            case 'evacuation':  $pdf->SetTextColor(230, 81, 0);  break;
            case 'warning':     $pdf->SetTextColor(245, 127, 23);break;
            default:            $pdf->SetTextColor(21, 101, 192);break;
        }
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($colWidths[1], 6, strtoupper($sev), 0, 0, 'C', true);

        // Title + message
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(33, 33, 33);
        $titleText = mb_strimwidth($alert['title'] ?? '', 0, 48, '...');
        $pdf->Cell($colWidths[2], 6, $titleText, 0, 0, 'L', true);

        $loc = mb_strimwidth($alert['location'] ?? 'N/A', 0, 24, '..');
        $pdf->Cell($colWidths[3], 6, $loc, 0, 0, 'L', true);

        $dt = '';
        if (!empty($alert['created_at'])) {
            $ts = strtotime($alert['created_at']);
            $dt = $ts ? date('M d, Y H:i', $ts) : $alert['created_at'];
        }
        $pdf->Cell($colWidths[4], 6, $dt, 0, 0, 'C', true);
        $pdf->Ln();

        // Sub-row for message
        if (!empty($alert['message'])) {
            $pdf->SetFillColor(...$fillBg);
            $pdf->SetX(6 + $colWidths[0] + $colWidths[1]);
            $pdf->SetFont('Arial', 'I', 7.5);
            $pdf->SetTextColor(100, 100, 100);
            $msgText = mb_strimwidth(strip_tags($alert['message']), 0, 120, '...');
            $pdf->Cell($colWidths[2] + $colWidths[3] + $colWidths[4], 5, $msgText, 0, 0, 'L', true);
            $pdf->Ln();
        }

        $pdf->SetTextColor(33, 33, 33);
        $odd = !$odd;
    }
    if (count($activeAlerts) > 10) {
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(6);
        $pdf->Cell(198, 5, '  ... and ' . (count($activeAlerts) - 10) . ' more alerts not shown. See full alerts in the system.', 0, 1, 'L');
    }
    $pdf->SetTextColor(33, 33, 33);
} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetX(6);
    $pdf->Cell(198, 8, '  No active alerts at time of report generation.', 0, 1, 'L');
    $pdf->SetTextColor(33, 33, 33);
}

// ════════════════════════════════════════════════════════
// SECTION 4 — CENTER-BY-CENTER OCCUPANCY TABLE
// ════════════════════════════════════════════════════════
if ($pdf->GetY() > 190) { $pdf->AddPage(); }

$pdf->SectionHeader('Evacuation Center Occupancy Report', 'Per-center capacity and utilization');

if (!empty($centers)) {
    // Table header
    $cw     = [7, 50, 28, 22, 22, 22, 35];
    $ch     = ['#', 'Center Name', 'Barangay', 'Capacity', 'Occupancy', '% Full', 'Status'];
    $ca     = ['C', 'L', 'L', 'C', 'C', 'C', 'C'];

    $pdf->SetFillColor(21, 101, 192);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetX(6);
    foreach ($ch as $idx => $h) {
        $pdf->Cell($cw[$idx], 7, $h, 0, 0, $ca[$idx], true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 8.5);
    $odd = true;
    foreach ($centers as $i => $center) {
        if ($pdf->GetY() > 262) { $pdf->AddPage(); }

        $cap = (int)($center['capacity']          ?? 0);
        $occ = (int)($center['current_occupancy'] ?? 0);
        $pct = $cap > 0 ? round(($occ / $cap) * 100) : 0;
        $status = strtolower($center['status'] ?? 'accepting');

        // Row background
        if ($status === 'full')        $fillBg = [255, 235, 238];
        elseif ($status === 'limited') $fillBg = [255, 248, 225];
        else                           $fillBg = $odd ? [255,255,255] : [240,248,255];

        $pdf->SetFillColor(...$fillBg);
        $pdf->SetTextColor(33, 33, 33);
        $rowY = $pdf->GetY();
        $pdf->SetX(6);

        $pdf->Cell($cw[0], 7, (string)($i + 1), 0, 0, 'C', true);
        $nameText = mb_strimwidth($center['name'] ?? 'Unnamed Center', 0, 42, '..');
        $pdf->Cell($cw[1], 7, $nameText, 0, 0, 'L', true);
        $brgy = mb_strimwidth($center['barangay'] ?? '—', 0, 22, '..');
        $pdf->Cell($cw[2], 7, $brgy, 0, 0, 'L', true);
        $pdf->Cell($cw[3], 7, number_format($cap), 0, 0, 'C', true);
        $pdf->Cell($cw[4], 7, number_format($occ), 0, 0, 'C', true);

        // % full with color
        if ($pct >= 100)     $pdf->SetTextColor(183, 28, 28);
        elseif ($pct >= 80)  $pdf->SetTextColor(230, 81, 0);
        else                 $pdf->SetTextColor(27, 94, 32);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->Cell($cw[5], 7, $pct . '%', 0, 0, 'C', true);

        // Status
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(33, 33, 33);
        switch ($status) {
            case 'accepting': $pdf->SetTextColor(27, 94, 32);  break;
            case 'limited':   $pdf->SetTextColor(230, 81, 0);  break;
            case 'full':      $pdf->SetTextColor(183, 28, 28); break;
        }
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($cw[6], 7, strtoupper($status), 0, 0, 'C', true);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(33, 33, 33);
        $pdf->Ln();
        $odd = !$odd;
    }

    // Totals row
    $pdf->SetFillColor(13, 71, 161);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetX(6);
    $pdf->Cell($cw[0], 7, '', 0, 0, 'C', true);
    $pdf->Cell($cw[1], 7, 'TOTALS', 0, 0, 'L', true);
    $pdf->Cell($cw[2], 7, count($centers) . ' centers', 0, 0, 'L', true);
    $pdf->Cell($cw[3], 7, number_format($stats['total_capacity']  ?? 0), 0, 0, 'C', true);
    $pdf->Cell($cw[4], 7, number_format($stats['total_evacuees'] ?? 0),  0, 0, 'C', true);
    $pctAll = ($stats['occupancy_rate'] ?? 0);
    $pdf->Cell($cw[5], 7, $pctAll . '%', 0, 0, 'C', true);
    $pdf->Cell($cw[6], 7, '', 0, 0, 'C', true);
    $pdf->Ln();
    $pdf->SetTextColor(33, 33, 33);

} else {
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetX(6);
    $pdf->Cell(198, 8, '  No evacuation centers registered.', 0, 1, 'L');
    $pdf->SetTextColor(33, 33, 33);
}

// ════════════════════════════════════════════════════════
// SECTION 5 — DISPLACED FAMILIES & EVACUATION REQUESTS
// ════════════════════════════════════════════════════════
if ($pdf->GetY() > 210) { $pdf->AddPage(); }

$pdf->SectionHeader('Displaced Families & Evacuation Requests', 'Request status breakdown and family counts');

// Request stat boxes
$reqBoxes = [
    ['Total Requests',  (string)count($requests),   'All requests',         [245,245,245]],
    ['Pending',         (string)$pendingCount,       'Awaiting review',      [255,243,224]],
    ['Approved',        (string)$approvedCount,      'Confirmed & placed',   [232,245,233]],
    ['Rejected',        (string)$rejectedCount,      'Denied requests',      [252,228,236]],
];

$yReq = $pdf->GetY();
foreach ($reqBoxes as $i => $box) {
    $bx = $startX + $i * ($boxW + $gapX);
    $pdf->StatBox($bx, $yReq, $boxW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
}
$pdf->SetY($yReq + $boxH + 3);

// Family summary boxes
$famBoxes = [
    ['Displaced Families', (string)$totalFamilies, 'Approved requests',  [232,245,233]],
    ['Total Persons',      (string)$totalPersons,  'Incl. family members',[227,242,253]],
    ['Avg. per Family',    $totalFamilies > 0 ? number_format($totalPersons/$totalFamilies, 1) : '0', 'Members/family', [245,245,245]],
];

$yFam = $pdf->GetY();
$famW = 60;
$famGap = 5;
foreach ($famBoxes as $i => $box) {
    $bx = $startX + $i * ($famW + $famGap);
    $pdf->StatBox($bx, $yFam, $famW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
}
$pdf->SetY($yFam + $boxH + 5);

// Recent approved requests table (last 15)
$approvedRequests = array_filter($requests, fn($r) => strtolower($r['status'] ?? '') === 'approved');
$approvedRequests = array_slice(array_values($approvedRequests), 0, 15);

if (!empty($approvedRequests)) {
    if ($pdf->GetY() > 200) { $pdf->AddPage(); }

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(33, 33, 33);
    $pdf->SetX(6);
    $pdf->Cell(198, 6, 'Recently Approved Evacuee Placements (showing up to 15):', 0, 1, 'L');

    $rcw    = [7, 45, 40, 16, 28, 50];
    $rch    = ['#', 'Resident Name', 'Assigned Center', 'Fam.', 'Priority', 'Date'];
    $rca    = ['C', 'L', 'L', 'C', 'C', 'L'];

    $pdf->SetFillColor(21, 101, 192);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetX(6);
    foreach ($rch as $idx => $h) {
        $pdf->Cell($rcw[$idx], 6.5, $h, 0, 0, $rca[$idx], true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 8);
    $odd = true;
    foreach ($approvedRequests as $i => $req) {
        if ($pdf->GetY() > 262) { $pdf->AddPage(); }

        $fillBg = $odd ? [255,255,255] : [240,248,255];
        $pdf->SetFillColor(...$fillBg);
        $pdf->SetTextColor(33, 33, 33);
        $pdf->SetX(6);

        $pdf->Cell($rcw[0], 6, (string)($i + 1), 0, 0, 'C', true);
        $pdf->Cell($rcw[1], 6, mb_strimwidth($req['user_name'] ?? 'Unknown', 0, 38, '..'), 0, 0, 'L', true);
        $pdf->Cell($rcw[2], 6, mb_strimwidth($req['center_name'] ?? '—', 0, 32, '..'), 0, 0, 'L', true);
        $pdf->Cell($rcw[3], 6, (string)($req['family_members'] ?? 1), 0, 0, 'C', true);

        $priority = ucfirst($req['priority'] ?? 'normal');
        switch (strtolower($priority)) {
            case 'critical': case 'high': $pdf->SetTextColor(183, 28, 28); break;
            case 'medium':                $pdf->SetTextColor(230, 81, 0);  break;
            default:                      $pdf->SetTextColor(27, 94, 32);  break;
        }
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell($rcw[4], 6, $priority, 0, 0, 'C', true);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(33, 33, 33);

        $dt = '';
        if (!empty($req['created_at'])) {
            $ts = strtotime($req['created_at']);
            $dt = $ts ? date('M d, Y H:i', $ts) : $req['created_at'];
        }
        $pdf->Cell($rcw[5], 6, $dt, 0, 0, 'L', true);
        $pdf->Ln();
        $odd = !$odd;
    }
    $pdf->SetTextColor(33, 33, 33);
}

// ════════════════════════════════════════════════════════
// SECTION 6 — NOTES / REMARKS
// ════════════════════════════════════════════════════════
if ($pdf->GetY() > 220) { $pdf->AddPage(); }

$pdf->SectionHeader('Remarks / Additional Notes');

$pdf->SetFillColor(248, 249, 250);
$pdf->SetDrawColor(189, 189, 189);
$pdf->SetLineWidth(0.3);
$pdf->SetX(6);
$notesY = $pdf->GetY();
$pdf->RoundedRect(6, $notesY, 198, 28, 2, 'FD');

$pdf->SetFont('Arial', '', 8.5);
$pdf->SetTextColor(100, 100, 100);
$pdf->SetXY(10, $notesY + 3);
$pdf->MultiCell(190, 5,
    "This report is an automatically generated situational summary produced by the SafeHaven Emergency Evacuation Management System.\n" .
    "Data reflects system state at the time of generation. For ground-truth verification, coordinate with respective barangay officials.\n" .
    "All figures are subject to revision pending field confirmation.",
    0, 'L');

$pdf->SetTextColor(33, 33, 33);
$pdf->SetY($notesY + 32);

// ════════════════════════════════════════════════════════
// SECTION 7 — SIGNATURE BLOCK
// ════════════════════════════════════════════════════════
if ($pdf->GetY() > 230) { $pdf->AddPage(); }

$pdf->SectionHeader('Certification & Signature Block');

$sigY = $pdf->GetY();

// 3-column signature block
$sigW  = 60;
$sigGap = 9;
$sigCols = [
    ['Prepared By',     'DRRM Admin Officer',        $adminName],
    ['Reviewed By',     'Operations Head',            '_____________________'],
    ['Approved By',     'DRRM Director',              '_____________________'],
];

foreach ($sigCols as $i => $col) {
    $sx = 9 + $i * ($sigW + $sigGap);
    $sy = $sigY;

    $pdf->SetFillColor(248, 249, 250);
    $pdf->SetDrawColor(189, 189, 189);
    $pdf->RoundedRect($sx, $sy, $sigW, 44, 2, 'FD');

    // Role label
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->SetTextColor(21, 101, 192);
    $pdf->SetXY($sx + 2, $sy + 3);
    $pdf->Cell($sigW - 4, 5, strtoupper($col[0]), 0, 1, 'C');

    // Signature line
    $pdf->SetDrawColor(33, 33, 33);
    $pdf->SetLineWidth(0.4);
    $pdf->Line($sx + 8, $sy + 26, $sx + $sigW - 8, $sy + 26);
    $pdf->SetLineWidth(0.2);

    // Name
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(33, 33, 33);
    $pdf->SetXY($sx + 2, $sy + 28);
    $pdf->Cell($sigW - 4, 5, $col[2], 0, 1, 'C');

    // Title
    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY($sx + 2, $sy + 33);
    $pdf->Cell($sigW - 4, 4, $col[1], 0, 1, 'C');

    // Date signed
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY($sx + 2, $sy + 38);
    $pdf->Cell($sigW - 4, 4, 'Date: _______________', 0, 1, 'C');

    $pdf->SetTextColor(33, 33, 33);
}

$pdf->SetY($sigY + 50);

// Official stamp placeholder
$pdf->SetFont('Arial', 'I', 7.5);
$pdf->SetTextColor(150, 150, 150);
$pdf->SetX(6);
$pdf->Cell(198, 5,
    'Document Control: ' . $reportRefNo . '  |  Generated: ' . $generatedAt .
    '  |  SafeHaven DRRM Information System  |  This document is system-generated.',
    0, 1, 'C');

$pdf->SetTextColor(33, 33, 33);

// ─── Output PDF ────────────────────────────────────────────────────────────────
$filename = 'SafeHaven_DRRM_Report_' . date('Ymd_His') . '.pdf';
$pdf->Output('D', $filename);
exit;
