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

$filters = [
    'id_jenis' => $_GET['jenis'] ?? '',
    'status_surat' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalSurat = SuratService::count($filters);
$pagination = new Pagination($totalSurat, $perPage, $page);

$suratList = SuratService::getAll($filters, $perPage, $offset);
$jenisSuratList = JenisSuratService::getAll();
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 p-4 lg:p-8 transition-all duration-300 w-full min-w-0">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Manajemen Surat</h1>
                <p class="text-gray-600 text-xs sm:text-sm">Kelola arsip surat masuk, keluar, dan proposal</p>
            </div>
            <button onclick="openAddModal()" class="w-full sm:w-auto bg-primary-600 text-white px-4 py-2.5 rounded-lg hover:bg-primary-700 transition flex items-center justify-center gap-2 shadow-sm text-sm font-medium">
                <i class="fas fa-plus"></i>
                <span>Tambah Surat</span>
            </button>
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
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Jenis Surat</label>
                    <select name="jenis" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm transition-shadow bg-white">
                        <option value="">Semua Jenis</option>
                        <?php foreach($jenisSuratList as $jenis): ?>
                        <option value="<?= $jenis['id'] ?>" <?= $filters['id_jenis'] == $jenis['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($jenis['nama_jenis']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm transition-shadow bg-white">
                        <option value="">Semua Status</option>
                        <option value="baru" <?= $filters['status_surat'] == 'baru' ? 'selected' : '' ?>>Baru</option>
                        <option value="proses" <?= $filters['status_surat'] == 'proses' ? 'selected' : '' ?>>Proses</option>
                        <option value="disetujui" <?= $filters['status_surat'] == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $filters['status_surat'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                        <option value="arsip" <?= $filters['status_surat'] == 'arsip' ? 'selected' : '' ?>>Arsip</option>
                    </select>
                </div>

                <div class="flex items-end sm:col-span-2 lg:col-span-1">
                    <button type="submit" class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition text-sm flex justify-center items-center gap-2 font-medium">
                        <i class="fas fa-filter"></i> Terapkan
                    </button>
                </div>
            </form>
        </div>

        <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Info Surat</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Asal/Tujuan</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Tgl Surat</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($suratList)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-search text-4xl text-gray-200 mb-3"></i>
                                        <p class="text-sm">Tidak ada surat ditemukan.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suratList as $surat): ?>
                            <tr class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 h-10 w-10 bg-primary-100 text-primary-600 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-bold text-gray-900 truncate"><?= htmlspecialchars($surat['nomor_agenda']) ?></div>
                                            <div class="text-xs text-gray-500 mt-0.5 truncate"><?= htmlspecialchars($surat['nomor_surat']) ?></div>
                                            <div class="text-sm text-gray-700 mt-1 line-clamp-2" title="<?= htmlspecialchars($surat['perihal']) ?>">
                                                <?= htmlspecialchars($surat['perihal']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div class="flex flex-col gap-1.5">
                                        <?php if ($surat['dari_instansi']): ?>
                                            <span class="inline-flex items-center text-xs" title="Dari">
                                                <i class="fas fa-arrow-right text-green-500 w-4"></i> <?= truncate($surat['dari_instansi'], 25) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($surat['ke_instansi']): ?>
                                            <span class="inline-flex items-center text-xs" title="Kepada">
                                                <i class="fas fa-arrow-left text-orange-500 w-4"></i> <?= truncate($surat['ke_instansi'], 25) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($surat['tanggal_surat'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php
                                    $badge = match($surat['status_surat']) {
                                        'baru' => 'bg-primary-100 text-primary-700 border-primary-200',
                                        'proses' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                        'disetujui' => 'bg-green-100 text-green-700 border-green-200',
                                        'ditolak' => 'bg-red-100 text-red-700 border-red-200',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200'
                                    };
                                    ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium border <?= $badge ?>">
                                        <?= ucfirst($surat['status_surat']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center space-x-2">
                                        <a href="surat_detail.php?id=<?= $surat['id'] ?>" class="text-gray-400 hover:text-primary-600 transition-colors p-1.5 hover:bg-gray-100 rounded-lg" title="Detail">
                                            <i class="fas fa-eye text-lg"></i>
                                        </a>
                                        
                                        <?php if (hasRole(['admin', 'superadmin'])): ?>
                                        <button onclick='openEditModal(<?= json_encode($surat) ?>)' class="text-gray-400 hover:text-yellow-600 transition-colors p-1.5 hover:bg-gray-100 rounded-lg" title="Edit">
                                            <i class="fas fa-edit text-lg"></i>
                                        </button>
                                        <button onclick="arsipkanSurat(<?= $surat['id'] ?>)" class="text-gray-400 hover:text-blue-600 transition-colors p-1.5 hover:bg-gray-100 rounded-lg" title="Arsipkan">
                                            <i class="fas fa-archive text-lg"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if (hasRole('superadmin')): ?>
                                        <button onclick="deleteSurat(<?= $surat['id'] ?>)" class="text-gray-400 hover:text-red-600 transition-colors p-1.5 hover:bg-gray-100 rounded-lg" title="Hapus">
                                            <i class="fas fa-trash text-lg"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if ($surat['lampiran_file']): ?>
                                        <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" target="_blank" class="text-gray-400 hover:text-green-600 transition-colors p-1.5 hover:bg-gray-100 rounded-lg" title="Download">
                                            <i class="fas fa-file-download text-lg"></i>
                                        </a>
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
                    <i class="fas fa-search text-4xl text-gray-200 mb-3"></i>
                    <p class="text-sm">Tidak ada surat ditemukan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($suratList as $surat): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 relative overflow-hidden">
                    <?php
                    $badge = match($surat['status_surat']) {
                        'baru' => 'bg-primary-50 text-primary-700 border-primary-100',
                        'proses' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
                        'disetujui' => 'bg-green-50 text-green-700 border-green-100',
                        'ditolak' => 'bg-red-50 text-red-700 border-red-100',
                        default => 'bg-gray-50 text-gray-700 border-gray-100'
                    };
                    ?>
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center text-primary-600 flex-shrink-0">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-gray-800 text-sm truncate"><?= htmlspecialchars($surat['nomor_agenda']) ?></h3>
                                <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($surat['nomor_surat']) ?></p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase tracking-wider flex-shrink-0 <?= $badge ?>">
                            <?= $surat['status_surat'] ?>
                        </span>
                    </div>

                    <div class="mb-3 pl-[3.25rem]">
                        <p class="text-sm font-medium text-gray-800 line-clamp-2 leading-snug"><?= htmlspecialchars($surat['perihal']) ?></p>
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <i class="far fa-calendar"></i> <?= date('d M Y', strtotime($surat['tanggal_surat'])) ?>
                        </p>
                    </div>

                    <div class="text-xs text-gray-600 bg-gray-50 p-2.5 rounded-lg mb-3 space-y-1.5 border border-gray-100">
                        <?php if ($surat['dari_instansi']): ?>
                            <div class="flex gap-2 items-start">
                                <i class="fas fa-arrow-right text-green-500 mt-0.5 w-3 text-center"></i> 
                                <span class="break-words w-full">Dari: <span class="font-medium text-gray-800"><?= htmlspecialchars($surat['dari_instansi']) ?></span></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($surat['ke_instansi']): ?>
                            <div class="flex gap-2 items-start">
                                <i class="fas fa-arrow-left text-orange-500 mt-0.5 w-3 text-center"></i> 
                                <span class="break-words w-full">Ke: <span class="font-medium text-gray-800"><?= htmlspecialchars($surat['ke_instansi']) ?></span></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-100 pt-3">
                        <a href="surat_detail.php?id=<?= $surat['id'] ?>" class="flex-1 text-center py-2 text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg text-xs font-medium transition-colors">
                            <i class="fas fa-eye mr-1"></i> Detail
                        </a>
                        
                        <?php if (hasRole(['admin', 'superadmin'])): ?>
                        <button onclick='openEditModal(<?= json_encode($surat) ?>)' class="p-2 text-yellow-600 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="arsipkanSurat(<?= $surat['id'] ?>)" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                            <i class="fas fa-archive"></i>
                        </button>
                        <?php endif; ?>

                        <?php if (hasRole('superadmin')): ?>
                        <button onclick="deleteSurat(<?= $surat['id'] ?>)" class="p-2 text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($surat['lampiran_file']): ?>
                        <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" target="_blank" class="p-2 text-green-600 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                            <i class="fas fa-file-download"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($pagination->hasPages()): ?>
        <div class="mt-6 px-4 py-3 bg-white rounded-lg shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-600 text-center sm:text-left w-full sm:w-auto">
                Halaman <span class="font-medium text-gray-900"><?= $page ?></span> dari <span class="font-medium text-gray-900"><?= ceil($totalSurat / $perPage) ?></span>
            </div>
            <div class="w-full sm:w-auto flex justify-center sm:justify-end">
                <?= $pagination->render('surat.php', $filters) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="suratModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto backdrop-blur-sm transition-opacity">
    <div class="flex items-center justify-center min-h-screen p-4 sm:p-6">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl relative flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10 rounded-t-xl flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-bold text-gray-800">Tambah Surat</h3>
                <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="overflow-y-auto p-6">
                <form id="suratForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="suratId">
                    
                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Jenis Surat <span class="text-red-500">*</span></label>
                            <select name="id_jenis" id="id_jenis" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow bg-white">
                                <option value="">Pilih Jenis</option>
                                <?php foreach ($jenisSuratList as $jenis): ?>
                                <option value="<?= $jenis['id'] ?>"><?= $jenis['nama_jenis'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor Surat</label>
                                <input type="text" name="nomor_surat" id="nomor_surat" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                                    placeholder="Generate Otomatis jika kosong">
                                <p class="text-xs text-gray-500 mt-1">Kosongkan untuk auto-generate</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Surat <span class="text-red-500">*</span></label>
                                <input type="date" name="tanggal_surat" id="tanggal_surat" required 
                                    value="<?= date('Y-m-d') ?>"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Diterima</label>
                            <input type="date" name="tanggal_diterima" id="tanggal_diterima" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Dari Instansi</label>
                                <input type="text" name="dari_instansi" id="dari_instansi" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                                    placeholder="Nama instansi pengirim">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Ke Instansi</label>
                                <input type="text" name="ke_instansi" id="ke_instansi" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                                    placeholder="Nama instansi tujuan">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Surat</label>
                            <textarea name="alamat_surat" id="alamat_surat" rows="2"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                                    placeholder="Alamat lengkap instansi"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Perihal <span class="text-red-500">*</span></label>
                            <textarea name="perihal" id="perihal" required rows="3"
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm"
                                    placeholder="Isi perihal surat"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Lampiran File</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:bg-gray-50 transition-colors cursor-pointer" onclick="document.getElementById('lampiran_file').click()">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                    <div class="flex text-sm text-gray-600 justify-center">
                                        <label for="lampiran_file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none">
                                            <span>Upload file</span>
                                            <input id="lampiran_file" name="lampiran_file" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileName(this)">
                                        </label>
                                        <p class="pl-1">atau drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, PNG, JPG (Max 5MB)</p>
                                    <p id="fileNameDisplay" class="text-sm font-medium text-gray-800 mt-2 hidden"></p>
                                    <p class="text-xs text-gray-500 mt-1">File saat ini: <span id="currentFile">-</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row justify-end gap-3 sticky bottom-0 bg-white">
                        <button type="button" onclick="closeModal()" 
                                class="w-full sm:w-auto px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors font-medium">
                            Batal
                        </button>
                        <button type="submit" id="btnSave"
                                class="w-full sm:w-auto px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors font-medium shadow-sm">
                            <i class="fas fa-save mr-2"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const handlerUrl = '../modules/surat/surat_handler.php';

function updateFileName(input) {
    const display = document.getElementById('fileNameDisplay');
    if (input.files && input.files[0]) {
        display.textContent = input.files[0].name;
        display.classList.remove('hidden');
    } else {
        display.classList.add('hidden');
    }
}

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Surat';
    document.getElementById('formAction').value = 'create';
    document.getElementById('suratForm').reset();
    document.getElementById('suratId').value = '';
    document.getElementById('currentFile').textContent = '-';
    document.getElementById('fileNameDisplay').classList.add('hidden');
    document.getElementById('tanggal_surat').value = '<?= date('Y-m-d') ?>';
    document.getElementById('suratModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
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
    document.getElementById('fileNameDisplay').classList.add('hidden');
    document.getElementById('suratModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('suratModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function deleteSurat(id) {
    Swal.fire({
        title: 'Hapus Surat?',
        text: 'Data yang dihapus tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(handlerUrl, { action: 'delete', id: id }, function(response) {
                if(response.status === 'success') {
                    Swal.fire('Terhapus!', response.message, 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            });
        }
    });
}

function arsipkanSurat(id) {
    Swal.fire({
        title: 'Arsipkan Surat?',
        text: 'Surat ini akan dipindahkan ke arsip.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Arsipkan'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(handlerUrl, { action: 'arsipkan', id: id }, function(response) {
                if(response.status === 'success') {
                    Swal.fire('Berhasil!', response.message, 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            }, 'json');
        }
    });
}

// Handle Form Submit via AJAX
$('#suratForm').on('submit', function(e) {
    e.preventDefault();
    
    const jenisId = $('#id_jenis').val();
    if (!jenisId) {
        Swal.fire('Validasi', 'Mohon pilih jenis surat', 'warning');
        return;
    }

    const formData = new FormData(this);
    const btn = $('#btnSave');
    const originalText = btn.html();
    
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');

    $.ajax({
        url: handlerUrl,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
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
            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

document.getElementById('suratModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include 'partials/footer.php'; ?>