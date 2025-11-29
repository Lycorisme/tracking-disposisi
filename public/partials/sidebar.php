<?php
// public/partials/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
$role = $user['role'] ?? '';

function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? 'sidebar-active' : '';
}
?>

<!-- Mobile menu button -->
<div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
    <div class="flex items-center justify-between p-4">
        <h1 class="text-xl font-bold text-gray-800"><?= APP_NAME ?></h1>
        <button id="mobile-menu-button" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-bars text-2xl"></i>
        </button>
    </div>
</div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex flex-col h-full">
        <!-- Logo/Header -->
        <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
            <h1 class="text-xl font-bold text-gray-800">Tracking Surat</h1>
            <button id="close-sidebar" class="lg:hidden text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- User Info -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                    <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate"><?= $user['nama_lengkap'] ?></p>
                    <p class="text-xs text-gray-500"><?= getRoleLabel($role) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
            <div class="px-3 space-y-1">
                <!-- Dashboard -->
                <a href="index.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('index.php') ?>">
                    <i class="fas fa-home w-5 mr-3"></i>
                    Dashboard
                </a>
                
                <!-- Surat -->
                <a href="surat.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('surat.php') ?>">
                    <i class="fas fa-envelope w-5 mr-3"></i>
                    Manajemen Surat
                </a>
                
                <!-- Disposisi Section -->
                <div class="pt-2">
                    <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Disposisi</p>
                    
                    <a href="disposisi_inbox.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('disposisi_inbox.php') ?>">
                        <i class="fas fa-inbox w-5 mr-3"></i>
                        Disposisi Masuk
                    </a>
                    
                    <a href="disposisi_outbox.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('disposisi_outbox.php') ?>">
                        <i class="fas fa-paper-plane w-5 mr-3"></i>
                        Disposisi Keluar
                    </a>
                    
                    <?php if (hasRole(['admin', 'superadmin'])): ?>
                    <a href="disposisi.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('disposisi.php') ?>">
                        <i class="fas fa-exchange-alt w-5 mr-3"></i>
                        Semua Disposisi
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Master Data (Admin & Superadmin only) -->
                <?php if (hasRole(['admin', 'superadmin'])): ?>
                <div class="pt-2">
                    <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Master Data</p>
                    
                    <a href="jenis_surat.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('jenis_surat.php') ?>">
                        <i class="fas fa-list w-5 mr-3"></i>
                        Jenis Surat
                    </a>
                </div>
                <?php endif; ?>
                
                <!-- Laporan (All roles) -->
                <div class="pt-2">
                    <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Laporan</p>
                    
                    <a href="laporan_surat_masuk.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50">
                        <i class="fas fa-file-alt w-5 mr-3"></i>
                        Surat Masuk
                    </a>
                    
                    <a href="laporan_surat_keluar.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50">
                        <i class="fas fa-file-export w-5 mr-3"></i>
                        Surat Keluar
                    </a>
                    
                    <a href="laporan_proposal.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50">
                        <i class="fas fa-file-invoice w-5 mr-3"></i>
                        Proposal
                    </a>
                    
                    <a href="laporan_disposisi.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50">
                        <i class="fas fa-chart-line w-5 mr-3"></i>
                        Laporan Disposisi
                    </a>
                    
                    <?php if (hasRole(['superadmin'])): ?>
                    <a href="laporan_aktivitas.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50">
                        <i class="fas fa-history w-5 mr-3"></i>
                        Log Aktivitas
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Arsip -->
                <a href="arsip_surat.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('arsip_surat.php') ?>">
                    <i class="fas fa-archive w-5 mr-3"></i>
                    Arsip Surat
                </a>
            </div>
        </nav>
        
        <!-- Bottom Menu -->
        <div class="border-t border-gray-200 p-4 space-y-1">
            <a href="profil.php" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md hover:bg-gray-50 <?= isActive('profil.php') ?>">
                <i class="fas fa-user w-5 mr-3"></i>
                Profil
            </a>
            
            <button onclick="confirmLogout()" class="w-full flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-md hover:bg-red-50">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                Logout
            </button>
        </div>
    </div>
</aside>

<!-- Overlay for mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"></div>

<script>
// Mobile menu toggle
document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.remove('hidden');
});

document.getElementById('close-sidebar')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.add('hidden');
});

document.getElementById('sidebar-overlay')?.addEventListener('click', function() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    this.classList.add('hidden');
});

// Logout confirmation
function confirmLogout() {
    Swal.fire({
        title: 'Yakin ingin logout?',
        text: 'Anda akan keluar dari sistem',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Logout',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'login.php?action=logout';
        }
    });
}
</script>