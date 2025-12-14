<?php
// public/laporan/laporan_aktivitas.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

requireLogin();
requireRole('superadmin');

$user = getCurrentUser();
$pageTitle = 'Laporan Aktivitas';

// Filter
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-d');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? '';
$page = $_GET['page'] ?? 1;

// Base Condition
$whereClause = "WHERE DATE(l.created_at) BETWEEN ? AND ?";
$params = [$tanggalDari, $tanggalSampai];
$types = 'ss';

if (!empty($userId)) {
    $whereClause .= " AND l.user_id = ?";
    $params[] = $userId;
    $types .= 'i';
}

// 1. Hitung Total Data
$countQuery = "SELECT COUNT(*) as total FROM log_aktivitas l $whereClause";
$countResult = dbSelect($countQuery, $params, $types);
$totalRows = $countResult[0]['total'] ?? 0;

// 2. Setup Pagination
$pagination = new Pagination($totalRows, 10, $page);
$offset = $pagination->getOffset();
$limit = $pagination->getLimit();

// 3. Ambil Data
$query = "SELECT l.*, u.nama_lengkap, u.email
          FROM log_aktivitas l
          JOIN users u ON l.user_id = u.id
          $whereClause
          ORDER BY l.created_at DESC
          LIMIT ? OFFSET ?";

$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$logList = dbSelect($query, $params, $types);

// Get users for filter
$userList = dbSelect("SELECT id, nama_lengkap FROM users WHERE status_aktif = 1 ORDER BY nama_lengkap");
?>

<?php include '../partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include '../partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-4 sm:p-6 lg:p-8">
            <!-- Header Section -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Log Aktivitas</h1>
                    <p class="text-sm sm:text-base text-gray-600">
                        Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?>
                    </p>
                </div>
                <a href="laporan_aktivitas_pdf.php?tanggal_dari=<?= $tanggalDari ?>&tanggal_sampai=<?= $tanggalSampai ?><?= !empty($userId) ? '&user_id=' . $userId : '' ?>" 
                   target="_blank"
                   class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg inline-flex items-center justify-center transition-colors text-sm sm:text-base whitespace-nowrap">
                    <i class="fas fa-file-pdf mr-2"></i>Cetak PDF
                </a>
            </div>
            
            <!-- Filter Section -->
            <div class="bg-white rounded-lg shadow p-4 mb-6 print:hidden">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">User</label>
                            <select name="user_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Semua User</option>
                                <?php foreach ($userList as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nama_lengkap']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
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
            
            <!-- Table Section -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <!-- Desktop Table -->
                <div class="hidden lg:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aktivitas</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($logList)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    Tidak ada log aktivitas untuk periode ini
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logList as $index => $log): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?= $offset + $index + 1 ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <?= formatDateTime($log['created_at']) ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($log['nama_lengkap']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($log['email']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-800">
                                            <?= ucfirst(str_replace('_', ' ', $log['aktivitas'])) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 min-w-[200px]">
                                        <?= htmlspecialchars($log['keterangan'] ?? '-') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden">
                    <?php if (empty($logList)): ?>
                    <div class="p-8 text-center text-gray-500">
                        Tidak ada log aktivitas untuk periode ini
                    </div>
                    <?php else: ?>
                        <?php foreach ($logList as $index => $log): ?>
                        <div class="border-b border-gray-200 p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500 mb-1">No. <?= $offset + $index + 1 ?></div>
                                    <div class="font-semibold text-gray-900 text-sm mb-1">
                                        <?= htmlspecialchars($log['nama_lengkap']) ?>
                                    </div>
                                    <div class="text-xs text-gray-500 mb-2">
                                        <?= htmlspecialchars($log['email']) ?>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-800 ml-2">
                                    <?= ucfirst(str_replace('_', ' ', $log['aktivitas'])) ?>
                                </span>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex">
                                    <span class="text-gray-500 w-20 flex-shrink-0">Waktu:</span>
                                    <span class="text-gray-700"><?= formatDateTime($log['created_at']) ?></span>
                                </div>
                                <div class="flex">
                                    <span class="text-gray-500 w-20 flex-shrink-0">Keterangan:</span>
                                    <span class="text-gray-700 flex-1"><?= htmlspecialchars($log['keterangan'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <div class="print:hidden">
                    <?= $pagination->render(BASE_URL . '/public/laporan/laporan_aktivitas.php', $_GET) ?>
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