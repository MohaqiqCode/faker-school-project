<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class EmployeeGenerator
{
    private PDO $db;
    private UserGenerator $userGenerator;
    private array $employeeIds = [];
    private int $employeeCounter = 0;
    
    public function __construct(UserGenerator $userGenerator)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userGenerator = $userGenerator;
    }
    
    public function generate(int $branchId, int $employeeRoleId, int $year, int $count): array
    {
        $employeeIds = [];
        
        $designations = [
            'administrative' => ['Office Manager', 'Data Entry Clerk', 'Front Desk Officer', 'HR Assistant'],
            'support' => ['Lab Assistant', 'Library Assistant', 'IT Support', 'Maintenance Worker'],
            'security' => ['Security Guard', 'Watchman', 'Gate Keeper'],
            'other' => ['Driver', 'Cook', 'Cleaner', 'Gardener']
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $this->employeeCounter++;
            $userId = $this->userGenerator->generateUser($branchId, $employeeRoleId, rand(0, 1) ? 'male' : 'female');
            $employeeCode = 'EMP' . sprintf('%06d', $this->employeeCounter);
            
            $employeeType = array_rand($designations);
            $designation = $designations[$employeeType][array_rand($designations[$employeeType])];
            
            $joiningYear = max(2016, $year - rand(1, 5));
            
            $stmt = $this->db->prepare("
                INSERT INTO employees (user_id, employee_code, branch_id, designation, employee_type, joining_date, salary_grade, bank_name, bank_account_number)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $employeeCode,
                $branchId,
                $designation,
                $employeeType,
                "{$joiningYear}-" . sprintf('%02d-%02d', rand(1, 12), rand(1, 28)),
                'GRADE_' . rand(1, 3),
                'Afghanistan International Bank',
                sprintf('%016d', rand(1000000000000000, 9999999999999999))
            ]);
            
            $employeeId = (int)$this->db->lastInsertId();
            $this->employeeIds[] = $employeeId;
            $employeeIds[] = $employeeId;
        }
        
        return $employeeIds;
    }
    
    public function getEmployees(): array
    {
        return $this->employeeIds;
    }
}