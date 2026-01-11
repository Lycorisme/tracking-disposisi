<?php
// public/laporan/laporan_surat_keluar.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Laporan Surat Keluar';

// --- Filter Logic ---
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$page = $_GET['page'] ?? 1;
$limit = 10;

// Base Query Condition (Surat Keluar id_jenis = 2)
$whereClause = "WHERE s.id_jenis = 2 AND DATE(s.tanggal_surat) BETWEEN ? AND ?";
$params = [$tanggalDari, $tanggalSampai];
$types = 'ss';

// --- 1. Stats Global ---
$statsQuery = "SELECT s.status_surat, COUNT(*) as total 
               FROM surat s 
               $whereClause 
               GROUP BY s.status_surat";
$statsData = dbSelect($statsQuery, $params, $types);

$totalSurat = 0;
$byStatus = [];
foreach ($statsData as $stat) {
    $byStatus[$stat['status_surat']] = $stat['total'];
    $totalSurat += $stat['total'];
}

// --- 2. Pagination ---
$countQuery = "SELECT COUNT(*) as total FROM surat s $whereClause";
$countResult = dbSelect($countQuery, $params, $types);
$totalRows = $countResult[0]['total'] ?? 0;

$pagination = new Pagination($totalRows, $limit, $page);
$offset = $pagination->getOffset();

// --- 3. Fetch Data ---
$query = "SELECT s.* FROM surat s 
          $whereClause 
          ORDER BY s.tanggal_surat DESC, s.created_at DESC 
          LIMIT ? OFFSET ?";

$paramsQuery = array_merge($params, [$limit, $offset]);
$typesQuery = $types . 'ii';

$suratList = dbSelect($query, $paramsQuery, $typesQuery);
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Header Section -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Laporan Surat Keluar</h1>
                    <p class="text-sm sm:text-base text-gray-600">
                        Periode: <span class="font-medium"><?= formatTanggal($tanggalDari) ?></span> s/d <span class="font-medium"><?= formatTanggal($tanggalSampai) ?></span>
                    </p>
                </div>
                
                <a href="laporan_surat_keluar_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?>" 
                   target="_blank"
                   class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center transition-colors text-sm sm:text-base whitespace-nowrap">
                    <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                </a>
            </div>
            
            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow p-4 mb-6 print:hidden">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Dari Tanggal</label>
                            <input type="date" 
                                   name="tanggal_dari" 
                                   value="<?= $tanggalDari ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                            <input type="date" 
                                   name="tanggal_sampai" 
                                   value="<?= $tanggalSampai ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-1">&nbsp;</label>
                            <button type="submit" 
                                    class="w-full bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-filter mr-2"></i>Filter Data
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-gray-600">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Total Surat Keluar</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800"><?= $totalSurat ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-blue-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Baru</p>
                    <p class="text-xl sm:text-2xl font-bold text-blue-600"><?= $byStatus['baru'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-yellow-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Diproses</p>
                    <p class="text-xl sm:text-2xl font-bold text-yellow-600"><?= $byStatus['proses'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-green-500 col-span-2 sm:col-span-2 lg:col-span-1">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Disetujui/Selesai</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-600"><?= ($byStatus['disetujui'] ?? 0) + ($byStatus['selesai'] ?? 0) ?></p>
                </div>
            </div>
            
            <!-- Table Section -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Agenda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ke Instansi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Surat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($suratList)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-paper-plane text-4xl mb-3 text-gray-300"></i>
                                        <p>Tidak ada data surat keluar untuk periode ini</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($suratList as $index => $surat): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-900"><?= $offset + $index + 1 ?></td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($surat['nomor_agenda']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars($surat['ke_instansi'] ?? '-') ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate" title="<?= htmlspecialchars($surat['perihal']) ?>">
                                        <?= htmlspecialchars(truncate($surat['perihal'], 40)) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700"><?= formatTanggal($surat['tanggal_surat']) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full <?= getStatusBadge($surat['status_surat']) ?>">
                                            <?= ucfirst($surat['status_surat']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden">
                    <?php if (empty($suratList)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-paper-plane text-4xl mb-3 text-gray-300"></i>
                            <p>Tidak ada data surat keluar untuk periode ini</p>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($suratList as $index => $surat): ?>
                        <div class="border-b border-gray-200 p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500 mb-1">No. <?= $offset + $index + 1 ?></div>
                                    <div class="font-semibold text-gray-900 text-sm mb-1">
                                        <?= htmlspecialchars($surat['nomor_agenda']) ?>
                                    </div>
                                    <div class="text-xs text-gray-600 mb-2">
                                        <?= htmlspecialchars(truncate($surat['perihal'], 60)) ?>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= getStatusBadge($surat['status_surat']) ?> ml-2">
                                    <?= ucfirst($surat['status_surat']) ?>
                                </span>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex">
                                    <span class="text-gray-500 w-24 flex-shrink-0">Ke Instansi:</span>
                                    <span class="text-gray-900 font-medium"><?= htmlspecialchars($surat['ke_instansi'] ?? '-') ?></span>
                                </div>
                                <div class="flex">
                                    <span class="text-gray-500 w-24 flex-shrink-0">Tgl Surat:</span>
                                    <span class="text-gray-700"><?= formatTanggal($surat['tanggal_surat']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <div class="bg-gray-50 border-t border-gray-200 print:hidden">
                    <?= $pagination->render(BASE_URL . '/laporan/laporan_surat_keluar.php', $_GET) ?>
                </div>
            </div>
            
            <!-- Print Info -->
            <div class="hidden print:block mt-8 text-sm text-gray-600">
                <p>Dicetak pada: <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
                <p>Dicetak oleh: <?= $user['nama_lengkap'] ?></p>
            </div>
        </main>
        
        <?php include '../partials/footer.php'; ?>
    </div>
</div>

<style>
@media print {
    .print\:hidden { display: none !important; }
    .print\:block { display: block !important; }
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
}
</style>