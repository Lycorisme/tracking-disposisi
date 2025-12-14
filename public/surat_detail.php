<?php
// public/surat_detail.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/surat/surat_service.php';
require_once __DIR__ . '/../modules/disposisi/disposisi_service.php';
require_once __DIR__ . '/../modules/users/users_service.php';

requireLogin();

$user = getCurrentUser();
$suratId = (int)($_GET['id'] ?? 0);

$surat = SuratService::getById($suratId);
if (!$surat) {
    header("Location: surat.php");
    exit;
}

$disposisiHistory = DisposisiService::getHistoryBySurat($suratId);

// PERBAIKAN DI SINI:
// Parameter 1: 'active' (hanya ambil user aktif)
// Parameter 2: $user['id'] (jangan tampilkan diri sendiri di dropdown)
$availableUsers = UsersService::getAll('active', $user['id']); 

$pageTitle = 'Detail Surat';
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <a href="surat.php" class="text-primary-600 hover:text-primary-800 text-sm mb-2 inline-block transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Surat
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Surat</h1>
                </div>
                
                <div class="flex space-x-2">
                    <?php if ($surat['lampiran_file']): ?>
                    <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>Lihat File
                    </a>
                    <?php endif; ?>
                    
                    <button onclick="openDisposisiModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm transition-colors shadow-sm">
                        <i class="fas fa-paper-plane mr-2"></i>Disposisi
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Surat</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="text-sm text-gray-600">Nomor Agenda</label><p class="font-semibold text-gray-800"><?= $surat['nomor_agenda'] ?></p></div>
                            <div><label class="text-sm text-gray-600">Nomor Surat</label><p class="font-semibold text-gray-800"><?= $surat['nomor_surat'] ?></p></div>
                            <div><label class="text-sm text-gray-600">Jenis Surat</label><p class="font-semibold text-gray-800"><?= $surat['nama_jenis'] ?></p></div>
                            <div>
                                <label class="text-sm text-gray-600">Status</label>
                                <p><span class="px-3 py-1 text-sm font-semibold rounded-full <?= getStatusBadge($surat['status_surat']) ?>"><?= ucfirst($surat['status_surat']) ?></span></p>
                            </div>
                            <div><label class="text-sm text-gray-600">Tanggal Surat</label><p class="font-semibold text-gray-800"><?= formatTanggal($surat['tanggal_surat']) ?></p></div>
                            <?php if ($surat['dari_instansi']): ?>
                            <div class="md:col-span-2"><label class="text-sm text-gray-600">Dari Instansi</label><p class="font-semibold text-gray-800"><?= $surat['dari_instansi'] ?></p></div>
                            <?php endif; ?>
                            <div class="md:col-span-2"><label class="text-sm text-gray-600">Perihal</label><p class="font-semibold text-gray-800"><?= nl2br($surat['perihal']) ?></p></div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-route mr-2 text-primary-600"></i> Tracking Disposisi
                        </h2>
                        
                        <div id="disposisi-timeline-container">
                            <?php if (empty($disposisiHistory)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Belum ada disposisi untuk surat ini</p>
                            </div>
                            <?php else: ?>
                            <div class="relative">
                                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                                <div class="space-y-6">
                                    <?php foreach ($disposisiHistory as $disp): 
                                        $bgClass = 'bg-primary-100'; $iconClass = 'fa-paper-plane text-primary-600'; 
                                        if ($disp['status_disposisi'] === 'selesai') { $bgClass = 'bg-green-100'; $iconClass = 'fa-check text-green-600'; }
                                        elseif ($disp['status_disposisi'] === 'ditolak') { $bgClass = 'bg-red-100'; $iconClass = 'fa-times text-red-600'; }
                                        elseif ($disp['status_disposisi'] === 'diproses') { $bgClass = 'bg-yellow-100'; $iconClass = 'fa-spinner text-yellow-600'; }
                                        elseif ($disp['status_disposisi'] === 'diterima') { $bgClass = 'bg-indigo-100'; $iconClass = 'fa-envelope-open text-indigo-600'; }
                                    ?>
                                    <div class="relative pl-14">
                                        <div class="absolute left-0 w-12 h-12 rounded-full flex items-center justify-center <?= $bgClass ?>">
                                            <i class="fas <?= $iconClass ?>"></i>
                                        </div>
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1">
                                                    <p class="font-semibold text-gray-800">
                                                        <?= $disp['dari_user_nama'] ?> <i class="fas fa-arrow-right text-gray-400 mx-2"></i> <?= $disp['ke_user_nama'] ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        <?= getRoleLabel($disp['dari_user_role']) ?> → <?= getRoleLabel($disp['ke_user_role']) ?>
                                                    </p>
                                                </div>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                                    <?= ucfirst($disp['status_disposisi']) ?>
                                                </span>
                                            </div>
                                            <?php if ($disp['catatan']): ?>
                                            <div class="mt-2 p-2 bg-white rounded border-l-4 border-gray-300">
                                                <p class="text-sm text-gray-700"><?= nl2br($disp['catatan']) ?></p>
                                            </div>
                                            <?php endif; ?>
                                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                                <i class="fas fa-clock mr-1"></i> Dikirim: <?= formatDateTime($disp['tanggal_disposisi']) ?>
                                                <?php if ($disp['tanggal_respon']): ?>
                                                <span class="ml-4"><i class="fas fa-check-circle mr-1"></i> Respon: <?= formatDateTime($disp['tanggal_respon']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-4">Statistik Disposisi</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-primary-50 rounded-lg">
                                <div class="flex items-center"><i class="fas fa-paper-plane text-primary-600 mr-2"></i><span class="text-sm text-gray-700">Total Disposisi</span></div>
                                <span class="font-bold text-gray-800" id="stat-total"><?= count($disposisiHistory) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<div id="disposisiModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Disposisi Surat</h3>
            </div>
            
            <form id="disposisiForm">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id_surat" value="<?= $suratId ?>">
                
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disposisi Ke *</label>
                        <select name="ke_user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
                            <option value="">Pilih User</option>
                            <?php foreach ($availableUsers as $availUser): ?>
                            <option value="<?= $availUser['id'] ?>">
                                <?= $availUser['nama_lengkap'] ?> (<?= getRoleLabel($availUser['nama_role']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($availableUsers)): ?>
                            <p class="text-xs text-red-500 mt-1">Tidak ada user aktif lain yang tersedia untuk didisposisi.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan / Instruksi</label>
                        <textarea name="catatan" rows="4" placeholder="Berikan catatan atau instruksi..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button type="button" onclick="closeDisposisiModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="submit" id="btnSubmitDisposisi" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors duration-200">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Disposisi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const disposisiHandlerUrl = '../modules/disposisi/disposisi_handler.php';

function openDisposisiModal() {
    document.getElementById('disposisiModal').classList.remove('hidden');
}

function closeDisposisiModal() {
    document.getElementById('disposisiModal').classList.add('hidden');
    document.getElementById('disposisiForm').reset();
}

// Handle Form Submit (AJAX)
$('#disposisiForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validasi
    const keUserId = document.querySelector('[name="ke_user_id"]').value;
    if (!keUserId) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Pilih user tujuan disposisi' });
        return false;
    }
    
    // UI State
    const btn = $('#btnSubmitDisposisi');
    const originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Mengirim...');
    
    $.ajax({
        url: disposisiHandlerUrl,
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).html(originalText);
            
            if (response.status === 'success') {
                closeDisposisiModal();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: response.message, timer: 1500, showConfirmButton: false });

                // Update Timeline HTML jika ada
                if (response.html) {
                    $('#disposisi-timeline-container').html(response.html);
                }
                // Update Counter
                if (response.count !== undefined) {
                    $('#stat-total').text(response.count);
                }
            } else {
                Swal.fire('Gagal', response.message, 'error');
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html(originalText);
            let msg = 'Terjadi kesalahan sistem';
            try {
                const res = JSON.parse(xhr.responseText);
                if(res.message) msg = res.message;
            } catch(e) {}
            Swal.fire('Error', msg, 'error');
        }
    });
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDisposisiModal();
});
</script>

<?php include 'partials/footer.php'; ?>