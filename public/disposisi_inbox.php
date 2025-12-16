<?php
// public/disposisi_inbox.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';

requireLogin();

$user = getCurrentUser();
$userId = (int)$user['id'];
$userRole = $user['id_role'] ?? 3;
$pageTitle = 'Disposisi Masuk';

// Filters
$filters = [
    'status_disposisi' => $_GET['status'] ?? '', // Filter berdasarkan status disposisi
    'search' => $_GET['search'] ?? ''
];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$conn = getConnection();

// ==========================================
// 1. QUERY BUILDER
// ==========================================

$columns = "s.*, 
            j.nama_jenis,
            u.nama_lengkap as dibuat_oleh_nama,
            d.id as id_disposisi, 
            d.status_disposisi,
            d.catatan as catatan_disposisi,
            d.tanggal_disposisi as tanggal_disposisi_terakhir,
            d.dari_user_id,
            (SELECT nama_lengkap FROM users WHERE id = d.dari_user_id) as dari_user_nama,
            (SELECT GROUP_CONCAT(CONCAT(u2.nama_lengkap, ' (', d2.status_disposisi, ')') SEPARATOR ', ')
             FROM disposisi d2
             JOIN users u2 ON d2.ke_user_id = u2.id
             WHERE d2.id_surat = s.id) as disposisi_info";

$joins = "FROM surat s
          JOIN jenis_surat j ON s.id_jenis = j.id
          JOIN users u ON s.dibuat_oleh = u.id";

// LOGIKA JOIN: Ambil disposisi terbaru
if ($userRole == 1) {
    // Admin: Melihat disposisi terbaru secara global per surat
    $joins .= " JOIN disposisi d ON d.id = (
                    SELECT MAX(d_inner.id) 
                    FROM disposisi d_inner 
                    WHERE d_inner.id_surat = s.id
                )";
} else {
    // Staff/User: Melihat disposisi terbaru yang dikirim KE user ini
    $joins .= " JOIN disposisi d ON d.id = (
                    SELECT MAX(d_inner.id) 
                    FROM disposisi d_inner 
                    WHERE d_inner.id_surat = s.id AND d_inner.ke_user_id = $userId
                )";
}

// === MODIFIKASI FILTER ===
// 1. Surat tidak boleh status 'arsip', 'disetujui', 'ditolak' (Hanya surat aktif)
// 2. Disposisi hanya status 'dikirim', 'diterima', 'diproses' (Inbox aktif)
$where = "WHERE s.status_surat NOT IN ('arsip', 'disetujui', 'ditolak')
          AND d.status_disposisi IN ('dikirim', 'diterima', 'diproses')";

$params = [];
$types = '';

// Filter Status Disposisi (Dropdown)
if (!empty($filters['status_disposisi'])) {
    $where .= " AND d.status_disposisi = ?";
    $params[] = $filters['status_disposisi'];
    $types .= 's';
}

// Filter Search
if (!empty($filters['search'])) {
    $search = "%" . $filters['search'] . "%";
    $where .= " AND (s.nomor_agenda LIKE ? OR s.perihal LIKE ? OR s.nomor_surat LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

// ==========================================
// 2. EXECUTE QUERIES
// ==========================================

// A. Hitung Total Data (untuk Pagination)
$countSql = "SELECT COUNT(*) as total $joins $where";
$stmtCount = $conn->prepare($countSql);
if (!empty($params)) {
    $stmtCount->bind_param($types, ...$params);
}
$stmtCount->execute();
$totalSurat = $stmtCount->get_result()->fetch_assoc()['total'];

// B. Ambil Data Utama
$sql = "SELECT $columns $joins $where ORDER BY d.tanggal_disposisi DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$suratList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pagination = new Pagination($totalSurat, $perPage, $page);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300 w-full min-w-0">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                    <i class=""></i>Disposisi Masuk
                </h1>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">
                    <?php if ($userRole == 1): ?>
                        Daftar disposisi aktif (Belum Selesai)
                    <?php else: ?>
                        Surat yang perlu Anda tindak lanjuti
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($userRole == 1): ?>
            <div class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                <i class="fas fa-shield-alt mr-1.5"></i> Mode Admin
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Pencarian</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                               class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm transition-shadow" 
                               placeholder="No. Agenda / Perihal...">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Status Disposisi</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm transition-shadow bg-white">
                        <option value="">Semua Aktif</option>
                        <option value="dikirim" <?= $filters['status_disposisi'] == 'dikirim' ? 'selected' : '' ?>>Dikirim (Baru)</option>
                        <option value="diterima" <?= $filters['status_disposisi'] == 'diterima' ? 'selected' : '' ?>>Diterima</option>
                        <option value="diproses" <?= $filters['status_disposisi'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                    <button type="submit" class="flex-1 lg:flex-none bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition text-sm flex justify-center items-center gap-2 font-medium">
                        <i class="fas fa-filter"></i> <span class="hidden sm:inline">Terapkan</span>
                    </button>
                    
                    <?php if (!empty($filters['search']) || !empty($filters['status_disposisi'])): ?>
                    <a href="disposisi_inbox.php" class="flex-1 lg:flex-none bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors flex justify-center items-center gap-2">
                        <i class="fas fa-times"></i> <span class="hidden sm:inline">Reset</span>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor Surat</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Perihal</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dari</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($suratList)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-check-circle text-4xl text-green-200 mb-3"></i>
                                        <p class="text-sm font-medium text-gray-900">Semua Beres!</p>
                                        <p class="text-xs text-gray-500">Tidak ada disposisi aktif yang perlu ditindaklanjuti.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suratList as $surat): 
                                // Data untuk Modal Update
                                $disposisiData = [
                                    'id' => $surat['id_disposisi'],
                                    'nomor_agenda' => $surat['nomor_agenda'],
                                    'perihal' => $surat['perihal'],
                                    'status_disposisi' => $surat['status_disposisi'],
                                    'catatan' => $surat['catatan_disposisi'] ?? ''
                                ];
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors" id="row-<?= $surat['id_disposisi'] ?>">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($surat['nomor_agenda']) ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($surat['nomor_surat']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium"><?= truncate($surat['perihal'], 60) ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Jenis: <?= htmlspecialchars($surat['nama_jenis']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-700">
                                        <?= htmlspecialchars($surat['dari_user_nama'] ?? '-') ?>
                                    </div>
                                    <?php if($surat['catatan_disposisi']): ?>
                                        <div class="text-xs text-gray-500 italic mt-1">"<?= truncate($surat['catatan_disposisi'], 30) ?>"</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    <?= formatDateTime($surat['tanggal_disposisi_terakhir']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border status-badge-<?= $surat['id_disposisi'] ?> <?= getDisposisiStatusBadge($surat['status_disposisi']) ?>">
                                        <span class="status-text-<?= $surat['id_disposisi'] ?>"><?= $surat['status_disposisi'] ?></span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center items-center gap-2">
                                        <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                                           class="text-primary-600 hover:text-primary-800 font-medium" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (in_array($surat['status_disposisi'], ['dikirim', 'diterima', 'diproses'])): ?>
                                        <button onclick='openUpdateModal(<?= json_encode($disposisiData) ?>)' 
                                                class="text-green-600 hover:text-green-800 font-medium btn-update-<?= $surat['id_disposisi'] ?>" 
                                                title="Update Status Disposisi">
                                            <i class="fas fa-edit"></i>
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
        </div>

        <div class="md:hidden space-y-4">
            <?php if (empty($suratList)): ?>
                <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-4xl text-green-200 mb-3"></i>
                    <p class="text-sm">Tidak ada surat disposisi masuk</p>
                </div>
            <?php else: ?>
                <?php foreach ($suratList as $surat): 
                     $disposisiData = [
                        'id' => $surat['id_disposisi'],
                        'nomor_agenda' => $surat['nomor_agenda'],
                        'perihal' => $surat['perihal'],
                        'status_disposisi' => $surat['status_disposisi'],
                        'catatan' => $surat['catatan_disposisi'] ?? ''
                    ];
                ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4" id="card-<?= $surat['id_disposisi'] ?>">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="text-xs font-bold text-gray-900"><?= htmlspecialchars($surat['nomor_agenda']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($surat['nomor_surat']) ?></p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-2 py-0.5 text-[10px] uppercase font-bold rounded border status-badge-<?= $surat['id_disposisi'] ?> <?= getDisposisiStatusBadge($surat['status_disposisi']) ?>">
                                <span class="status-text-<?= $surat['id_disposisi'] ?>"><?= $surat['status_disposisi'] ?></span>
                            </span>
                        </div>
                    </div>

                    <p class="text-sm text-gray-800 mt-2 line-clamp-2"><?= htmlspecialchars($surat['perihal']) ?></p>
                    
                    <div class="mt-2 text-xs text-gray-500">
                        <span class="font-medium text-gray-700">Dari:</span> <?= htmlspecialchars($surat['dari_user_nama'] ?? '?') ?>
                    </div>

                    <div class="text-xs text-gray-500 mt-3 flex items-center border-t border-gray-100 pt-2">
                        <i class="far fa-clock mr-1"></i>
                        <?= formatDateTime($surat['tanggal_disposisi_terakhir']) ?>
                    </div>

                    <div class="mt-3 flex gap-2">
                        <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                           class="flex-1 text-center py-2 text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg text-xs font-medium">
                            <i class="fas fa-eye mr-1"></i> Detail
                        </a>
                        
                        <?php if (in_array($surat['status_disposisi'], ['dikirim', 'diterima', 'diproses'])): ?>
                        <button onclick='openUpdateModal(<?= json_encode($disposisiData) ?>)' 
                                class="flex-1 text-center py-2 text-green-600 bg-green-50 hover:bg-green-100 rounded-lg text-xs font-medium btn-update-<?= $surat['id_disposisi'] ?>">
                            <i class="fas fa-edit mr-1"></i> Update
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($pagination->hasPages()): ?>
        <div class="mt-6 px-4 py-3 bg-white rounded-lg shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-600">
                Halaman <?= $page ?> dari <?= ceil($totalSurat / $perPage) ?>
            </div>
            <div>
                <?= $pagination->render('disposisi_inbox.php', $filters) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="updateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto backdrop-blur-sm transition-all">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 transform transition-all scale-100">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-xl">
                <h3 class="text-lg font-bold text-gray-800">Update Status Disposisi</h3>
                <p class="text-xs text-gray-500 mt-0.5">Ubah status pengerjaan disposisi ini</p>
            </div>
            
            <form id="updateDisposisiForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="disposisiId">
                
                <div class="px-6 py-6 space-y-5">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <p class="text-xs text-blue-600 uppercase font-bold mb-1">Surat Terkait</p>
                        <p class="text-sm font-semibold text-gray-900" id="modalNomorAgenda"></p>
                        <p class="text-sm text-gray-700 mt-1 line-clamp-2" id="modalPerihal"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status Baru <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select name="status" id="statusSelect" required class="w-full pl-4 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow appearance-none bg-white">
                                <option value="diproses">Diproses (Sedang dikerjakan)</option>
                                <option value="disetujui">Disetujui / Selesai</option>
                                <option value="ditolak">Ditolak / Kembalikan</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan Tambahan</label>
                        <textarea name="catatan" rows="3" placeholder="Tambahkan catatan untuk update status ini (opsional)..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow resize-none"></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" onclick="closeUpdateModal()" class="w-full sm:w-auto px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-white transition-all">
                        Batal
                    </button>
                    <button type="submit" id="btnUpdate" class="w-full sm:w-auto px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium shadow-md hover:shadow-lg transition-all flex items-center justify-center">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const disposisiHandlerPath = '../modules/disposisi/disposisi_handler.php';

// Helper: Get Tailwind classes for status badge
function getStatusBadgeClass(status) {
    const badges = {
        'dikirim': 'bg-blue-100 text-blue-800 border-blue-200',
        'diterima': 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'diproses': 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'disetujui': 'bg-green-100 text-green-800 border-green-200',
        'selesai': 'bg-green-100 text-green-800 border-green-200',
        'ditolak': 'bg-red-100 text-red-800 border-red-200'
    };
    return badges[status] || 'bg-gray-100 text-gray-800 border-gray-200';
}

function openUpdateModal(disposisi) {
    // Populate Data
    document.getElementById('disposisiId').value = disposisi.id;
    document.getElementById('modalNomorAgenda').textContent = disposisi.nomor_agenda;
    document.getElementById('modalPerihal').textContent = disposisi.perihal;
    
    // Set default select value based on current status
    const statusSelect = document.getElementById('statusSelect');
    if (disposisi.status_disposisi === 'dikirim' || disposisi.status_disposisi === 'diterima') {
        statusSelect.value = 'diproses';
    } else {
        statusSelect.value = 'disetujui';
    }
    
    // Show Modal
    document.getElementById('updateModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

function closeUpdateModal() {
    document.getElementById('updateModal').classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('updateDisposisiForm').reset();
}

// Handle Form Submit via AJAX
$('#updateDisposisiForm').on('submit', function(e) {
    e.preventDefault();
    
    const btn = $('#btnUpdate');
    const originalText = btn.html();
    btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed').html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

    const disposisiId = $('#disposisiId').val();
    const newStatus = $('#statusSelect').val();

    $.ajax({
        url: disposisiHandlerPath,
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed').html(originalText);
            
            if (response.status === 'success') {
                closeUpdateModal();
                
                // Update UI Real-time (Badge Status)
                const statusBadge = $('.status-badge-' + disposisiId);
                const statusText = $('.status-text-' + disposisiId);
                
                // Reset classes & Add new ones
                statusBadge.attr('class', 'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase border status-badge-' + disposisiId + ' ' + getStatusBadgeClass(newStatus));
                statusText.text(newStatus);
                
                // Jika status final (disetujui/ditolak), sembunyikan tombol update
                // DAN hilangkan baris tersebut karena ini halaman Inbox Aktif
                if (newStatus === 'disetujui' || newStatus === 'ditolak' || newStatus === 'selesai') {
                    $('#row-' + disposisiId).fadeOut(500, function() { $(this).remove(); });
                    $('#card-' + disposisiId).fadeOut(500, function() { $(this).remove(); });
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Gagal', response.message, 'error');
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed').html(originalText);
            let msg = 'Terjadi kesalahan sistem';
            try {
                const res = JSON.parse(xhr.responseText);
                if(res.message) msg = res.message;
            } catch(e) {}
            
            // Handle Session Timeout
            if (xhr.status === 401) {
                 Swal.fire({
                    icon: 'warning',
                    title: 'Sesi Habis',
                    text: 'Silakan login kembali.'
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', msg, 'error');
            }
        }
    });
});

// Close modal on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeUpdateModal();
});

// Close modal on click outside
document.getElementById('updateModal').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});
</script>

<?php include 'partials/footer.php'; ?>