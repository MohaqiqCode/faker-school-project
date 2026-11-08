<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class ExpenseGenerator
{
    private PDO $db;
    
    private const EXPENSE_CATEGORIES = [
        'salary', 'utilities', 'maintenance', 'supplies', 'transport', 'events', 'other'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO expenses (branch_id, expense_category, description, amount, expense_date, payment_method, vendor_name, invoice_number, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        $expenseCount = 0;
        $batchSize = 500;
        
        foreach ($branchIds as $branchId) {
            // Generate expenses from 2016 to 2026
            for ($year = 2016; $year <= 2026; $year++) {
                // Monthly expenses
                for ($month = 1; $month <= 12; $month++) {
                    // Random number of expenses per month (10-30)
                    $monthlyExpenses = rand(10, 30);
                    
                    for ($i = 0; $i < $monthlyExpenses; $i++) {
                        $category = self::EXPENSE_CATEGORIES[array_rand(self::EXPENSE_CATEGORIES)];
                        $description = $this->getExpenseDescription($category);
                        $amount = $this->getExpenseAmount($category);
                        $expenseDate = sprintf('%d-%02d-%02d', $year, $month, rand(1, 28));
                        
                        $stmt->execute([
                            $branchId,
                            $category,
                            $description,
                            $amount,
                            $expenseDate,
                            ['cash', 'bank_transfer', 'check'][array_rand(['cash', 'bank_transfer', 'check'])],
                            $this->getRandomVendor($category),
                            'INV-' . sprintf('%08d', rand(1000, 9999)),
                            rand(0, 1) ? 'paid' : 'approved'
                        ]);
                        
                        $expenseCount++;
                        
                        if ($expenseCount % $batchSize === 0) {
                            $this->db->commit();
                            $this->db->beginTransaction();
                            echo "  Generated {$expenseCount} expenses...\n";
                        }
                    }
                }
            }
        }
        
        $this->db->commit();
        echo "Total expenses generated: {$expenseCount}\n";
    }
    
    private function getExpenseDescription(string $category): string
    {
        $descriptions = [
            'salary' => ['Monthly salary payment', 'Bonus payment', 'Overtime payment'],
            'utilities' => ['Electricity bill', 'Water bill', 'Internet bill', 'Phone bill'],
            'maintenance' => ['Building repair', 'Equipment maintenance', 'Garden maintenance'],
            'supplies' => ['Office supplies', 'Cleaning supplies', 'Teaching materials'],
            'transport' => ['Fuel cost', 'Vehicle maintenance', 'Transport allowance'],
            'events' => ['Annual day celebration', 'Sports day', 'Cultural event'],
            'other' => ['Miscellaneous expense', 'Emergency repair', 'Consultation fee']
        ];
        
        return $descriptions[$category][array_rand($descriptions[$category])] ?? 'General expense';
    }
    
    private function getExpenseAmount(string $category): float
    {
        $ranges = [
            'salary' => [50000, 500000],
            'utilities' => [5000, 50000],
            'maintenance' => [10000, 100000],
            'supplies' => [5000, 30000],
            'transport' => [10000, 50000],
            'events' => [20000, 150000],
            'other' => [5000, 50000]
        ];
        
        $range = $ranges[$category] ?? [5000, 50000];
        return rand($range[0], $range[1]);
    }
    
    private function getRandomVendor(string $category): string
    {
        $vendors = [
            'salary' => ['School Payroll', 'Bank Transfer'],
            'utilities' => ['Da Afghanistan Breshna Sherkat', 'Afghan Telecom'],
            'maintenance' => ['Kabul Construction Co', 'Maintenance Services Ltd'],
            'supplies' => ['Kabul Stationery Mart', 'Educational Supplies Co'],
            'transport' => ['Kabul Fuel Station', 'Auto Repair Shop'],
            'events' => ['Event Management Co', 'Catering Services'],
            'other' => ['General Services', 'Miscellaneous Vendor']
        ];
        
        return $vendors[$category][array_rand($vendors[$category])] ?? 'Unknown Vendor';
    }
}