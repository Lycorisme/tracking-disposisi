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
$userId = $user['id'];
$userRole = $user['id_role'] ?? 3;
$pageTitle = 'Disposisi Masuk';

// Filter disposisi - hanya yang ditujukan ke user ini
$filters = [
    'ke_user_id' => $userId, // PENTING: Filter by user
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

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Disposisi Masuk</h1>
                <p class="text-sm sm:text-base text-gray-600">Disposisi surat yang ditujukan kepada Anda</p>
            </div>
            
            <!-- Filter Form -->
            <div class="bg-white rounded-lg shadow p-4 mb-4 sm:mb-6">
                <form method="GET" class="space-y-3 sm:space-y-0 sm:flex sm:gap-2">
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>"
                           placeholder="Cari nomor surat, perihal..." 
                           class="w-full sm:flex-1 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    
                    <select name="status" class="w-full sm:w-auto px-3 sm:px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
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
            
            <!-- Desktop Table -->
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
                        <tbody class="bg-white divide-y divide-gray-200" id="disposisiTableBody">
                            <?php if (empty($disposisiList)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-5xl mb-3 text-gray-300"></i>
                                    <p>Tidak ada disposisi masuk</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($disposisiList as $disp): 
                                    // Background color untuk row
                                    $rowBgClass = '';
                                    if ($disp['status_disposisi'] === 'ditolak') {
                                        $rowBgClass = 'bg-red-50';
                                    } elseif ($disp['status_disposisi'] === 'selesai') {
                                        $rowBgClass = 'bg-green-50';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors <?= $rowBgClass ?>" id="row-<?= $disp['id'] ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($disp['dari_user_nama']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($disp['nomor_agenda']) ?></div>
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
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full status-badge-<?= $disp['id'] ?> <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                            <span class="status-text-<?= $disp['id'] ?>"><?= ucfirst($disp['status_disposisi']) ?></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex space-x-2">
                                            <a href="surat_detail.php?id=<?= $disp['id_surat'] ?>" class="text-primary-600 hover:text-primary-800 transition-colors" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if (in_array($disp['status_disposisi'], ['dikirim', 'diterima', 'diproses'])): ?>
                                            <button onclick='openUpdateModal(<?= json_encode($disp) ?>)' class="text-green-600 hover:text-green-800 transition-colors" title="Update Status">
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

            <!-- Mobile Card View -->
            <div class="lg:hidden space-y-4" id="disposisiMobileContainer">
                <?php if (empty($disposisiList)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-5xl mb-3 text-gray-300"></i>
                    <p>Tidak ada disposisi masuk</p>
                </div>
                <?php else: ?>
                    <?php foreach ($disposisiList as $disp): 
                        // Background color untuk card mobile
                        $cardBgClass = '';
                        if ($disp['status_disposisi'] === 'ditolak') {
                            $cardBgClass = 'border-l-4 border-red-500 bg-red-50';
                        } elseif ($disp['status_disposisi'] === 'selesai') {
                            $cardBgClass = 'border-l-4 border-green-500 bg-green-50';
                        }
                    ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden <?= $cardBgClass ?>" id="card-<?= $disp['id'] ?>">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full status-badge-<?= $disp['id'] ?> <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                    <span class="status-text-<?= $disp['id'] ?>"><?= ucfirst($disp['status_disposisi']) ?></span>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-user mr-1"></i><?= htmlspecialchars($disp['dari_user_nama']) ?>
                                </span>
                            </div>
                            
                            <div class="mb-3">
                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($disp['nomor_agenda']) ?></p>
                                <p class="text-xs text-gray-500 line-clamp-2"><?= htmlspecialchars($disp['perihal']) ?></p>
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
                                <a href="surat_detail.php?id=<?= $disp['id_surat'] ?>" class="flex-1 bg-primary-50 text-primary-600 hover:bg-primary-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-eye mr-1"></i>Lihat
                                </a>
                                
                                <?php if (in_array($disp['status_disposisi'], ['dikirim', 'diterima', 'diproses'])): ?>
                                <button onclick='openUpdateModal(<?= json_encode($disp) ?>)' class="flex-1 bg-green-50 text-green-600 hover:bg-green-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors">
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

<!-- Modal Update Status -->
<div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
                <h3 class="text-base sm:text-lg font-semibold text-gray-800">Update Status Disposisi</h3>
            </div>
            
            <form id="updateDisposisiForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="disposisiId">
                
                <div class="px-4 sm:px-6 py-4 space-y-4">
                    <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                        <p class="text-xs sm:text-sm text-gray-600 mb-1">Nomor Agenda:</p>
                        <p class="text-sm sm:text-base font-semibold text-gray-800" id="modalNomorAgenda"></p>
                        <p class="text-xs sm:text-sm text-gray-600 mt-2 mb-1">Perihal:</p>
                        <p class="text-xs sm:text-sm text-gray-800" id="modalPerihal"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" id="statusSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            <!-- HAPUS option "diterima" karena auto-accept -->
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="ditolak">Ditolak</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Tambahan</label>
                        <textarea name="catatan" rows="3" placeholder="Tambahkan catatan untuk update status ini..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>
                </div>
                
                <div class="px-4 sm:px-6 py-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-0 sm:space-x-2">
                    <button type="button" onclick="closeUpdateModal()" class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="btnUpdate" class="w-full sm:w-auto px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const disposisiHandlerPath = '../modules/disposisi/disposisi_handler.php';

function getStatusBadgeClass(status) {
    const badges = {
        'dikirim': 'bg-blue-100 text-blue-800',
        'diterima': 'bg-indigo-100 text-indigo-800',
        'diproses': 'bg-yellow-100 text-yellow-800',
        'selesai': 'bg-green-100 text-green-800',
        'ditolak': 'bg-red-100 text-red-800'
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
}

function openUpdateModal(disposisi) {
    document.getElementById('disposisiId').value = disposisi.id;
    document.getElementById('modalNomorAgenda').textContent = disposisi.nomor_agenda;
    document.getElementById('modalPerihal').textContent = disposisi.perihal;
    
    const statusSelect = document.getElementById('statusSelect');
    if (disposisi.status_disposisi === 'dikirim' || disposisi.status_disposisi === 'diterima') {
        statusSelect.value = 'diproses';
    } else {
        statusSelect.value = 'selesai';
    }
    
    document.getElementById('updateModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.add('hidden');
    document.body.style.overflow = '';
}

$('#updateDisposisiForm').on('submit', function(e) {
    e.preventDefault();
    
    const btn = $('#btnUpdate');
    const originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Loading...');

    const disposisiId = $('#disposisiId').val();
    const newStatus = $('#statusSelect').val();

    $.ajax({
        url: disposisiHandlerPath,
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).html(originalText);
            
            if (response.status === 'success') {
                closeUpdateModal();
                
                // Update UI Real-time
                const statusBadge = $('.status-badge-' + disposisiId);
                const statusText = $('.status-text-' + disposisiId);
                const row = $('#row-' + disposisiId);
                const card = $('#card-' + disposisiId);
                
                // Update badge
                statusBadge.removeClass('bg-blue-100 text-blue-800 bg-indigo-100 text-indigo-800 bg-yellow-100 text-yellow-800 bg-green-100 text-green-800 bg-red-100 text-red-800');
                statusBadge.addClass(getStatusBadgeClass(newStatus));
                statusText.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                
                // Update row background
                row.removeClass('bg-red-50 bg-green-50');
                card.removeClass('border-l-4 border-red-500 bg-red-50 border-l-4 border-green-500 bg-green-50');
                
                if (newStatus === 'ditolak') {
                    row.addClass('bg-red-50');
                    card.addClass('border-l-4 border-red-500 bg-red-50');
                } else if (newStatus === 'selesai') {
                    row.addClass('bg-green-50');
                    card.addClass('border-l-4 border-green-500 bg-green-50');
                }
                
                // Hide update button
                if (newStatus === 'selesai' || newStatus === 'ditolak') {
                    $('#row-' + disposisiId + ' button[onclick*="openUpdateModal"]').fadeOut();
                    $('#card-' + disposisiId + ' button[onclick*="openUpdateModal"]').fadeOut();
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                Swal.fire('Gagal', response.message, 'error');
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html(originalText);
            let msg = 'Terjadi kesalahan sistem';
            
            if (xhr.status === 401) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Habis',
                    text: 'Sesi login Anda telah habis. Halaman akan di-refresh.',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
                return;
            }
            
            try {
                const res = JSON.parse(xhr.responseText);
                if(res.message) msg = res.message;
            } catch(e) {}
            Swal.fire('Error', msg, 'error');
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeUpdateModal();
});

document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});
</script>

<?php include 'partials/footer.php'; ?>