<?php
// public/profil.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/users/users_service.php';

requireLogin();

$user = getCurrentUser();
// Refresh data user terbaru
$userData = UsersService::getById($user['id']); 

// LOGIKA BAGIAN:
// 1. Cek apakah user punya custom bagian
// 2. Jika tidak, gunakan deskripsi role
// 3. Jika tidak ada deskripsi role, gunakan nama role biasa
$roleDefaultDescription = !empty($userData['role_deskripsi']) ? $userData['role_deskripsi'] : getRoleLabel($userData['nama_role']);
$displayBagian = !empty($userData['nama_bagian_custom']) ? $userData['nama_bagian_custom'] : $roleDefaultDescription;

$pageTitle = 'Profil Saya';
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Profil Saya</h1>
                <p class="text-gray-500 text-sm">Kelola informasi pribadi dan keamanan akun Anda</p>
            </div>
            
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <div class="xl:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="h-32 bg-gradient-to-r from-primary-600 to-primary-400"></div>
                        <div class="px-6 pb-6 relative">
                            <div class="flex justify-center -mt-12 mb-4">
                                <div class="w-24 h-24 bg-white rounded-full p-1 shadow-lg">
                                    <div class="w-full h-full bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-3xl font-bold">
                                        <?= strtoupper(substr($userData['nama_lengkap'], 0, 2)) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <h2 class="text-xl font-bold text-gray-800" id="display_nama"><?= htmlspecialchars($userData['nama_lengkap']) ?></h2>
                                <p class="text-sm text-gray-500 mb-4" id="display_email"><?= htmlspecialchars($userData['email']) ?></p>
                                
                                <div class="flex flex-wrap justify-center gap-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        <?= getRoleLabel($userData['nama_role']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-6 border-t border-gray-100 space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Bergabung</span>
                                    <span class="font-medium text-gray-700"><?= formatTanggal($userData['created_at']) ?></span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Bagian</span>
                                    <span class="font-medium text-gray-700 truncate max-w-[150px]" id="display_bagian" title="<?= htmlspecialchars($displayBagian) ?>">
                                        <?= htmlspecialchars($displayBagian) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="xl:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-primary-50 rounded-lg text-primary-600">
                                <i class="fas fa-user-edit text-lg"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">Edit Informasi</h3>
                        </div>
                        
                        <form id="profileForm">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" id="default_role_desc" value="<?= htmlspecialchars($roleDefaultDescription) ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($userData['nama_lengkap']) ?>" required
                                           class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required
                                           class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">
                                        Bagian / Divisi 
                                        <span class="text-gray-400 font-normal normal-case ml-1">(Default: <?= htmlspecialchars($roleDefaultDescription) ?>)</span>
                                    </label>
                                    <input type="text" name="nama_bagian_custom" value="<?= htmlspecialchars($userData['nama_bagian_custom'] ?? '') ?>" 
                                           placeholder="<?= htmlspecialchars($roleDefaultDescription) ?>"
                                           class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all">
                                    <p class="text-xs text-gray-400 mt-1">Kosongkan jika ingin menggunakan default dari sistem.</p>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" id="btnSaveProfile" class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200 flex items-center gap-2">
                                    <i class="fas fa-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-yellow-50 rounded-lg text-yellow-600">
                                <i class="fas fa-lock text-lg"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800">Keamanan</h3>
                        </div>
                        
                        <form id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="space-y-4 mb-6">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Password Saat Ini</label>
                                    <div class="relative">
                                        <input type="password" name="password_lama" required
                                               class="w-full pl-4 pr-10 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                                        <i class="fas fa-key absolute right-3 top-3 text-gray-400"></i>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Password Baru</label>
                                        <input type="password" name="password_baru" id="password_baru" required minlength="6"
                                               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Konfirmasi Password</label>
                                        <input type="password" name="password_konfirmasi" id="password_konfirmasi" required minlength="6"
                                               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition-all">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" id="btnSavePassword" class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 hover:text-gray-900 font-medium rounded-lg shadow-sm transition-all duration-200">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                    
                </div>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const handlerUrl = '../modules/users/users_handler.php';

    // 1. Handle Update Profile
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $('#btnSaveProfile');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

        $.ajax({
            url: handlerUrl,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalText);
                
                if(response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Update UI Card secara Realtime tanpa refresh
                    $('#display_nama').text($('input[name="nama_lengkap"]').val());
                    $('#display_email').text($('input[name="email"]').val());
                    
                    const bagianCustom = $('input[name="nama_bagian_custom"]').val().trim();
                    const defaultRoleDesc = $('#default_role_desc').val();
                    
                    // Logic UI: Jika custom kosong, gunakan default
                    if(bagianCustom) {
                        $('#display_bagian').text(bagianCustom);
                    } else {
                        $('#display_bagian').text(defaultRoleDesc);
                    }
                    
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                console.error(xhr.responseText);
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            }
        });
    });

    // 2. Handle Ganti Password
    $('#passwordForm').on('submit', function(e) {
        e.preventDefault();
        
        const p1 = $('#password_baru').val();
        const p2 = $('#password_konfirmasi').val();
        
        if (p1 !== p2) {
            Swal.fire('Validasi Gagal', 'Konfirmasi password tidak cocok!', 'error');
            return;
        }

        const btn = $('#btnSavePassword');
        const originalText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...');

        $.ajax({
            url: handlerUrl,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalText);
                
                if(response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#passwordForm')[0].reset(); 
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html(originalText);
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            }
        });
    });
});
</script>

<?php include 'partials/footer.php'; ?>