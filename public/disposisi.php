<?php
// public/disposisi.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../modules/disposisi/disposisi_service.php';

requireLogin();
requireRole(['admin', 'superadmin']);

$user = getCurrentUser();
$pageTitle = 'Semua Disposisi';

$filters = [
    'status_disposisi' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalDisposisi = DisposisiService::count($filters);
$pagination = new Pagination($totalDisposisi, $perPage, $page);

$disposisiList = DisposisiService::getAll($filters, $perPage, $offset);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Semua Disposisi</h1>
                <p class="text-gray-600">Monitoring semua disposisi surat</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="Cari nomor surat, perihal..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    
                    <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="dikirim" <?= $filters['status_disposisi'] == 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                        <option value="diterima" <?= $filters['status_disposisi'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="diproses" <?= $filters['status_disposisi'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $filters['status_disposisi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="ditolak" <?= $filters['status_disposisi'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                    
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-search"></i>
                    </button>
                    
                    <?php if (!empty($filters['search']) || !empty($filters['status_disposisi'])): ?>
                    <a href="disposisi.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Surat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($disposisiList)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-exchange-alt text-5xl mb-3 text-gray-300"></i>
                                    <p>Tidak ada data disposisi</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($disposisiList as $disp): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <span class="font-medium text-gray-900"><?= $disp['dari_user_nama'] ?></span>
                                            <i class="fas fa-arrow-right text-gray-400 mx-2"></i>
                                            <span class="font-medium text-gray-900"><?= $disp['ke_user_nama'] ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= $disp['nomor_agenda'] ?></div>
                                        <div class="text-xs text-gray-500"><?= truncate($disp['perihal'], 40) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-700 max-w-xs">
                                            <?= $disp['catatan'] ? truncate($disp['catatan'], 50) : '-' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= formatDateTime($disp['tanggal_disposisi']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                            <?= ucfirst($disp['status_disposisi']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <a href="surat_detail.php?id=<?= $disp['id_surat'] ?>" 
                                           class="text-blue-600 hover:text-blue-800" 
                                           title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($pagination->hasPages()): ?>
                <div class="border-t border-gray-200">
                    <?= $pagination->render('disposisi.php', ['status' => $filters['status_disposisi'], 'search' => $filters['search']]) ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>