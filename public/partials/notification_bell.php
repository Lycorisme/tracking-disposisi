<?php
/**
 * Notification Bell Component
 * File: public/partials/notification_bell.php
 * 
 * Component ini ditambahkan di header.php untuk menampilkan:
 * - Icon lonceng dengan badge unread count
 * - Modal notifikasi di tengah layar
 * - Max 5 notifikasi dengan scroll
 */

// Load notification service
require_once __DIR__ . '/../../modules/notifications/notification_service.php';

$unreadCount = NotificationService::countUnread($currentUser['id']);
?>

<!-- Notification Bell Icon -->
<div class="relative">
    <button onclick="toggleNotificationModal()" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none rounded-full hover:bg-gray-100 transition-colors">
        <i class="fas fa-bell text-xl"></i>
        
        <!-- Badge Unread Count -->
        <span id="notif-badge" class="<?= $unreadCount > 0 ? '' : 'hidden' ?> absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center animate-pulse">
            <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
        </span>
    </button>
</div>

<!-- Notification Modal (Center Screen) -->
<div id="notification-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[60] flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[600px] flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 bg-gradient-to-r from-primary-50 to-white">
            <div class="flex items-center gap-2">
                <i class="fas fa-bell text-primary-600"></i>
                <h3 class="font-bold text-gray-800">Notifikasi</h3>
                <span id="notif-count-text" class="text-xs text-gray-500">
                    (<span id="notif-unread-number"><?= $unreadCount ?></span> belum dibaca)
                </span>
            </div>
            <button onclick="closeNotificationModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Body (Scrollable) -->
        <div id="notification-list" class="flex-1 overflow-y-auto p-4 space-y-2">
            <!-- Loading State -->
            <div id="notif-loading" class="flex flex-col items-center justify-center py-8 text-gray-400">
                <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                <p class="text-sm">Memuat notifikasi...</p>
            </div>
            
            <!-- Empty State -->
            <div id="notif-empty" class="hidden flex flex-col items-center justify-center py-8 text-gray-400">
                <i class="fas fa-bell-slash text-4xl mb-2"></i>
                <p class="text-sm">Tidak ada notifikasi</p>
            </div>
            
            <!-- Notifications will be loaded here via AJAX -->
        </div>
        
        <!-- Modal Footer -->
        <div class="border-t border-gray-200 p-3 bg-gray-50 rounded-b-xl">
            <button onclick="markAllAsRead()" class="w-full text-sm text-primary-600 hover:text-primary-800 font-medium py-2 px-4 rounded-lg hover:bg-primary-50 transition-colors">
                <i class="fas fa-check-double mr-2"></i>Tandai Semua Sudah Dibaca
            </button>
        </div>
    </div>
</div>

<script>
// Global state
let isNotificationModalOpen = false;
let notificationCheckInterval = null;

// Toggle modal
function toggleNotificationModal() {
    if (isNotificationModalOpen) {
        closeNotificationModal();
    } else {
        openNotificationModal();
    }
}

function openNotificationModal() {
    document.getElementById('notification-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    isNotificationModalOpen = true;
    loadNotifications();
}

function closeNotificationModal() {
    document.getElementById('notification-modal').classList.add('hidden');
    document.body.style.overflow = '';
    isNotificationModalOpen = false;
}

// Load notifications via AJAX
function loadNotifications() {
    const loadingEl = document.getElementById('notif-loading');
    const emptyEl = document.getElementById('notif-empty');
    const listEl = document.getElementById('notification-list');
    
    loadingEl.classList.remove('hidden');
    emptyEl.classList.add('hidden');
    
    // Clear existing notifications
    const existingNotifs = listEl.querySelectorAll('.notif-item');
    existingNotifs.forEach(el => el.remove());
    
    fetch('<?= BASE_URL ?>/../modules/notifications/notification_handler.php?action=get_recent')
        .then(res => res.json())
        .then(data => {
            loadingEl.classList.add('hidden');
            
            if (data.status === 'success') {
                const notifications = data.notifications;
                
                if (notifications.length === 0) {
                    emptyEl.classList.remove('hidden');
                    return;
                }
                
                // Render notifications
                notifications.forEach(notif => {
                    const notifEl = createNotificationElement(notif);
                    listEl.appendChild(notifEl);
                });
                
                // Update badge
                updateNotificationBadge(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            loadingEl.classList.add('hidden');
            emptyEl.classList.remove('hidden');
        });
}

// Create notification element
function createNotificationElement(notif) {
    const div = document.createElement('div');
    div.className = `notif-item p-3 rounded-lg border transition-all cursor-pointer ${
        notif.is_read == 0 
            ? 'bg-primary-50 border-primary-200 hover:bg-primary-100' 
            : 'bg-white border-gray-200 hover:bg-gray-50'
    }`;
    
    div.onclick = () => handleNotificationClick(notif);
    
    // Icon based on type
    const iconMap = {
        'disposisi_baru': 'fa-paper-plane text-blue-500',
        'surat_masuk': 'fa-envelope text-green-500',
        'surat_update': 'fa-sync text-yellow-500',
        'surat_selesai': 'fa-check-circle text-emerald-500'
    };
    
    const iconClass = iconMap[notif.type] || 'fa-bell text-gray-500';
    
    // Time ago
    const timeAgo = formatTimeAgo(notif.created_at);
    
    div.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-1">
                <i class="fas ${iconClass} text-lg"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900 mb-1">${escapeHtml(notif.title)}</p>
                <p class="text-xs text-gray-600 line-clamp-2">${escapeHtml(notif.message || '')}</p>
                <p class="text-xs text-gray-400 mt-1">
                    <i class="far fa-clock mr-1"></i>${timeAgo}
                </p>
            </div>
            ${notif.is_read == 0 ? '<div class="flex-shrink-0"><div class="w-2 h-2 bg-primary-500 rounded-full"></div></div>' : ''}
        </div>
    `;
    
    return div;
}

// Handle notification click
function handleNotificationClick(notif) {
    // Mark as read
    if (notif.is_read == 0) {
        fetch('<?= BASE_URL ?>/../modules/notifications/notification_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=mark_read&id=${notif.id}`
        }).then(() => {
            updateNotificationCount();
        });
    }
    
    // Navigate to URL
    if (notif.url) {
        window.location.href = '<?= BASE_URL ?>' + notif.url;
    }
}

// Mark all as read
function markAllAsRead() {
    fetch('<?= BASE_URL ?>/../modules/notifications/notification_handler.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_all_read'
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            loadNotifications();
            updateNotificationBadge(0);
        }
    });
}

// Update notification badge
function updateNotificationBadge(count) {
    const badge = document.getElementById('notif-badge');
    const countNumber = document.getElementById('notif-unread-number');
    
    if (count > 0) {
        badge.classList.remove('hidden');
        badge.textContent = count > 9 ? '9+' : count;
        if (countNumber) countNumber.textContent = count;
    } else {
        badge.classList.add('hidden');
        if (countNumber) countNumber.textContent = '0';
    }
}

// Check for new notifications periodically
function startNotificationPolling() {
    // Check every 30 seconds
    notificationCheckInterval = setInterval(() => {
        updateNotificationCount();
    }, 30000);
}

function updateNotificationCount() {
    fetch('<?= BASE_URL ?>/../modules/notifications/notification_handler.php?action=count_unread')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                updateNotificationBadge(data.count);
                
                // Reload if modal is open
                if (isNotificationModalOpen) {
                    loadNotifications();
                }
            }
        });
}

// Helper functions
function formatTimeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diffMs = now - past;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins} menit lalu`;
    if (diffHours < 24) return `${diffHours} jam lalu`;
    if (diffDays < 7) return `${diffDays} hari lalu`;
    
    return past.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && isNotificationModalOpen) {
        closeNotificationModal();
    }
});

// Close modal on outside click
document.getElementById('notification-modal')?.addEventListener('click', (e) => {
    if (e.target.id === 'notification-modal') {
        closeNotificationModal();
    }
});

// Start polling on page load
document.addEventListener('DOMContentLoaded', () => {
    startNotificationPolling();
});
</script>