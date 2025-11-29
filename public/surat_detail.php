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
    redirect('surat.php?error=not_found');
    exit;
}

$disposisiHistory = DisposisiService::getHistoryBySurat($suratId);
$availableUsers = UsersService::getAll($user['id']);

$pageTitle = 'Detail Surat';
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <a href="surat.php" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Surat
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Surat</h1>
                </div>
                
                <div class="flex space-x-2">
                    <?php if ($surat['lampiran_file']): ?>
                    <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" 
                       target="_blank"
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-file-pdf mr-2"></i>Lihat File
                    </a>
                    <?php endif; ?>
                    
                    <button onclick="openDisposisiModal()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                        <i class="fas fa-paper-plane mr-2"></i>Disposisi
                    </button>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Surat Details -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Surat</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-600">Nomor Agenda</label>
                                <p class="font-semibold text-gray-800"><?= $surat['nomor_agenda'] ?></p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-600">Nomor Surat</label>
                                <p class="font-semibold text-gray-800"><?= $surat['nomor_surat'] ?></p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-600">Jenis Surat</label>
                                <p class="font-semibold text-gray-800"><?= $surat['nama_jenis'] ?></p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-600">Status</label>
                                <p>
                                    <span class="px-3 py-1 text-sm font-semibold rounded-full <?= getStatusBadge($surat['status_surat']) ?>">
                                        <?= ucfirst($surat['status_surat']) ?>
                                    </span>
                                </p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-600">Tanggal Surat</label>
                                <p class="font-semibold text-gray-800"><?= formatTanggal($surat['tanggal_surat']) ?></p>
                            </div>
                            
                            <?php if ($surat['tanggal_diterima']): ?>
                            <div>
                                <label class="text-sm text-gray-600">Tanggal Diterima</label>
                                <p class="font-semibold text-gray-800"><?= formatTanggal($surat['tanggal_diterima']) ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($surat['dari_instansi']): ?>
                            <div class="md:col-span-2">
                                <label class="text-sm text-gray-600">Dari Instansi</label>
                                <p class="font-semibold text-gray-800"><?= $surat['dari_instansi'] ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($surat['ke_instansi']): ?>
                            <div class="md:col-span-2">
                                <label class="text-sm text-gray-600">Ke Instansi</label>
                                <p class="font-semibold text-gray-800"><?= $surat['ke_instansi'] ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="md:col-span-2">
                                <label class="text-sm text-gray-600">Alamat Surat</label>
                                <p class="font-semibold text-gray-800"><?= nl2br($surat['alamat_surat']) ?></p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="text-sm text-gray-600">Perihal</label>
                                <p class="font-semibold text-gray-800"><?= nl2br($surat['perihal']) ?></p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-600">Dibuat Oleh</label>
                                <p class="font-semibold text-gray-800"><?= $surat['dibuat_oleh_nama'] ?></p>
                            </div>
                            
                            <div>
                                <label class="text-sm text-gray-600">Tanggal Dibuat</label>
                                <p class="font-semibold text-gray-800"><?= formatDateTime($surat['created_at']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tracking Disposisi -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-route mr-2 text-blue-600"></i>
                            Tracking Disposisi
                        </h2>
                        
                        <?php if (empty($disposisiHistory)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Belum ada disposisi untuk surat ini</p>
                        </div>
                        <?php else: ?>
                        <div class="relative">
                            <!-- Timeline -->
                            <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                            
                            <div class="space-y-6">
                                <?php foreach ($disposisiHistory as $index => $disp): ?>
                                <div class="relative pl-14">
                                    <!-- Timeline dot -->
                                    <div class="absolute left-0 w-12 h-12 rounded-full flex items-center justify-center
                                                <?php 
                                                    if ($disp['status_disposisi'] === 'selesai') echo 'bg-green-100';
                                                    elseif ($disp['status_disposisi'] === 'ditolak') echo 'bg-red-100';
                                                    elseif ($disp['status_disposisi'] === 'diproses') echo 'bg-yellow-100';
                                                    else echo 'bg-blue-100';
                                                ?>">
                                        <i class="fas 
                                                <?php 
                                                    if ($disp['status_disposisi'] === 'selesai') echo 'fa-check text-green-600';
                                                    elseif ($disp['status_disposisi'] === 'ditolak') echo 'fa-times text-red-600';
                                                    elseif ($disp['status_disposisi'] === 'diproses') echo 'fa-spinner text-yellow-600';
                                                    else echo 'fa-paper-plane text-blue-600';
                                                ?>"></i>
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex-1">
                                                <p class="font-semibold text-gray-800">
                                                    <?= $disp['dari_user_nama'] ?> 
                                                    <i class="fas fa-arrow-right text-gray-400 mx-2"></i>
                                                    <?= $disp['ke_user_nama'] ?>
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
                                        <div class="mt-2 p-2 bg-white rounded border-l-4 border-blue-500">
                                            <p class="text-sm text-gray-700"><?= nl2br($disp['catatan']) ?></p>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-2 flex items-center text-xs text-gray-500">
                                            <i class="fas fa-clock mr-1"></i>
                                            Dikirim: <?= formatDateTime($disp['tanggal_disposisi']) ?>
                                            <?php if ($disp['tanggal_respon']): ?>
                                            <span class="ml-4">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Respon: <?= formatDateTime($disp['tanggal_respon']) ?>
                                            </span>
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
                
                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-4">Statistik Disposisi</h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-paper-plane text-blue-600 mr-2"></i>
                                    <span class="text-sm text-gray-700">Total Disposisi</span>
                                </div>
                                <span class="font-bold text-gray-800"><?= count($disposisiHistory) ?></span>
                            </div>
                            
                            <?php
                            $statusCount = ['dikirim' => 0, 'diproses' => 0, 'selesai' => 0, 'ditolak' => 0];
                            foreach ($disposisiHistory as $disp) {
                                $statusCount[$disp['status_disposisi']]++;
                            }
                            ?>
                            
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                                    <span class="text-sm text-gray-700">Selesai</span>
                                </div>
                                <span class="font-bold text-gray-800"><?= $statusCount['selesai'] ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-spinner text-yellow-600 mr-2"></i>
                                    <span class="text-sm text-gray-700">Diproses</span>
                                </div>
                                <span class="font-bold text-gray-800"><?= $statusCount['diproses'] ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- File Info -->
                    <?php if ($surat['lampiran_file']): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-4">Lampiran File</h3>
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-file-pdf text-red-600 text-2xl"></i>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate"><?= $surat['lampiran_file'] ?></p>
                                <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" 
                                   target="_blank"
                                   class="text-xs text-blue-600 hover:text-blue-800">
                                    Lihat File <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<!-- Modal Disposisi -->
<div id="disposisiModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Disposisi Surat</h3>
            </div>
            
            <form method="POST" action="modules/disposisi/disposisi_handler.php">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id_surat" value="<?= $suratId ?>">
                <input type="hidden" name="redirect" value="surat_detail.php?id=<?= $suratId ?>">
                
                <div class="px-6 py-4 space-y-4">
                    <!-- Tujuan User -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Disposisi Ke *</label>
                        <select name="ke_user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih User</option>
                            <?php foreach ($availableUsers as $availUser): ?>
                            <option value="<?= $availUser['id'] ?>">
                                <?= $availUser['nama_lengkap'] ?> (<?= getRoleLabel($availUser['nama_role']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Catatan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan / Instruksi</label>
                        <textarea name="catatan" rows="4" 
                                  placeholder="Berikan catatan atau instruksi untuk disposisi ini..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-2">
                    <button type="button" onclick="closeDisposisiModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Disposisi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDisposisiModal() {
    document.getElementById('disposisiModal').classList.remove('hidden');
}

function closeDisposisiModal() {
    document.getElementById('disposisiModal').classList.add('hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDisposisiModal();
    }
});
</script>