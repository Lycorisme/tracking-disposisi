<?php
// public/laporan/laporan_disposisi.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../modules/disposisi/disposisi_service.php';
require_once __DIR__ . '/../../includes/pagination.php';

requireLogin();
$user = getCurrentUser();
$pageTitle = 'Laporan Disposisi';

// Get filters
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$page = $_GET['page'] ?? 1;

$whereClause = "WHERE DATE(d.tanggal_disposisi) BETWEEN ? AND ?";
$paramsCount = [$tanggalDari, $tanggalSampai];
$typesCount = 'ss';

// 1. Ambil Statistik Global
$statsQuery = "SELECT d.status_disposisi, d.tanggal_disposisi, d.tanggal_respon 
               FROM disposisi d 
               $whereClause";
$statsData = dbSelect($statsQuery, $paramsCount, $typesCount);

$totalDisposisi = count($statsData);
$byStatus = [];
$totalResponseTime = 0;
$respondedCount = 0;

foreach ($statsData as $disp) {
    $status = $disp['status_disposisi'];
    if (!isset($byStatus[$status])) $byStatus[$status] = 0;
    $byStatus[$status]++;

    if ($disp['tanggal_respon']) {
        $sent = strtotime($disp['tanggal_disposisi']);
        $responded = strtotime($disp['tanggal_respon']);
        $diff = $responded - $sent;
        $totalResponseTime += $diff;
        $respondedCount++;
    }
}
$avgResponseHours = $respondedCount > 0 ? round($totalResponseTime / $respondedCount / 3600, 1) : 0;

// 2. Setup Pagination
$pagination = new Pagination($totalDisposisi, 10, $page);
$offset = $pagination->getOffset();
$limit = $pagination->getLimit();

// 3. Ambil Data Tabel
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
          $whereClause
          ORDER BY d.tanggal_disposisi DESC
          LIMIT ? OFFSET ?";

$params = [$tanggalDari, $tanggalSampai, $limit, $offset];
$types = 'ssii';

$disposisiList = dbSelect($query, $params, $types);
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Header Section -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Laporan Disposisi</h1>
                        <p class="text-sm sm:text-base text-gray-600">
                            Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?>
                        </p>
                    </div>
                    <a href="laporan_disposisi_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?>" 
                       target="_blank"
                       class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center transition-colors text-sm sm:text-base whitespace-nowrap">
                        <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                    </a>
                </div>
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
                                    class="w-full bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                                <i class="fas fa-filter mr-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-gray-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Total Disposisi</p>
                    <p class="text-xl sm:text-2xl font-bold text-gray-800"><?= $totalDisposisi ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-blue-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Dikirim</p>
                    <p class="text-xl sm:text-2xl font-bold text-blue-600"><?= $byStatus['dikirim'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-yellow-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Diproses</p>
                    <p class="text-xl sm:text-2xl font-bold text-yellow-600"><?= $byStatus['diproses'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-green-500">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Selesai</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-600"><?= $byStatus['selesai'] ?? 0 ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-3 sm:p-4 border-l-4 border-purple-500 col-span-2 sm:col-span-3 lg:col-span-1">
                    <p class="text-xs sm:text-sm text-gray-600 mb-1">Avg Response</p>
                    <p class="text-xl sm:text-2xl font-bold text-purple-600"><?= $avgResponseHours ?>h</p>
                </div>
            </div>
            
            <!-- Table Section -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Agenda</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dari</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kepada</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Disposisi</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tgl Respon</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($disposisiList)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    Tidak ada data disposisi untuk periode ini
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($disposisiList as $index => $disp): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?= $offset + $index + 1 ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($disp['nomor_agenda']) ?></div>
                                        <div class="text-xs text-gray-500 truncate max-w-[200px]">
                                            <?= htmlspecialchars(truncate($disp['perihal'], 30)) ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($disp['dari_user_nama']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($disp['ke_user_nama']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= formatDateTime($disp['tanggal_disposisi']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <?= $disp['tanggal_respon'] ? formatDateTime($disp['tanggal_respon']) : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
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

                <!-- Mobile Card View -->
                <div class="lg:hidden">
                    <?php if (empty($disposisiList)): ?>
                    <div class="p-8 text-center text-gray-500">
                        Tidak ada data disposisi untuk periode ini
                    </div>
                    <?php else: ?>
                        <?php foreach ($disposisiList as $index => $disp): ?>
                        <div class="border-b border-gray-200 p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500 mb-1">No. <?= $offset + $index + 1 ?></div>
                                    <div class="font-semibold text-gray-900 text-sm mb-1">
                                        <?= htmlspecialchars($disp['nomor_agenda']) ?>
                                    </div>
                                    <div class="text-xs text-gray-600 mb-2">
                                        <?= htmlspecialchars(truncate($disp['perihal'], 50)) ?>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getDisposisiStatusBadge($disp['status_disposisi']) ?> ml-2">
                                    <?= ucfirst($disp['status_disposisi']) ?>
                                </span>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex">
                                    <span class="text-gray-500 w-24 flex-shrink-0">Dari:</span>
                                    <span class="text-gray-900 font-medium"><?= htmlspecialchars($disp['dari_user_nama']) ?></span>
                                </div>
                                <div class="flex">
                                    <span class="text-gray-500 w-24 flex-shrink-0">Kepada:</span>
                                    <span class="text-gray-900 font-medium"><?= htmlspecialchars($disp['ke_user_nama']) ?></span>
                                </div>
                                <div class="flex">
                                    <span class="text-gray-500 w-24 flex-shrink-0">Tgl Disposisi:</span>
                                    <span class="text-gray-700"><?= formatDateTime($disp['tanggal_disposisi']) ?></span>
                                </div>
                                <div class="flex">
                                    <span class="text-gray-500 w-24 flex-shrink-0">Tgl Respon:</span>
                                    <span class="text-gray-700">
                                        <?= $disp['tanggal_respon'] ? formatDateTime($disp['tanggal_respon']) : '-' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <div class="print:hidden">
                    <?= $pagination->render(BASE_URL . '/public/laporan/laporan_disposisi.php', $_GET) ?>
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