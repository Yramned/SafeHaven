<?php
/**
 * SafeHaven - Evacuation Controller
 */

require_once MODEL_PATH . 'CapacityModel.php';

class EvacuationController {
    
    public static function requestForm() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        // CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $pageTitle = 'Evacuation Request - SafeHaven';
        $activePage = 'evacuation-request';
        $extraCss = ['assets/css/evacuation-request.css'];
        $extraJs = ['assets/js/evacuation-request.js'];
        
        require_once VIEW_PATH . 'evacuation/request.php';
    }
    
    public static function submitRequest() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'index.php?page=evacuation-request');
            exit;
        }
        
        // In a real application, you would process the request here
        // For now, we'll just redirect to a success page
        
        $_SESSION['evacuation_success'] = true;
        header('Location: ' . BASE_URL . 'index.php?page=evacuation-result&status=approved');
        exit;
    }
    
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
        
        require_once VIEW_PATH . 'evacuation/result.php';
    }
    
    public static function centers() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'index.php?page=login');
            exit;
        }
        
        $pageTitle = 'Evacuation Centers - SafeHaven';
        $activePage = 'evacuation-centers';
        $extraCss = ['assets/css/eva.css'];
        $extraJs = ['assets/js/eva.js'];
        
        // Sample evacuation centers data
        $evacuationCenters = [
            ['id'=>1,'name'=>'Barangay Central Gym','barangay'=>'Zone 1','capacity'=>150,'current'=>120,'status'=>'limited'],
            ['id'=>2,'name'=>'Community Center North','barangay'=>'Zone 2','capacity'=>200,'current'=>85,'status'=>'accepting'],
            ['id'=>3,'name'=>'Sports Complex East','barangay'=>'Zone 3','capacity'=>300,'current'=>300,'status'=>'full'],
            ['id'=>4,'name'=>'Elementary School West','barangay'=>'Zone 4','capacity'=>180,'current'=>95,'status'=>'accepting'],
            ['id'=>5,'name'=>'Barangay Hall South','barangay'=>'Zone 5','capacity'=>120,'current'=>110,'status'=>'limited'],
        ];
        
        // Calculate statistics
        $totalEvacuees = array_sum(array_column($evacuationCenters, 'current'));
        $totalCapacity = array_sum(array_column($evacuationCenters, 'capacity'));
        $occupancyRate = round(($totalEvacuees / $totalCapacity) * 100);
        $availableBeds = $totalCapacity - $totalEvacuees;
        
        $accepting = count(array_filter($evacuationCenters, fn($c) => $c['status'] === 'accepting'));
        $limited = count(array_filter($evacuationCenters, fn($c) => $c['status'] === 'limited'));
        $full = count(array_filter($evacuationCenters, fn($c) => $c['status'] === 'full'));
        
        $statistics = [
            'totalEvacuees' => $totalEvacuees,
            'totalCapacity' => $totalCapacity,
            'occupancyRate' => $occupancyRate,
            'availableBeds' => $availableBeds,
            'totalCenters' => count($evacuationCenters),
            'accepting' => $accepting,
            'limited' => $limited,
            'full' => $full
        ];
        
        require_once VIEW_PATH . 'evacuation/centers.php';
    }
}