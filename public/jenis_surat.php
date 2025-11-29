<?php
// public/jenis_surat.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/jenis_surat/jenis_surat_service.php';

requireLogin();
requireRole(['admin', 'superadmin']);

$user = getCurrentUser();
$pageTitle = 'Jenis Surat';

$jenisSuratList = JenisSuratService::getAll();
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Master Jenis Surat</h1>
                    <p class="text-gray-600">Kelola jenis-jenis surat</p>
                </div>
                <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Tambah Jenis
                </button>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
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
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $index + 1 ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= $jenis['nama_jenis'] ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600"><?= $jenis['keterangan'] ?? '-' ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick='openEditModal(<?= json_encode($jenis) ?>)' 
                                                class="text-yellow-600 hover:text-yellow-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (hasRole('superadmin')): ?>
                                        <button onclick="deleteJenis(<?= $jenis['id'] ?>)" 
                                                class="text-red-600 hover:text-red-800">
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
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<!-- Modal Add/Edit -->
<div id="jenisModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-800">Tambah Jenis Surat</h3>
            </div>
            
            <form id="jenisForm" method="POST" action="modules/jenis_surat/jenis_surat_handler.php">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="jenisId">
                
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Jenis *</label>
                        <input type="text" name="nama_jenis" id="nama_jenis" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
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
    confirmDelete(function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'modules/jenis_surat/jenis_surat_handler.php';
        
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
    }, 'Jenis surat ini');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>