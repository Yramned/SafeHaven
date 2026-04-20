<?php
/**
 * SafeHaven - DRRM Situational/Incident Report PDF Generator
 * Generates a downloadable FPDF report for admin use.
 *
 * Accessible at: index.php?page=drrm-report
 * Authorization: Admin only
 */

class ReportController {

    /**
     * Generate and stream the DRRM PDF report.
     * Called by: index.php case 'drrm-report'
     */
    public static function generate(): void {

        // ─── Auth Guard ─────────────────────────────────────────────────────
        if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }

        require_once CONFIG_PATH . 'database.php';
        require_once MODEL_PATH . 'EvacuationCenterModel.php';
        require_once MODEL_PATH . 'EvacuationModel.php';
        require_once MODEL_PATH . 'AlertModel.php';
        require_once ROOT_PATH . 'lib/fpdf/fpdf.php';

        // ─── Fetch Data ──────────────────────────────────────────────────────
        try {
            $stats    = EvacuationCenterModel::getStatistics();
            $centers  = EvacuationCenterModel::getAll();
            $requests = EvacuationModel::getAll();
            $alerts   = AlertModel::getAll();
        } catch (Exception $e) {
            error_log('[ReportController] Data fetch error: ' . $e->getMessage());
            http_response_code(500);
            echo '<h2>Report generation failed.</h2><p>Could not load data from the database. Please try again.</p>';
            exit;
        }

        // Aggregate request counts
        $pendingCount  = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $totalFamilies = 0;
        $totalPersons  = 0;

        foreach ($requests as $req) {
            $status = strtolower($req['status'] ?? 'pending');
            $fm = (int)($req['family_members'] ?? 1);
            if ($status === 'pending')                              $pendingCount++;
            elseif ($status === 'approved')                        { $approvedCount++; $totalFamilies++; $totalPersons += $fm; }
            elseif (in_array($status, ['rejected', 'denied']))     $rejectedCount++;
        }

        // Active alerts summary
        $criticalAlerts   = 0;
        $warningAlerts    = 0;
        $evacuationAlerts = 0;
        $infoAlerts       = 0;
        $activeAlerts     = [];
        foreach ($alerts as $alert) {
            $sev = strtolower($alert['severity'] ?? 'info');
            if ($sev === 'critical')        $criticalAlerts++;
            elseif ($sev === 'warning')     $warningAlerts++;
            elseif ($sev === 'evacuation')  $evacuationAlerts++;
            else                            $infoAlerts++;
            $activeAlerts[] = $alert;
        }

        // Generation meta
        $generatedAt = date('F d, Y  h:i A');
        $reportDate  = date('Y-m-d');
        $adminName   = $_SESSION['user_name'] ?? 'Administrator';
        $reportRefNo = 'SH-RPT-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

        // Logo image path (stored in assets/images)
        $logoPath = ROOT_PATH . 'assets/images/safehaven_logo.png';
        $hasLogo  = file_exists($logoPath);

        // ─── Custom FPDF class ───────────────────────────────────────────────
        class DrrmReport extends FPDF {

            public $reportTitle = 'SITUATIONAL / INCIDENT REPORT';
            public $generatedAt = '';
            public $reportRefNo = '';
            public $logoPath    = '';
            public $hasLogo     = false;

            // Color palette
            private $colorNavy   = [13,  71, 161];
            private $colorBlue   = [21, 101, 192];
            private $colorLight  = [227, 242, 253];
            private $colorWhite  = [255, 255, 255];
            private $colorGray   = [245, 245, 245];
            private $colorText   = [33,  33,  33];
            private $colorMuted  = [100, 100, 100];
            private $colorBorder = [189, 189, 189];

            function Header() {
                // Navy banner
                $this->SetFillColor(...$this->colorNavy);
                $this->Rect(0, 0, 210, 30, 'F');

                // Amber left accent
                $this->SetFillColor(255, 193, 7);
                $this->Rect(0, 0, 6, 30, 'F');

                // Logo image (if available)
                if ($this->hasLogo && $this->logoPath) {
                    try {
                        $this->Image($this->logoPath, 8, 4, 20, 20);
                        $textX = 30;
                    } catch (Exception $e) {
                        $textX = 10;
                    }
                } else {
                    // Fallback shield icon drawn as text symbol
                    $this->SetFont('Arial', 'B', 18);
                    $this->SetTextColor(255, 193, 7);
                    $this->SetXY(8, 5);
                    $this->Cell(20, 18, chr(164), 0, 0, 'C');
                    $textX = 30;
                }

                // Organisation + title
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('Arial', 'B', 14);
                $this->SetXY($textX, 4);
                $this->Cell(0, 7, 'SAFEHAVEN EMERGENCY EVACUATION SYSTEM', 0, 1, 'L');

                $this->SetFont('Arial', '', 8.5);
                $this->SetX($textX);
                $this->Cell(0, 5, 'Disaster Risk Reduction and Management Office  |  Cebu City, Philippines', 0, 1, 'L');

                $this->SetFont('Arial', 'B', 10);
                $this->SetX($textX);
                $this->Cell(0, 6, strtoupper($this->reportTitle), 0, 1, 'L');

                // Ref + date top-right
                $this->SetFont('Arial', '', 7.5);
                $this->SetXY(130, 5);
                $this->Cell(70, 4, 'Ref No: ' . $this->reportRefNo, 0, 1, 'R');
                $this->SetX(130);
                $this->Cell(70, 4, 'Generated: ' . $this->generatedAt, 0, 1, 'R');

                $this->SetTextColor(...$this->colorText);
                $this->SetY(34);
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

            function StatBox($x, $y, $w, $h, $label, $value, $sub, $fillR, $fillG, $fillB) {
                $this->SetXY($x, $y);
                $this->SetFillColor($fillR, $fillG, $fillB);
                $this->SetDrawColor(...$this->colorBorder);
                $this->RoundedRect($x, $y, $w, $h, 2, 'FD');

                $this->SetFont('Arial', '', 7.5);
                $this->SetTextColor(80, 80, 80);
                $this->SetXY($x + 2, $y + 2);
                $this->Cell($w - 4, 5, strtoupper($label), 0, 1, 'C');

                $this->SetFont('Arial', 'B', 18);
                $this->SetTextColor(...$this->colorNavy);
                $this->SetX($x + 2);
                $this->Cell($w - 4, 11, $value, 0, 1, 'C');

                $this->SetFont('Arial', '', 7);
                $this->SetTextColor(100, 100, 100);
                $this->SetX($x + 2);
                $this->Cell($w - 4, 4, $sub, 0, 1, 'C');

                $this->SetTextColor(...$this->colorText);
                $this->SetDrawColor(0, 0, 0);
            }

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

            function Divider() {
                $this->Ln(2);
                $this->SetDrawColor(...$this->colorBorder);
                $this->SetLineWidth(0.3);
                $this->Line(6, $this->GetY(), 204, $this->GetY());
                $this->SetLineWidth(0.2);
                $this->Ln(2);
            }
        }

        // ─── Build PDF ───────────────────────────────────────────────────────
        $pdf = new DrrmReport('P', 'mm', 'A4');
        $pdf->AliasNbPages();
        $pdf->generatedAt = $generatedAt;
        $pdf->reportRefNo = $reportRefNo;
        $pdf->logoPath    = $logoPath;
        $pdf->hasLogo     = $hasLogo;
        $pdf->SetMargins(6, 36, 6);
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
        $yMeta = $pdf->GetY();
        $pdf->SetY($yMeta + 2);

        $pdf->SetX(10);
        $pdf->Cell(90, 4.5, 'Report Reference No.: ' . $reportRefNo, 0, 1);
        $pdf->SetX(10);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(90, 4.5, 'Date Generated: ' . $generatedAt, 0, 1);
        $pdf->SetX(10);
        $pdf->Cell(90, 4.5, 'Prepared By: ' . $adminName . '  (DRRM Administrator)', 0, 1);

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
        // SECTION 2 — SYSTEM LOGO / VISUAL BANNER
        // ════════════════════════════════════════════════════════
        $pdf->SectionHeader('System Overview & Report Identity', 'SafeHaven Emergency Evacuation Management System');

        // Visual identity panel (image or colored banner with system info)
        $bannerY = $pdf->GetY();
        $pdf->SetFillColor(13, 71, 161);
        $pdf->SetDrawColor(189, 189, 189);
        $pdf->RoundedRect(6, $bannerY, 198, 28, 2, 'FD');

        if ($hasLogo) {
            try {
                $pdf->Image($logoPath, 10, $bannerY + 4, 20, 20);
                $infoX = 34;
            } catch (Exception $e) {
                $infoX = 10;
            }
        } else {
            $infoX = 10;
        }

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY($infoX, $bannerY + 3);
        $pdf->Cell(130, 8, 'SafeHaven', 0, 1, 'L');

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX($infoX);
        $pdf->Cell(130, 5, 'Emergency Evacuation Management System', 0, 1, 'L');

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX($infoX);
        $pdf->Cell(130, 5, 'DRRM Office  |  Cebu City, Philippines', 0, 1, 'L');

        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetX($infoX);
        $pdf->Cell(130, 4, 'Disaster Risk Reduction and Management Information System', 0, 1, 'L');

        // Right side: quick stats badge
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY(140, $bannerY + 5);
        $pdf->Cell(58, 6, (string)($stats['total_centers'] ?? 0) . ' Centers', 0, 1, 'R');
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetX(140);
        $pdf->Cell(58, 5, (string)($stats['total_evacuees'] ?? 0) . ' Evacuees', 0, 1, 'R');
        $pdf->SetX(140);
        $pdf->Cell(58, 5, count($activeAlerts) . ' Active Alerts', 0, 1, 'R');
        $pdf->SetX(140);
        $pdf->Cell(58, 5, count($requests) . ' Total Requests', 0, 1, 'R');

        $pdf->SetTextColor(33, 33, 33);
        $pdf->SetY($bannerY + 32);

        // ════════════════════════════════════════════════════════
        // SECTION 3 — KEY STATISTICS (stat boxes)
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

        $subBoxes = [
            ['Accepting',    (string)($stats['accepting']      ?? 0), 'Centers open',       [232,245,233]],
            ['Limited',      (string)($stats['limited']        ?? 0), 'Nearing capacity',   [255,243,224]],
            ['Full',         (string)($stats['full']           ?? 0), 'At capacity',        [252,228,236]],
            ['Avail. Beds',  (string)($stats['available_beds'] ?? 0), 'Remaining capacity', [227,242,253]],
        ];

        $yStats2 = $pdf->GetY();
        foreach ($subBoxes as $i => $box) {
            $bx = $startX + $i * ($boxW + $gapX);
            $pdf->StatBox($bx, $yStats2, $boxW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
        }
        $pdf->SetY($yStats2 + $boxH + 4);

        // ════════════════════════════════════════════════════════
        // SECTION 4 — ACTIVE ALERTS SUMMARY
        // ════════════════════════════════════════════════════════
        $pdf->SectionHeader('Active Situational Alerts', 'Current alerts as of ' . $generatedAt);

        $alertBoxes = [
            ['Critical',     (string)$criticalAlerts,      'Critical level',     [252,228,236]],
            ['Evacuation',   (string)$evacuationAlerts,    'Evacuation alerts',  [255,235,210]],
            ['Warning',      (string)$warningAlerts,       'Warning level',      [255,243,224]],
            ['Total Alerts', (string)count($activeAlerts), 'All active',         [245,245,245]],
        ];

        $yAlerts = $pdf->GetY();
        foreach ($alertBoxes as $i => $box) {
            $bx = $startX + $i * ($boxW + $gapX);
            $pdf->StatBox($bx, $yAlerts, $boxW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
        }
        $pdf->SetY($yAlerts + $boxH + 4);

        if (!empty($activeAlerts)) {
            $recentAlerts = array_slice($activeAlerts, 0, 10);
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
                $pdf->SetX(6);

                $pdf->Cell($colWidths[0], 6, (string)($i + 1), 0, 0, 'C', true);

                $sev = strtolower($alert['severity'] ?? 'info');
                switch ($sev) {
                    case 'critical':   $pdf->SetTextColor(183, 28, 28); break;
                    case 'evacuation': $pdf->SetTextColor(230, 81, 0);  break;
                    case 'warning':    $pdf->SetTextColor(245, 127, 23);break;
                    default:           $pdf->SetTextColor(21, 101, 192);break;
                }
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->Cell($colWidths[1], 6, strtoupper($sev), 0, 0, 'C', true);

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
                $pdf->Cell(198, 5, '  ... and ' . (count($activeAlerts) - 10) . ' more alerts. See full alerts in the system.', 0, 1, 'L');
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
        // SECTION 5 — CENTER-BY-CENTER OCCUPANCY TABLE
        // ════════════════════════════════════════════════════════
        if ($pdf->GetY() > 190) { $pdf->AddPage(); }

        $pdf->SectionHeader('Evacuation Center Occupancy Report', 'Per-center capacity and utilization');

        if (!empty($centers)) {
            $cw = [7, 50, 28, 22, 22, 22, 35];
            $ch = ['#', 'Center Name', 'Barangay', 'Capacity', 'Occupancy', '% Full', 'Status'];
            $ca = ['C', 'L', 'L', 'C', 'C', 'C', 'C'];

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

                $cap    = (int)($center['capacity']          ?? 0);
                $occ    = (int)($center['current_occupancy'] ?? 0);
                $pct    = $cap > 0 ? round(($occ / $cap) * 100) : 0;
                $status = strtolower($center['status'] ?? 'accepting');

                if ($status === 'full')            $fillBg = [255, 235, 238];
                elseif ($status === 'limited')     $fillBg = [255, 248, 225];
                else                               $fillBg = $odd ? [255,255,255] : [240,248,255];

                $pdf->SetFillColor(...$fillBg);
                $pdf->SetTextColor(33, 33, 33);
                $pdf->SetX(6);

                $pdf->Cell($cw[0], 7, (string)($i + 1), 0, 0, 'C', true);
                $pdf->Cell($cw[1], 7, mb_strimwidth($center['name'] ?? 'Unnamed', 0, 42, '..'), 0, 0, 'L', true);
                $pdf->Cell($cw[2], 7, mb_strimwidth($center['barangay'] ?? '—', 0, 22, '..'), 0, 0, 'L', true);
                $pdf->Cell($cw[3], 7, number_format($cap), 0, 0, 'C', true);
                $pdf->Cell($cw[4], 7, number_format($occ), 0, 0, 'C', true);

                if ($pct >= 100)     $pdf->SetTextColor(183, 28, 28);
                elseif ($pct >= 80)  $pdf->SetTextColor(230, 81, 0);
                else                 $pdf->SetTextColor(27, 94, 32);
                $pdf->SetFont('Arial', 'B', 8.5);
                $pdf->Cell($cw[5], 7, $pct . '%', 0, 0, 'C', true);

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
            $pdf->Cell($cw[5], 7, ($stats['occupancy_rate'] ?? 0) . '%', 0, 0, 'C', true);
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
        // SECTION 6 — DISPLACED FAMILIES & EVACUATION REQUESTS
        // ════════════════════════════════════════════════════════
        if ($pdf->GetY() > 210) { $pdf->AddPage(); }

        $pdf->SectionHeader('Displaced Families & Evacuation Requests', 'Request status breakdown and family counts');

        $reqBoxes = [
            ['Total Requests', (string)count($requests),   'All requests',        [245,245,245]],
            ['Pending',        (string)$pendingCount,      'Awaiting review',     [255,243,224]],
            ['Approved',       (string)$approvedCount,     'Confirmed & placed',  [232,245,233]],
            ['Rejected',       (string)$rejectedCount,     'Denied requests',     [252,228,236]],
        ];

        $yReq = $pdf->GetY();
        foreach ($reqBoxes as $i => $box) {
            $bx = $startX + $i * ($boxW + $gapX);
            $pdf->StatBox($bx, $yReq, $boxW, $boxH, $box[0], $box[1], $box[2], ...$box[3]);
        }
        $pdf->SetY($yReq + $boxH + 3);

        $famBoxes = [
            ['Displaced Families', (string)$totalFamilies, 'Approved requests',  [232,245,233]],
            ['Total Persons',      (string)$totalPersons,  'Incl. family members',[227,242,253]],
            ['Avg. per Family',    $totalFamilies > 0 ? number_format($totalPersons / $totalFamilies, 1) : '0', 'Members/family', [245,245,245]],
        ];

        $yFam = $pdf->GetY();
        $famW = 60; $famGap = 5;
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

            $rcw = [7, 45, 40, 16, 28, 50];
            $rch = ['#', 'Resident Name', 'Assigned Center', 'Fam.', 'Priority', 'Date'];
            $rca = ['C', 'L', 'L', 'C', 'C', 'L'];

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
        // SECTION 7 — NOTES / REMARKS
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
        // SECTION 8 — SIGNATURE BLOCK
        // ════════════════════════════════════════════════════════
        if ($pdf->GetY() > 230) { $pdf->AddPage(); }

        $pdf->SectionHeader('Certification & Signature Block');

        $sigY  = $pdf->GetY();
        $sigW  = 60;
        $sigGap = 9;
        $sigCols = [
            ['Prepared By',  'DRRM Admin Officer', $adminName],
            ['Reviewed By',  'Operations Head',    '_____________________'],
            ['Approved By',  'DRRM Director',      '_____________________'],
        ];

        foreach ($sigCols as $i => $col) {
            $sx = 9 + $i * ($sigW + $sigGap);
            $sy = $sigY;

            $pdf->SetFillColor(248, 249, 250);
            $pdf->SetDrawColor(189, 189, 189);
            $pdf->RoundedRect($sx, $sy, $sigW, 44, 2, 'FD');

            $pdf->SetFont('Arial', 'B', 7.5);
            $pdf->SetTextColor(21, 101, 192);
            $pdf->SetXY($sx + 2, $sy + 3);
            $pdf->Cell($sigW - 4, 5, strtoupper($col[0]), 0, 1, 'C');

            $pdf->SetDrawColor(33, 33, 33);
            $pdf->SetLineWidth(0.4);
            $pdf->Line($sx + 8, $sy + 26, $sx + $sigW - 8, $sy + 26);
            $pdf->SetLineWidth(0.2);

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(33, 33, 33);
            $pdf->SetXY($sx + 2, $sy + 28);
            $pdf->Cell($sigW - 4, 5, $col[2], 0, 1, 'C');

            $pdf->SetFont('Arial', '', 7.5);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY($sx + 2, $sy + 33);
            $pdf->Cell($sigW - 4, 4, $col[1], 0, 1, 'C');

            $pdf->SetFont('Arial', '', 7);
            $pdf->SetXY($sx + 2, $sy + 38);
            $pdf->Cell($sigW - 4, 4, 'Date: _______________', 0, 1, 'C');

            $pdf->SetTextColor(33, 33, 33);
        }

        $pdf->SetY($sigY + 50);

        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->SetX(6);
        $pdf->Cell(198, 5,
            'Document Control: ' . $reportRefNo . '  |  Generated: ' . $generatedAt .
            '  |  SafeHaven DRRM Information System  |  This document is system-generated.',
            0, 1, 'C');

        $pdf->SetTextColor(33, 33, 33);

        // ─── Output PDF ──────────────────────────────────────────────────────
        $filename = 'SafeHaven_DRRM_Report_' . date('Ymd_His') . '.pdf';
        $pdf->Output('D', $filename);
        exit;
    }
}
