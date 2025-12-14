<?php
// public/register.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Load settings manual
$conn = getConnection();
$settings = $conn->query("SELECT * FROM settings WHERE id = 1 LIMIT 1")->fetch_assoc();
$appName = $settings['app_name'] ?? 'Tracking Disposisi';
$appDesc = $settings['app_description'] ?? 'Sistem Disposisi Digital';
$appLogo = $settings['app_logo'] ?? null;
$themeColor = $settings['theme_color'] ?? 'blue';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - <?= htmlspecialchars($appName) ?></title>
    
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="h-screen bg-gray-50 overflow-hidden">
    
    <div class="flex h-full">
        <div class="hidden lg:flex w-1/2 bg-primary-600 relative items-center justify-center overflow-hidden">
            <div class="absolute inset-0 bg-primary-600 opacity-90 z-10"></div>
            <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" 
                 class="absolute inset-0 w-full h-full object-cover" alt="Background">
            
            <div class="relative z-20 text-center px-12 text-white">
                <div class="mb-6 inline-block p-4 bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl">
                    <i class="fas fa-user-plus text-6xl"></i>
                </div>
                <h1 class="text-4xl font-bold mb-4">Bergabung Sekarang</h1>
                <p class="text-lg text-primary-100 font-light leading-relaxed">Daftarkan diri Anda untuk mulai mengelola disposisi surat dengan mudah dan efisien.</p>
            </div>
            
            <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-white/10 rounded-full z-10 blur-3xl"></div>
            <div class="absolute top-1/4 -left-12 w-48 h-48 bg-white/10 rounded-full z-10 blur-3xl"></div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 relative overflow-y-auto">
            <div class="w-full max-w-md space-y-8">
                
                <div class="text-center lg:text-left">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Buat Akun Baru</h2>
                    <p class="mt-2 text-sm text-gray-600">Lengkapi data diri Anda di bawah ini.</p>
                </div>

                <form class="mt-8 space-y-5" id="registerForm">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="nama_lengkap" required 
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow sm:text-sm" 
                                   placeholder="Contoh: Budi Santoso">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Instansi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" name="email" required 
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow sm:text-sm" 
                                   placeholder="nama@instansi.com">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="password" required minlength="6"
                                   class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-shadow sm:text-sm" 
                                   placeholder="Buat password aman">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer" onclick="togglePassword()">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="toggleIcon"></i>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Minimal 6 karakter</p>
                    </div>

                    <div>
                        <button type="submit" id="btnRegister"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-user-plus text-primary-500 group-hover:text-primary-400 transition-colors"></i>
                            </span>
                            Daftar Sekarang
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Sudah punya akun? 
                        <a href="login.php" class="font-medium text-primary-600 hover:text-primary-500 transition-colors">
                            Login disini
                        </a>
                    </p>
                </div>
            </div>
            
            <div class="absolute bottom-4 text-center w-full text-xs text-gray-400 lg:hidden">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>
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
                                text: 'Akun Anda telah dibuat dan menunggu persetujuan Admin.',
                                confirmButtonText: 'Ke Halaman Login',
                                confirmButtonColor: '#10B981'
                            }).then(() => {
                                window.location.href = 'login.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: res.message
                            });
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
</body>
</html>