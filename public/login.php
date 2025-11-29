<?php
// public/login.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
    exit;
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
    exit;
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            // Redirect to intended page or dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-700 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-white rounded-full shadow-lg mb-4">
                <i class="fas fa-envelope-open-text text-5xl text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2"><?= APP_NAME ?></h1>
            <p class="text-blue-100">Bank Kalsel - Sistem Disposisi Digital</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login ke Sistem</h2>
            
            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?= $error ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['status']) && $_GET['status'] === 'logged_out'): ?>
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span>Anda telah berhasil logout</span>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <!-- Email -->
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
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="nama@email.com">
                </div>
                
                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-1 text-gray-400"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-12"
                               placeholder="Masukkan password">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i id="toggleIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Login
                </button>
            </form>
            
            <!-- Demo Accounts Info -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-xs font-semibold text-gray-600 mb-2">Akun Demo:</p>
                <div class="text-xs text-gray-600 space-y-1">
                    <div><strong>Kepala Bagian:</strong> superadmin@bankkalsel.com / admin123</div>
                    <div><strong>Karyawan:</strong> karyawan@bankkalsel.com / admin123</div>
                    <div><strong>Magang:</strong> magang@bankkalsel.com / admin123</div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-6 text-white text-sm">
            <p>&copy; <?= date('Y') ?> Bank Kalsel. All rights reserved.</p>
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
        
        // Form validation
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