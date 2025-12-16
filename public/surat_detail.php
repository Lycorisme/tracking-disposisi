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
$userId = $user['id'];
$userRole = $user['id_role'] ?? 3; // 1: Admin, 2: Karyawan, 3: Magang
$suratId = (int)($_GET['id'] ?? 0);

$surat = SuratService::getById($suratId);
if (!$surat) {
    header("Location: surat.php?error=" . urlencode("Surat tidak ditemukan"));
    exit;
}

// ===== AUTO-ACCEPT DISPOSISI =====
// Jika user membuka detail surat yang didisposisi ke dia, 
// otomatis update status dari 'dikirim' ke 'diterima'
DisposisiService::autoAcceptDisposisi($suratId, $userId);

// Get disposisi history untuk surat ini (Full History)
$disposisiHistory = DisposisiService::getHistoryBySurat($suratId);

// Ambil user aktif selain diri sendiri (untuk modal disposisi)
$availableUsers = UsersService::getAll('active', $user['id']); 

// Check apakah surat bisa didisposisi (Cek teknis: apakah ada disposisi gantung?)
$canDisposeCheck = DisposisiService::checkSuratAvailability($suratId);

$pageTitle = 'Detail Surat - ' . $surat['nomor_agenda'];

// Cek Status Surat Selesai (Final)
$isSuratSelesai = in_array($surat['status_surat'], ['disetujui', 'ditolak', 'arsip']);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50/50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            
            <div class="mb-8">
                <nav class="flex mb-3" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="surat.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-primary-600 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i> Daftar Surat
                            </a>
                        </li>
                    </ol>
                </nav>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 tracking-tight">Detail Surat</h1>
                        <p class="mt-1 text-sm text-gray-500">Informasi lengkap dan riwayat disposisi surat.</p>
                    </div>
                    
                    <div class="flex items-center gap-3 flex-wrap">
                        <?php if ($surat['lampiran_file']): ?>
                        <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" target="_blank" 
                           class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all"
                           title="Buka file surat di tab baru">
                            <i class="fas fa-external-link-alt mr-2 text-blue-500"></i> Lihat Surat
                        </a>
                        <?php else: ?>
                        <button disabled class="inline-flex items-center px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg font-medium text-sm text-gray-400 cursor-not-allowed">
                            <i class="fas fa-file-slash mr-2"></i> Tidak Ada File
                        </button>
                        <?php endif; ?>

                        <button onclick="window.print()" class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all">
                            <i class="fas fa-print mr-2 text-gray-500"></i> Cetak
                        </button>

                        <?php if (!$isSuratSelesai): ?>
                        <div class="flex gap-2">
                            <button onclick="updateStatusSurat(<?= $suratId ?>, 'disetujui')" 
                                    class="inline-flex items-center px-4 py-2.5 bg-green-600 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all shadow-green-200">
                                <i class="fas fa-check-circle mr-2"></i> Setujui
                            </button>
                            <button onclick="updateStatusSurat(<?= $suratId ?>, 'ditolak')" 
                                    class="inline-flex items-center px-4 py-2.5 bg-red-600 border border-transparent rounded-lg font-medium text-sm text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all shadow-red-200">
                                <i class="fas fa-times-circle mr-2"></i> Tolak
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($userRole != 3): // Hide untuk Anak Magang ?>
                            <?php 
                                $disableDisposisi = false;
                                $disposisiBtnText = "Disposisi";
                                $disposisiTooltip = "Kirim disposisi surat ini";
                                $btnColorClass = "bg-primary-600 hover:bg-primary-700 text-white";

                                // LOGIKA 1: Jika Role Karyawan & Surat Selesai => DISABLE
                                if ($userRole == 2 && $isSuratSelesai) {
                                    $disableDisposisi = true;
                                    $disposisiBtnText = "Surat Selesai";
                                    $disposisiTooltip = "Surat ini sudah selesai, tidak dapat didisposisi lagi.";
                                    $btnColorClass = "bg-gray-400 cursor-not-allowed opacity-60 text-white";
                                }
                                // LOGIKA 2: Jika Role Admin & Surat Selesai => ENABLE ("Kuasa Admin")
                                elseif ($userRole == 1 && $isSuratSelesai) {
                                    $disableDisposisi = false; // Admin boleh override
                                    $disposisiTooltip = "Mode Admin: Disposisi ulang surat selesai";
                                }
                                
                                // LOGIKA 3: Cek Teknis (Apakah ada disposisi gantung?)
                                // Jika ada disposisi yang statusnya masih 'dikirim'/'diterima'/'diproses' -> jangan tumpuk
                                if (!$canDisposeCheck['can_dispose']) {
                                    $disableDisposisi = true;
                                    $disposisiBtnText = "Sedang Diproses";
                                    $disposisiTooltip = htmlspecialchars($canDisposeCheck['message']);
                                    $btnColorClass = "bg-yellow-500 hover:bg-yellow-600 text-white cursor-not-allowed opacity-80";
                                }
                            ?>

                            <?php if (!$disableDisposisi): ?>
                            <button onclick="openDisposisiModal()" 
                                    title="<?= $disposisiTooltip ?>"
                                    class="inline-flex items-center px-4 py-2.5 border border-transparent rounded-lg font-medium text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all transform active:scale-95 <?= $btnColorClass ?>">
                                <i class="fas fa-paper-plane mr-2"></i> <?= $disposisiBtnText ?>
                            </button>
                            <?php else: ?>
                            <button disabled 
                                    title="<?= $disposisiTooltip ?>"
                                    class="inline-flex items-center px-4 py-2.5 border border-transparent rounded-lg font-medium text-sm shadow-sm <?= $btnColorClass ?>">
                                <i class="fas fa-lock mr-2"></i> <?= $disposisiBtnText ?>
                            </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                            <h2 class="text-base font-semibold text-gray-800">
                                <i class="far fa-envelope mr-2 text-gray-400"></i> Atribut Surat
                            </h2>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= getStatusBadge($surat['status_surat']) ?>">
                                <?= ucfirst($surat['status_surat']) ?>
                            </span>
                        </div>
                        
                        <div class="p-6">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Agenda</dt>
                                    <dd class="mt-1 text-sm font-semibold text-gray-900 bg-gray-50 inline-block px-2 py-1 rounded border border-gray-200">
                                        <?= htmlspecialchars($surat['nomor_agenda']) ?>
                                    </dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Surat</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($surat['nomor_surat']) ?></dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Surat</dt>
                                    <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($surat['nama_jenis']) ?></dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Surat</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <i class="far fa-calendar-alt mr-1 text-gray-400"></i>
                                        <?= formatTanggal($surat['tanggal_surat']) ?>
                                    </dd>
                                </div>
                                <?php if ($surat['dari_instansi']): ?>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dari Instansi</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-medium"><?= htmlspecialchars($surat['dari_instansi']) ?></dd>
                                </div>
                                <?php endif; ?>
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Perihal</dt>
                                    <dd class="mt-2 text-sm text-gray-800 bg-gray-50 p-3 rounded-lg border border-gray-100 leading-relaxed">
                                        <?= nl2br(htmlspecialchars($surat['perihal'])) ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                            <h2 class="text-base font-semibold text-gray-800">
                                <i class="fas fa-history mr-2 text-primary-600"></i> Riwayat Disposisi
                            </h2>
                            <span class="bg-primary-50 text-primary-700 text-xs px-2 py-1 rounded-md font-medium">
                                <?= count($disposisiHistory) ?> Aktivitas
                            </span>
                        </div>
                        
                        <div class="p-6" id="disposisi-timeline-container">
                            <?php if (empty($disposisiHistory)): ?>
                                <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                        <i class="fas fa-inbox text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-medium">Belum ada disposisi untuk surat ini</p>
                                </div>
                            <?php else: ?>
                                <div class="flow-root">
                                    <ul role="list" class="-mb-8">
                                        <?php foreach ($disposisiHistory as $index => $disp): 
                                            $isLast = $index === array_key_last($disposisiHistory);
                                            
                                            // Warna Icon & Badge berdasarkan Status Disposisi
                                            $iconBg = 'bg-primary-500'; 
                                            $iconSymbol = 'fa-paper-plane';
                                            $rowBgClass = '';
                                            
                                            if ($disp['status_disposisi'] === 'selesai') { 
                                                $iconBg = 'bg-green-500'; 
                                                $iconSymbol = 'fa-check'; 
                                                $rowBgClass = 'bg-green-50 border-green-200';
                                            } elseif ($disp['status_disposisi'] === 'ditolak') { 
                                                $iconBg = 'bg-red-500'; 
                                                $iconSymbol = 'fa-times'; 
                                                $rowBgClass = 'bg-red-50 border-red-200';
                                            } elseif ($disp['status_disposisi'] === 'diproses') { 
                                                $iconBg = 'bg-yellow-500'; 
                                                $iconSymbol = 'fa-spinner'; 
                                            } elseif ($disp['status_disposisi'] === 'diterima') { 
                                                $iconBg = 'bg-blue-500'; 
                                                $iconSymbol = 'fa-envelope-open'; 
                                            }
                                        ?>
                                        <li>
                                            <div class="relative pb-8">
                                                <?php if (!$isLast || $isSuratSelesai): ?>
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                <?php endif; ?>
                                                
                                                <div class="relative flex space-x-3">
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white <?= $iconBg ?>">
                                                            <i class="fas <?= $iconSymbol ?> text-white text-xs"></i>
                                                        </span>
                                                    </div>
                                                    <div class="min-w-0 flex-1 pt-1.5">
                                                        <div class="w-full p-3 rounded-lg border <?= $rowBgClass ? $rowBgClass : 'border-gray-100 bg-white' ?>">
                                                            <div class="flex justify-between items-start mb-1">
                                                                <p class="text-sm text-gray-500">
                                                                    <span class="font-bold text-gray-900"><?= htmlspecialchars($disp['dari_user_nama']) ?></span>
                                                                    <i class="fas fa-long-arrow-alt-right mx-1 text-gray-400"></i>
                                                                    <span class="font-bold text-gray-900"><?= htmlspecialchars($disp['ke_user_nama']) ?></span>
                                                                </p>
                                                                <time class="whitespace-nowrap text-xs text-gray-400">
                                                                    <?= formatDateTime($disp['tanggal_disposisi']) ?>
                                                                </time>
                                                            </div>
                                                            
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded bg-gray-100 text-gray-600">
                                                                    <?= getRoleLabel($disp['dari_user_role']) ?> &rarr; <?= getRoleLabel($disp['ke_user_role']) ?>
                                                                </span>
                                                                <span class="px-2 py-0.5 text-[10px] uppercase font-bold rounded border <?= getDisposisiStatusBadge($disp['status_disposisi']) ?>">
                                                                    <?= htmlspecialchars($disp['status_disposisi']) ?>
                                                                </span>
                                                            </div>

                                                            <?php if ($disp['catatan']): ?>
                                                            <div class="bg-white rounded-lg p-3 border border-gray-200 text-sm text-gray-700 italic">
                                                                <i class="fas fa-comment-dots mr-1 text-gray-400"></i>
                                                                "<?= nl2br(htmlspecialchars($disp['catatan'])) ?>"
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($disp['tanggal_respon']): ?>
                                                            <div class="mt-2 flex items-center text-xs text-green-600 font-medium">
                                                                <i class="fas fa-check-double mr-1"></i>
                                                                Direspon pada: <?= formatDateTime($disp['tanggal_respon']) ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>

                                        <?php if ($isSuratSelesai): ?>
                                        <li>
                                            <div class="relative pb-8">
                                                <div class="relative flex space-x-3">
                                                    <?php
                                                        $finalIcon = 'fa-check';
                                                        $finalBg = 'bg-gray-500';
                                                        $finalTitle = 'Surat Selesai';
                                                        $finalDesc = 'Status akhir: ' . ucfirst($surat['status_surat']);

                                                        if ($surat['status_surat'] == 'disetujui') {
                                                            $finalIcon = 'fa-check-circle';
                                                            $finalBg = 'bg-green-600';
                                                            $finalTitle = 'Surat Disetujui';
                                                            $finalDesc = 'Surat telah selesai dan disetujui.';
                                                        } elseif ($surat['status_surat'] == 'ditolak') {
                                                            $finalIcon = 'fa-times-circle';
                                                            $finalBg = 'bg-red-600';
                                                            $finalTitle = 'Surat Ditolak';
                                                            $finalDesc = 'Pengajuan surat ini telah ditolak.';
                                                        } elseif ($surat['status_surat'] == 'arsip') {
                                                            $finalIcon = 'fa-archive';
                                                            $finalBg = 'bg-blue-600';
                                                            $finalTitle = 'Surat Diarsipkan';
                                                            $finalDesc = 'Surat telah masuk ke arsip.';
                                                        }
                                                    ?>
                                                    <div>
                                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white <?= $finalBg ?>">
                                                            <i class="fas <?= $finalIcon ?> text-white text-xs"></i>
                                                        </span>
                                                    </div>
                                                    <div class="min-w-0 flex-1 pt-1.5">
                                                        <div class="w-full p-3 rounded-lg border border-gray-200 bg-gray-50 opacity-90">
                                                            <div class="flex items-center">
                                                                <p class="text-sm font-bold text-gray-900 mr-2"><?= $finalTitle ?></p>
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                                    Selesai
                                                                </span>
                                                            </div>
                                                            <p class="text-xs text-gray-500 mt-1"><?= $finalDesc ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                        <?php endif; ?>

                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Ringkasan</h3>
                        
                        <div class="flex items-center p-4 bg-primary-50 rounded-xl mb-4 border border-primary-100">
                            <div class="p-3 rounded-full bg-primary-100 text-primary-600 mr-4">
                                <i class="fas fa-share-alt text-lg"></i>
                            </div>
                            <div>
                                <p class="text-xs text-primary-600 font-medium uppercase">Total Disposisi</p>
                                <p class="text-2xl font-bold text-gray-900" id="stat-total"><?= count($disposisiHistory) ?></p>
                            </div>
                        </div>

                        <div class="text-xs text-gray-500">
                            <p class="mb-2"><i class="fas fa-info-circle mr-1"></i> Surat ini dibuat oleh:</p>
                            <div class="flex items-center gap-2">
                                <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                                    <?= substr($surat['dibuat_oleh_nama'] ?? 'U', 0, 1) ?>
                                </div>
                                <span class="font-medium text-gray-700"><?= htmlspecialchars($surat['dibuat_oleh_nama'] ?? 'Unknown') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<?php if ($userRole != 3): ?>
<div id="disposisiModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 overflow-y-auto backdrop-blur-sm transition-all">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 transform transition-all scale-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Kirim Disposisi</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Teruskan surat ini ke pengguna lain</p>
                </div>
                <button onclick="closeDisposisiModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="disposisiForm">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id_surat" value="<?= $suratId ?>">
                
                <div class="px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Tujuan Disposisi <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <select name="ke_user_id" required class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow appearance-none bg-white">
                                <option value="">-- Pilih Penerima --</option>
                                <?php foreach ($availableUsers as $availUser): ?>
                                <option value="<?= $availUser['id'] ?>">
                                    <?= htmlspecialchars($availUser['nama_lengkap']) ?> &mdash; <?= getRoleLabel($availUser['nama_role']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </div>
                        </div>
                        <?php if(empty($availableUsers)): ?>
                            <p class="text-xs text-red-500 mt-1.5 flex items-center"><i class="fas fa-exclamation-circle mr-1"></i> Tidak ada user aktif lain.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Catatan / Instruksi</label>
                        <textarea name="catatan" rows="4" placeholder="Tuliskan instruksi atau catatan untuk penerima..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-shadow resize-none"></textarea>
                        <p class="text-xs text-gray-400 mt-1 text-right">Opsional</p>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-xl flex flex-col-reverse sm:flex-row justify-end gap-3">
                    <button type="button" onclick="closeDisposisiModal()" class="w-full sm:w-auto px-5 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-white focus:outline-none focus:ring-2 focus:ring-gray-200 transition-all shadow-sm">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitDisposisi" class="w-full sm:w-auto px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all flex items-center justify-center">
                        <i class="fas fa-paper-plane mr-2"></i> Kirim Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
const disposisiHandlerUrl = '../modules/disposisi/disposisi_handler.php';
const suratHandlerUrl = '../modules/surat/surat_handler.php';

// ===== UPDATE STATUS SURAT (Acc/Tolak) - Global Function =====
function updateStatusSurat(suratId, newStatus) {
    const statusText = newStatus === 'disetujui' ? 'menyetujui' : 'menolak';
    const statusColor = newStatus === 'disetujui' ? '#10B981' : '#EF4444';
    const iconType = newStatus === 'disetujui' ? 'success' : 'warning';
    
    Swal.fire({
        title: `Konfirmasi ${newStatus === 'disetujui' ? 'Persetujuan' : 'Penolakan'}`,
        text: `Anda yakin ingin ${statusText} surat ini? Tindakan ini tidak dapat dibatalkan.`,
        icon: iconType,
        showCancelButton: true,
        confirmButtonColor: statusColor,
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Ya, ${newStatus === 'disetujui' ? 'Setujui' : 'Tolak'}`,
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: suratHandlerUrl,
                type: 'POST',
                data: {
                    action: 'update_status',
                    id: suratId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let msg = 'Terjadi kesalahan sistem';
                    try {
                        const res = JSON.parse(xhr.responseText);
                        if(res.message) msg = res.message;
                    } catch(e) {}
                    Swal.fire('Error', msg, 'error');
                }
            });
        }
    });
}

// ===== DISPOSISI MODAL LOGIC (Only for Non-Magang) =====
<?php if ($userRole != 3): ?>
function openDisposisiModal() {
    document.getElementById('disposisiModal').classList.remove('hidden');
}

function closeDisposisiModal() {
    document.getElementById('disposisiModal').classList.add('hidden');
    document.getElementById('disposisiForm').reset();
}

// Handle Form Submit (AJAX) - Disposisi
$('#disposisiForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validasi
    const keUserId = document.querySelector('[name="ke_user_id"]').value;
    if (!keUserId) {
        Swal.fire({ 
            icon: 'warning', 
            title: 'Peringatan', 
            text: 'Silakan pilih tujuan disposisi terlebih dahulu.',
            confirmButtonColor: '#d33'
        });
        return false;
    }
    
    // UI Loading State
    const btn = $('#btnSubmitDisposisi');
    const originalText = btn.html();
    btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed').html('<i class="fas fa-spinner fa-spin mr-2"></i> Mengirim...');
    
    $.ajax({
        url: disposisiHandlerUrl,
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed').html(originalText);
            
            if (response.status === 'success') {
                closeDisposisiModal();
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Terkirim!', 
                    text: response.message, 
                    timer: 1500, 
                    showConfirmButton: false 
                }).then(() => {
                    location.reload(); 
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: response.message,
                    confirmButtonColor: '#d33'
                });
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed').html(originalText);
            let msg = 'Terjadi kesalahan sistem';
            try {
                const res = JSON.parse(xhr.responseText);
                if(res.message) msg = res.message;
            } catch(e) {}
            Swal.fire({
                icon: 'error',
                title: 'Error Sistem',
                text: msg,
                confirmButtonColor: '#d33'
            });
        }
    });
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDisposisiModal();
});

// Close modal on click outside
const modal = document.getElementById('disposisiModal');
if(modal){
    modal.addEventListener('click', function(e) {
        if (e.target === this) closeDisposisiModal();
    });
}
<?php endif; ?>
</script>

<?php include 'partials/footer.php'; ?>