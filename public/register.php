<?php
// public/register.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$appName = defined('APP_NAME') ? APP_NAME : 'Tracking Disposisi';
$appDescription = 'Sistem Disposisi Digital';
if (function_exists('getSetting')) {
    $appName = getSetting('app_name', APP_NAME);
    $appDescription = getSetting('app_description', 'Sistem Disposisi Digital');
    $appLogo = getSetting('app_logo');
    $appFavicon = getSetting('app_favicon');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - <?= htmlspecialchars($appName) ?></title>
    
    <?php if (isset($appFavicon) && $appFavicon): ?>
    <link rel="icon" href="<?= SETTINGS_UPLOAD_URL . htmlspecialchars($appFavicon) ?>" type="image/x-icon">
    <?php endif; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-700 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo & Title Section - Responsive -->
        <div class="text-center mb-6 sm:mb-8">
            <div class="inline-block p-3 sm:p-4 bg-white rounded-full shadow-lg mb-3 sm:mb-4">
                <?php if (isset($appLogo) && $appLogo): ?>
                    <img src="<?= SETTINGS_UPLOAD_URL . $appLogo ?>" alt="Logo" class="h-12 sm:h-16 w-auto">
                <?php else: ?>
                    <i class="fas fa-envelope-open-text text-4xl sm:text-5xl text-blue-600"></i>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1 sm:mb-2"><?= htmlspecialchars($appName) ?></h1>
            <p class="text-sm sm:text-base text-blue-100"><?= htmlspecialchars($appDescription) ?></p>
        </div>
        
        <!-- Register Card - Responsive -->
        <div class="bg-white rounded-lg shadow-2xl p-6 sm:p-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2 text-center">Daftar Akun Baru</h2>
            <p class="text-gray-500 text-xs sm:text-sm text-center mb-4 sm:mb-6">Isi data diri Anda untuk mendaftar ke sistem</p>
            
            <form id="registerForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-1 text-gray-400"></i>
                        Nama Lengkap
                    </label>
                    <input type="text" 
                           name="nama_lengkap" 
                           required
                           class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base"
                           placeholder="Contoh: Budi Santoso">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-1 text-gray-400"></i>
                        Email
                    </label>
                    <input type="email" 
                           name="email" 
                           required
                           class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base"
                           placeholder="nama@email.com">
                </div>
                
                <div class="mb-4 sm:mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1 text-gray-400"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10 sm:pr-12 text-sm sm:text-base"
                               placeholder="Buat password aman">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="toggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" 
                        id="btnRegister"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 sm:py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg text-sm sm:text-base">
                    <i class="fas fa-user-plus mr-2"></i>
                    Daftar Sekarang
                </button>

                <div class="mt-4 sm:mt-6 text-center border-t pt-4">
                    <p class="text-xs sm:text-sm text-gray-600">Sudah punya akun?</p>
                    <a href="login.php" class="text-blue-600 hover:text-blue-800 font-semibold text-xs sm:text-sm">
                        Login disini
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Footer - Responsive -->
        <div class="text-center mt-4 sm:mt-6 text-white text-xs sm:text-sm">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        $(document).ready(function() {
            $('#registerForm').on('submit', function(e) {
                e.preventDefault();
                
                const btn = $('#btnRegister');
                const originalText = btn.html();
                
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...');

                $.ajax({
                    url: '<?= BASE_URL ?>/../modules/auth/register_handler.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        btn.prop('disabled', false).html(originalText);
                        
                        if(res.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Pendaftaran Berhasil!',
                                text: 'Akun Anda telah dibuat dan menunggu persetujuan Admin. Silakan hubungi Admin untuk aktivasi.',
                                confirmButtonText: 'OK, Kembali ke Login',
                                confirmButtonColor: '#2563EB'
                            }).then((result) => {
                                window.location.href = 'login.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Mendaftar',
                                text: res.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        btn.prop('disabled', false).html(originalText);
                        console.error(xhr.responseText);
                        Swal.fire('Error', 'Terjadi kesalahan sistem. Silakan coba lagi.', 'error');
                    }
                });
            });
        });
    </script>
</body>
</html>