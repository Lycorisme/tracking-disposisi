<?php
// public/login.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
    exit;
}

if (isLoggedIn()) {
    redirect('index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$appName = getSetting('app_name', APP_NAME);
$appDescription = getSetting('app_description', 'Sistem Disposisi Digital');
$appLogo = getSetting('app_logo');
$appFavicon = getSetting('app_favicon');

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= htmlspecialchars($appName) ?></title>
    
    <?php if ($appFavicon): ?>
    <link rel="icon" href="<?= SETTINGS_UPLOAD_URL . htmlspecialchars($appFavicon) ?>" type="image/x-icon">
    <?php endif; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-700 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo & Title Section - Responsive -->
        <div class="text-center mb-6 sm:mb-8">
            <div class="inline-block p-3 sm:p-4 bg-white rounded-full shadow-lg mb-3 sm:mb-4">
                <?php if ($appLogo): ?>
                    <img src="<?= SETTINGS_UPLOAD_URL . $appLogo ?>" alt="Logo" class="h-12 sm:h-16 w-auto">
                <?php else: ?>
                    <i class="fas fa-envelope-open-text text-4xl sm:text-5xl text-blue-600"></i>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-1 sm:mb-2"><?= htmlspecialchars($appName) ?></h1>
            <p class="text-sm sm:text-base text-blue-100"><?= htmlspecialchars($appDescription) ?></p>
        </div>
        
        <!-- Login Card - Responsive -->
        <div class="bg-white rounded-lg shadow-2xl p-6 sm:p-8">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6 text-center">Login ke Sistem</h2>
            
            <?php if ($error): ?>
            <div class="mb-4 p-3 sm:p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded text-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?= $error ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'logged_out'): ?>
            <div class="mb-4 p-3 sm:p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded text-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>Anda telah berhasil logout</span>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-1 text-gray-400"></i>
                        Email
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required
                           value="<?= $email ?? '' ?>"
                           class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm sm:text-base"
                           placeholder="nama@email.com">
                </div>
                
                <div class="mb-4 sm:mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1 text-gray-400"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10 sm:pr-12 text-sm sm:text-base"
                               placeholder="Masukkan password">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="toggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 sm:py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg text-sm sm:text-base">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>

                <div class="mt-4 text-center border-t pt-4">
                    <p class="text-xs sm:text-sm text-gray-600">Belum punya akun?</p>
                    <a href="register.php" class="text-blue-600 hover:text-blue-800 font-semibold text-xs sm:text-sm">
                        Daftar Akun Baru
                    </a>
                </div>
            </form>
            
            <!-- Demo Accounts - Responsive -->
            <div class="mt-4 sm:mt-6 p-3 sm:p-4 bg-gray-50 rounded-lg">
                <p class="text-xs font-semibold text-gray-600 mb-2">Akun Demo:</p>
                <div class="text-[10px] sm:text-xs text-gray-600 space-y-1">
                    <div><strong>Kepala Bagian:</strong> <span class="break-all">superadmin@bankkalsel.com</span> / admin123</div>
                    <div><strong>Karyawan:</strong> <span class="break-all">karyawan@bankkalsel.com</span> / admin123</div>
                    <div><strong>Magang:</strong> <span class="break-all">magang@bankkalsel.com</span> / admin123</div>
                </div>
            </div>
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
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Email dan password harus diisi'
                });
            }
        });
    </script>
</body>
</html>