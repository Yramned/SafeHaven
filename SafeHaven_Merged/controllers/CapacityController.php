<?php
/**
 * SafeHaven - Capacity Management Controller (Admin Only)
 */

require_once MODEL_PATH . 'CapacityModel.php';

class CapacityController {
    
    private static function checkAdminAccess() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php?page=dashboard');
            exit;
        }
    }
    
    public static function index() {
        self::checkAdminAccess();
        
        $stats = CapacityModel::getStats();
        $requests = CapacityModel::getPendingRequests();
        
        $pageTitle = 'Capacity Management - SafeHaven';
        $activePage = 'capacity';
        $extraCss = ['assets/css/Capacity.css'];
        $extraJs = ['assets/js/capacity.js'];
        
        require_once VIEW_PATH . 'admin/capacity.php';
    }
    
    public static function approveRequest() {
        self::checkAdminAccess();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $requestId = $_POST['request_id'] ?? '';
        $result = CapacityModel::approveRequest($requestId);
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    public static function denyRequest() {
        self::checkAdminAccess();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        
        $requestId = $_POST['request_id'] ?? '';
        $result = CapacityModel::denyRequest($requestId);
        
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}