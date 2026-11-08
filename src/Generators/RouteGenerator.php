<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class RouteGenerator
{
    private PDO $db;
    private array $routes = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds, array $vehicles, array $drivers): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $routeStmt = $this->db->prepare("
            INSERT INTO transport_routes (branch_id, route_name, route_code, start_point, end_point, distance_km, estimated_time_minutes, vehicle_id, driver_id, monthly_fee, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stopStmt = $this->db->prepare("
            INSERT INTO route_stops (route_id, stop_name, stop_order, pickup_time, drop_time, latitude, longitude)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $kabulAreas = [
            'Wazir Akbar Khan', 'Shahr-e-Naw', 'Karte Parwan', 'Karte Seh',
            'Karte Char', 'Macrorayan', 'Khair Khana', 'Dasht-e-Barchi',
            'Company', 'Taimani', 'Qala-e-Fathullah', 'Deh Afghanan'
        ];
        
        foreach ($branchIds as $branchId) {
            if (!isset($vehicles[$branchId]) || !isset($drivers[$branchId])) continue;
            
            $branchVehicles = $vehicles[$branchId];
            $branchDrivers = $drivers[$branchId];
            
            // Each branch gets 3-8 routes
            $numRoutes = rand(3, 8);
            
            for ($i = 0; $i < $numRoutes; $i++) {
                $routeName = "Route " . ($i + 1);
                $routeCode = 'RTE' . sprintf('%03d', $branchId) . sprintf('%02d', $i + 1);
                
                $startArea = $kabulAreas[array_rand($kabulAreas)];
                $endArea = $kabulAreas[array_rand($kabulAreas)];
                
                $stmt = $routeStmt;
                $stmt->execute([
                    $branchId,
                    $routeName,
                    $routeCode,
                    $startArea,
                    $endArea,
                    rand(5, 30),
                    rand(20, 90),
                    $branchVehicles[$i % count($branchVehicles)] ?? null,
                    $branchDrivers[$i % count($branchDrivers)] ?? null,
                    rand(500, 2000)
                ]);
                
                $routeId = (int)$this->db->lastInsertId();
                $this->routes[$branchId][] = $routeId;
                
                // Create stops for this route
                $numStops = rand(5, 15);
                $stopAreas = array_rand(array_flip($kabulAreas), min($numStops, count($kabulAreas)));
                
                foreach ($stopAreas as $index => $stopName) {
                    $pickupHour = 7 + intdiv($index, 3);
                    $pickupMinute = ($index % 3) * 20;
                    
                    $dropHour = 13 + intdiv($index, 3);
                    $dropMinute = ($index % 3) * 20;
                    
                    $stopStmt->execute([
                        $routeId,
                        $stopName,
                        $index + 1,
                        sprintf('%02d:%02d:00', $pickupHour % 24, $pickupMinute),
                        sprintf('%02d:%02d:00', $dropHour % 24, $dropMinute),
                        34.5 + (rand(0, 100) / 1000),
                        69.2 + (rand(0, 100) / 1000)
                    ]);
                }
            }
        }
        
        $this->db->commit();
        
        return $this->routes;
    }
    
    public function getRoutes(): array
    {
        return $this->routes;
    }
}