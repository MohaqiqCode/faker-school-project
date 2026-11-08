<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class NotificationGenerator
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $userIds, array $branches, array $classes, array $sections, array $roles): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (sender_id, recipient_type, recipient_id, title, message, notification_type, channel, is_sent, sent_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        $notifCount = 0;
        $batchSize = 500;
        
        $notificationTypes = ['announcement', 'reminder', 'alert', 'info', 'emergency'];
        $channels = ['email', 'sms', 'push', 'in_app'];
        
        $templates = [
            'announcement' => [
                'School Assembly Tomorrow',
                'Parent-Teacher Meeting',
                'New Academic Year Starting',
                'Examination Schedule Published'
            ],
            'reminder' => [
                'Fee Payment Due',
                'Assignment Submission Deadline',
                'Library Books Return',
                'Medical Checkup Camp'
            ],
            'alert' => [
                'School Closed Due to Weather',
                'Emergency Meeting',
                'Schedule Change Notification',
                'Important Update'
            ],
            'info' => [
                'New Course Added',
                'Teacher Training Workshop',
                'Sports Day Announcement',
                'Cultural Event Information'
            ],
            'emergency' => [
                'Urgent Safety Drill',
                'School Closure Notice',
                'Health Advisory',
                'Security Alert'
            ]
        ];
        
        // Generate 10,000 notifications
        for ($i = 0; $i < 10000; $i++) {
            $senderId = !empty($userIds) ? $userIds[array_rand($userIds)] : null;
            $recipientType = ['all', 'branch', 'class', 'section', 'role', 'individual'][array_rand(['all', 'branch', 'class', 'section', 'role', 'individual'])];
            
            $recipientId = null;
            switch ($recipientType) {
                case 'branch':
                    $recipientId = !empty($branches) ? array_rand($branches) : null;
                    break;
                case 'class':
                    $recipientId = !empty($classes) ? $this->getRandomClassId($classes) : null;
                    break;
                case 'section':
                    $recipientId = !empty($sections) ? $this->getRandomSectionId($sections) : null;
                    break;
                case 'role':
                    $recipientId = !empty($roles) ? $roles[array_rand($roles)] : null;
                    break;
                case 'individual':
                    $recipientId = !empty($userIds) ? $userIds[array_rand($userIds)] : null;
                    break;
            }
            
            $notifType = $notificationTypes[array_rand($notificationTypes)];
            $titles = $templates[$notifType];
            $title = $titles[array_rand($titles)];
            
            $stmt->execute([
                $senderId,
                $recipientType,
                $recipientId,
                $title,
                "This is an important notification regarding {$title}.",
                $notifType,
                $channels[array_rand($channels)]
            ]);
            
            $notifCount++;
            
            if ($notifCount % $batchSize === 0) {
                $this->db->commit();
                $this->db->beginTransaction();
                echo "  Generated {$notifCount} notifications...\n";
            }
        }
        
        $this->db->commit();
        echo "Total notifications generated: {$notifCount}\n";
    }
    
    private function getRandomClassId(array $classes): ?int
    {
        $classIds = [];
        foreach ($classes as $branchClasses) {
            foreach ($branchClasses as $classId) {
                $classIds[] = $classId;
            }
        }
        return !empty($classIds) ? $classIds[array_rand($classIds)] : null;
    }
    
    private function getRandomSectionId(array $sections): ?int
    {
        $sectionIds = [];
        foreach ($sections as $classSections) {
            foreach ($classSections as $sectionId) {
                $sectionIds[] = $sectionId;
            }
        }
        return !empty($sectionIds) ? $sectionIds[array_rand($sectionIds)] : null;
    }
}