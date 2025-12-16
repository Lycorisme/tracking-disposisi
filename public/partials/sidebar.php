<?php
// public/partials/sidebar.php

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

if (file_exists(__DIR__ . '/../../modules/users/users_service.php')) {
    require_once __DIR__ . '/../../modules/users/users_service.php';
}

if (file_exists(__DIR__ . '/../../modules/notifications/notification_service.php')) {
    require_once __DIR__ . '/../../modules/notifications/notification_service.php';
}

$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
$userRole = $user['id_role'] ?? 3;
$role = $user['role'] ?? 'user';

// Load settings
$appName = function_exists('getSetting') ? getSetting('app_name', 'Tracking Disposisi') : 'Tracking Disposisi';
$appLogo = function_exists('getSetting') ? getSetting('app_logo') : null;

// Pending User Count (Superadmin only)
$pendingCount = 0;
if (hasRole('superadmin') && class_exists('UsersService')) {
    $pendingCount = UsersService::countPending();
}

// BADGE INBOX: Hitung surat aktif yang disposisinya belum selesai
$inboxBadge = 0;
if (class_exists('NotificationService')) {
    // Fungsi ini menghitung unique surat yang status disposisinya ('dikirim', 'diterima', 'diproses')
    // DAN status suratnya masih ('baru', 'proses')
    $inboxBadge = NotificationService::countActiveNotifications($user['id']);
}

function isActive($page) {
    global $currentPage;
    return $currentPage === $page 
        ? 'bg-primary-50 text-primary-600 border-r-4 border-primary-600' 
        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
}

function isGroupActive($pages) {
    global $currentPage;
    return in_array($currentPage, $pages) ? 'block' : 'hidden';
}
?>

<div class="lg:hidden fixed top-0 left-0 right-0 z-30 bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4">
    <div class="flex items-center space-x-3">
        <?php if ($appLogo): ?>
        <img src="<?= SETTINGS_UPLOAD_URL . $appLogo ?>" alt="Logo" class="h-8 w-auto">
        <?php endif; ?>
        <span class="font-bold text-gray-800 truncate max-w-[200px]"><?= htmlspecialchars($appName) ?></span>
    </div>
    <button id="mobile-menu-button" class="text-gray-600 hover:text-primary-600 focus:outline-none p-2 rounded-md hover:bg-gray-100">
        <i class="fas fa-bars text-xl"></i>
    </button>
</div>

<div class="h-16 lg:hidden"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col h-full border-r border-gray-200">
    
    <div class="hidden lg:flex items-center h-16 px-6 border-b border-gray-200 bg-white">
        <div class="flex items-center space-x-2">
            <?php if ($appLogo): ?>
            <img src="<?= SETTINGS_UPLOAD_URL . $appLogo ?>" alt="Logo" class="h-8 w-auto">
            <?php else: ?>
            <i class="fas fa-paper-plane text-primary-600 text-2xl"></i>
            <?php endif; ?>
            <h1 class="text-lg font-bold text-gray-800 truncate"><?= htmlspecialchars($appName) ?></h1>
        </div>
    </div>
    
    <div class="p-4 border-b border-gray-200 bg-gray-50/50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center text-white font-bold shadow text-lg shrink-0">
                <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate" title="<?= htmlspecialchars($user['nama_lengkap']) ?>">
                    <?= htmlspecialchars($user['nama_lengkap']) ?>
                </p>
                <div class="flex items-center mt-0.5">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                    <p class="text-xs text-gray-500 capitalize">
                        <?php 
                        $roleLabels = [1 => 'Kepala Bagian', 2 => 'Karyawan', 3 => 'Anak Magang'];
                        echo $roleLabels[$userRole] ?? getRoleLabel($role);
                        ?>
                    </p>
                </div>
            </div>
            <button id="close-sidebar" class="lg:hidden text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6 custom-scrollbar">
        
        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Utama</p>
            <div class="space-y-1">
                <a href="<?= BASE_URL ?>/index.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('index.php') ?>">
                    <i class="fas fa-home w-6 text-center mr-2"></i>
                    Dashboard
                </a>
            </div>
        </div>

        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Persuratan</p>
            <div class="space-y-1">
                <a href="<?= BASE_URL ?>/surat.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('surat.php') ?>">
                    <i class="fas fa-envelope w-6 text-center mr-2"></i>
                    Semua Surat
                </a>
                
                <a href="<?= BASE_URL ?>/disposisi_inbox.php" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('disposisi_inbox.php') ?>">
                    <div class="flex items-center">
                        <i class="fas fa-inbox w-6 text-center mr-2"></i>
                        Disposisi Masuk
                    </div>
                    <?php if ($inboxBadge > 0): ?>
                    <span id="inbox-badge" class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm animate-pulse">
                        <?= $inboxBadge ?>
                    </span>
                    <?php else: ?>
                    <span id="inbox-badge" class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm animate-pulse" style="display: none;"></span>
                    <?php endif; ?>
                </a>
                
                <?php if ($userRole != 3): ?>
                <a href="<?= BASE_URL ?>/disposisi_outbox.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('disposisi_outbox.php') ?>">
                    <i class="fas fa-paper-plane w-6 text-center mr-2"></i>
                    Disposisi Keluar
                </a>
                <?php endif; ?>
                
                <?php if ($userRole != 3): ?>
                <a href="<?= BASE_URL ?>/disposisi.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('disposisi.php') ?>">
                    <i class="fas fa-exchange-alt w-6 text-center mr-2"></i>
                    Monitoring Disposisi
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Laporan & Arsip</p>
            <div class="space-y-1">
                
                <?php if ($userRole != 3): ?>
                <div class="relative">
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 focus:outline-none transition-colors" onclick="toggleMenu('laporan-menu')">
                        <div class="flex items-center">
                            <i class="fas fa-file-alt w-6 text-center mr-2"></i>
                            Pusat Laporan
                        </div>
                        <i id="laporan-arrow" class="fas fa-chevron-down text-xs transition-transform duration-300"></i>
                    </button>
                    <div id="laporan-menu" class="mt-1 ml-6 space-y-1 pl-3 border-l-2 border-gray-200 hidden">
                        <a href="<?= BASE_URL ?>/laporan/laporan_masuk.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-primary-600 hover:bg-gray-50 <?= isActive('laporan_masuk.php') ?>">Surat Masuk</a>
                        <a href="<?= BASE_URL ?>/laporan/laporan_keluar.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-primary-600 hover:bg-gray-50 <?= isActive('laporan_keluar.php') ?>">Surat Keluar</a>
                        <a href="<?= BASE_URL ?>/laporan/laporan_proposal.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-primary-600 hover:bg-gray-50 <?= isActive('laporan_proposal.php') ?>">Proposal</a>
                        <a href="<?= BASE_URL ?>/laporan/laporan_disposisi.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-primary-600 hover:bg-gray-50 <?= isActive('laporan_disposisi.php') ?>">Disposisi</a>
                        <?php if ($userRole == 1): ?>
                        <a href="<?= BASE_URL ?>/laporan/laporan_aktivitas.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-primary-600 hover:bg-gray-50 <?= isActive('laporan_aktivitas.php') ?>">Log Aktivitas</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <a href="<?= BASE_URL ?>/arsip_surat.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('arsip_surat.php') ?>">
                    <i class="fas fa-archive w-6 text-center mr-2"></i>
                    Arsip Digital
                </a>
            </div>
        </div>

        <?php if ($userRole != 3): ?>
        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Administrator</p>
            <div class="space-y-1">
                <a href="<?= BASE_URL ?>/jenis_surat.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('jenis_surat.php') ?>">
                    <i class="fas fa-tags w-6 text-center mr-2"></i>
                    Master Jenis Surat
                </a>

                <?php if ($userRole == 1): ?>
                <a href="<?= BASE_URL ?>/users.php" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('users.php') ?>">
                    <div class="flex items-center">
                        <i class="fas fa-users-cog w-6 text-center mr-2"></i>
                        Manajemen User
                    </div>
                    <?php if ($pendingCount > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm animate-pulse">
                        <?= $pendingCount ?>
                    </span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>/pengaturan.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('pengaturan.php') ?>">
                    <i class="fas fa-cog w-6 text-center mr-2"></i>
                    Pengaturan Sistem
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </nav>

    <div class="p-4 border-t border-gray-200 bg-gray-50">
        <a href="<?= BASE_URL ?>/profil.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-white hover:shadow-sm transition-all <?= isActive('profil.php') ?>">
            <i class="fas fa-user-circle w-6 text-center mr-2"></i>
            Profil Saya
        </a>
        <button onclick="confirmLogout()" class="w-full mt-2 flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-md hover:bg-red-50 transition-colors">
            <i class="fas fa-sign-out-alt w-6 text-center mr-2"></i>
            Logout
        </button>
    </div>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden transition-opacity backdrop-blur-sm"></div>

<script>
function toggleMenu(menuId) {
    const menu = document.getElementById(menuId);
    const arrow = document.getElementById('laporan-arrow');
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        if(arrow) arrow.style.transform = 'rotate(180deg)';
    } else {
        menu.classList.add('hidden');
        if(arrow) arrow.style.transform = 'rotate(0deg)';
    }
}

// Mobile Sidebar
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebar-overlay');
const mobileBtn = document.getElementById('mobile-menu-button');
const closeBtn = document.getElementById('close-sidebar');

if(mobileBtn) {
    mobileBtn.addEventListener('click', () => {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });
}

function closeSidebarFn() {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
}

if(closeBtn) closeBtn.addEventListener('click', closeSidebarFn);
if(overlay) overlay.addEventListener('click', closeSidebarFn);

function confirmLogout() {
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sistem?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= BASE_URL ?>/login.php?action=logout';
        }
    });
}

// UPDATE BADGE SECARA REALTIME VIA AJAX
function updateInboxBadge() {
    // Panggil handler count_active
    fetch('<?= BASE_URL ?>/../modules/notifications/notification_handler.php?action=count_active')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                let badge = document.getElementById('inbox-badge');
                
                // Jika elemen badge belum ada di DOM (kasus awal kosong), kita cari parent link-nya
                if (!badge) {
                    const inboxLink = document.querySelector('a[href*="disposisi_inbox.php"]');
                    if (inboxLink) {
                        const badgeHtml = `<span id="inbox-badge" class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm animate-pulse" style="display:none;"></span>`;
                        inboxLink.insertAdjacentHTML('beforeend', badgeHtml);
                        badge = document.getElementById('inbox-badge');
                    }
                }

                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error updating inbox badge:', error);
        });
}

// Update badge setiap 30 detik agar selalu sinkron
setInterval(updateInboxBadge, 30000);

// Panggil sekali saat load page
document.addEventListener('DOMContentLoaded', updateInboxBadge);
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}
</style>