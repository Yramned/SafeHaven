<?php
class CommandCenterController {
    private $evacuationCenters;
    private $statistics;

    public function __construct() {
        $this->loadData();
    }

    private function loadData() {
        $this->evacuationCenters = [
            ['id'=>1,'name'=>'Barangay Central Gym','barangay'=>'Zone 1','capacity'=>150,'current'=>120,'status'=>'limited'],
            ['id'=>2,'name'=>'Community Center North','barangay'=>'Zone 2','capacity'=>200,'current'=>85,'status'=>'accepting'],
            ['id'=>3,'name'=>'Sports Complex East','barangay'=>'Zone 3','capacity'=>300,'current'=>300,'status'=>'full'],
            ['id'=>4,'name'=>'Elementary School West','barangay'=>'Zone 4','capacity'=>180,'current'=>95,'status'=>'accepting'],
            ['id'=>5,'name'=>'Barangay Hall South','barangay'=>'Zone 5','capacity'=>120,'current'=>110,'status'=>'limited'],
        ];
        $this->calculateStatistics();
    }

    private function calculateStatistics() {
        $totalEvacuees = 0;
        $totalCapacity = 0;
        $accepting = $limited = $full = 0;

        foreach ($this->evacuationCenters as $c) {
            $totalEvacuees += $c['current'];
            $totalCapacity += $c['capacity'];
            if ($c['status']==='accepting') $accepting++;
            if ($c['status']==='limited') $limited++;
            if ($c['status']==='full') $full++;
        }

        $this->statistics = [
            'totalEvacuees'=>$totalEvacuees,
            'totalCapacity'=>$totalCapacity,
            'occupancyRate'=>round(($totalEvacuees/$totalCapacity)*100),
            'availableBeds'=>$totalCapacity-$totalEvacuees,
            'totalCenters'=>count($this->evacuationCenters),
            'accepting'=>$accepting,
            'limited'=>$limited,
            'full'=>$full
        ];
    }

    public function stats(){ return $this->statistics; }
    public function centers(){ return $this->evacuationCenters; }

    public function statusColor($s){
        return match($s){
            'accepting'=>'#10b981',
            'limited'=>'#f59e0b',
            'full'=>'#ef4444',
            default=>'#6b7280'
        };
    }

    public function statusIcon($s){
        return match($s){
            'accepting'=>'🟢',
            'limited'=>'🟡',
            'full'=>'🔴',
            default=>'⚪'
        };
    }
}

$c = new CommandCenterController();
$stats = $c->stats();
$centers = $c->centers();

$pageTitle  = 'SafeHaven – Evacuation Centers';
$activePage = 'evacuation-centers';
$extraCss   = ['assets/css/eva.css'];
$extraJs    = ['assets/js/eva.js'];

require_once VIEW_PATH . 'shared/dashboard-header.php';
?>

<div class="container">
<header class="header">
    <h1>Command Center Dashboard</h1>
    <p>Evacuation Center Monitoring</p>
</header>

<section>
<h2>City-Wide Overview</h2>
<div class="stats-grid">
    <div class="stat-card">
        <div>👥 Total Evacuees</div>
        <strong><?=number_format($stats['totalEvacuees'])?></strong>
    </div>
    <div class="stat-card">
        <div>📊 Occupancy Rate</div>
        <strong><?=$stats['occupancyRate']?>%</strong>
    </div>
    <div class="stat-card">
        <div>🛏️ Available Beds</div>
        <strong><?=number_format($stats['availableBeds'])?></strong>
    </div>
    <div class="stat-card">
        <div>🏢 Total Centers</div>
        <strong><?=$stats['totalCenters']?></strong>
        <small>
            🟢 <?=$stats['accepting']?> | 🟡 <?=$stats['limited']?> | 🔴 <?=$stats['full']?>
        </small>
    </div>
</div>
</section>

<section>
<h2>Evacuation Centers</h2>
<div class="centers-grid">
<?php foreach($centers as $center): 
$p = round(($center['current']/$center['capacity'])*100); ?>
<div class="center-card">
    <div class="center-header">
        <h3><?=$center['name']?></h3>
        <span class="status-badge" style="background:<?=$c->statusColor($center['status'])?>">
            <?=$c->statusIcon($center['status'])?> <?=ucfirst($center['status'])?>
        </span>
    </div>
    <p class="center-location">📍 <?=$center['barangay']?></p>
    <div class="bar">
        <div class="fill" style="width:<?=$p?>%; background:<?=$c->statusColor($center['status'])?>"></div>
    </div>
    <small class="center-occupancy"><?=$center['current']?> / <?=$center['capacity']?> occupants (<?=$p?>%)</small>
</div>
<?php endforeach; ?>
</div>
</section>

<footer class="footer-info">
<p>Last updated: <span id="lastUpdate"></span></p>
<script>
document.getElementById('lastUpdate').textContent = new Date().toLocaleString();
</script>
</footer>
</div>

<?php require_once VIEW_PATH . 'shared/footer.php'; ?>
