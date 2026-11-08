<?php

namespace App\Generators;

use App\Core\Database;
use Faker\Factory;
use PDO;

class UserGenerator
{
    private PDO $db;
    private \Faker\Generator $faker;
    private array $userIds = [];
    private int $userCounter = 0;
    
    private const AFGHAN_MALE_NAMES = [
        'Ahmad', 'Mohammad', 'Abdul Basir', 'Abdul Qadir', 'Farid', 'Hamid',
        'Bilal', 'Jawad', 'Najibullah', 'Habibullah', 'Samiullah', 'Matiullah',
        'Tamim', 'Yama', 'Faisal', 'Naseer Ahmad'
    ];
    
    private const AFGHAN_FEMALE_NAMES = [
        'Fatima', 'Maryam', 'Zahra', 'Shukria', 'Amina', 'Khadija',
        'Nasrin', 'Roya', 'Farzana', 'Nilab', 'Mahboba', 'Parwana', 'Sitara', 'Nazifa'
    ];
    
    private const AFGHAN_LAST_NAMES = [
        'Mohammadi', 'Ahmadi', 'Rahimi', 'Karimi', 'Hakimi', 'Noori',
        'Hussaini', 'Aryan', 'Wardak', 'Shinwari', 'Safi', 'Mansoori'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->faker = Factory::create();
    }
    
    public function generateUser(int $branchId, int $roleId, string $gender = 'male'): int
    {
        $this->userCounter++;
        $firstName = $this->getRandomName($gender);
        $lastName = self::AFGHAN_LAST_NAMES[array_rand(self::AFGHAN_LAST_NAMES)];
        $userCode = sprintf('USR%08d', $this->userCounter);
        $email = strtolower(str_replace(' ', '.', $firstName . '.' . $lastName . $this->userCounter)) . '@afghanschool.edu.af';
        
        $stmt = $this->db->prepare("
            INSERT INTO users (user_code, branch_id, role_id, first_name, last_name, email, password, phone, gender, date_of_birth, address, city, state, country, postal_code, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Afghanistan', ?, 1)
        ");
        
        $password = password_hash('password123', PASSWORD_DEFAULT);
        $phone = '+93' . sprintf('%09d', rand(700000000, 799999999));
        $dob = $this->faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d');
        
        $cities = ['Kabul', 'Bamyan', 'Ghazni', 'Ghor'];
        $states = ['Kabul', 'Bamyan', 'Ghazni', 'Ghor'];
        $cityIndex = array_rand($cities);
        
        $stmt->execute([
            $userCode,
            $branchId,
            $roleId,
            $firstName,
            $lastName,
            $email,
            $password,
            $phone,
            $gender,
            $dob,
            $this->faker->address(),
            $cities[$cityIndex],
            $states[$cityIndex],
            sprintf('%04d', rand(1000, 9999))
        ]);
        
        $userId = (int)$this->db->lastInsertId();
        $this->userIds[] = $userId;
        
        return $userId;
    }
    
    private function getRandomName(string $gender): string
    {
        if ($gender === 'female') {
            return self::AFGHAN_FEMALE_NAMES[array_rand(self::AFGHAN_FEMALE_NAMES)];
        }
        return self::AFGHAN_MALE_NAMES[array_rand(self::AFGHAN_MALE_NAMES)];
    }
    
    public function getUsers(): array
    {
        return $this->userIds;
    }
}