<?php

namespace App\Seeders;

use App\Core\Database;
use PDO;

class DatabaseSeeder
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function run(): void
    {
        echo "============================================\n";
        echo "STARTING DATA GENERATION PROCESS\n";
        echo "============================================\n\n";
        
        $startTime = microtime(true);
        
        // Step 1: Generate Branches
        echo "[1/31] Generating Branches...\n";
        $branchGen = new \App\Generators\BranchGenerator();
        $branches = $branchGen->generate();
        echo "Generated " . count($branches) . " branches\n\n";
        
        // Step 2: Generate Roles
        echo "[2/31] Generating Roles...\n";
        $roleGen = new \App\Generators\RoleGenerator();
        $roles = $roleGen->generate();
        echo "Generated " . count($roles) . " roles\n\n";
        
        // Step 3: Generate Permissions
        echo "[3/31] Generating Permissions...\n";
        $permGen = new \App\Generators\PermissionGenerator();
        $permissions = $permGen->generate();
        $permGen->assignPermissions($roles, $permissions);
        echo "Generated " . count($permissions) . " permissions\n\n";
        
        // Step 4: Generate Departments
        echo "[4/31] Generating Departments...\n";
        $deptGen = new \App\Generators\DepartmentGenerator();
        $departments = $deptGen->generate($branches);
        echo "Generated departments for all branches\n\n";
        
        // Step 5: Generate Academic Years
        echo "[5/31] Generating Academic Years...\n";
        $ayGen = new \App\Generators\AcademicYearGenerator();
        $academicYears = $ayGen->generate($branches);
        echo "Generated academic years\n\n";
        
        // Step 6: Generate Semesters
        echo "[6/31] Generating Semesters...\n";
        $semGen = new \App\Generators\SemesterGenerator();
        $semesters = $semGen->generate($academicYears);
        echo "Generated semesters\n\n";
        
        // Step 7: Generate Classes
        echo "[7/31] Generating Classes...\n";
        $classGen = new \App\Generators\ClassGenerator();
        $classes = $classGen->generate($branches);
        echo "Generated classes for all branches\n\n";
        
        // Step 8: Generate Sections
        echo "[8/31] Generating Sections...\n";
        $sectionGen = new \App\Generators\SectionGenerator();
        $sections = $sectionGen->generate($classes);
        echo "Generated sections\n\n";
        
        // Step 9: Generate Subjects
        echo "[9/31] Generating Subjects...\n";
        $subjectGen = new \App\Generators\SubjectGenerator();
        $subjects = $subjectGen->generate($departments);
        echo "Generated " . count($subjects) . " subjects\n\n";
        
        // Initialize User Generator
        echo "[10/31] Initializing User Generator...\n";
        $userGen = new \App\Generators\UserGenerator();
        
        // Step 11: Generate Admin Users
        echo "[11/31] Generating Admin Users...\n";
        foreach ($branches as $branchId) {
            $userGen->generateUser($branchId, $roles['admin'], 'male');
            $userGen->generateUser($branchId, $roles['principal'], 'male');
            $userGen->generateUser($branchId, $roles['vice_principal'], rand(0, 1) ? 'male' : 'female');
        }
        
        // Step 12: Generate Teachers
        echo "[12/31] Generating Teachers...\n";
        $teacherGen = new \App\Generators\TeacherGenerator($userGen);
        
        $teacherGrowth = [
            2016 => 80, 2017 => 110, 2018 => 150, 2019 => 200,
            2020 => 250, 2021 => 300, 2022 => 350, 2023 => 400,
            2024 => 450, 2025 => 480, 2026 => 500
        ];
        
        $allTeachers = [];
        foreach ($branches as $branchId) {
            foreach ($teacherGrowth as $year => $total) {
                $branchCount = ceil($total / count($branches));
                $allTeachers[$branchId][$year] = $teacherGen->generate(
                    $branchId, $roles['teacher'], $year, $branchCount, $departments[$branchId]
                );
            }
        }
        
        // Step 13: Generate Employees
        echo "[13/31] Generating Employees...\n";
        $empGen = new \App\Generators\EmployeeGenerator($userGen);
        
        $empGrowth = [
            2016 => 30, 2017 => 45, 2018 => 60, 2019 => 80,
            2020 => 100, 2021 => 120, 2022 => 140, 2023 => 165,
            2024 => 185, 2025 => 200, 2026 => 220
        ];
        
        $allEmployees = [];
        foreach ($branches as $branchId) {
            foreach ($empGrowth as $year => $total) {
                $branchCount = ceil($total / count($branches));
                $allEmployees[$branchId][$year] = $empGen->generate(
                    $branchId, $roles['receptionist'], $year, $branchCount
                );
            }
        }
        
        // Step 14: Generate Class-Subject Mappings
        echo "[14/31] Generating Class-Subject Mappings...\n";
        $this->generateClassSubjects($classes, $subjects, $academicYears, $allTeachers);
        
        // Step 15: Generate Parents and Students
        echo "[15/31] Generating Parents and Students...\n";
        $parentGen = new \App\Generators\ParentGenerator($userGen);
        $studentGen = new \App\Generators\StudentGenerator($userGen);
        $studentParentGen = new \App\Generators\StudentParentGenerator();
        
        $studentGrowth = [
            2016 => 1500, 2017 => 1800, 2018 => 2200, 2019 => 2700,
            2020 => 3300, 2021 => 4200, 2022 => 5400, 2023 => 6800,
            2024 => 8200, 2025 => 9800, 2026 => 12000
        ];
        
        $allStudents = [];
        
        foreach ($branches as $branchId) {
            foreach ($studentGrowth as $year => $total) {
                $branchStudentCount = ceil($total / count($branches));
                
                for ($i = 0; $i < $branchStudentCount; $i++) {
                    $parentId = $parentGen->generate($branchId, $roles['parent']);
                    
                    $startYear = rand(2016, $year);
                    $grade = min(12, max(1, ($year - $startYear) + 1));
                    
                    if (isset($classes[$branchId][$grade])) {
                        $classId = $classes[$branchId][$grade];
                        $sectionKey = array_rand($sections[$classId]);
                        $sectionId = $sections[$classId][$sectionKey];
                        
                        $studentId = $studentGen->generate($branchId, $roles['student'], $classId, $sectionId, $year);
                        $allStudents[$year][] = $studentId;
                        
                        $studentParentGen->linkStudentToParent($studentId, $parentId);
                    }
                }
            }
        }
        
        // Step 16: Generate Student Attendance
        echo "[16/31] Generating Student Attendance...\n";
        $attendanceGen = new \App\Generators\AttendanceGenerator();
        $attendanceGen->generate($allStudents, $classes, $sections);
        
        // Step 17: Generate Teacher Attendance
        echo "[17/31] Generating Teacher Attendance...\n";
        $attendanceGen->generateTeacherAttendance($allTeachers);
        
        // Step 18: Generate Exam Types
        echo "[18/31] Generating Exam Types...\n";
        $examTypeGen = new \App\Generators\ExamTypeGenerator();
        $examTypes = $examTypeGen->generate();
        
        // Step 19: Generate Exams
        echo "[19/31] Generating Exams...\n";
        $examGen = new \App\Generators\ExamGenerator();
        $exams = $examGen->generate($examTypes, $academicYears, $semesters, $classes, $subjects, $allTeachers);
        
        // Step 20: Generate Exam Results
        echo "[20/31] Generating Exam Results...\n";
        $examResultGen = new \App\Generators\ExamResultGenerator();
        $examResultGen->generate($exams, $allStudents);
        
        // Step 21: Generate Fee Categories
        echo "[21/31] Generating Fee Categories...\n";
        $feeCatGen = new \App\Generators\FeeCategoryGenerator();
        $feeCategories = $feeCatGen->generate($branches);
        
        // Step 22: Generate Fee Structures
        echo "[22/31] Generating Fee Structures...\n";
        $feeStructGen = new \App\Generators\FeeStructureGenerator();
        $feeStructures = $feeStructGen->generate($feeCategories, $classes, $academicYears);
        
        // Step 23: Generate Student Fees
        echo "[23/31] Generating Student Fees...\n";
        $studentFeeGen = new \App\Generators\StudentFeeGenerator();
        $studentFees = $studentFeeGen->generate($allStudents, $feeStructures, $semesters);
        
        // Step 24: Generate Fee Payments
        echo "[24/31] Generating Fee Payments...\n";
        $feePaymentGen = new \App\Generators\FeePaymentGenerator();
        $feePaymentGen->generate($studentFees);
        
        // Step 25: Generate Expenses
        echo "[25/31] Generating Expenses...\n";
        $expenseGen = new \App\Generators\ExpenseGenerator();
        $expenseGen->generate($branches);
        
        // Step 26: Generate Salary Structures
        echo "[26/31] Generating Salary Structures...\n";
        $salaryStructGen = new \App\Generators\SalaryStructureGenerator();
        $salaryStructures = $salaryStructGen->generate($branches);
        
        // Step 27: Generate Payroll
        echo "[27/31] Generating Payroll Records...\n";
        $payrollGen = new \App\Generators\PayrollGenerator();
        $payrollGen->generate($allTeachers, $allEmployees, $salaryStructures);
        
        // Step 28: Generate Library Books
        echo "[28/31] Generating Library Books...\n";
        $libraryGen = new \App\Generators\LibraryGenerator();
        $books = $libraryGen->generate($branches);
        
        // Step 29: Generate Book Borrowings
        echo "[29/31] Generating Book Borrowings...\n";
        $borrowingGen = new \App\Generators\BookBorrowingGenerator();
        $borrowingGen->generate($books, $allStudents, $allTeachers);
        
        // Step 30: Generate Transport
        echo "[30/31] Generating Transport System...\n";
        $vehicleGen = new \App\Generators\VehicleGenerator();
        $vehicles = $vehicleGen->generate($branches);
        
        $driverGen = new \App\Generators\DriverGenerator();
        $drivers = $driverGen->generate($allEmployees, $vehicles);
        
        $routeGen = new \App\Generators\RouteGenerator();
        $routes = $routeGen->generate($branches, $vehicles, $drivers);
        
        $transportGen = new \App\Generators\StudentTransportGenerator();
        $transportGen->generate($allStudents, $routes);
        
        // Step 31: Generate Remaining Data
        echo "[31/31] Generating Inventory, Notifications, and Logs...\n";
        $invCatGen = new \App\Generators\InventoryCategoryGenerator();
        $invCategories = $invCatGen->generate($branches);
        
        $invItemGen = new \App\Generators\InventoryItemGenerator();
        $invItemGen->generate($invCategories, $branches);
        
        $notifGen = new \App\Generators\NotificationGenerator();
        $notifGen->generate($userGen->getUsers(), $branches, $classes, $sections, $roles);
        
        $auditGen = new \App\Generators\AuditLogGenerator();
        $auditGen->generate($userGen->getUsers());
        
        $sysLogGen = new \App\Generators\SystemLogGenerator();
        $sysLogGen->generate();
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "\n============================================\n";
        echo "DATA GENERATION COMPLETED SUCCESSFULLY!\n";
        echo "Total time: {$duration} seconds\n";
        echo "============================================\n";
    }
    
    private function generateClassSubjects(array $classes, array $subjects, array $academicYears, array $allTeachers): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO class_subjects (class_id, subject_id, academic_year_id, teacher_id, periods_per_week, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        $this->db->beginTransaction();
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
                            
                            $stmt->execute([
                                $classId,
                                $subjectId,
                                $academicYearId,
                                $teacherId,
                                rand(2, 5)
                            ]);
                            
                            $count++;
                        }
                    }
                }
            }
        }
        
        $this->db->commit();
        echo "  Generated {$count} class-subject mappings\n";
    }
}