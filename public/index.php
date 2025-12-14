<?php
// public/index.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/surat/surat_service.php';
require_once __DIR__ . '/../modules/disposisi/disposisi_service.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Dashboard';

$suratStats = SuratService::getStatistics();
$disposisiStats = DisposisiService::getStatistics($user['id']);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Welcome Section - Responsive -->
            <div class="mb-6 sm:mb-8">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Selamat Datang, <?= $user['nama_lengkap'] ?>!</h1>
                <p class="text-sm sm:text-base text-gray-600">Role: <?= getRoleLabel($user['role']) ?></p>
            </div>
            
            <!-- Stats Cards - Responsive Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <?php 
                $totalSurat = 0;
                foreach ($suratStats['by_jenis'] as $jenis) {
                    $totalSurat += $jenis['total'];
                }
                ?>
                
                <!-- Total Surat -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm text-gray-600 mb-1 truncate">Total Surat</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?= $totalSurat ?></p>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 ml-2">
                            <i class="fas fa-envelope text-blue-600 text-lg sm:text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Disposisi Masuk -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm text-gray-600 mb-1 truncate">Disposisi Masuk</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?= $disposisiStats['inbox_total'] ?? 0 ?></p>
                            <p class="text-xs text-yellow-600 mt-1 truncate">
                                <i class="fas fa-clock"></i> <?= $disposisiStats['inbox_dikirim'] ?? 0 ?> Menunggu
                            </p>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 ml-2">
                            <i class="fas fa-inbox text-green-600 text-lg sm:text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Disposisi Keluar -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm text-gray-600 mb-1 truncate">Disposisi Keluar</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?= $disposisiStats['outbox_total'] ?? 0 ?></p>
                            <p class="text-xs text-blue-600 mt-1 truncate">
                                <i class="fas fa-check"></i> <?= $disposisiStats['outbox_selesai'] ?? 0 ?> Selesai
                            </p>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0 ml-2">
                            <i class="fas fa-paper-plane text-yellow-600 text-lg sm:text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Arsip -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs sm:text-sm text-gray-600 mb-1 truncate">Arsip Surat</p>
                            <p class="text-2xl sm:text-3xl font-bold text-gray-800"><?= $suratStats['total_arsip'] ?? 0 ?></p>
                        </div>
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0 ml-2">
                            <i class="fas fa-archive text-gray-600 text-lg sm:text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row - Responsive Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <!-- Surat by Jenis -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Surat Berdasarkan Jenis</h2>
                    <div class="space-y-4">
                        <?php foreach ($suratStats['by_jenis'] as $index => $jenis): ?>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs sm:text-sm text-gray-600 truncate flex-1"><?= $jenis['nama_jenis'] ?></span>
                                <span class="text-xs sm:text-sm font-semibold text-gray-800 ml-2"><?= $jenis['total'] ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <?php 
                                $percentage = $totalSurat > 0 ? ($jenis['total'] / $totalSurat * 100) : 0;
                                $colors = ['bg-blue-600', 'bg-green-600', 'bg-yellow-600'];
                                $barColor = $colors[$index % count($colors)];
                                ?>
                                <div class="<?= $barColor ?> h-2 rounded-full transition-all duration-500" style="width: <?= $percentage ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Surat by Status -->
                <div class="bg-white rounded-lg shadow p-4 sm:p-6">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Surat Berdasarkan Status</h2>
                    <div class="space-y-3">
                        <?php 
                        $statusLabels = [
                            'baru' => ['label' => 'Baru', 'icon' => 'fa-plus-circle', 'color' => 'text-blue-600', 'bg' => 'bg-blue-50'],
                            'proses' => ['label' => 'Proses', 'icon' => 'fa-spinner', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-50'],
                            'disetujui' => ['label' => 'Disetujui', 'icon' => 'fa-check-circle', 'color' => 'text-green-600', 'bg' => 'bg-green-50'],
                            'ditolak' => ['label' => 'Ditolak', 'icon' => 'fa-times-circle', 'color' => 'text-red-600', 'bg' => 'bg-red-50']
                        ];
                        
                        $statusCounts = array_column($suratStats['by_status'], 'total', 'status_surat');
                        
                        foreach ($statusLabels as $status => $info):
                            $count = $statusCounts[$status] ?? 0;
                        ?>
                        <div class="flex items-center justify-between p-3 <?= $info['bg'] ?> rounded-lg">
                            <div class="flex items-center min-w-0 flex-1">
                                <i class="fas <?= $info['icon'] ?> <?= $info['color'] ?> mr-2 sm:mr-3 flex-shrink-0"></i>
                                <span class="text-xs sm:text-sm text-gray-700 truncate"><?= $info['label'] ?></span>
                            </div>
                            <span class="text-base sm:text-lg font-semibold text-gray-800 ml-2"><?= $count ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Surat - Responsive -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-800">Surat Terbaru</h2>
                </div>
                
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Agenda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perihal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($suratStats['recent'])): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2 text-gray-300 block"></i>
                                    <p>Belum ada surat</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($suratStats['recent'] as $surat): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $surat['nomor_agenda'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= $surat['nama_jenis'] ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?= truncate($surat['perihal'], 50) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= formatTanggal($surat['tanggal_surat']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($surat['status_surat']) ?>">
                                            <?= ucfirst($surat['status_surat']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="surat_detail.php?id=<?= $surat['id'] ?>" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i> Lihat
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden divide-y divide-gray-200">
                    <?php if (empty($suratStats['recent'])): ?>
                    <div class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                        <p>Belum ada surat</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($suratStats['recent'] as $surat): ?>
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate"><?= $surat['nomor_agenda'] ?></p>
                                    <p class="text-xs text-gray-500"><?= $surat['nama_jenis'] ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($surat['status_surat']) ?> ml-2 flex-shrink-0">
                                    <?= ucfirst($surat['status_surat']) ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-700 mb-2 line-clamp-2"><?= $surat['perihal'] ?></p>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span><i class="far fa-calendar mr-1"></i><?= formatTanggal($surat['tanggal_surat']) ?></span>
                                <a href="surat_detail.php?id=<?= $surat['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="fas fa-eye mr-1"></i>Lihat
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 text-center">
                    <a href="surat.php" class="text-blue-600 hover:text-blue-800 text-xs sm:text-sm font-medium">
                        Lihat Semua Surat <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<?php include 'partials/footer.php'; ?>