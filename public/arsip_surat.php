<?php
// public/arsip_surat.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../modules/surat/surat_service.php';

requireLogin();

$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['id_role'] ?? 3;
$pageTitle = 'Arsip Surat';

// Pagination Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// ===== FILTER BERDASARKAN ROLE =====
// - Superadmin & Admin: Lihat semua arsip
// - Anak Magang: Lihat hanya arsip surat yang pernah ditugaskan ke dia

$params = [];
$types = '';

$query = "SELECT s.*, 
          js.nama_jenis,
          u.nama_lengkap as dibuat_oleh_nama
          FROM surat s
          LEFT JOIN jenis_surat js ON s.id_jenis = js.id
          LEFT JOIN users u ON s.dibuat_oleh = u.id
          WHERE s.status_surat = 'arsip'";

// Filter untuk anak magang (role 3)
if ($userRole == 3) {
    $query .= " AND s.id IN (
                    SELECT id_surat FROM disposisi WHERE ke_user_id = ?
                )";
    $params[] = $userId;
    $types .= 'i';
}

// Filter search jika ada
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $query .= " AND (s.nomor_agenda LIKE ? OR s.perihal LIKE ? OR s.nomor_surat LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

// Count total untuk pagination
$countQuery = "SELECT COUNT(*) as total FROM surat s WHERE s.status_surat = 'arsip'";
if ($userRole == 3) {
    $countQuery .= " AND s.id IN (SELECT id_surat FROM disposisi WHERE ke_user_id = ?)";
    $countParams = [$userId];
    $countTypes = 'i';
} else {
    $countParams = [];
    $countTypes = '';
}

if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $countQuery .= " AND (s.nomor_agenda LIKE ? OR s.perihal LIKE ? OR s.nomor_surat LIKE ?)";
    $countParams[] = $search;
    $countParams[] = $search;
    $countParams[] = $search;
    $countTypes .= 'sss';
}

$totalResult = dbSelectOne($countQuery, $countParams, $countTypes);
$totalArsip = $totalResult['total'] ?? 0;

$pagination = new Pagination($totalArsip, $perPage, $page);

// Add order and pagination
$query .= " ORDER BY s.updated_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$arsipList = dbSelect($query, $params, $types);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-50">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64 transition-all duration-300">
        <main class="p-4 sm:p-6 lg:p-8">
            <div class="mb-4 sm:mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Arsip Surat</h1>
                <p class="text-sm sm:text-base text-gray-600">
                    <?php if ($userRole == 3): ?>
                        Daftar surat yang telah diarsipkan dan pernah Anda tangani
                    <?php else: ?>
                        Daftar surat yang telah diarsipkan
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Search Filter -->
            <?php if ($totalArsip > 0 || !empty($_GET['search'])): ?>
            <div class="bg-white rounded-lg shadow p-4 mb-4 sm:mb-6">
                <form method="GET" class="flex gap-2">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                           placeholder="Cari nomor agenda, perihal, atau nomor surat..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500">
                    
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-search"></i><span class="ml-2 hidden sm:inline">Cari</span>
                    </button>
                    
                    <?php if (!empty($_GET['search'])): ?>
                    <a href="arsip_surat.php" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors">
                        <i class="fas fa-times"></i><span class="ml-2 hidden sm:inline">Reset</span>
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Desktop Table -->
            <div class="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Agenda</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Perihal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Surat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diarsipkan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($arsipList)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-archive text-5xl mb-3 text-gray-300"></i>
                                    <p>
                                        <?php if (!empty($_GET['search'])): ?>
                                            Tidak ditemukan arsip dengan kata kunci "<?= htmlspecialchars($_GET['search']) ?>"
                                        <?php elseif ($userRole == 3): ?>
                                            Belum ada surat yang diarsipkan yang pernah Anda tangani
                                        <?php else: ?>
                                            Belum ada surat yang diarsipkan
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($arsipList as $surat): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($surat['nomor_agenda']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($surat['nomor_surat']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= htmlspecialchars($surat['nama_jenis']) ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= formatTanggal($surat['updated_at']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex space-x-2">
                                            <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                                               class="text-primary-600 hover:text-primary-800 transition-colors" 
                                               title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($surat['lampiran_file']): ?>
                                            <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" 
                                               target="_blank"
                                               class="text-green-600 hover:text-green-800 transition-colors" 
                                               title="Lihat File">
                                                <i class="fas fa-file-pdf"></i>
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
                
                <?php if ($pagination->hasPages()): ?>
                <div class="border-t border-gray-200 px-4 py-3">
                    <?= $pagination->render('arsip_surat.php', ['search' => $_GET['search'] ?? '']) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Card View -->
            <div class="lg:hidden space-y-4">
                <?php if (empty($arsipList)): ?>
                <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                    <i class="fas fa-archive text-5xl mb-3 text-gray-300"></i>
                    <p>
                        <?php if (!empty($_GET['search'])): ?>
                            Tidak ditemukan arsip dengan kata kunci "<?= htmlspecialchars($_GET['search']) ?>"
                        <?php elseif ($userRole == 3): ?>
                            Belum ada surat yang diarsipkan yang pernah Anda tangani
                        <?php else: ?>
                            Belum ada surat yang diarsipkan
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                    <?php foreach ($arsipList as $surat): ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-semibold text-gray-900 truncate"><?= htmlspecialchars($surat['nomor_agenda']) ?></h3>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($surat['nomor_surat']) ?></p>
                                </div>
                                <span class="ml-2 px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-full whitespace-nowrap">
                                    <?= htmlspecialchars($surat['nama_jenis']) ?>
                                </span>
                            </div>
                            
                            <p class="text-sm text-gray-700 mb-2 line-clamp-2"><?= htmlspecialchars($surat['perihal']) ?></p>
                            
                            <?php if ($surat['dari_instansi']): ?>
                            <p class="text-xs text-gray-500 mb-2">
                                <i class="fas fa-building mr-1"></i>
                                <?= truncate($surat['dari_instansi'], 40) ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                <span><i class="far fa-calendar mr-1"></i><?= formatTanggal($surat['tanggal_surat']) ?></span>
                                <span><i class="fas fa-archive mr-1"></i><?= formatTanggal($surat['updated_at']) ?></span>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                                   class="flex-1 bg-primary-50 text-primary-600 hover:bg-primary-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-eye mr-1"></i>Detail
                                </a>
                                
                                <?php if ($surat['lampiran_file']): ?>
                                <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" 
                                   target="_blank"
                                   class="flex-1 bg-green-50 text-green-600 hover:bg-green-100 text-center py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-file-pdf mr-1"></i>File
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($pagination->hasPages()): ?>
                    <div class="bg-white rounded-lg shadow p-4">
                        <?= $pagination->render('arsip_surat.php', ['search' => $_GET['search'] ?? '']) ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<?php include 'partials/footer.php'; ?>