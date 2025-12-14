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
        $error = 'Email dan password wajib diisi';
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

// Data Settings
$conn = getConnection();
$settings = $conn->query("SELECT * FROM settings WHERE id = 1 LIMIT 1")->fetch_assoc();
$appName = $settings['app_name'] ?? 'Tracking Disposisi';
$appDesc = $settings['app_description'] ?? 'Sistem Manajemen Surat & Disposisi Digital';
$appLogo = $settings['app_logo'] ?? null;
$themeColor = $settings['theme_color'] ?? 'blue';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($appName) ?></title>
    
    <?php if ($settings['app_favicon']): ?>
    <link rel="icon" href="<?= SETTINGS_UPLOAD_URL . $settings['app_favicon'] ?>" type="image/x-icon">
    <?php endif; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: tailwind.colors.<?= $themeColor ?> } } }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="h-screen bg-gray-50 overflow-hidden">
    
    <div class="flex h-full">
        <div class="hidden lg:flex w-1/2 bg-primary-600 relative items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-primary-600 opacity-90 z-10"></div>
            <img src="https://images.unsplash.com/photo-1497215728101-856f4ea42174?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" 
                 class="absolute inset-0 w-full h-full object-cover" alt="Background">
            
            <div class="relative z-20 text-center px-12 text-white">
                <div class="mb-6 inline-block p-4 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl">
                    <?php if ($appLogo): ?>
                        <img src="<?= SETTINGS_UPLOAD_URL . $appLogo ?>" alt="Logo" class="h-20 w-auto">
                    <?php else: ?>
                        <i class="fas fa-paper-plane text-6xl"></i>
                    <?php endif; ?>
                </div>
                <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($appName) ?></h1>
                <p class="text-lg text-primary-100 font-light leading-relaxed"><?= htmlspecialchars($appDesc) ?></p>
            </div>
            
            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full z-10 blur-3xl"></div>
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full z-10 blur-3xl"></div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 relative">
            <div class="w-full max-w-md space-y-8">
                
                <div class="lg:hidden text-center mb-8">
                    <?php if ($appLogo): ?>
                        <img src="<?= SETTINGS_UPLOAD_URL . $appLogo ?>" alt="Logo" class="h-16 mx-auto mb-4">
                    <?php else: ?>
                        <div class="w-16 h-16 bg-primary-600 rounded-xl flex items-center justify-center text-white text-3xl mx-auto mb-4">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($appName) ?></h2>
                </div>

                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Selamat Datang Kembali</h2>
                    <p class="mt-2 text-sm text-gray-600">Silakan login untuk mengakses akun Anda.</p>
                </div>

                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-red-500"></i></div>
                        <div class="ml-3"><p class="text-sm text-red-700"><?= $error ?></p></div>
                    </div>
                </div>
                <?php endif; ?>

                <form class="mt-8 space-y-6" action="" method="POST" id="loginForm">
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input id="email" name="email" type="email" autocomplete="email" required 
                                       class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow sm:text-sm" 
                                       placeholder="nama@instansi.com" value="<?= $email ?? '' ?>">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input id="password" name="password" type="password" required 
                                       class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow sm:text-sm" 
                                       placeholder="••••••••">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword()">
                                    <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="toggleIcon"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="text-sm">
                            <a href="register.php" class="font-medium text-primary-600 hover:text-primary-500 transition-colors">
                                Belum punya akun? Daftar
                            </a>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-sign-in-alt text-primary-500 group-hover:text-primary-400 transition-colors"></i>
                            </span>
                            Masuk Sekarang
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                        <div class="relative flex justify-center text-sm"><span class="px-2 bg-gray-50 text-gray-500">Akun Demo</span></div>
                    </div>
                    <div class="mt-6 grid grid-cols-3 gap-2">
                        <div class="text-center p-2 bg-white rounded border border-gray-100 shadow-sm">
                            <div class="text-[10px] font-bold text-gray-500 uppercase">Admin</div>
                            <div class="text-xs text-primary-600 font-mono mt-1">superadmin</div>
                        </div>
                        <div class="text-center p-2 bg-white rounded border border-gray-100 shadow-sm">
                            <div class="text-[10px] font-bold text-gray-500 uppercase">Karyawan</div>
                            <div class="text-xs text-primary-600 font-mono mt-1">karyawan</div>
                        </div>
                        <div class="text-center p-2 bg-white rounded border border-gray-100 shadow-sm">
                            <div class="text-[10px] font-bold text-gray-500 uppercase">Magang</div>
                            <div class="text-xs text-primary-600 font-mono mt-1">magang</div>
                        </div>
                    </div>
                    <p class="text-center text-xs text-gray-400 mt-2">Password semua akun: <strong>admin123</strong></p>
                </div>
            </div>
            
            <div class="absolute bottom-4 text-center w-full text-xs text-gray-400">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>. All rights reserved.
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>