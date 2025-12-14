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

// Ambil Statistik
$suratStats = SuratService::getStatistics();
$disposisiStats = DisposisiService::getStatistics($user['id']);

// Ambil 5 surat terbaru
$recentSurat = SuratService::getAll([], 5, 0);

// --- LOGIKA TAMBAHAN UNTUK DASHBOARD ---

// 1. Hitung Surat Masuk & Keluar dari data 'by_jenis'
$totalSuratMasuk = 0;
$totalSuratKeluar = 0;
$totalSurat = 0;

if (!empty($suratStats['by_jenis'])) {
    foreach ($suratStats['by_jenis'] as $jenis) {
        $nama = strtolower($jenis['nama_jenis']);
        if (strpos($nama, 'masuk') !== false) {
            $totalSuratMasuk += $jenis['total'];
        } elseif (strpos($nama, 'keluar') !== false) {
            $totalSuratKeluar += $jenis['total'];
        }
        $totalSurat += $jenis['total'];
    }
}

// Masukkan ke array agar bisa dipanggil di HTML
$suratStats['masuk'] = $totalSuratMasuk;
$suratStats['keluar'] = $totalSuratKeluar;

// 2. Hitung Disposisi Pending (Inbox yang belum selesai/dibaca)
// Asumsi: inbox_dikirim atau inbox_total adalah yang perlu tindakan
$totalDisposisiPending = $disposisiStats['inbox_dikirim'] ?? ($disposisiStats['inbox_total'] ?? 0);
$disposisiStats['pending'] = $totalDisposisiPending;


// --- PERSIAPAN DATA CHART ---

// Data Chart Jenis Surat
$jenisLabels = [];
$jenisData = [];
if (!empty($suratStats['by_jenis'])) {
    foreach ($suratStats['by_jenis'] as $jenis) {
        $jenisLabels[] = $jenis['nama_jenis'];
        $jenisData[] = $jenis['total'];
    }
}

// Data Chart Status Surat
$statusLabels = [];
$statusData = [];
$statusOrder = ['baru', 'proses', 'disetujui', 'ditolak'];
$tempStatus = array_column($suratStats['by_status'] ?? [], 'total', 'status_surat');

foreach ($statusOrder as $st) {
    $statusLabels[] = ucfirst($st);
    $statusData[] = $tempStatus[$st] ?? 0;
}
?>

<?php include 'partials/header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        Halo, <span class="text-primary-600"><?= htmlspecialchars($user['nama_lengkap']) ?></span> 👋
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <?= date('l, d F Y') ?> &bull; Bagian: <?= htmlspecialchars($user['nama_bagian'] ?? '-') ?>
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="surat_tambah.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-all shadow-sm hover:shadow-md">
                        <i class="fas fa-plus mr-2"></i> Buat Surat
                    </a>
                    <a href="disposisi_inbox.php" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition-all shadow-sm">
                        <i class="fas fa-inbox mr-2"></i> Cek Inbox
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-primary-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Surat Masuk</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1"><?= $suratStats['masuk'] ?? 0 ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center text-primary-600 shadow-sm">
                            <i class="fas fa-envelope text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-green-600 bg-green-50 w-fit px-2 py-1 rounded-full">
                        <i class="fas fa-check-circle mr-1"></i> Data Terupdate
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Surat Keluar</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1"><?= $suratStats['keluar'] ?? 0 ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 shadow-sm">
                            <i class="fas fa-paper-plane text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-gray-500">
                        Total semua surat keluar
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-amber-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Perlu Tindakan</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1"><?= $disposisiStats['pending'] ?? 0 ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 shadow-sm">
                            <i class="fas fa-bell text-xl <?= (($disposisiStats['pending'] ?? 0) > 0) ? 'animate-bounce' : '' ?>"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-amber-600 bg-amber-50 w-fit px-2 py-1 rounded-full">
                        <i class="fas fa-clock mr-1"></i> Disposisi Pending
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gray-100 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex justify-between items-start">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Arsip</p>
                            <h3 class="text-3xl font-bold text-gray-800 mt-1"><?= $suratStats['total_arsip'] ?? 0 ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-gray-200 rounded-xl flex items-center justify-center text-gray-600 shadow-sm">
                            <i class="fas fa-archive text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-xs text-gray-500">
                        Arsip digital tersimpan
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                
                <div class="xl:col-span-2 space-y-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <div class="flex items-center gap-2">
                                <div class="w-1 h-6 bg-primary-600 rounded-full"></div>
                                <h2 class="text-lg font-bold text-gray-800">Surat Terbaru</h2>
                            </div>
                            <a href="surat.php" class="text-sm text-primary-600 hover:text-primary-800 font-medium hover:underline">
                                Lihat Semua
                            </a>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Surat</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenis</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <?php if (empty($recentSurat)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center justify-center text-gray-400">
                                                    <i class="fas fa-inbox text-4xl mb-3 opacity-30"></i>
                                                    <p class="text-sm">Belum ada data surat terbaru.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recentSurat as $surat): ?>
                                        <tr class="hover:bg-gray-50/80 transition-colors group">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 group-hover:bg-primary-50 group-hover:text-primary-600 transition-colors">
                                                        <i class="fas fa-file-alt"></i>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($surat['nomor_agenda']) ?></div>
                                                        <div class="text-xs text-gray-500 max-w-[200px] truncate" title="<?= htmlspecialchars($surat['perihal']) ?>">
                                                            <?= htmlspecialchars($surat['perihal']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-600"><?= htmlspecialchars($surat['nama_jenis']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-600"><?= date('d M Y', strtotime($surat['tanggal_surat'])) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php
                                                $badgeClass = match($surat['status_surat']) {
                                                    'baru' => 'bg-primary-50 text-primary-700 border border-primary-100',
                                                    'proses' => 'bg-yellow-50 text-yellow-700 border border-yellow-100',
                                                    'disetujui' => 'bg-green-50 text-green-700 border border-green-100',
                                                    'ditolak' => 'bg-red-50 text-red-700 border border-red-100',
                                                    'arsip' => 'bg-gray-50 text-gray-700 border border-gray-200',
                                                    default => 'bg-gray-50 text-gray-700'
                                                };
                                                ?>
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $badgeClass ?>">
                                                    <?= ucfirst($surat['status_surat']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <a href="surat_detail.php?id=<?= $surat['id'] ?>" class="text-gray-400 hover:text-primary-600 transition-colors">
                                                    <i class="fas fa-eye text-lg"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="xl:col-span-1 space-y-6">
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Status Surat</h3>
                        <div class="relative h-48">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="mt-4 text-center text-xs text-gray-400">
                            Distribusi status surat terkini
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Kategori Surat</h3>
                        <div class="relative h-48">
                            <canvas id="jenisChart"></canvas>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart 1: Donut Chart Status
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusLabels) ?>,
            datasets: [{
                data: <?= json_encode($statusData) ?>,
                backgroundColor: [
                    '#3b82f6', // Baru
                    '#eab308', // Proses
                    '#22c55e', // Disetujui
                    '#ef4444'  // Ditolak
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } }
                }
            },
            cutout: '70%'
        }
    });

    // Chart 2: Bar Chart Jenis
    const ctxJenis = document.getElementById('jenisChart').getContext('2d');
    new Chart(ctxJenis, {
        type: 'bar',
        data: {
            labels: <?= json_encode($jenisLabels) ?>,
            datasets: [{
                label: 'Jumlah',
                data: <?= json_encode($jenisData) ?>,
                backgroundColor: '#6366f1',
                borderRadius: 4,
                barThickness: 20
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    ticks: { display: false }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>