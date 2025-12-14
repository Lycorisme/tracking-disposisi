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

$filters = [
    'ke_user_id' => $user['id'],
    'status_disposisi' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalDisposisi = DisposisiService::count($filters);
$pagination = new Pagination($totalDisposisi, $perPage, $page);

$disposisiList = DisposisiService::getAll($filters, $perPage, $offset);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Disposisi Masuk</h1>
                <p class="text-sm sm:text-base text-gray-600">Daftar disposisi yang dikirim kepada Anda</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 mb-4 sm:mb-6">
                <form method="GET" class="space-y-3 sm:space-y-0 sm:flex sm:gap-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="Cari nomor surat, perihal..." 
                           class="w-full sm:flex-1 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    
                    <select name="status" class="w-full sm:w-auto px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Semua Status</option>
                        <option value="dikirim" <?= $filters['status_disposisi'] == 'dikirim' ? 'selected' : '' ?>>Dikirim</option>
                        <option value="diterima" <?= $filters['status_disposisi'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="diproses" <?= $filters['status_disposisi'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $filters['status_disposisi'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="ditolak" <?= $filters['status_disposisi'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 sm:flex-none bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-search"></i><span class="ml-2 sm:hidden">Cari</span>
                        </button>
                        
                        <?php if (!empty($filters['search']) || !empty($filters['status_disposisi'])): ?>
                        <a href="disposisi_inbox.php" class="flex-1 sm:flex-none bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors">
                            <i class="fas fa-times"></i><span class="ml-2 sm:hidden">Reset</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
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
                                <tr class="hover:bg-gray-50 transition-colors">
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
                                               class="text-primary-600 hover:text-primary-800 transition-colors" 
                                               title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($disp['status_disposisi'] === 'dikirim' || $disp['status_disposisi'] === 'diterima' || $disp['status_disposisi'] === 'diproses'): ?>
                                            <button onclick='openUpdateModal(<?= json_encode($disp) ?>)' 
                                                    class="text-green-600 hover:text-green-800 transition-colors" 
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
                
                <?php if ($pagination->hasPages()): ?>
                <div class="border-t border-gray-200 px-4 py-3">
                    <?= $pagination->render('disposisi_inbox.php', ['status' => $filters['status_disposisi'], 'search' => $filters['search']]) ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="lg:hidden space-y-4">
                <?php if (empty($disposisiList)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-5xl mb-3 text-gray-300"></i>
                    <p>Tidak ada disposisi masuk</p>
                </div>
                <?php else: ?>
                    <?php foreach ($disposisiList as $disp): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                    <?= ucfirst($disp['status_disposisi']) ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-user mr-1"></i><?= $disp['dari_user_nama'] ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm font-semibold text-gray-900"><?= $disp['nomor_agenda'] ?></p>
                                <p class="text-xs text-gray-500 line-clamp-2"><?= $disp['perihal'] ?></p>
                            </div>
                            
                            <?php if ($disp['catatan']): ?>
                            <div class="mb-3 p-2 bg-gray-50 rounded text-xs text-gray-700">
                                <i class="fas fa-comment-dots mr-1"></i>
                                <?= truncate($disp['catatan'], 100) ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-xs text-gray-500 mb-3">
                                <i class="far fa-clock mr-1"></i>
                                <?= formatDateTime($disp['tanggal_disposisi']) ?>
                            </div>
                            
                            <div class="flex gap-2">
                                <a href="surat_detail.php?id=<?= $disp['id_surat'] ?>" 
                                   class="flex-1 bg-primary-50 text-primary-600 hover:bg-primary-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-eye mr-1"></i>Lihat
                                </a>
                                
                                <?php if ($disp['status_disposisi'] === 'dikirim' || $disp['status_disposisi'] === 'diterima' || $disp['status_disposisi'] === 'diproses'): ?>
                                <button onclick='openUpdateModal(<?= json_encode($disp) ?>)' 
                                        class="flex-1 bg-green-50 text-green-600 hover:bg-green-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-check-circle mr-1"></i>Update
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($pagination->hasPages()): ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <?= $pagination->render('disposisi_inbox.php', ['status' => $filters['status_disposisi'], 'search' => $filters['search']]) ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-semibold text-gray-800">Update Status Disposisi</h3>
            </div>
            
            <form id="updateDisposisiForm" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="disposisiId">
                <input type="hidden" name="redirect" value="disposisi_inbox.php">
                
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                        <p class="text-xs sm:text-sm text-gray-600 mb-1">Nomor Agenda:</p>
                        <p class="text-sm sm:text-base font-semibold text-gray-800" id="modalNomorAgenda"></p>
                        <p class="text-xs sm:text-sm text-gray-600 mt-2 mb-1">Perihal:</p>
                        <p class="text-xs sm:text-sm text-gray-800" id="modalPerihal"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" id="statusSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="diterima">Diterima</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                        <textarea name="catatan" rows="3" 
                                  placeholder="Tambahkan catatan untuk update status ini..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                </div>
                
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-0 sm:space-x-2">
                    <button type="button" onclick="closeUpdateModal()" 
                            class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="w-full sm:w-auto px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const disposisiHandlerPath = '../modules/disposisi/disposisi_handler.php';

function openUpdateModal(disposisi) {
    document.getElementById('disposisiId').value = disposisi.id;
    document.getElementById('modalNomorAgenda').textContent = disposisi.nomor_agenda;
    document.getElementById('modalPerihal').textContent = disposisi.perihal;
    
    const statusSelect = document.getElementById('statusSelect');
    if (disposisi.status_disposisi === 'dikirim') {
        statusSelect.value = 'diterima';
    } else if (disposisi.status_disposisi === 'diterima') {
        statusSelect.value = 'diproses';
    } else {
        statusSelect.value = 'selesai';
    }
    
    document.getElementById('updateDisposisiForm').action = disposisiHandlerPath;
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

document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});
</script>

<?php include 'partials/footer.php'; ?>