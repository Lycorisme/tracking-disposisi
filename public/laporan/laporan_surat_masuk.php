<?php
// public/laporan/laporan_surat_masuk.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../modules/surat/surat_service.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Laporan Surat Masuk';

// Get date filters
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

$filters = [
    'id_jenis' => 1, // Surat Masuk
    'tanggal_dari' => $tanggalDari,
    'tanggal_sampai' => $tanggalSampai,
    'include_arsip' => true
];

$suratList = SuratService::getAll($filters, 1000, 0);
$totalSurat = count($suratList);

// Group by status
$byStatus = [];
foreach ($suratList as $surat) {
    $status = $surat['status_surat'];
    if (!isset($byStatus[$status])) {
        $byStatus[$status] = 0;
    }
    $byStatus[$status]++;
}
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Laporan Surat Masuk</h1>
                    <p class="text-gray-600">Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?></p>
                </div>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg print:hidden">
                    <i class="fas fa-print mr-2"></i>Cetak
                </button>
            </div>
            
            <!-- Filter -->
            <div class="bg-white rounded-lg shadow p-4 mb-6 print:hidden">
                <form method="GET" class="flex flex-col sm:flex-row gap-2">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Dari Tanggal</label>
                        <input type="date" name="tanggal_dari" value="<?= $tanggalDari ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Sampai Tanggal</label>
                        <input type="date" name="tanggal_sampai" value="<?= $tanggalSampai ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Total Surat Masuk</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $totalSurat ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Baru</p>
                    <p class="text-2xl font-bold text-blue-600"><?= $byStatus['baru'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Diproses</p>
                    <p class="text-2xl font-bold text-yellow-600"><?= $byStatus['proses'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Selesai</p>
                    <p class="text-2xl font-bold text-green-600"><?= $byStatus['disetujui'] ?? 0 ?></p>
                </div>
            </div>
            
            <!-- Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No. Agenda</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dari Instansi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Surat</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Diterima</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($suratList)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    Tidak ada data surat masuk untuk periode ini
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($suratList as $index => $surat): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?= $index + 1 ?></td>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900"><?= $surat['nomor_agenda'] ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= $surat['dari_instansi'] ?? '-' ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= truncate($surat['perihal'], 50) ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= formatTanggal($surat['tanggal_surat']) ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= formatTanggal($surat['tanggal_diterima']) ?></td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($surat['status_surat']) ?>">
                                            <?= ucfirst($surat['status_surat']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Footer for Print -->
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