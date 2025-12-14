<?php
// public/jenis_surat.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php'; // Load pagination helper
require_once __DIR__ . '/../modules/jenis_surat/jenis_surat_service.php';

requireLogin();
requireRole(['admin', 'superadmin']);

$user = getCurrentUser();
$pageTitle = 'Jenis Surat';

// --- LOGIKA PAGINATION ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Hitung total data
$allData = JenisSuratService::getAll(); 
$totalData = count($allData);

// Ambil data untuk halaman ini (Manual slicing)
$jenisSuratList = array_slice($allData, $offset, $perPage);

$pagination = new Pagination($totalData, $perPage, $page);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Master Jenis Surat</h1>
                    <p class="text-sm sm:text-base text-gray-600">Kelola jenis-jenis surat</p>
                </div>
                <button onclick="openAddModal()" class="w-full sm:w-auto bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                    <i class="fas fa-plus mr-2"></i>Tambah Jenis
                </button>
            </div>
            
            <div class="hidden md:block bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Jenis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($jenisSuratList)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                Tidak ada data jenis surat
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($jenisSuratList as $index => $jenis): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $offset + $index + 1 ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($jenis['nama_jenis']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600"><?= htmlspecialchars($jenis['keterangan'] ?? '-') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick='openEditModal(<?= json_encode($jenis) ?>)' 
                                                class="text-yellow-600 hover:text-yellow-800 transition-colors"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (hasRole('superadmin')): ?>
                                        <button onclick="deleteJenis(<?= $jenis['id'] ?>)" 
                                                class="text-red-600 hover:text-red-800 transition-colors"
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
                
                <?php if ($pagination->hasPages()): ?>
                <div class="border-t border-gray-200 px-4 py-3">
                    <?= $pagination->render('jenis_surat.php') ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="md:hidden space-y-4">
                <?php if (empty($jenisSuratList)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    Tidak ada data jenis surat
                </div>
                <?php else: ?>
                    <?php foreach ($jenisSuratList as $index => $jenis): ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-6 h-6 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        <?= $offset + $index + 1 ?>
                                    </span>
                                    <h3 class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($jenis['nama_jenis']) ?></h3>
                                </div>
                                <p class="text-xs text-gray-600 ml-8"><?= htmlspecialchars($jenis['keterangan'] ?? '-') ?></p>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 ml-8">
                            <button onclick='openEditModal(<?= json_encode($jenis) ?>)' 
                                    class="flex-1 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>
                            <?php if (hasRole('superadmin')): ?>
                            <button onclick="deleteJenis(<?= $jenis['id'] ?>)" 
                                    class="flex-1 bg-red-50 text-red-600 hover:bg-red-100 py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-trash mr-1"></i>Hapus
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($pagination->hasPages()): ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <?= $pagination->render('jenis_surat.php') ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<div id="jenisModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
            <h3 id="modalTitle" class="text-base sm:text-lg font-semibold text-gray-800">Tambah Jenis Surat</h3>
        </div>
        
        <form id="jenisForm">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="jenisId">
            
            <div class="px-4 sm:px-6 py-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Jenis *</label>
                    <input type="text" name="nama_jenis" id="nama_jenis" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                    <textarea name="keterangan" id="keterangan" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"></textarea>
                </div>
            </div>
            
            <div class="px-4 sm:px-6 py-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row justify-end gap-2 sm:gap-0 sm:space-x-2">
                <button type="button" onclick="closeModal()" 
                        class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" id="btnSave"
                        class="w-full sm:w-auto px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Path ke Handler
const handlerUrl = '../modules/jenis_surat/jenis_surat_handler.php';

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Jenis Surat';
    document.getElementById('formAction').value = 'create';
    document.getElementById('jenisForm').reset();
    document.getElementById('jenisId').value = '';
    document.getElementById('jenisModal').classList.remove('hidden');
}

function openEditModal(jenis) {
    document.getElementById('modalTitle').textContent = 'Edit Jenis Surat';
    document.getElementById('formAction').value = 'update';
    document.getElementById('jenisId').value = jenis.id;
    document.getElementById('nama_jenis').value = jenis.nama_jenis;
    document.getElementById('keterangan').value = jenis.keterangan || '';
    document.getElementById('jenisModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('jenisModal').classList.add('hidden');
}

function deleteJenis(id) {
    Swal.fire({
        title: 'Hapus Jenis Surat?',
        text: 'Data yang dihapus tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            // AJAX Delete
            $.post(handlerUrl, { action: 'delete', id: id }, function(response) {
                if(response.status === 'success') {
                    Swal.fire('Terhapus!', response.message, 'success')
                        .then(() => location.reload()); // Reload agar tabel terupdate
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        }
    });
}

// Handle Form Submit via AJAX
$('#jenisForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validasi Sederhana
    const namaJenis = $('#nama_jenis').val().trim();
    if (!namaJenis) {
        Swal.fire('Validasi', 'Nama jenis surat harus diisi', 'warning');
        return;
    }

    const formData = $(this).serialize();
    const btn = $('#btnSave');
    const originalText = btn.html();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

    $.ajax({
        url: handlerUrl,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).html(originalText);
            
            if (response.status === 'success') {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Gagal', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            btn.prop('disabled', false).html(originalText);
            console.error(xhr.responseText);
            Swal.fire('Error', 'Terjadi kesalahan sistem. Cek console log.', 'error');
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

document.getElementById('jenisModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include 'partials/footer.php'; ?>