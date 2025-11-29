<?php
// public/disposisi_inbox.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../modules/disposisi/disposisi_service.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Disposisi Masuk';

// Get filters
$filters = [
    'ke_user_id' => $user['id'],
    'status_disposisi' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination
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
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Disposisi Masuk</h1>
                <p class="text-gray-600">Daftar disposisi yang dikirim kepada Anda</p>
            </div>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" class="flex flex-col sm:flex-row gap-2">
                    <!-- Search -->
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="Cari nomor surat, perihal..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    
                    <!-- Status Filter -->
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
                    <a href="disposisi_inbox.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dari</th>
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
                                    <i class="fas fa-inbox text-5xl mb-3 text-gray-300"></i>
                                    <p>Tidak ada disposisi masuk</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($disposisiList as $disp): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= $disp['dari_user_nama'] ?></div>
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
                                        <div class="flex space-x-2">
                                            <a href="surat_detail.php?id=<?= $disp['id_surat'] ?>" 
                                               class="text-blue-600 hover:text-blue-800" 
                                               title="Lihat Surat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($disp['status_disposisi'] === 'dikirim' || $disp['status_disposisi'] === 'diterima' || $disp['status_disposisi'] === 'diproses'): ?>
                                            <button onclick='openUpdateModal(<?= json_encode($disp) ?>)' 
                                                    class="text-green-600 hover:text-green-800" 
                                                    title="Update Status">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($pagination->hasPages()): ?>
                <div class="border-t border-gray-200">
                    <?= $pagination->render('disposisi_inbox.php', ['status' => $filters['status_disposisi'], 'search' => $filters['search']]) ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<!-- Modal Update Status -->
<div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Update Status Disposisi</h3>
            </div>
            
            <form method="POST" action="modules/disposisi/disposisi_handler.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="disposisiId">
                <input type="hidden" name="redirect" value="disposisi_inbox.php">
                
                <div class="px-6 py-4 space-y-4">
                    <!-- Info Surat -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-1">Nomor Agenda:</p>
                        <p class="font-semibold text-gray-800" id="modalNomorAgenda"></p>
                        <p class="text-sm text-gray-600 mt-2 mb-1">Perihal:</p>
                        <p class="text-sm text-gray-800" id="modalPerihal"></p>
                    </div>
                    
                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" id="statusSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="diterima">Diterima</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    
                    <!-- Catatan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                        <textarea name="catatan" rows="3" 
                                  placeholder="Tambahkan catatan untuk update status ini..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button type="button" onclick="closeUpdateModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openUpdateModal(disposisi) {
    document.getElementById('disposisiId').value = disposisi.id;
    document.getElementById('modalNomorAgenda').textContent = disposisi.nomor_agenda;
    document.getElementById('modalPerihal').textContent = disposisi.perihal;
    
    // Set current status as default
    const statusSelect = document.getElementById('statusSelect');
    if (disposisi.status_disposisi === 'dikirim') {
        statusSelect.value = 'diterima';
    } else if (disposisi.status_disposisi === 'diterima') {
        statusSelect.value = 'diproses';
    } else {
        statusSelect.value = 'selesai';
    }
    
    document.getElementById('updateModal').classList.remove('hidden');
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUpdateModal();
    }
});
</script>