<?php
// public/laporan/laporan_aktivitas.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireLogin();
requireRole('superadmin');

$user = getCurrentUser();
$pageTitle = 'Laporan Aktivitas';

$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-d');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? '';

$query = "SELECT l.*, u.nama_lengkap, u.email
          FROM log_aktivitas l
          JOIN users u ON l.user_id = u.id
          WHERE DATE(l.created_at) BETWEEN ? AND ?";

$params = [$tanggalDari, $tanggalSampai];
$types = 'ss';

if (!empty($userId)) {
    $query .= " AND l.user_id = ?";
    $params[] = $userId;
    $types .= 'i';
}

$query .= " ORDER BY l.created_at DESC LIMIT 500";

$logList = dbSelect($query, $params, $types);

// Get users for filter
$userList = dbSelect("SELECT id, nama_lengkap FROM users WHERE status_aktif = 1 ORDER BY nama_lengkap");

// Group by activity type
$byActivity = [];
foreach ($logList as $log) {
    $act = $log['aktivitas'];
    if (!isset($byActivity[$act])) $byActivity[$act] = 0;
    $byActivity[$act]++;
}
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Log Aktivitas</h1>
                    <p class="text-gray-600">Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?></p>
                </div>
                <a href="laporan_aktivitas_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?><?= !empty($userId) ? '&user_id=' . $userId : '' ?>" 
                   target="_blank"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                </a>
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
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">User</label>
                        <select name="user_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua User</option>
                            <?php foreach ($userList as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nama_lengkap']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Ringkasan Aktivitas</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($byActivity as $activity => $count): ?>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-600 mb-1"><?= ucfirst(str_replace('_', ' ', $activity)) ?></p>
                        <p class="text-xl font-bold text-gray-800"><?= $count ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aktivitas</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($logList)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada log aktivitas untuk periode ini</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logList as $index => $log): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-900"><?= $index + 1 ?></td>
                                    <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
                                        <?= formatDateTime($log['created_at']) ?>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($log['nama_lengkap']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($log['email']) ?></div>
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            <?= ucfirst(str_replace('_', ' ', $log['aktivitas'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-700">
                                        <?= htmlspecialchars($log['keterangan'] ?? '-') ?>
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
                <p class="mt-2 text-xs">Catatan: Maksimal 500 log terbaru ditampilkan</p>
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