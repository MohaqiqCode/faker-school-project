<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class BranchGenerator
{
    private PDO $db;
    private array $branches = [];
    
    private const BRANCH_DATA = [
        ['code' => 'KBL001', 'name' => 'Kabul Central', 'city' => 'Kabul', 'state' => 'Kabul', 'capacity' => 2000],
        ['code' => 'KBL002', 'name' => 'Pul-e-Surkh', 'city' => 'Kabul', 'state' => 'Kabul', 'capacity' => 1500],
        ['code' => 'KBL003', 'name' => 'Pul-e-Khoshk', 'city' => 'Kabul', 'state' => 'Kabul', 'capacity' => 1200],
        ['code' => 'KBL004', 'name' => 'Qala-e-Naw', 'city' => 'Kabul', 'state' => 'Kabul', 'capacity' => 1000],
        ['code' => 'KBL005', 'name' => 'Sar-e-Pul', 'city' => 'Kabul', 'state' => 'Kabul', 'capacity' => 800],
        ['code' => 'BMN001', 'name' => 'Bamyan Main', 'city' => 'Bamyan', 'state' => 'Bamyan', 'capacity' => 1500],
        ['code' => 'GZN001', 'name' => 'Ghazni Main', 'city' => 'Ghazni', 'state' => 'Ghazni', 'capacity' => 1500],
        ['code' => 'GHR001', 'name' => 'Ghor Main', 'city' => 'Ghor', 'state' => 'Ghor', 'capacity' => 1200],
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(): array
    {
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO branches (branch_code, name, address, city, state, country, postal_code, phone, email, principal_name, established_date, total_capacity, is_active)
            VALUES (?, ?, ?, ?, ?, 'Afghanistan', ?, ?, ?, ?, ?, ?, 1)
        ");
        
        foreach (self::BRANCH_DATA as $index => $data) {
            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['name'] . ' School, Main Road',
                $data['city'],
                $data['state'],
                sprintf('%04d', rand(1000, 9999)),
                sprintf('+93%09d', rand(700000000, 799999999)),
                strtolower(str_replace([' ', '-'], ['.', ''], $data['name'])) . '@afghanschool.edu.af',
                $this->getRandomPrincipal(),
                $this->getEstablishmentDate($index),
                $data['capacity']
            ]);
            
            $this->branches[$index + 1] = (int)$this->db->lastInsertId();
        }
        
        $this->db->commit();
        
        return $this->branches;
    }
    
    private function getRandomPrincipal(): string
    {
        $principals = [
            'Ahmad Rahimi', 'Mohammad Karimi', 'Abdul Basir Ahmadi',
            'Farid Noori', 'Hamid Hussaini', 'Jawad Safi',
            'Najibullah Wardak', 'Samiullah Hakimi'
        ];
        return $principals[array_rand($principals)];
    }
    
    private function getEstablishmentDate(int $index): string
    {
        $years = [2000, 2003, 2005, 2007, 2010, 2012, 2015, 2018];
        return sprintf('%d-%02d-%02d', $years[$index], rand(1, 12), rand(1, 28));
    }
    
    public function getBranches(): array
    {
        return $this->branches;
    }
}