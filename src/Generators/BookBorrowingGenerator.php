<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class BookBorrowingGenerator
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $books, array $allStudents, array $allTeachers): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO book_borrowings (book_id, user_id, borrow_date, due_date, return_date, fine_amount, fine_paid, status, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $this->db->beginTransaction();
        $borrowingCount = 0;
        $batchSize = 1000;
        $totalBorrowings = 50000;
        
        // Get all student and teacher user IDs
        $studentUsers = $this->getStudentUserIds($allStudents);
        $teacherUsers = $this->getTeacherUserIds($allTeachers);
        $allUsers = array_merge($studentUsers, $teacherUsers);
        
        for ($i = 0; $i < $totalBorrowings; $i++) {
            $bookId = $books[array_rand($books)];
            $userId = $allUsers[array_rand($allUsers)];
            
            $borrowYear = rand(2016, 2026);
            $borrowDate = sprintf('%d-%02d-%02d', $borrowYear, rand(1, 12), rand(1, 28));
            $dueDate = date('Y-m-d', strtotime($borrowDate . ' +14 days'));
            
            $isReturned = rand(0, 1);
            $returnDate = $isReturned ? date('Y-m-d', strtotime($borrowDate . ' +' . rand(1, 30) . ' days')) : null;
            
            // Calculate fine if overdue
            $fineAmount = 0;
            $status = 'borrowed';
            
            if ($returnDate && strtotime($returnDate) > strtotime($dueDate)) {
                $daysLate = (strtotime($returnDate) - strtotime($dueDate)) / (60 * 60 * 24);
                $fineAmount = $daysLate * 10; // 10 AFN per day
                $status = 'returned';
            } elseif ($returnDate) {
                $status = 'returned';
            } elseif (!$returnDate && strtotime(date('Y-m-d')) > strtotime($dueDate)) {
                $status = 'overdue';
                $daysLate = (strtotime(date('Y-m-d')) - strtotime($dueDate)) / (60 * 60 * 24);
                $fineAmount = $daysLate * 10;
            }
            
            $stmt->execute([
                $bookId,
                $userId,
                $borrowDate,
                $dueDate,
                $returnDate,
                $fineAmount,
                $fineAmount > 0 ? rand(0, 1) : 0,
                $status,
                $status === 'overdue' ? 'Book is overdue' : null
            ]);
            
            $borrowingCount++;
            
            if ($borrowingCount % $batchSize === 0) {
                $this->db->commit();
                $this->db->beginTransaction();
                echo "  Generated {$borrowingCount} borrowings...\n";
            }
        }
        
        $this->db->commit();
        echo "Total borrowings generated: {$borrowingCount}\n";
    }
    
    private function getStudentUserIds(array $allStudents): array
    {
        $userIds = [];
        $stmt = $this->db->prepare("SELECT user_id FROM students WHERE id = ?");
        
        foreach ($allStudents as $year => $students) {
            foreach ($students as $studentId) {
                $stmt->execute([$studentId]);
                $result = $stmt->fetch();
                if ($result) {
                    $userIds[] = (int)$result['user_id'];
                }
            }
        }
        
        return $userIds;
    }
    
    private function getTeacherUserIds(array $allTeachers): array
    {
        $userIds = [];
        $stmt = $this->db->prepare("SELECT user_id FROM teachers WHERE id = ?");
        
        foreach ($allTeachers as $branchId => $years) {
            foreach ($years as $year => $teachers) {
                foreach ($teachers as $teacherId) {
                    $stmt->execute([$teacherId]);
                    $result = $stmt->fetch();
                    if ($result) {
                        $userIds[] = (int)$result['user_id'];
                    }
                }
            }
        }
        
        return $userIds;
    }
}