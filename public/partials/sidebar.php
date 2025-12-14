<?php
// public/partials/sidebar.php

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

if (file_exists(__DIR__ . '/../../modules/users/users_service.php')) {
    require_once __DIR__ . '/../../modules/users/users_service.php';
}

$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser(); 
$role = $user['role'] ?? '';

// Hitung pending user jika superadmin
$pendingCount = 0;
if (function_exists('hasRole') && hasRole('superadmin') && class_exists('UsersService')) {
    $pendingCount = UsersService::countPending();
}

// --- FUNGSI HELPER (DIBUNGKUS AGAR AMAN DARI DUPLIKASI) ---

if (!function_exists('isActive')) {
    function isActive($page) {
        global $currentPage;
        return $currentPage === $page 
            ? 'bg-blue-50 text-blue-600 border-r-4 border-blue-600' 
            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
    }
}

if (!function_exists('isGroupActive')) {
    function isGroupActive($pages) {
        global $currentPage;
        return in_array($currentPage, $pages) ? 'block' : 'hidden';
    }
}
?>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col pt-16 border-r border-gray-200">
    
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold shadow text-lg flex-shrink-0">
                <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate"><?= $user['nama_lengkap'] ?></p>
                <div class="flex items-center mt-0.5">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse flex-shrink-0"></span>
                    <p class="text-xs text-gray-500 capitalize truncate"><?= function_exists('getRoleLabel') ? getRoleLabel($role) : $role ?></p>
                </div>
            </div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
        
        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Utama</p>
            <div class="space-y-1">
                <a href="<?= BASE_URL ?>/index.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('index.php') ?>">
                    <i class="fas fa-home w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Dashboard</span>
                </a>
            </div>
        </div>

        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Persuratan</p>
            <div class="space-y-1">
                <a href="<?= BASE_URL ?>/surat.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('surat.php') ?>">
                    <i class="fas fa-envelope w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Semua Surat</span>
                </a>
                <a href="<?= BASE_URL ?>/disposisi_inbox.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('disposisi_inbox.php') ?>">
                    <i class="fas fa-inbox w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Disposisi Masuk</span>
                </a>
                <a href="<?= BASE_URL ?>/disposisi_outbox.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('disposisi_outbox.php') ?>">
                    <i class="fas fa-paper-plane w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Disposisi Keluar</span>
                </a>
                <?php if (function_exists('hasRole') && hasRole(['admin', 'superadmin'])): ?>
                <a href="<?= BASE_URL ?>/disposisi.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('disposisi.php') ?>">
                    <i class="fas fa-exchange-alt w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Monitoring Disposisi</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Laporan & Arsip</p>
            <div class="space-y-1">
                <div class="relative">
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-gray-50 focus:outline-none transition-colors" onclick="toggleMenu('laporan-menu')">
                        <div class="flex items-center min-w-0 flex-1">
                            <i class="fas fa-file-alt w-6 text-center mr-2 flex-shrink-0"></i>
                            <span class="truncate">Pusat Laporan</span>
                        </div>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 flex-shrink-0" id="laporan-arrow"></i>
                    </button>
                    <div id="laporan-menu" class="<?= isGroupActive(['laporan_surat_masuk.php', 'laporan_surat_keluar.php', 'laporan_proposal.php', 'laporan_disposisi.php', 'laporan_aktivitas.php']) ?> pl-9 space-y-1 mt-1">
                        <a href="<?= BASE_URL ?>/laporan/laporan_surat_masuk.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-50 <?= isActive('laporan_surat_masuk.php') ?> truncate">Surat Masuk</a>
                        <a href="<?= BASE_URL ?>/laporan/laporan_surat_keluar.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-50 <?= isActive('laporan_surat_keluar.php') ?> truncate">Surat Keluar</a>
                        <a href="<?= BASE_URL ?>/laporan/laporan_proposal.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-50 <?= isActive('laporan_proposal.php') ?> truncate">Proposal</a>
                        <a href="<?= BASE_URL ?>/laporan/laporan_disposisi.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-50 <?= isActive('laporan_disposisi.php') ?> truncate">Disposisi</a>
                        <?php if (function_exists('hasRole') && hasRole(['superadmin'])): ?>
                        <a href="<?= BASE_URL ?>/laporan/laporan_aktivitas.php" class="block px-3 py-2 text-sm text-gray-600 rounded-md hover:text-gray-900 hover:bg-gray-50 <?= isActive('laporan_aktivitas.php') ?> truncate">Log Aktivitas</a>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/arsip_surat.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('arsip_surat.php') ?>">
                    <i class="fas fa-archive w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Arsip Digital</span>
                </a>
            </div>
        </div>

        <?php if (function_exists('hasRole') && hasRole(['admin', 'superadmin'])): ?>
        <div>
            <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Administrator</p>
            <div class="space-y-1">
                <a href="<?= BASE_URL ?>/jenis_surat.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('jenis_surat.php') ?>">
                    <i class="fas fa-tags w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Master Jenis Surat</span>
                </a>

                <?php if (hasRole('superadmin')): ?>
                <a href="<?= BASE_URL ?>/users.php" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('users.php') ?>">
                    <div class="flex items-center min-w-0 flex-1">
                        <i class="fas fa-users-cog w-6 text-center mr-2 flex-shrink-0"></i>
                        <span class="truncate">Manajemen User</span>
                    </div>
                    <?php if ($pendingCount > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm animate-pulse flex-shrink-0 ml-1">
                        <?= $pendingCount ?>
                    </span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>/pengaturan.php" class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors <?= isActive('pengaturan.php') ?>">
                    <i class="fas fa-cog w-6 text-center mr-2 flex-shrink-0"></i>
                    <span class="truncate">Pengaturan Sistem</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </nav>

    <div class="p-4 border-t border-gray-200 bg-gray-50 flex-shrink-0">
        <a href="<?= BASE_URL ?>/profil.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-md hover:bg-white hover:shadow-sm transition-all mb-2 <?= isActive('profil.php') ?>">
            <i class="fas fa-user-circle w-6 text-center mr-2 flex-shrink-0"></i>
            <span class="truncate">Profil Saya</span>
        </a>
        <button onclick="confirmLogout()" class="w-full flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-md hover:bg-red-50 transition-colors">
            <i class="fas fa-sign-out-alt w-6 text-center mr-2 flex-shrink-0"></i>
            <span class="truncate">Logout</span>
        </button>
    </div>
</aside>

<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden transition-opacity"></div>

<script>
// Fungsi Toggle Menu Dropdown Laporan
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

// Logic untuk Sidebar Mobile
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    // Ambil tombol hamburger dari header (karena id-nya unik, bisa diambil dari file lain)
    const mobileBtn = document.getElementById('mobile-menu-button'); 
    
    // Buka Sidebar
    if(mobileBtn) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Matikan scroll body
        });
    }

    // Tutup Sidebar
    const closeSidebar = () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = ''; // Nyalakan scroll body
    };

    if(overlay) overlay.addEventListener('click', closeSidebar);
});

// Konfirmasi Logout
function confirmLogout() {
    Swal.fire({
        title: 'Konfirmasi Logout',
        text: 'Apakah Anda yakin ingin keluar dari sistem?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= BASE_URL ?>/login.php?action=logout';
        }
    });
}
</script>