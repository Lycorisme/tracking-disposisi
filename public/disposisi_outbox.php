<?php
// public/disposisi_outbox.php - FIXED: Tampilkan unique surat yang didisposisikan OLEH user
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pagination.php';

requireLogin();

$user = getCurrentUser();
$userId = $user['id'];
$userRole = $user['id_role'] ?? 3;
$pageTitle = 'Disposisi Keluar';

// Anak magang tidak bisa akses halaman ini
if ($userRole == 3) {
    header("Location: index.php?error=" . urlencode("Anda tidak memiliki akses ke halaman ini"));
    exit;
}

// Filters
$filters = [
    'status_surat' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// QUERY BARU: Ambil UNIQUE SURAT yang DIDISPOSISIKAN OLEH user
$conn = getConnection();

$sql = "SELECT DISTINCT s.*, 
               j.nama_jenis,
               u.nama_lengkap as dibuat_oleh_nama,
               (SELECT GROUP_CONCAT(CONCAT(u2.nama_lengkap, ' (', d2.status_disposisi, ')') SEPARATOR ', ')
                FROM disposisi d2
                JOIN users u2 ON d2.ke_user_id = u2.id
                WHERE d2.id_surat = s.id AND d2.dari_user_id = ?) as disposisi_info,
               (SELECT MAX(d3.tanggal_disposisi) 
                FROM disposisi d3 
                WHERE d3.id_surat = s.id AND d3.dari_user_id = ?) as tanggal_disposisi_terakhir
        FROM surat s
        JOIN jenis_surat j ON s.id_jenis = j.id
        JOIN users u ON s.dibuat_oleh = u.id
        JOIN disposisi d ON s.id = d.id_surat
        WHERE s.status_surat NOT IN ('arsip')";

$params = [$userId, $userId];
$types = 'ii';

// Filter by user role
if ($userRole != 1) {
    // Non-superadmin: hanya surat yang DIDISPOSISIKAN OLEH mereka
    $sql .= " AND d.dari_user_id = ?";
    $params[] = $userId;
    $types .= 'i';
}

// Filter status surat
if (!empty($filters['status_surat'])) {
    $sql .= " AND s.status_surat = ?";
    $params[] = $filters['status_surat'];
    $types .= 's';
}

// Filter search
if (!empty($filters['search'])) {
    $search = "%" . $filters['search'] . "%";
    $sql .= " AND (s.nomor_agenda LIKE ? OR s.perihal LIKE ? OR s.nomor_surat LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

// Count total
$countSql = "SELECT COUNT(DISTINCT s.id) as total FROM surat s
             JOIN disposisi d ON s.id = d.id_surat
             WHERE s.status_surat NOT IN ('arsip')";

if ($userRole != 1) {
    $countSql .= " AND d.dari_user_id = $userId";
}

if (!empty($filters['status_surat'])) {
    $countSql .= " AND s.status_surat = '{$filters['status_surat']}'";
}

if (!empty($filters['search'])) {
    $search = "%" . $filters['search'] . "%";
    $countSql .= " AND (s.nomor_agenda LIKE '$search' OR s.perihal LIKE '$search' OR s.nomor_surat LIKE '$search')";
}

$countResult = $conn->query($countSql)->fetch_assoc();
$totalSurat = $countResult['total'];

// Get data with pagination
$sql .= " ORDER BY tanggal_disposisi_terakhir DESC LIMIT ? OFFSET ?";
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
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                    <i class=""></i>Disposisi Keluar
                </h1>
                <p class="text-gray-600 text-xs sm:text-sm mt-1">
                    <?php if ($userRole == 1): ?>
                        Semua surat yang Anda disposisikan
                    <?php else: ?>
                        Surat yang Anda disposisikan ke staff lain
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if ($userRole == 1): ?>
            <div class="inline-flex items-center px-3 py-1.5 bg-orange-100 text-orange-800 text-xs font-medium rounded-full">
                <i class="fas fa-shield-alt mr-1.5"></i> Mode Admin
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Section -->
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
                    <label class="block text-xs font-medium text-gray-500 mb-1.5">Status Surat</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm transition-shadow bg-white">
                        <option value="">Semua Status</option>
                        <option value="baru" <?= $filters['status_surat'] == 'baru' ? 'selected' : '' ?>>Baru</option>
                        <option value="proses" <?= $filters['status_surat'] == 'proses' ? 'selected' : '' ?>>Proses</option>
                        <option value="disetujui" <?= $filters['status_surat'] == 'disetujui' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="ditolak" <?= $filters['status_surat'] == 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                    <button type="submit" class="flex-1 lg:flex-none bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition text-sm flex justify-center items-center gap-2 font-medium">
                        <i class="fas fa-filter"></i> <span class="hidden sm:inline">Terapkan</span>
                    </button>
                    
                    <?php if (!empty($filters['search']) || !empty($filters['status_surat'])): ?>
                    <a href="disposisi_outbox.php" class="flex-1 lg:flex-none bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium text-center transition-colors flex justify-center items-center gap-2">
                        <i class="fas fa-times"></i> <span class="hidden sm:inline">Reset</span>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Desktop Table -->
        <div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nomor Surat</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Perihal</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Didisposisi Ke</th>
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
                                        <i class="fas fa-paper-plane text-4xl text-gray-200 mb-3"></i>
                                        <p class="text-sm">Belum ada surat yang Anda disposisikan</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($suratList as $surat): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
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
                                    <div class="text-xs text-gray-600 max-w-xs">
                                        <?= $surat['disposisi_info'] ? truncate($surat['disposisi_info'], 100) : '-' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    <i class="far fa-clock mr-1"></i>
                                    <?= formatDateTime($surat['tanggal_disposisi_terakhir']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php
                                    $badge = match($surat['status_surat']) {
                                        'baru' => 'bg-blue-100 text-blue-700 border-blue-200',
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
                                    <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                                       class="text-primary-600 hover:text-primary-800 font-medium">
                                        <i class="fas fa-eye mr-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="md:hidden space-y-4">
            <?php if (empty($suratList)): ?>
                <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">
                    <i class="fas fa-paper-plane text-4xl text-gray-200 mb-3"></i>
                    <p class="text-sm">Belum ada surat yang Anda disposisikan</p>
                </div>
            <?php else: ?>
                <?php foreach ($suratList as $surat): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="text-xs font-bold text-gray-900"><?= htmlspecialchars($surat['nomor_agenda']) ?></p>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($surat['nomor_surat']) ?></p>
                        </div>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border uppercase <?= getStatusBadge($surat['status_surat']) ?>">
                            <?= $surat['status_surat'] ?>
                        </span>
                    </div>

                    <p class="text-sm text-gray-800 mt-2 line-clamp-2"><?= htmlspecialchars($surat['perihal']) ?></p>

                    <div class="text-xs text-gray-500 mt-3 flex items-center">
                        <i class="far fa-clock mr-1"></i>
                        <?= formatDateTime($surat['tanggal_disposisi_terakhir']) ?>
                    </div>

                    <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                       class="mt-3 block text-center py-2 text-primary-600 bg-primary-50 hover:bg-primary-100 rounded-lg text-xs font-medium">
                        <i class="fas fa-eye mr-1"></i> Lihat Detail
                    </a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination->hasPages()): ?>
        <div class="mt-6 px-4 py-3 bg-white rounded-lg shadow-sm border border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm text-gray-600">
                Halaman <?= $page ?> dari <?= ceil($totalSurat / $perPage) ?>
            </div>
            <div>
                <?= $pagination->render('disposisi_outbox.php', $filters) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'partials/footer.php'; ?>