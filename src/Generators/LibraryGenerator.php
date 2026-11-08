<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class LibraryGenerator
{
    private PDO $db;
    private array $books = [];
    
    private const BOOK_CATEGORIES = [
        'Textbooks', 'Reference Books', 'Fiction', 'Non-Fiction',
        'Science', 'History', 'Religion', 'Literature', 'Poetry',
        'Children Books', 'Educational', 'Magazines'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO library_books (branch_id, isbn, book_code, title, author, publisher, edition, category, subject, publication_year, total_copies, available_copies, shelf_location, purchase_date, purchase_price, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $bookCount = 0;
        $totalBooks = 10000;
        
        foreach ($branchIds as $branchId) {
            $branchBooks = ceil($totalBooks / count($branchIds));
            
            for ($i = 0; $i < $branchBooks; $i++) {
                $bookCode = 'BK' . sprintf('%08d', $bookCount + 1);
                $title = $this->getRandomBookTitle();
                $author = $this->getRandomAuthor();
                $publisher = $this->getRandomPublisher();
                $category = self::BOOK_CATEGORIES[array_rand(self::BOOK_CATEGORIES)];
                
                $totalCopies = rand(1, 10);
                $availableCopies = rand(0, $totalCopies);
                
                $stmt->execute([
                    $branchId,
                    '978' . rand(1000000000, 9999999999),
                    $bookCode,
                    $title,
                    $author,
                    $publisher,
                    rand(1, 5) . 'th Edition',
                    $category,
                    $category,
                    rand(2000, 2025),
                    $totalCopies,
                    $availableCopies,
                    'SHELF-' . strtoupper(substr($category, 0, 1)) . rand(1, 20),
                    sprintf('%d-%02d-%02d', rand(2016, 2025), rand(1, 12), rand(1, 28)),
                    rand(200, 3000)
                ]);
                
                $bookId = (int)$this->db->lastInsertId();
                $this->books[] = $bookId;
                $bookCount++;
                
                if ($bookCount % 500 === 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                    echo "  Generated {$bookCount} books...\n";
                }
            }
        }
        
        $this->db->commit();
        echo "Total books generated: {$bookCount}\n";
        
        return $this->books;
    }
    
    private function getRandomBookTitle(): string
    {
        $titles = [
            'Mathematics for Grade 10', 'Physics Principles', 'Chemistry Basics',
            'Biology Advanced', 'Dari Literature', 'Pashto Grammar',
            'English Composition', 'Islamic Studies', 'Afghan History',
            'World Geography', 'Computer Science Fundamentals', 'Poetry Collection',
            'Science Encyclopedia', 'Social Studies Guide', 'Art and Drawing',
            'Handwriting Practice', 'General Knowledge', 'Moral Education',
            'Environmental Science', 'Physical Education Guide'
        ];
        
        return $titles[array_rand($titles)];
    }
    
    private function getRandomAuthor(): string
    {
        $authors = [
            'Dr. Ahmad Rahimi', 'Prof. Mohammad Karimi', 'Fatima Ahmadi',
            'Abdul Basir Noori', 'Hamid Hussaini', 'Dr. Nilab Wardak',
            'Prof. Samiullah Hakimi', 'Maryam Safi', 'Jawad Aryan',
            'Roya Mohammadi'
        ];
        
        return $authors[array_rand($authors)];
    }
    
    private function getRandomPublisher(): string
    {
        $publishers = [
            'Kabul University Press', 'Afghan Education Ministry',
            'Bamyan Publications', 'Ghazni Book House', 'Herat Publishers',
            'Afghan Academic Press', 'Kabul Book Center'
        ];
        
        return $publishers[array_rand($publishers)];
    }
    
    public function getBooks(): array
    {
        return $this->books;
    }
}