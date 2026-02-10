<?php
/**
 * SafeHaven - Capacity Model
 */

require_once CONFIG_PATH . 'database.php';

class CapacityModel {
    
    public static function getData() {
        return JSONDatabase::read(CAPACITY_FILE);
    }
    
    public static function getStats() {
        $data = self::getData();
        $capacity = $data['capacity'] ?? ['current' => 0, 'max' => 300];
        $requests = $data['requests'] ?? [];
        
        $current = $capacity['current'];
        $max = $capacity['max'];
        $available = $max - $current;
        $percentage = $max > 0 ? round(($current / $max) * 100) : 0;
        $pending = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'pending'));
        
        return [
            'current' => $current,
            'max' => $max,
            'available' => $available,
            'percentage' => $percentage,
            'pending' => $pending
        ];
    }
    
    public static function getPendingRequests() {
        $data = self::getData();
        $requests = $data['requests'] ?? [];
        return array_filter($requests, fn($r) => ($r['status'] ?? '') === 'pending');
    }
    
    public static function approveRequest($requestId) {
        $data = self::getData();
        $requests = $data['requests'] ?? [];
        $capacity = $data['capacity'] ?? ['current' => 0, 'max' => 300];
        
        foreach ($requests as $key => &$request) {
            if ($request['id'] === $requestId && ($request['status'] ?? '') === 'pending') {
                $familySize = $request['family_size'] ?? 1;
                
                if ($capacity['current'] + $familySize > $capacity['max']) {
                    return ['success' => false, 'message' => 'Not enough capacity'];
                }
                
                $request['status'] = 'approved';
                $capacity['current'] += $familySize;
                
                $data['requests'] = $requests;
                $data['capacity'] = $capacity;
                
                JSONDatabase::write(CAPACITY_FILE, $data);
                
                return ['success' => true, 'message' => 'Request approved'];
            }
        }
        
        return ['success' => false, 'message' => 'Request not found'];
    }
    
    public static function denyRequest($requestId) {
        $data = self::getData();
        $requests = $data['requests'] ?? [];
        
        foreach ($requests as $key => &$request) {
            if ($request['id'] === $requestId && ($request['status'] ?? '') === 'pending') {
                $request['status'] = 'denied';
                
                $data['requests'] = $requests;
                JSONDatabase::write(CAPACITY_FILE, $data);
                
                return ['success' => true, 'message' => 'Request denied'];
            }
        }
        
        return ['success' => false, 'message' => 'Request not found'];
    }
    
    public static function initialize() {
        $defaultData = [
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
        
        JSONDatabase::initialize(CAPACITY_FILE, $defaultData);
    }
}