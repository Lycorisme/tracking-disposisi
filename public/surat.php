<?php
// public/surat.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../modules/surat/surat_service.php';
require_once __DIR__ . '/../modules/jenis_surat/jenis_surat_service.php';

requireLogin();

$user = getCurrentUser();
$pageTitle = 'Manajemen Surat';

// Get filters
$filters = [
    'id_jenis' => $_GET['jenis'] ?? '',
    'status_surat' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalSurat = SuratService::count($filters);
$pagination = new Pagination($totalSurat, $perPage, $page);

$suratList = SuratService::getAll($filters, $perPage, $offset);
$jenisSuratList = JenisSuratService::getAll();
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Manajemen Surat</h1>
                <p class="text-gray-600">Kelola semua surat masuk, keluar, dan proposal</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Tambah Surat
                        </button>
                    </div>
                    
                    <form method="GET" class="flex flex-col sm:flex-row gap-2">
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($filters['search']) ?>"
                               placeholder="Cari nomor surat, perihal..." 
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        
                        <select name="jenis" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Jenis</option>
                            <?php foreach ($jenisSuratList as $jenis): ?>
                            <option value="<?= $jenis['id'] ?>" <?= $filters['id_jenis'] == $jenis['id'] ? 'selected' : '' ?>>
                                <?= $jenis['nama_jenis'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="baru" <?= $filters['status_surat'] == 'baru' ? 'selected' : '' ?>>Baru</option>
                            <option value="proses" <?= $filters['status_surat'] == 'proses' ? 'selected' : '' ?>>Proses</option>
                            <option value="disetujui" <?= $filters['status_surat'] == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                            <option value="ditolak" <?= $filters['status_surat'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        </select>
                        
                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-search"></i>
                        </button>
                        
                        <?php if (!empty($filters['search']) || !empty($filters['id_jenis']) || !empty($filters['status_surat'])): ?>
                        <a href="surat.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-times"></i>
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Agenda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($suratList)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-5xl mb-3 text-gray-300"></i>
                                    <p>Tidak ada data surat</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($suratList as $surat): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= $surat['nomor_agenda'] ?></div>
                                        <div class="text-xs text-gray-500"><?= $surat['nomor_surat'] ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= $surat['nama_jenis'] ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= truncate($surat['perihal'], 60) ?></div>
                                        <?php if ($surat['dari_instansi']): ?>
                                        <div class="text-xs text-gray-500">Dari: <?= truncate($surat['dari_instansi'], 30) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= formatTanggal($surat['tanggal_surat']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getStatusBadge($surat['status_surat']) ?>">
                                            <?= ucfirst($surat['status_surat']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex space-x-2">
                                            <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                                               class="text-blue-600 hover:text-blue-800" 
                                               title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if (hasRole(['admin', 'superadmin'])): ?>
                                            <button onclick='openEditModal(<?= json_encode($surat) ?>)' 
                                                    class="text-yellow-600 hover:text-yellow-800" 
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button onclick="arsipkanSurat(<?= $surat['id'] ?>)" 
                                                    class="text-gray-600 hover:text-gray-800" 
                                                    title="Arsipkan">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if (hasRole('superadmin')): ?>
                                            <button onclick="deleteSurat(<?= $surat['id'] ?>)" 
                                                    class="text-red-600 hover:text-red-800" 
                                                    title="Hapus">
                                                <i class="fas fa-trash"></i>
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
                <div class="border-t border-gray-200">
                    <?= $pagination->render('surat.php', ['jenis' => $filters['id_jenis'], 'status' => $filters['status_surat'], 'search' => $filters['search']]) ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<div id="suratModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Tambah Surat</h3>
            </div>
            
            <form id="suratForm" method="POST" enctype="multipart/form-data" action="<?php echo BASE_URL; ?>/../modules/surat/surat_handler.php">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="suratId">
                
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Surat *</label>
                        <select name="id_jenis" id="id_jenis" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih Jenis</option>
                            <?php foreach ($jenisSuratList as $jenis): ?>
                            <option value="<?= $jenis['id'] ?>"><?= $jenis['nama_jenis'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Surat *</label>
                            <input type="text" name="nomor_surat" id="nomor_surat" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Surat *</label>
                            <input type="date" name="tanggal_surat" id="tanggal_surat" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Diterima (opsional)</label>
                        <input type="date" name="tanggal_diterima" id="tanggal_diterima" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dari Instansi</label>
                            <input type="text" name="dari_instansi" id="dari_instansi" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ke Instansi</label>
                            <input type="text" name="ke_instansi" id="ke_instansi" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Surat *</label>
                        <textarea name="alamat_surat" id="alamat_surat" required rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Perihal *</label>
                        <textarea name="perihal" id="perihal" required rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Lampiran File (PDF, JPG, PNG - Max 5MB)</label>
                        <input type="file" name="lampiran_file" id="lampiran_file" 
                               accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">File saat ini: <span id="currentFile">-</span></p>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Surat';
    document.getElementById('formAction').value = 'create';
    document.getElementById('suratForm').reset();
    document.getElementById('suratId').value = '';
    document.getElementById('currentFile').textContent = '-';
    document.getElementById('suratModal').classList.remove('hidden');
}

function openEditModal(surat) {
    document.getElementById('modalTitle').textContent = 'Edit Surat';
    document.getElementById('formAction').value = 'update';
    document.getElementById('suratId').value = surat.id;
    document.getElementById('id_jenis').value = surat.id_jenis;
    document.getElementById('nomor_surat').value = surat.nomor_surat;
    document.getElementById('tanggal_surat').value = surat.tanggal_surat;
    document.getElementById('tanggal_diterima').value = surat.tanggal_diterima || '';
    document.getElementById('dari_instansi').value = surat.dari_instansi || '';
    document.getElementById('ke_instansi').value = surat.ke_instansi || '';
    document.getElementById('alamat_surat').value = surat.alamat_surat;
    document.getElementById('perihal').value = surat.perihal;
    document.getElementById('currentFile').textContent = surat.lampiran_file || '-';
    document.getElementById('suratModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('suratModal').classList.add('hidden');
}

// PERBAIKAN: action Javascript juga diarahkan ke ../modules/surat/surat_handler.php
function arsipkanSurat(id) {
    confirmAction('Arsipkan surat ini?', function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../modules/surat/surat_handler.php'; // Updated path
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'arsipkan';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    });
}

function deleteSurat(id) {
    confirmDelete(function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../modules/surat/surat_handler.php'; // Updated path
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }, 'Surat ini');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>