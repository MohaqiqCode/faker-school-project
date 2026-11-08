<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class AttendanceGenerator
{
    private PDO $db;
    
    private const STATUS_DISTRIBUTION = [
        'present' => 92,
        'late' => 3,
        'absent' => 4,
        'excused' => 1
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $allStudents, array $classes, array $sections): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO student_attendance (student_id, class_id, section_id, attendance_date, status, check_in_time, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        
        $totalRecords = 0;
        $batchSize = 1000;
        $this->db->beginTransaction();
        
        foreach ($allStudents as $year => $students) {
            foreach ($students as $studentId) {
                // Get student's class and section
                $studentInfo = $this->getStudentInfo($studentId);
                if (!$studentInfo) {
                    continue;
                }
                
                // Check if we have class_id and section_id
                if (empty($studentInfo['current_class_id']) || empty($studentInfo['current_section_id'])) {
                    continue;
                }
                
                // Generate attendance for each school day
                $startDate = "{$year}-03-21";
                $endDate = ($year + 1) . "-03-20";
                
                $currentDate = new \DateTime($startDate);
                $endDateTime = new \DateTime($endDate);
                
                while ($currentDate <= $endDateTime) {
                    // Skip Fridays (5 = Friday in ISO-8601)
                    if ($currentDate->format('N') == 5) {
                        $currentDate->modify('+1 day');
                        continue;
                    }
                    
                    // Skip Saturdays and Sundays
                    if ($currentDate->format('N') >= 6) {
                        $currentDate->modify('+1 day');
                        continue;
                    }
                    
                    // Random holiday (10% chance)
                    if (rand(1, 100) <= 10) {
                        $currentDate->modify('+1 day');
                        continue;
                    }
                    
                    $status = $this->getRandomStatus();
                    $checkInTime = ($status === 'present' || $status === 'late') ? 
                        sprintf('%02d:%02d:%02d', rand(7, 9), rand(0, 59), rand(0, 59)) : null;
                    
                    $remarks = null;
                    if ($status === 'late') {
                        $remarks = 'Arrived late';
                    } elseif ($status === 'absent') {
                        $remarks = 'Absent without notice';
                    } elseif ($status === 'excused') {
                        $remarks = 'Excused absence';
                    }
                    
                    try {
                        $stmt->execute([
                            $studentId,
                            $studentInfo['current_class_id'],
                            $studentInfo['current_section_id'],
                            $currentDate->format('Y-m-d'),
                            $status,
                            $checkInTime,
                            $remarks
                        ]);
                        
                        $totalRecords++;
                        
                        if ($totalRecords % $batchSize === 0) {
                            $this->db->commit();
                            $this->db->beginTransaction();
                            echo "  Generated {$totalRecords} attendance records...\n";
                        }
                    } catch (\PDOException $e) {
                        // Skip duplicate entries and continue
                        if ($e->getCode() != 23000) {
                            throw $e;
                        }
                    }
                    
                    $currentDate->modify('+1 day');
                }
            }
        }
        
        $this->db->commit();
        echo "Total student attendance records generated: {$totalRecords}\n";
    }
    
    private function getRandomStatus(): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach (self::STATUS_DISTRIBUTION as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }
        
        return 'present';
    }
    
    private function getStudentInfo(int $studentId): ?array
    {
        // FIXED: Changed current_class_id to class_id and current_section_id to section_id
        $stmt = $this->db->prepare("
            SELECT current_class_id, current_section_id
            FROM students 
            WHERE id = ?
        ");
        $stmt->execute([$studentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }
    
    public function generateTeacherAttendance(array $allTeachers): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO teacher_attendance (teacher_id, attendance_date, status, check_in_time, remarks)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        
        $this->db->beginTransaction();
        $count = 0;
        $batchSize = 500;
        
        foreach ($allTeachers as $branchId => $years) {
            foreach ($years as $year => $teachers) {
                foreach ($teachers as $teacherId) {
                    $startDate = new \DateTime("{$year}-03-21");
                    $endDate = new \DateTime(($year + 1) . "-03-20");
                    
                    while ($startDate <= $endDate) {
                        // Skip Fridays and weekends
                        if ($startDate->format('N') >= 5) {
                            $startDate->modify('+1 day');
                            continue;
                        }
                        
                        // Random holiday (10% chance)
                        if (rand(1, 100) <= 10) {
                            $startDate->modify('+1 day');
                            continue;
                        }
                        
                        $status = $this->getRandomStatus();
                        $checkInTime = ($status === 'present' || $status === 'late') ? 
                            sprintf('%02d:%02d:%02d', rand(7, 9), rand(0, 59), rand(0, 59)) : null;
                        
                        $remarks = null;
                        if ($status === 'late') {
                            $remarks = 'Arrived late';
                        } elseif ($status === 'absent') {
                            $remarks = 'Absent without notice';
                        } elseif ($status === 'excused') {
                            $remarks = 'Excused absence';
                        }
                        
                        try {
                            $stmt->execute([
                                $teacherId,
                                $startDate->format('Y-m-d'),
                                $status,
                                $checkInTime,
                                $remarks
                            ]);
                            
                            $count++;
                            
                            if ($count % $batchSize === 0) {
                                $this->db->commit();
                                $this->db->beginTransaction();
                                echo "  Generated {$count} teacher attendance records...\n";
                            }
                        } catch (\PDOException $e) {
                            // Skip duplicate entries and continue
                            if ($e->getCode() != 23000) {
                                throw $e;
                            }
                        }
                        
                        $startDate->modify('+1 day');
                    }
                }
            }
        }
        
        $this->db->commit();
        echo "Total teacher attendance records generated: {$count}\n";
    }
}