<?php
// public/partials/header.php
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../../config/config.php';
}

if (!function_exists('getCurrentUser')) {
    require_once __DIR__ . '/../../includes/auth.php'; 
}

$appName = function_exists('getSetting') ? getSetting('app_name', APP_NAME) : APP_NAME;
$appFavicon = function_exists('getSetting') ? getSetting('app_favicon') : null;
$appLogo = function_exists('getSetting') ? getSetting('app_logo') : null;

// Warna tema dinamis
$themeColorName = function_exists('getSetting') ? getSetting('theme_color', 'blue') : 'blue';

// Get current user
$currentUser = function_exists('getCurrentUser') ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?><?= htmlspecialchars($appName) ?></title>
    
    <?php if ($appFavicon): ?>
    <link rel="icon" href="<?= SETTINGS_UPLOAD_URL . htmlspecialchars($appFavicon) ?>" type="image/x-icon">
    <?php endif; ?>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: tailwind.colors.<?= $themeColorName ?> 
                    }
                }
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-active { 
            @apply bg-primary-50 border-r-4 border-primary-600 text-primary-600; 
        }
        .line-clamp-2 { 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
        }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
        @media (max-width: 1023px) { .table-responsive { display: none; } }
        
        /* Animation untuk notification badge */
        @keyframes pulse-ring {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            50% {
                box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-100">

    <header class="fixed top-0 left-0 right-0 h-16 bg-white shadow-sm z-50 border-b border-gray-200 flex items-center justify-between px-4 lg:px-6">
        
        <!-- Left: Mobile Menu + Logo -->
        <div class="flex items-center gap-4">
            <button id="mobile-menu-button" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none p-1.5 rounded-md hover:bg-gray-100 transition-colors">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <div class="flex items-center gap-3">
                <?php if ($appLogo): ?>
                    <img src="<?= SETTINGS_UPLOAD_URL . $appLogo ?>" alt="Logo" class="h-8 w-auto">
                <?php endif; ?>
                <h1 class="text-lg font-bold text-gray-800 truncate hidden sm:block">
                    <?= htmlspecialchars($appName) ?>
                </h1>
            </div>
        </div>

        <!-- Right: Notification Bell + User Menu -->
        <div class="flex items-center gap-3">
            <?php if ($currentUser): ?>
            
            <!-- NOTIFICATION BELL -->
            <?php 
            // Include notification bell component
            if (file_exists(__DIR__ . '/notification_bell.php')) {
                include __DIR__ . '/notification_bell.php';
            }
            ?>
            
            <!-- USER MENU -->
            <div class="relative ml-3">
                <button type="button" onclick="toggleUserDropdown()" class="flex items-center max-w-xs text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" id="user-menu-button">
                    <span class="sr-only">Open user menu</span>
                    
                    <div class="h-9 w-9 rounded-full bg-primary-600 flex items-center justify-center text-white font-bold shadow-sm">
                        <?= strtoupper(substr($currentUser['nama_lengkap'], 0, 1)) ?>
                    </div>
                    
                    <span class="hidden md:block ml-3 font-medium text-gray-700 text-sm">
                        <?= htmlspecialchars($currentUser['nama_lengkap']) ?>
                        <i class="fas fa-chevron-down ml-1 text-xs text-gray-400"></i>
                    </span>
                </button>

                <div id="user-dropdown-menu" class="hidden origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" role="menu">
                    <div class="px-4 py-2 border-b border-gray-100 md:hidden">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['nama_lengkap']) ?></p>
                        <p class="text-xs text-gray-500">
                            <?php 
                            $roleLabels = [1 => 'Kepala Bagian', 2 => 'Karyawan', 3 => 'Anak Magang'];
                            echo $roleLabels[$currentUser['id_role']] ?? ucfirst($currentUser['role'] ?? 'User');
                            ?>
                        </p>
                    </div>

                    <a href="<?= BASE_URL ?>/profil.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                        <i class="fas fa-user mr-2 w-4 text-center"></i> Profil Saya
                    </a>
                    
                    <?php if (isset($currentUser['id_role']) && $currentUser['id_role'] == 1): // Superadmin ?>
                    <a href="<?= BASE_URL ?>/pengaturan.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                        <i class="fas fa-cog mr-2 w-4 text-center"></i> Setting
                    </a>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-100"></div>
                    
                    <button onclick="confirmLogoutGlobal()" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" role="menuitem">
                        <i class="fas fa-sign-out-alt mr-2 w-4 text-center"></i> Logout
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="h-16 w-full"></div>

    <script>
        function toggleUserDropdown() {
            const menu = document.getElementById('user-dropdown-menu');
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }
        }

        // Close dropdown on outside click
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-dropdown-menu');
            const button = document.getElementById('user-menu-button');
            if (menu && button && !menu.classList.contains('hidden') && !button.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        function confirmLogoutGlobal() {
            Swal.fire({
                title: 'Konfirmasi Logout',
                text: 'Apakah Anda yakin ingin keluar?',
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
    </script>

    <?php
    // Flash Message Handling
    if (function_exists('hasFlash') && hasFlash()) {
        $flash = getFlash();
        $icon = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'error' : 'info');
        echo "<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({icon: '{$icon}', title: '" . ucfirst($flash['type']) . "', text: '" . addslashes($flash['message']) . "', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end'}));</script>";
    }
    if (isset($_GET['success']) || isset($_GET['error'])) {
        $msg = isset($_GET['success']) ? $_GET['success'] : $_GET['error'];
        $icon = isset($_GET['success']) ? 'success' : 'error';
        echo "<script>document.addEventListener('DOMContentLoaded', () => Swal.fire({icon: '{$icon}', title: '" . ucfirst($icon) . "', text: '" . addslashes($msg) . "', timer: 3000, showConfirmButton: false, toast: true, position: 'top-end'}));</script>";
    }
    ?>