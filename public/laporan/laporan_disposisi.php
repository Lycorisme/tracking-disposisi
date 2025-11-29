<?php
// public/laporan/laporan_disposisi.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../modules/disposisi/disposisi_service.php';

requireLogin();
$user = getCurrentUser();
$pageTitle = 'Laporan Disposisi';

// Get filters
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

// Get all disposisi within date range
$query = "SELECT d.*, 
                 s.nomor_agenda, s.nomor_surat, s.perihal,
                 js.nama_jenis,
                 u1.nama_lengkap as dari_user_nama,
                 u2.nama_lengkap as ke_user_nama
          FROM disposisi d
          JOIN surat s ON d.id_surat = s.id
          JOIN jenis_surat js ON s.id_jenis = js.id
          JOIN users u1 ON d.dari_user_id = u1.id
          JOIN users u2 ON d.ke_user_id = u2.id
          WHERE DATE(d.tanggal_disposisi) BETWEEN ? AND ?
          ORDER BY d.tanggal_disposisi DESC";

$disposisiList = dbSelect($query, [$tanggalDari, $tanggalSampai], 'ss');
$totalDisposisi = count($disposisiList);

// Group by status
$byStatus = [];
foreach ($disposisiList as $disp) {
    $status = $disp['status_disposisi'];
    if (!isset($byStatus[$status])) $byStatus[$status] = 0;
    $byStatus[$status]++;
}

// Calculate average response time
$totalResponseTime = 0;
$respondedCount = 0;
foreach ($disposisiList as $disp) {
    if ($disp['tanggal_respon']) {
        $sent = strtotime($disp['tanggal_disposisi']);
        $responded = strtotime($disp['tanggal_respon']);
        $diff = $responded - $sent;
        $totalResponseTime += $diff;
        $respondedCount++;
    }
}
$avgResponseHours = $respondedCount > 0 ? round($totalResponseTime / $respondedCount / 3600, 1) : 0;
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Laporan Disposisi</h1>
                    <p class="text-gray-600">Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?></p>
                </div>
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg print:hidden">
                    <i class="fas fa-print mr-2"></i>Cetak
                </button>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 mb-6 print:hidden">
                <form method="GET" class="flex flex-col sm:flex-row gap-2">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Dari Tanggal</label>
                        <input type="date" name="tanggal_dari" value="<?= $tanggalDari ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Sampai Tanggal</label>
                        <input type="date" name="tanggal_sampai" value="<?= $tanggalSampai ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Total Disposisi</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $totalDisposisi ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Dikirim</p>
                    <p class="text-2xl font-bold text-blue-600"><?= $byStatus['dikirim'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Diproses</p>
                    <p class="text-2xl font-bold text-yellow-600"><?= $byStatus['diproses'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Selesai</p>
                    <p class="text-2xl font-bold text-green-600"><?= $byStatus['selesai'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600 mb-1">Avg Response</p>
                    <p class="text-2xl font-bold text-purple-600"><?= $avgResponseHours ?>h</p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No. Agenda</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dari</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kepada</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Disposisi</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Respon</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($disposisiList)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada data disposisi untuk periode ini</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($disposisiList as $index => $disp): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?= $index + 1 ?></td>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                        <?= $disp['nomor_agenda'] ?>
                                        <div class="text-xs text-gray-500"><?= truncate($disp['perihal'], 30) ?></div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= $disp['dari_user_nama'] ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= $disp['ke_user_nama'] ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700"><?= formatDateTime($disp['tanggal_disposisi']) ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        <?= $disp['tanggal_respon'] ? formatDateTime($disp['tanggal_respon']) : '-' ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                            <?= ucfirst($disp['status_disposisi']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
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