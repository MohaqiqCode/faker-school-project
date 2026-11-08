<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class FeePaymentGenerator
{
    private PDO $db;
    private int $paymentCounter = 0;
    
    private const PAYMENT_METHODS = ['cash', 'bank_transfer', 'online', 'check'];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $studentFees): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO fee_payments (student_fee_id, payment_number, amount, payment_method, transaction_id, payment_date, receipt_number, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $this->db->beginTransaction();
        $paymentCount = 0;
        $batchSize = 1000;
        
        foreach ($studentFees as $studentFeeId) {
            $feeInfo = $this->getFeeInfo($studentFeeId);
            if (!$feeInfo) continue;
            
            $netPayable = (float)$feeInfo['net_payable'];
            
            // Determine payment pattern
            $paymentPattern = $this->getPaymentPattern();
            
            switch ($paymentPattern) {
                case 'full':
                    $this->createPayment($stmt, $studentFeeId, $netPayable, $feeInfo['due_date']);
                    $paymentCount++;
                    break;
                    
                case 'partial':
                    // Two installments
                    $firstPayment = $netPayable * 0.6;
                    $secondPayment = $netPayable * 0.4;
                    
                    $this->createPayment($stmt, $studentFeeId, $firstPayment, $feeInfo['due_date']);
                    $paymentCount++;
                    
                    $lateDate = date('Y-m-d', strtotime($feeInfo['due_date'] . ' +15 days'));
                    $this->createPayment($stmt, $studentFeeId, $secondPayment, $lateDate, true);
                    $paymentCount++;
                    break;
                    
                case 'late':
                    $lateDate = date('Y-m-d', strtotime($feeInfo['due_date'] . ' +30 days'));
                    $this->createPayment($stmt, $studentFeeId, $netPayable, $lateDate, true);
                    $paymentCount++;
                    break;
                    
                case 'discount':
                    $discountedAmount = $netPayable * 0.85; // 15% discount
                    $this->createPayment($stmt, $studentFeeId, $discountedAmount, $feeInfo['due_date'], false, true);
                    $paymentCount++;
                    break;
            }
            
            if ($paymentCount % $batchSize === 0) {
                $this->db->commit();
                $this->db->beginTransaction();
                echo "  Generated {$paymentCount} fee payments...\n";
            }
        }
        
        $this->db->commit();
        echo "Total fee payments generated: {$paymentCount}\n";
    }
    
    private function createPayment($stmt, int $studentFeeId, float $amount, string $dueDate, bool $isLate = false, bool $isDiscount = false): void
    {
        $this->paymentCounter++;
        $paymentNumber = 'PAY' . sprintf('%010d', $this->paymentCounter);
        $paymentMethod = self::PAYMENT_METHODS[array_rand(self::PAYMENT_METHODS)];
        $transactionId = 'TXN' . sprintf('%015d', rand(100000000000000, 999999999999999));
        $receiptNumber = 'RCP' . sprintf('%010d', $this->paymentCounter);
        
        $paymentDate = $isLate ? 
            date('Y-m-d', strtotime($dueDate . ' +' . rand(5, 30) . ' days')) :
            date('Y-m-d', strtotime($dueDate . ' -' . rand(1, 5) . ' days'));
        
        $remarks = '';
        if ($isLate) $remarks = 'Late payment';
        if ($isDiscount) $remarks = 'Payment with scholarship discount';
        
        $stmt->execute([
            $studentFeeId,
            $paymentNumber,
            $amount,
            $paymentMethod,
            $transactionId,
            $paymentDate,
            $receiptNumber,
            $remarks
        ]);
        
        // Update student fee status
        $this->updateFeeStatus($studentFeeId);
    }
    
    private function getPaymentPattern(): string
    {
        $patterns = ['full', 'full', 'full', 'partial', 'partial', 'late', 'discount'];
        return $patterns[array_rand($patterns)];
    }
    
    private function getFeeInfo(int $studentFeeId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT net_payable, due_date, status 
            FROM student_fees 
            WHERE id = ?
        ");
        $stmt->execute([$studentFeeId]);
        return $stmt->fetch() ?: null;
    }
    
    private function updateFeeStatus(int $studentFeeId): void
    {
        $stmt = $this->db->prepare("
            UPDATE student_fees sf
            SET status = CASE 
                WHEN sf.paid_amount >= sf.net_payable THEN 'paid'
                WHEN sf.paid_amount > 0 THEN 'partial'
                WHEN sf.due_date < CURDATE() THEN 'overdue'
                ELSE 'pending'
            END
            WHERE sf.id = ?
        ");
        $stmt->execute([$studentFeeId]);
    }
}