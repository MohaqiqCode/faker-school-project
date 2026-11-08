<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class VehicleGenerator
{
    private PDO $db;
    private array $vehicles = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO vehicles (branch_id, vehicle_number, vehicle_type, model, capacity, registration_date, insurance_expiry, fitness_certificate_expiry, fuel_type, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $vehicleTypes = ['Bus', 'Mini Bus', 'Van', 'SUV', 'Sedan'];
        $vehicleModels = [
            'Bus' => ['Hino 500', 'Isuzu NQR', 'Tata Marcopolo'],
            'Mini Bus' => ['Toyota Coaster', 'Mitsubishi Rosa', 'Nissan Civilian'],
            'Van' => ['Toyota Hiace', 'Kia Bongo', 'Hyundai H100'],
            'SUV' => ['Toyota Land Cruiser', 'Nissan Patrol', 'Ford Everest'],
            'Sedan' => ['Toyota Corolla', 'Honda Civic', 'Hyundai Elantra']
        ];
        
        $fuelTypes = ['petrol', 'diesel', 'cng'];
        
        foreach ($branchIds as $branchId) {
            // Each branch gets 5-10 vehicles
            $numVehicles = rand(5, 10);
            
            for ($i = 0; $i < $numVehicles; $i++) {
                $type = $vehicleTypes[array_rand($vehicleTypes)];
                $model = $vehicleModels[$type][array_rand($vehicleModels[$type])];
                $vehicleNumber = 'AFG-' . sprintf('%04d', rand(1000, 9999)) . '-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 2));
                
                $capacity = match($type) {
                    'Bus' => rand(40, 60),
                    'Mini Bus' => rand(20, 30),
                    'Van' => rand(10, 15),
                    'SUV' => rand(5, 7),
                    'Sedan' => rand(4, 5),
                    default => rand(10, 30)
                };
                
                $regYear = rand(2015, 2025);
                
                $stmt->execute([
                    $branchId,
                    $vehicleNumber,
                    $type,
                    $model,
                    $capacity,
                    sprintf('%d-%02d-%02d', $regYear, rand(1, 12), rand(1, 28)),
                    sprintf('%d-12-31', rand(2025, 2027)),
                    sprintf('%d-12-31', rand(2025, 2027)),
                    $fuelTypes[array_rand($fuelTypes)]
                ]);
                
                $vehicleId = (int)$this->db->lastInsertId();
                $this->vehicles[$branchId][] = $vehicleId;
            }
        }
        
        $this->db->commit();
        
        return $this->vehicles;
    }
    
    public function getVehicles(): array
    {
        return $this->vehicles;
    }
}