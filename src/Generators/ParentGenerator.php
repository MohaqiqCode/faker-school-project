<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class ParentGenerator
{
    private PDO $db;
    private UserGenerator $userGenerator;
    private array $parentIds = [];
    private int $parentCounter = 0;
    
    public function __construct(UserGenerator $userGenerator)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userGenerator = $userGenerator;
    }
    
    public function generate(int $branchId, int $parentRoleId): int
    {
        $this->parentCounter++;
        $gender = rand(0, 1) ? 'male' : 'female';
        $userId = $this->userGenerator->generateUser($branchId, $parentRoleId, $gender);
        $parentCode = 'PRT' . sprintf('%06d', $this->parentCounter);
        
        $occupations = ['Teacher', 'Doctor', 'Engineer', 'Business Owner', 'Government Employee', 'Farmer', 'Shopkeeper', 'Driver'];
        $workplaces = ['Government Office', 'Private Company', 'Hospital', 'School', 'Market', 'Farm'];
        
        $stmt = $this->db->prepare("
            INSERT INTO parents (user_id, parent_code, occupation, workplace, annual_income, relationship, is_primary_contact)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $relationship = $gender === 'male' ? 'father' : 'mother';
        
        $stmt->execute([
            $userId,
            $parentCode,
            $occupations[array_rand($occupations)],
            $workplaces[array_rand($workplaces)],
            rand(50000, 500000),
            $relationship
        ]);
        
        $parentId = (int)$this->db->lastInsertId();
        $this->parentIds[] = $parentId;
        
        return $parentId;
    }
    
    public function getParents(): array
    {
        return $this->parentIds;
    }
}