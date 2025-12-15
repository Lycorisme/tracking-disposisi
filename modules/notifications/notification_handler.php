<?php
// modules/notifications/notification_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/notification_service.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_recent':
            $notifications = NotificationService::getRecent($user['id']);
            $unreadCount = NotificationService::countUnread($user['id']);
            
            echo json_encode([
                'status' => 'success',
                'notifications' => $notifications,
                'unread_count' => $unreadCount
            ]);
            break;
            
        case 'mark_read':
            $notifId = (int)$_POST['id'];
            NotificationService::markAsRead($notifId, $user['id']);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Notifikasi ditandai sudah dibaca'
            ]);
            break;
            
        case 'mark_all_read':
            NotificationService::markAllAsRead($user['id']);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Semua notifikasi ditandai sudah dibaca'
            ]);
            break;
            
        case 'count_unread':
            $count = NotificationService::countUnread($user['id']);
            
            echo json_encode([
                'status' => 'success',
                'count' => $count
            ]);
            break;
            
        case 'count_active':
            // Count active surat yang user terlibat (untuk badge sidebar)
            $count = NotificationService::countActiveNotifications($user['id']);
            
            echo json_encode([
                'status' => 'success',
                'count' => $count
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}