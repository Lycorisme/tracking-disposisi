<?php
// public/profil.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/users/users_service.php';

requireLogin();

$user = getCurrentUser();
$userData = UsersService::getById($user['id']);
$pageTitle = 'Profil';
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Profil Saya</h1>
                <p class="text-sm sm:text-base text-gray-600">Kelola informasi profil dan keamanan akun Anda</p>
            </div>
            
            <!-- Grid Layout - Responsive -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                        <div class="text-center">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-blue-600 rounded-full flex items-center justify-center text-white text-2xl sm:text-3xl font-bold mx-auto mb-3 sm:mb-4">
                                <?= strtoupper(substr($userData['nama_lengkap'], 0, 2)) ?>
                            </div>
                            <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-1"><?= htmlspecialchars($userData['nama_lengkap']) ?></h2>
                            <p class="text-xs sm:text-sm text-gray-600 mb-2 break-all"><?= htmlspecialchars($userData['email']) ?></p>
                            <span class="inline-block px-3 py-1 text-xs sm:text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?= getRoleLabel($userData['nama_role']) ?>
                            </span>
                        </div>
                        
                        <div class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-200">
                            <div class="space-y-3">
                                <div class="flex items-center text-xs sm:text-sm">
                                    <i class="fas fa-calendar-alt text-gray-400 w-5 mr-2 flex-shrink-0"></i>
                                    <span class="text-gray-600">Bergabung:</span>
                                    <span class="ml-auto font-medium text-gray-800 text-right"><?= formatTanggal($userData['created_at']) ?></span>
                                </div>
                                
                                <?php if (!empty($userData['nama_bagian'])): ?>
                                <div class="flex items-center text-xs sm:text-sm">
                                    <i class="fas fa-building text-gray-400 w-5 mr-2 flex-shrink-0"></i>
                                    <span class="text-gray-600">Bagian:</span>
                                    <span class="ml-auto font-medium text-gray-800 text-right truncate max-w-[60%]"><?= htmlspecialchars($userData['nama_bagian']) ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex items-center text-xs sm:text-sm">
                                    <i class="fas fa-circle text-green-500 w-5 mr-2 flex-shrink-0"></i>
                                    <span class="font-medium text-green-600">Akun Aktif</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Forms -->
                <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                    <!-- Update Profile Form -->
                    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Informasi Profil</h3>
                        
                        <form method="POST" action="../modules/users/users_handler.php">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                                    <input type="text" 
                                           name="nama_lengkap" 
                                           value="<?= htmlspecialchars($userData['nama_lengkap']) ?>" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input type="email" 
                                           name="email" 
                                           value="<?= htmlspecialchars($userData['email']) ?>" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" 
                                            class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm sm:text-base">
                                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Change Password Form -->
                    <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                        <h3 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Ganti Password</h3>
                        
                        <form method="POST" action="../modules/users/users_handler.php" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Lama *</label>
                                    <input type="password" 
                                           name="password_lama" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru *</label>
                                    <input type="password" 
                                           name="password_baru" 
                                           id="password_baru"
                                           required
                                           minlength="6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                                    <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru *</label>
                                    <input type="password" 
                                           name="password_konfirmasi" 
                                           id="password_konfirmasi"
                                           required
                                           minlength="6"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm sm:text-base">
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" 
                                            class="w-full sm:w-auto bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg text-sm sm:text-base">
                                        <i class="fas fa-key mr-2"></i>Ganti Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<script>
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const passwordBaru = document.getElementById('password_baru').value;
    const passwordKonfirmasi = document.getElementById('password_konfirmasi').value;
    
    if (passwordBaru !== passwordKonfirmasi) {
        e.preventDefault();
        alert('Password baru dan konfirmasi password tidak cocok!');
    }
});
</script>

<?php include 'partials/footer.php'; ?>