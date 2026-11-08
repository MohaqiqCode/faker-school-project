<?php

namespace App\Seeders;

use App\Core\Database;
use PDO;

class DatabaseSeederFast
{
    private PDO $db;
    
    // Reduced data volumes for faster generation
    private const STUDENT_GROWTH = [
        2016 => 150,  // بود 1500
        2017 => 180,  // بود 1800
        2018 => 220,  // بود 2200
        2019 => 270,  // بود 2700
        2020 => 330,  // بود 3300
        2021 => 420,  // بود 4200
        2022 => 540,  // بود 5400
        2023 => 680,  // بود 6800
        2024 => 820,  // بود 8200
        2025 => 980,  // بود 9800
        2026 => 1200  // بود 12000
    ];
    
    private const TEACHER_GROWTH = [
        2016 => 8,    // بود 80
        2017 => 11,   // بود 110
        2018 => 15,   // بود 150
        2019 => 20,   // بود 200
        2020 => 25,   // بود 250
        2021 => 30,   // بود 300
        2022 => 35,   // بود 350
        2023 => 40,   // بود 400
        2024 => 45,   // بود 450
        2025 => 48,   // بود 480
        2026 => 50    // بود 500
    ];
    
    private const EMPLOYEE_GROWTH = [
        2016 => 3,    // بود 30
        2017 => 5,    // بود 45
        2018 => 6,    // بود 60
        2019 => 8,    // بود 80
        2020 => 10,   // بود 100
        2021 => 12,   // بود 120
        2022 => 14,   // بود 140
        2023 => 17,   // بود 165
        2024 => 19,   // بود 185
        2025 => 20,   // بود 200
        2026 => 22    // بود 220
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function run(): void
    {
        $startTime = microtime(true);
        
        // Steps 1-9: Generate basic structure (fast)
        echo "[1/31] Generating Branches...\n";
        $branchGen = new \App\Generators\BranchGenerator();
        $branches = $branchGen->generate();
        
        echo "[2/31] Generating Roles...\n";
        $roleGen = new \App\Generators\RoleGenerator();
        $roles = $roleGen->generate();
        
        echo "[3/31] Generating Permissions...\n";
        $permGen = new \App\Generators\PermissionGenerator();
        $permissions = $permGen->generate();
        $permGen->assignPermissions($roles, $permissions);
        
        echo "[4/31] Generating Departments...\n";
        $deptGen = new \App\Generators\DepartmentGenerator();
        $departments = $deptGen->generate($branches);
        
        echo "[5/31] Generating Academic Years...\n";
        $ayGen = new \App\Generators\AcademicYearGenerator();
        $academicYears = $ayGen->generate($branches);
        
        echo "[6/31] Generating Semesters...\n";
        $semGen = new \App\Generators\SemesterGenerator();
        $semesters = $semGen->generate($academicYears);
        
        echo "[7/31] Generating Classes...\n";
        $classGen = new \App\Generators\ClassGenerator();
        $classes = $classGen->generate($branches);
        
        echo "[8/31] Generating Sections...\n";
        $sectionGen = new \App\Generators\SectionGenerator();
        $sections = $sectionGen->generate($classes);
        
        echo "[9/31] Generating Subjects...\n";
        $subjectGen = new \App\Generators\SubjectGenerator();
        $subjects = $subjectGen->generate($departments);
        
        // Initialize generators
        echo "[10/31] Initializing User Generator...\n";
        $userGen = new \App\Generators\UserGenerator();
        
        // Generate admin users
        echo "[11/31] Generating Admin Users...\n";
        foreach ($branches as $branchId) {
            $userGen->generateUser($branchId, $roles['admin'], 'male');
            $userGen->generateUser($branchId, $roles['principal'], 'male');
        }
        
        // Generate teachers (REDUCED)
        echo "[12/31] Generating Teachers (FAST)...\n";
        $teacherGen = new \App\Generators\TeacherGenerator($userGen);
        $allTeachers = [];
        foreach ($branches as $branchId) {
            foreach (self::TEACHER_GROWTH as $year => $total) {
                $branchCount = max(1, ceil($total / count($branches)));
                $allTeachers[$branchId][$year] = $teacherGen->generate(
                    $branchId, $roles['teacher'], $year, $branchCount, $departments[$branchId]
                );
            }
        }
        
        // Generate employees (REDUCED)
        echo "[13/31] Generating Employees (FAST)...\n";
        $empGen = new \App\Generators\EmployeeGenerator($userGen);
        $allEmployees = [];
        foreach ($branches as $branchId) {
            foreach (self::EMPLOYEE_GROWTH as $year => $total) {
                $branchCount = max(1, ceil($total / count($branches)));
                $allEmployees[$branchId][$year] = $empGen->generate(
                    $branchId, $roles['receptionist'], $year, $branchCount
                );
            }
        }
        
        // Generate class subjects
        echo "[14/31] Generating Class-Subject Mappings...\n";
        $this->generateClassSubjectsFast($classes, $subjects, $academicYears, $allTeachers);
        
        // Generate parents and students (REDUCED - THIS WAS THE SLOW PART)
        echo "[15/31] Generating Parents and Students (FAST)...\n";
        $parentGen = new \App\Generators\ParentGenerator($userGen);
        $studentGen = new \App\Generators\StudentGenerator($userGen);
        $studentParentGen = new \App\Generators\StudentParentGenerator();
        
        $allStudents = [];
        
        foreach ($branches as $branchId) {
            foreach (self::STUDENT_GROWTH as $year => $total) {
                $branchStudentCount = max(1, ceil($total / count($branches)));
                
                echo "  Branch {$branchId}, Year {$year}: Generating {$branchStudentCount} students...\n";
                
                for ($i = 0; $i < $branchStudentCount; $i++) {
                    $parentId = $parentGen->generate($branchId, $roles['parent']);
                    
                    $startYear = rand(2016, $year);
                    $grade = min(12, max(1, ($year - $startYear) + 1));
                    
                    if (isset($classes[$branchId][$grade])) {
                        $classId = $classes[$branchId][$grade];
                        $sectionKey = array_rand($sections[$classId]);
                        $sectionId = $sections[$classId][$sectionKey];
                        
                        $studentId = $studentGen->generate(
    $branchId,
    $roles['student'],
    $classId,
    $sectionId,
    $year
);

$allStudents[$year][] = [
    'student_id' => $studentId,
    'class_id'   => $classId,
    'section_id' => $sectionId
];
                        
                        $studentParentGen->linkStudentToParent($studentId, $parentId);
                    }
                }
            }
        }
        
        // Steps 16-17: Attendance (REDUCED)
        echo "[16-17] Generating Attendance (FAST - Sample only)...\n";
        $this->generateSampleAttendance($allStudents, $allTeachers);
        
        // Steps 18-20: Exams and Results (REDUCED)
        echo "[18-20] Generating Exams and Results (FAST)...\n";
        $examTypeGen = new \App\Generators\ExamTypeGenerator();
        $examTypes = $examTypeGen->generate();
        
        $examGen = new \App\Generators\ExamGenerator();
        $exams = $examGen->generate($examTypes, $academicYears, $semesters, $classes, $subjects, $allTeachers);
        
        $examResultGen = new \App\Generators\ExamResultGenerator();
        $examResultGen->generate($exams, $allStudents);
        
        // Steps 21-24: Finance (REDUCED)
        echo "[21-24] Generating Finance Data (FAST)...\n";
        $feeCatGen = new \App\Generators\FeeCategoryGenerator();
        $feeCategories = $feeCatGen->generate($branches);
        
        $feeStructGen = new \App\Generators\FeeStructureGenerator();
        $feeStructures = $feeStructGen->generate($feeCategories, $classes, $academicYears);
        
        $studentFeeGen = new \App\Generators\StudentFeeGenerator();
        $studentFees = $studentFeeGen->generate($allStudents, $feeStructures, $semesters);
        
        $feePaymentGen = new \App\Generators\FeePaymentGenerator();
        $feePaymentGen->generate($studentFees);
        
        echo "[25/31] Generating Expenses...\n";
        $expenseGen = new \App\Generators\ExpenseGenerator();
        $expenseGen->generate($branches);
        
        // Steps 26-27: Payroll
        echo "[26-27] Generating Payroll...\n";
        $salaryStructGen = new \App\Generators\SalaryStructureGenerator();
        $salaryStructures = $salaryStructGen->generate($branches);
        
        $payrollGen = new \App\Generators\PayrollGenerator();
        $payrollGen->generate($allTeachers, $allEmployees, $salaryStructures);
        
        // Steps 28-29: Library (REDUCED)
        echo "[28-29] Generating Library Data (FAST)...\n";
        $libraryGen = new \App\Generators\LibraryGenerator();
        $books = $libraryGen->generate($branches);
        
        $borrowingGen = new \App\Generators\BookBorrowingGenerator();
        $borrowingGen->generate($books, $allStudents, $allTeachers);
        
        // Step 30: Transport
        echo "[30/31] Generating Transport System...\n";
        $vehicleGen = new \App\Generators\VehicleGenerator();
        $vehicles = $vehicleGen->generate($branches);
        
        $driverGen = new \App\Generators\DriverGenerator();
        $drivers = $driverGen->generate($allEmployees, $vehicles);
        
        $routeGen = new \App\Generators\RouteGenerator();
        $routes = $routeGen->generate($branches, $vehicles, $drivers);
        
        $transportGen = new \App\Generators\StudentTransportGenerator();
        $transportGen->generate($allStudents, $routes);
        
        // Step 31: Remaining data
        echo "[31/31] Generating Inventory, Notifications, and Logs...\n";
        $invCatGen = new \App\Generators\InventoryCategoryGenerator();
        $invCategories = $invCatGen->generate($branches);
        
        $invItemGen = new \App\Generators\InventoryItemGenerator();
        $invItemGen->generate($invCategories, $branches);
        
        // Generate fewer notifications and logs
        echo "  Generating sample notifications and logs...\n";
        // Skip or reduce notification, audit, and system logs
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "\n============================================\n";
        echo "FAST DATA GENERATION COMPLETED!\n";
        echo "Total time: {$duration} seconds\n";
        echo "============================================\n";
    }
    
    private function generateClassSubjectsFast($classes, $subjects, $academicYears, $allTeachers): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO class_subjects (class_id, subject_id, academic_year_id, teacher_id, periods_per_week, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        $count = 0;
        foreach ($classes as $branchId => $grades) {
            foreach ($grades as $grade => $classId) {
                $curriculum = \App\Generators\SubjectGenerator::CURRICULUM[$grade] ?? [];
                
                foreach ($curriculum as $subjectName) {
                    $subjectKey = "{$branchId}_{$subjectName}";
                    if (!isset($subjects[$subjectKey])) continue;
                    
                    $subjectId = $subjects[$subjectKey];
                    
                    if (isset($academicYears[$branchId])) {
                        foreach ($academicYears[$branchId] as $year => $academicYearId) {
                            $teacherId = null;
                            if (isset($allTeachers[$branchId][$year]) && !empty($allTeachers[$branchId][$year])) {
                                $teacherId = $allTeachers[$branchId][$year][array_rand($allTeachers[$branchId][$year])];
                            }
                            
                            $stmt->execute([$classId, $subjectId, $academicYearId, $teacherId, rand(2, 5)]);
                            $count++;
                        }
                    }
                }
            }
        }
        echo "  Generated {$count} class-subject mappings\n";
    }
    
 private function generateSampleAttendance($allStudents, $allTeachers): void
{
    $sampleSize = 10000;

    echo "  Generating {$sampleSize} sample attendance records...\n";

    $stmt = $this->db->prepare("
        INSERT IGNORE INTO student_attendance
        (
            student_id,
            class_id,
            section_id,
            attendance_date,
            status,
            check_in_time,
            check_out_time,
            remarks,
            marked_by
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $students = [];

    foreach ($allStudents as $yearStudents) {
        foreach ($yearStudents as $student) {
            $students[] = $student;
        }
    }

    if (empty($students)) {
        echo "  No students found!\n";
        return;
    }

    $usedKeys = [];

    $generated = 0;
    $attempts = 0;
    $maxAttempts = $sampleSize * 10;

    $statuses = [
        'present',
        'present',
        'present',
        'present',
        'late',
        'absent',
        'excused'
    ];

    while ($generated < $sampleSize && $attempts < $maxAttempts) {

        $attempts++;

        $student = $students[array_rand($students)];

        $studentId = $student['student_id'];
        $classId   = $student['class_id'];
        $sectionId = $student['section_id'];

        $attendanceDate = date(
            'Y-m-d',
            strtotime('-' . rand(1, 365) . ' days')
        );

        $uniqueKey = $studentId . '_' . $attendanceDate;

        if (isset($usedKeys[$uniqueKey])) {
            continue;
        }

        $usedKeys[$uniqueKey] = true;

        $status = $statuses[array_rand($statuses)];

        $checkIn = null;
        $checkOut = null;

        if ($status === 'present') {

            $checkIn = sprintf(
                '%02d:%02d:00',
                rand(7, 8),
                rand(0, 59)
            );

            $checkOut = sprintf(
                '%02d:%02d:00',
                rand(13, 15),
                rand(0, 59)
            );

        } elseif ($status === 'late') {

            $checkIn = sprintf(
                '%02d:%02d:00',
                rand(8, 10),
                rand(0, 59)
            );

            $checkOut = sprintf(
                '%02d:%02d:00',
                rand(13, 15),
                rand(0, 59)
            );
        }

        $remarks = null;

        if ($status === 'absent') {
            $remarks = 'Absent';
        } elseif ($status === 'excused') {
            $remarks = 'Excused absence';
        } elseif ($status === 'late') {
            $remarks = 'Late arrival';
        }

        try {

            $stmt->execute([
                $studentId,
                $classId,
                $sectionId,
                $attendanceDate,
                $status,
                $checkIn,
                $checkOut,
                $remarks,
                null
            ]);

            $generated++;

        } catch (\PDOException $e) {

            continue;
        }
    }

    echo "  Generated {$generated} attendance records\n";
}
}