<?php

/**
 * Standalone Fee Structure and Fee Payment Generator
 * 
 * Usage: php generate_fees_only.php
 * 
 * This script generates only fee structures and fee payments
 * It fetches all required data directly from the database
 * No dependencies on other generators or the main seeder
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set unlimited execution time for large datasets
set_time_limit(0);

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Generators\FeeStructureGenerator;
use App\Generators\FeePaymentGenerator;

echo "============================================\n";
echo "FEE STRUCTURES AND PAYMENTS GENERATOR\n";
echo "============================================\n\n";

try {
    // Initialize database connection
    $db = Database::getInstance()->getConnection();
    
    // Check database connection
    echo "Checking database connection...\n";
    $db->query("SELECT 1");
    echo "Database connection successful!\n\n";
    
    // Check if required tables exist
    echo "Checking required tables...\n";
    $requiredTables = [
        'branches',
        'classes', 
        'fee_categories',
        'academic_years',
        'fee_structures',
        'students',
        'student_fees',
        'fee_payments',
        'student_promotions'
    ];
    
    $missingTables = [];
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "WARNING: The following tables are missing:\n";
        foreach ($missingTables as $table) {
            echo "  - $table\n";
        }
        echo "\n";
    } else {
        echo "All required tables exist!\n\n";
    }
    
    // Check if base data exists
    echo "Checking existing data...\n";
    $branchesCount = $db->query("SELECT COUNT(*) FROM branches")->fetchColumn();
    $classesCount = $db->query("SELECT COUNT(*) FROM classes")->fetchColumn();
    $feeCategoriesCount = $db->query("SELECT COUNT(*) FROM fee_categories")->fetchColumn();
    $academicYearsCount = $db->query("SELECT COUNT(*) FROM academic_years")->fetchColumn();
    $studentsCount = $db->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
    
    echo "Branches: $branchesCount\n";
    echo "Classes: $classesCount\n";
    echo "Fee Categories: $feeCategoriesCount\n";
    echo "Academic Years: $academicYearsCount\n";
    echo "Active Students: $studentsCount\n\n";
    
    if ($branchesCount === 0 || $classesCount === 0 || 
        $feeCategoriesCount === 0 || $academicYearsCount === 0 || 
        $studentsCount === 0) {
        echo "ERROR: Missing required base data!\n";
        echo "Please run the full data generator first to populate branches, classes, fee categories, academic years, and students.\n";
        exit(1);
    }
    
    // Ask for confirmation
    echo "============================================\n";
    echo "OPTIONS:\n";
    echo "1. Generate NEW fee structures AND payments (will clean existing data)\n";
    echo "2. Generate ONLY fee structures (keep existing payments)\n";
    echo "3. Generate ONLY fee payments from existing structures\n";
    echo "4. Exit\n";
    echo "============================================\n";
    echo "Enter your choice (1-4): ";
    
    $choice = trim(fgets(STDIN));
    
    $startTime = microtime(true);
    
    switch ($choice) {
        case '1':
            // Clean existing data
            echo "\nCleaning existing fee structures and payments...\n";
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("DELETE FROM fee_payments");
            $db->exec("DELETE FROM student_fees");
            $db->exec("DELETE FROM fee_structures");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "Cleaned successfully!\n\n";
            
            // Generate Fee Structures
            generateFeeStructures();
            
            // Generate Student Fees
            $studentFeeIds = generateStudentFees();
            
            // Generate Fee Payments
            if (!empty($studentFeeIds)) {
                generateFeePayments($studentFeeIds);
            }
            break;
            
        case '2':
            // Clean only fee structures
            echo "\nCleaning existing fee structures...\n";
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            $db->exec("DELETE FROM fee_payments");
            $db->exec("DELETE FROM student_fees");
            $db->exec("DELETE FROM fee_structures");
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "Cleaned successfully!\n\n";
            
            // Generate Fee Structures
            generateFeeStructures();
            
            // Generate Student Fees
            $studentFeeIds = generateStudentFees();
            
            echo "\nSkipping fee payments generation.\n";
            break;
            
        case '3':
            // Get existing student fees
            echo "\nFetching existing student fees...\n";
            $studentFeeIds = $db->query(
                "SELECT id FROM student_fees WHERE status IN ('pending', 'partial', 'overdue') AND net_payable > 0"
            )->fetchAll(PDO::FETCH_COLUMN);
            
            echo "Found " . count($studentFeeIds) . " student fees\n";
            
            if (empty($studentFeeIds)) {
                echo "No student fees found. Please generate fee structures first (Option 1 or 2).\n";
                exit(1);
            }
            
            // Clean only payments
            echo "Cleaning existing fee payments...\n";
            $db->exec("DELETE FROM fee_payments");
            $db->exec("UPDATE student_fees SET paid_amount = 0, status = 'pending'");
            echo "Cleaned successfully!\n\n";
            
            // Generate Fee Payments
            generateFeePayments($studentFeeIds);
            break;
            
        case '4':
            echo "Exiting...\n";
            exit(0);
            
        default:
            echo "Invalid choice. Exiting...\n";
            exit(1);
    }
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n============================================\n";
    echo "GENERATION COMPLETED SUCCESSFULLY!\n";
    echo "Total time: {$duration} seconds\n";
    echo "============================================\n";
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Generate Fee Structures
 */
function generateFeeStructures(): array
{
    echo "============================================\n";
    echo "STEP 1: GENERATING FEE STRUCTURES\n";
    echo "============================================\n\n";
    
    $db = Database::getInstance()->getConnection();
    $feeStructures = [];
    
    // Fetch branches
    $branches = $db->query("SELECT id FROM branches ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($branches) . " branches\n";
    
    // Fetch fee categories grouped by branch
    $feeCategories = [];
    $catQuery = $db->query("SELECT id, branch_id FROM fee_categories ORDER BY branch_id, id");
    while ($row = $catQuery->fetch()) {
        $feeCategories[$row['branch_id']][] = $row['id'];
    }
    echo "Fee categories loaded for " . count($feeCategories) . " branches\n";
    
    // Fetch classes grouped by branch and grade
    $classes = [];
    $classQuery = $db->query("
        SELECT id, branch_id, class_level 
        FROM classes 
        WHERE is_active = 1 
        ORDER BY branch_id, class_level
    ");
    while ($row = $classQuery->fetch()) {
        $classes[$row['branch_id']][$row['class_level']] = $row['id'];
    }
    echo "Classes loaded for " . count($classes) . " branches\n";
    
    // Fetch academic years grouped by branch and year
    $academicYears = [];
    $ayQuery = $db->query("
        SELECT id, branch_id, 
               CAST(SUBSTRING_INDEX(year_name, ' ', -1) AS UNSIGNED) as year
        FROM academic_years 
        ORDER BY branch_id, year
    ");
    while ($row = $ayQuery->fetch()) {
        // Handle cases where year extraction might fail
        $year = (int)$row['year'];
        if ($year > 2000 && $year < 2100) {
            $academicYears[$row['branch_id']][$year] = $row['id'];
        }
    }
    echo "Academic years loaded for " . count($academicYears) . " branches\n\n";
    
    // Base amounts for different fee categories
    $baseAmounts = [
        0 => 500,   // Tuition Fee
        1 => 2000,  // Admission Fee
        2 => 300,   // Library Fee
        3 => 400,   // Computer Lab Fee
        4 => 200,   // Sports Fee
        5 => 600,   // Examination Fee
        6 => 800,   // Transport Fee
        7 => 1500,  // Hostel Fee
    ];
    
    // Prepare insert statement
    $stmt = $db->prepare("
        INSERT INTO fee_structures (
            fee_category_id, 
            class_id, 
            academic_year_id, 
            amount, 
            late_fee_amount, 
            discount_allowed, 
            due_day, 
            is_active,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $db->beginTransaction();
    $totalGenerated = 0;
    $skippedBranches = [];
    
    foreach ($branches as $branchId) {
        // Check if branch has required data
        if (!isset($classes[$branchId]) || empty($classes[$branchId])) {
            $skippedBranches[] = "Branch $branchId (no classes)";
            continue;
        }
        
        if (!isset($feeCategories[$branchId]) || empty($feeCategories[$branchId])) {
            $skippedBranches[] = "Branch $branchId (no fee categories)";
            continue;
        }
        
        if (!isset($academicYears[$branchId]) || empty($academicYears[$branchId])) {
            $skippedBranches[] = "Branch $branchId (no academic years)";
            continue;
        }
        
        $expectedRecords = count($classes[$branchId]) * count($academicYears[$branchId]) * count($feeCategories[$branchId]);
        echo "Processing Branch $branchId: " . 
             count($classes[$branchId]) . " classes × " . 
             count($academicYears[$branchId]) . " academic years × " . 
             count($feeCategories[$branchId]) . " fee categories = " .
             $expectedRecords . " records\n";
        
        foreach ($classes[$branchId] as $grade => $classId) {
            foreach ($academicYears[$branchId] as $year => $academicYearId) {
                $catIndex = 0;
                foreach ($feeCategories[$branchId] as $categoryId) {
                    $baseAmount = $baseAmounts[$catIndex] ?? 500;
                    $amount = $baseAmount + ($grade * 100);
                    $lateFee = round($amount * 0.1, 2);
                    $discountAllowed = rand(0, 20);
                    
                    $stmt->execute([
                        $categoryId,
                        $classId,
                        $academicYearId,
                        $amount,
                        $lateFee,
                        $discountAllowed,
                        10
                    ]);
                    
                    $feeStructId = (int)$db->lastInsertId();
                    $feeStructures[$branchId][$categoryId][$classId][$academicYearId] = $feeStructId;
                    
                    $totalGenerated++;
                    $catIndex++;
                }
            }
        }
    }
    
    $db->commit();
    
    echo "\n=== FEE STRUCTURES SUMMARY ===\n";
    echo "Total fee structures generated: $totalGenerated\n";
    
    if (!empty($skippedBranches)) {
        echo "Skipped branches:\n";
        foreach ($skippedBranches as $skip) {
            echo "  - $skip\n";
        }
    }
    echo "\n";
    
    return $feeStructures;
}

/**
 * Generate Student Fees from Fee Structures
 */
function generateStudentFees(): array
{
    echo "============================================\n";
    echo "STEP 2: GENERATING STUDENT FEES\n";
    echo "============================================\n\n";
    
    $db = Database::getInstance()->getConnection();
    $studentFeeIds = [];
    
    // Fetch fee structures with details
    $feeStructQuery = $db->query("
        SELECT 
            fs.id as fee_structure_id,
            fs.fee_category_id,
            fs.class_id,
            fs.academic_year_id,
            fs.amount,
            fs.discount_allowed,
            fs.due_day,
            fc.frequency,
            fc.is_recurring,
            ay.branch_id
        FROM fee_structures fs
        JOIN fee_categories fc ON fs.fee_category_id = fc.id
        JOIN academic_years ay ON fs.academic_year_id = ay.id
        WHERE fs.is_active = 1
    ");
    
    $feeStructures = $feeStructQuery->fetchAll();
    echo "Found " . count($feeStructures) . " fee structures\n";
    
    if (empty($feeStructures)) {
        echo "ERROR: No fee structures found. Cannot generate student fees.\n";
        return [];
    }
    
    // Fetch active students with their current class
    $studentsQuery = $db->query("
        SELECT 
            s.id as student_id,
            s.branch_id,
            s.current_class_id,
            s.admission_date,
            s.user_id
        FROM students s
        WHERE s.is_active = 1
        AND s.current_class_id IS NOT NULL
    ");
    
    $students = $studentsQuery->fetchAll();
    echo "Found " . count($students) . " active students with class assignments\n\n";
    
    if (empty($students)) {
        echo "ERROR: No active students found. Cannot generate student fees.\n";
        return [];
    }
    
    // Prepare insert statement for student_fees
    $stmt = $db->prepare("
        INSERT INTO student_fees (
            student_id,
            fee_structure_id,
            semester_id,
            total_amount,
            discount_amount,
            net_payable,
            paid_amount,
            due_date,
            status,
            created_at
        )
        VALUES (?, ?, NULL, ?, ?, ?, 0, ?, 'pending', NOW())
    ");
    
    $db->beginTransaction();
    $totalGenerated = 0;
    $batchSize = 500;
    $skippedOneTime = 0;
    
    foreach ($students as $student) {
        foreach ($feeStructures as $feeStruct) {
            // Only assign fees that match the student's branch and class
            if ($feeStruct['branch_id'] != $student['branch_id']) {
                continue;
            }
            
            if ($feeStruct['class_id'] != $student['current_class_id']) {
                continue;
            }
            
            // Skip one-time fees for existing students (not new admissions)
            if ($feeStruct['is_recurring'] == 0) {
                // Check if student was admitted recently (within 2 months)
                $admissionDate = strtotime($student['admission_date']);
                $twoMonthsAgo = strtotime('-2 months');
                
                if ($admissionDate < $twoMonthsAgo) {
                    $skippedOneTime++;
                    continue;
                }
            }
            
            // Calculate amounts
            $totalAmount = (float)$feeStruct['amount'];
            $discountPercent = $feeStruct['discount_allowed'];
            $discountAmount = round($totalAmount * ($discountPercent / 100), 2);
            
            // Randomly apply discount (30% chance)
            if (rand(1, 100) <= 30) {
                $netPayable = $totalAmount - $discountAmount;
            } else {
                $netPayable = $totalAmount;
                $discountAmount = 0;
            }
            
            // Calculate due date based on frequency
            $dueDate = calculateDueDate(
                $feeStruct['frequency'],
                $feeStruct['due_day']
            );
            
            $stmt->execute([
                $student['student_id'],
                $feeStruct['fee_structure_id'],
                $totalAmount,
                $discountAmount,
                $netPayable,
                $dueDate
            ]);
            
            $studentFeeIds[] = (int)$db->lastInsertId();
            $totalGenerated++;
            
            if ($totalGenerated % $batchSize === 0) {
                $db->commit();
                $db->beginTransaction();
                echo "  Generated $totalGenerated student fees...\n";
            }
        }
    }
    
    $db->commit();
    
    echo "\n=== STUDENT FEES SUMMARY ===\n";
    echo "Total student fees generated: $totalGenerated\n";
    echo "Skipped one-time fees (old students): $skippedOneTime\n\n";
    
    return $studentFeeIds;
}

/**
 * Generate Fee Payments
 */
function generateFeePayments(array $studentFeeIds): void
{
    echo "============================================\n";
    echo "STEP 3: GENERATING FEE PAYMENTS\n";
    echo "============================================\n\n";
    
    if (empty($studentFeeIds)) {
        echo "ERROR: No student fees provided. Cannot generate payments.\n";
        return;
    }
    
    $db = Database::getInstance()->getConnection();
    
    $paymentMethods = ['cash', 'check', 'bank_transfer', 'credit_card', 'online', 'other'];
    $paymentCounter = 0;
    
    // Fetch student fees with details
    $stmt = $db->prepare("
        SELECT 
            sf.id,
            sf.net_payable,
            sf.due_date,
            sf.status
        FROM student_fees sf
        WHERE sf.id = ?
        AND sf.net_payable > 0
        AND sf.status NOT IN ('paid', 'waived')
    ");
    
    // Prepare payment insert
    $paymentStmt = $db->prepare("
        INSERT INTO fee_payments (
            student_fee_id,
            payment_number,
            amount,
            payment_method,
            transaction_id,
            payment_date,
            receipt_number,
            remarks,
            created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $db->beginTransaction();
    $paymentCount = 0;
    $skippedCount = 0;
    $batchSize = 500;
    
    foreach ($studentFeeIds as $studentFeeId) {
        $stmt->execute([$studentFeeId]);
        $feeInfo = $stmt->fetch();
        
        if (!$feeInfo || $feeInfo['net_payable'] <= 0) {
            $skippedCount++;
            continue;
        }
        
        $netPayable = (float)$feeInfo['net_payable'];
        $paymentPattern = getPaymentPattern();
        
        switch ($paymentPattern) {
            case 'full':
                createPaymentRecord(
                    $paymentStmt, 
                    $studentFeeId, 
                    $netPayable, 
                    $feeInfo['due_date'],
                    $paymentMethods,
                    $paymentCounter
                );
                $paymentCount++;
                break;
                
            case 'partial':
                $firstPayment = round($netPayable * 0.6, 2);
                $secondPayment = round($netPayable * 0.4, 2);
                
                createPaymentRecord(
                    $paymentStmt, 
                    $studentFeeId, 
                    $firstPayment, 
                    $feeInfo['due_date'],
                    $paymentMethods,
                    $paymentCounter
                );
                $paymentCount++;
                
                $lateDate = date('Y-m-d', strtotime($feeInfo['due_date'] . ' +15 days'));
                createPaymentRecord(
                    $paymentStmt, 
                    $studentFeeId, 
                    $secondPayment, 
                    $lateDate,
                    $paymentMethods,
                    $paymentCounter,
                    true
                );
                $paymentCount++;
                break;
                
            case 'late':
                $lateDate = date('Y-m-d', strtotime($feeInfo['due_date'] . ' +30 days'));
                createPaymentRecord(
                    $paymentStmt, 
                    $studentFeeId, 
                    $netPayable, 
                    $lateDate,
                    $paymentMethods,
                    $paymentCounter,
                    true
                );
                $paymentCount++;
                break;
                
            case 'discount':
                $discountedAmount = round($netPayable * 0.85, 2);
                createPaymentRecord(
                    $paymentStmt, 
                    $studentFeeId, 
                    $discountedAmount, 
                    $feeInfo['due_date'],
                    $paymentMethods,
                    $paymentCounter,
                    false,
                    true
                );
                $paymentCount++;
                break;
        }
        
        // Update student fee status
        updateFeeStatus($db, $studentFeeId);
        
        if ($paymentCount % $batchSize === 0) {
            $db->commit();
            $db->beginTransaction();
            echo "  Generated $paymentCount fee payments...\n";
        }
    }
    
    $db->commit();
    
    echo "\n=== FEE PAYMENTS SUMMARY ===\n";
    echo "Total payments generated: $paymentCount\n";
    echo "Skipped student fees: $skippedCount\n\n";
}

/**
 * Helper function to calculate due date
 */
function calculateDueDate(string $frequency, int $dueDay): string
{
    $currentDate = date('Y-m-d');
    $day = min($dueDay, 28); // Ensure day is valid for all months
    
    switch ($frequency) {
        case 'monthly':
            $dueDate = date('Y-m-' . sprintf('%02d', $day));
            if (strtotime($dueDate) < strtotime($currentDate)) {
                $dueDate = date('Y-m-' . sprintf('%02d', $day), strtotime('+1 month'));
            }
            break;
            
        case 'quarterly':
            $currentMonth = (int)date('n');
            $quarterMonth = ceil($currentMonth / 3) * 3;
            $dueDate = date("Y-" . sprintf('%02d', $quarterMonth) . "-" . sprintf('%02d', $day));
            break;
            
        case 'half_yearly':
            $currentMonth = (int)date('n');
            $halfYearMonth = $currentMonth <= 6 ? 6 : 12;
            $dueDate = date("Y-" . sprintf('%02d', $halfYearMonth) . "-" . sprintf('%02d', $day));
            break;
            
        case 'yearly':
            $dueDate = date('Y-' . sprintf('%02d', $day) . '-' . sprintf('%02d', $day));
            break;
            
        case 'one_time':
        default:
            $dueDate = date('Y-m-' . sprintf('%02d', $day), strtotime('+1 month'));
            break;
    }
    
    return $dueDate;
}

/**
 * Helper function to create a payment record
 */
function createPaymentRecord(
    $stmt,
    int $studentFeeId,
    float $amount,
    string $dueDate,
    array $paymentMethods,
    int &$paymentCounter,
    bool $isLate = false,
    bool $isDiscount = false
): void {
    $paymentCounter++;
    $paymentNumber = 'PAY-' . date('Ymd') . '-' . sprintf('%06d', $paymentCounter);
    $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
    $transactionId = 'TXN-' . date('YmdHis') . '-' . sprintf('%04d', rand(1000, 9999));
    $receiptNumber = 'RCP-' . date('Ymd') . '-' . sprintf('%06d', $paymentCounter);
    
    // Calculate payment date
    if ($isLate) {
        $paymentDate = date('Y-m-d H:i:s', strtotime($dueDate . ' +' . rand(5, 30) . ' days'));
    } else {
        $paymentDate = date('Y-m-d H:i:s', strtotime($dueDate . ' -' . rand(1, 5) . ' days'));
    }
    
    // Ensure payment date is not in the future
    if (strtotime($paymentDate) > time()) {
        $paymentDate = date('Y-m-d H:i:s');
    }
    
    // Determine remarks
    if ($isLate) {
        $remarks = 'Late payment';
    } elseif ($isDiscount) {
        $remarks = 'Payment with scholarship discount';
    } else {
        $remarks = 'Regular payment';
    }
    
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
}

/**
 * Helper function to determine payment pattern
 */
function getPaymentPattern(): string
{
    $rand = rand(1, 100);
    
    if ($rand <= 50) {
        return 'full';      // 50% chance
    } elseif ($rand <= 75) {
        return 'partial';   // 25% chance
    } elseif ($rand <= 88) {
        return 'late';      // 13% chance
    } else {
        return 'discount';  // 12% chance
    }
}

/**
 * Helper function to update fee status
 */
function updateFeeStatus($db, int $studentFeeId): void
{
    // Update status based on total paid amount
    $stmt = $db->prepare("
        UPDATE student_fees sf
        SET 
            paid_amount = (
                SELECT COALESCE(SUM(amount), 0) 
                FROM fee_payments 
                WHERE student_fee_id = sf.id
            ),
            status = CASE 
                WHEN (
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM fee_payments 
                    WHERE student_fee_id = sf.id
                ) >= sf.net_payable THEN 'paid'
                WHEN (
                    SELECT COALESCE(SUM(amount), 0) 
                    FROM fee_payments 
                    WHERE student_fee_id = sf.id
                ) > 0 THEN 'partial'
                WHEN sf.due_date < CURDATE() THEN 'overdue'
                ELSE 'pending'
            END,
            updated_at = NOW()
        WHERE sf.id = ?
    ");
    $stmt->execute([$studentFeeId]);
}