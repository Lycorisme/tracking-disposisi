<?php
// public/users.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/users/users_service.php';

// Pastikan hanya Superadmin yang bisa akses
requireLogin();
requireRole('superadmin');

$pageTitle = 'Manajemen User';
$currentStatus = $_GET['status'] ?? 'all';

// --- LOGIKA PAGINATION ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Jumlah data per halaman
$offset = ($page - 1) * $limit;

// Ambil semua data (karena method getAll di service belum support pagination langsung)
$allUsers = UsersService::getAll($currentStatus);
$totalData = count($allUsers);
$totalPages = ceil($totalData / $limit);

// Slice array untuk pagination halaman ini
$listUsers = array_slice($allUsers, $offset, $limit);

// Data Role untuk Dropdown Edit (JS)
$listRoles = UsersService::getRoles(); 
$rolesOptions = [];
foreach ($listRoles as $r) {
    $rolesOptions[$r['id']] = ucfirst($r['nama_role']);
}
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen bg-gray-100">
    <?php include 'partials/sidebar.php'; ?>

    <div class="flex-1 lg:ml-64 p-4 sm:p-6 lg:p-8 transition-all duration-300">
        <div class="mb-6">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Manajemen Pengguna</h1>
            <p class="text-sm sm:text-base text-gray-600">Kelola pendaftaran, hak akses, dan role pengguna</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
            <div class="border-b border-gray-200 overflow-x-auto scrollbar-hide">
                <nav class="flex -mb-px min-w-max px-2">
                    <?php
                    $tabs = [
                        'all' => 'Semua User',
                        'pending' => 'Menunggu',
                        'active' => 'Aktif',
                        'rejected' => 'Ditolak'
                    ];
                    
                    foreach ($tabs as $key => $label): 
                        $isActive = ($currentStatus == $key);
                        $activeClass = $isActive 
                            ? 'border-primary-500 text-primary-600' 
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                    ?>
                        <a href="?status=<?= $key ?>" class="whitespace-nowrap py-4 px-4 text-center border-b-2 font-medium text-sm <?= $activeClass ?> transition-colors duration-200 flex items-center">
                            <?= $label ?>
                            <?php if ($key == 'pending'): ?>
                                <?php $count = UsersService::countPending(); ?>
                                <?php if ($count > 0): ?>
                                    <span class="ml-2 bg-red-100 text-red-600 py-0.5 px-2 rounded-full text-xs font-bold shadow-sm"><?= $count ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <div class="hidden md:block bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama / Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role & Bagian</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($listUsers)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                                        <span>Tidak ada data user ditemukan.</span>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($listUsers as $index => $u): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center w-12">
                                    <?= $offset + $index + 1 ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold">
                                            <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center group cursor-pointer" onclick="changeRole(<?= $u['id'] ?>, <?= $u['id_role'] ?>)">
                                        <span class="text-sm text-gray-900 font-medium mr-2">
                                            <?= ucfirst($u['nama_role'] ?? 'User') ?>
                                        </span>
                                        <i class="fas fa-pencil-alt text-gray-300 group-hover:text-primary-500 text-xs transition-colors" title="Ubah Role"></i>
                                    </div>
                                    <?php 
                                        $bagian = !empty($u['nama_bagian_custom']) ? $u['nama_bagian_custom'] : ($u['nama_bagian'] ?? '-');
                                    ?>
                                    <div class="text-xs text-gray-500 mt-1">Bagian: <?= htmlspecialchars($bagian) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <?php
                                    $statusConfig = [
                                        'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'label' => 'Aktif'],
                                        'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Pending'],
                                        'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Ditolak'],
                                        'banned' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => 'Banned']
                                    ];
                                    $status = $u['status'] ?? 'pending';
                                    $config = $statusConfig[$status] ?? $statusConfig['pending'];
                                    ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $config['bg'] . ' ' . $config['text'] ?>">
                                        <?= $config['label'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <?php if ($status === 'pending'): ?>
                                        <div class="flex justify-center space-x-2">
                                            <button onclick="updateStatus(<?= $u['id'] ?>, 'approve')" class="text-white bg-green-500 hover:bg-green-600 px-3 py-1 rounded shadow-sm transition-colors text-xs" title="Setujui">
                                                <i class="fas fa-check mr-1"></i> Terima
                                            </button>
                                            <button onclick="updateStatus(<?= $u['id'] ?>, 'reject')" class="text-white bg-red-500 hover:bg-red-600 px-3 py-1 rounded shadow-sm transition-colors text-xs" title="Tolak">
                                                <i class="fas fa-times mr-1"></i> Tolak
                                            </button>
                                        </div>
                                    <?php elseif ($status === 'active'): ?>
                                        <button onclick="deleteUser(<?= $u['id'] ?>)" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-full transition-colors" title="Hapus User">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php elseif ($status === 'rejected'): ?>
                                        <button onclick="updateStatus(<?= $u['id'] ?>, 'approve')" class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-full transition-colors text-xs" title="Aktifkan Kembali">
                                            <i class="fas fa-undo mr-1"></i> Pulihkan
                                        </button>
                                        <button onclick="deleteUser(<?= $u['id'] ?>)" class="text-gray-400 hover:text-red-600 ml-2" title="Hapus Permanen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="md:hidden space-y-4">
            <?php if (empty($listUsers)): ?>
                <div class="bg-white rounded-lg shadow-sm p-8 text-center text-gray-500">
                    <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                    <p>Tidak ada data user ditemukan.</p>
                </div>
            <?php else: ?>
                <?php foreach ($listUsers as $u): ?>
                    <?php
                        $status = $u['status'] ?? 'pending';
                        $config = $statusConfig[$status] ?? $statusConfig['pending'];
                        $bagian = !empty($u['nama_bagian_custom']) ? $u['nama_bagian_custom'] : ($u['nama_bagian'] ?? '-');
                    ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-bold text-sm shrink-0">
                                    <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($u['nama_lengkap']) ?></h3>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold <?= $config['bg'] . ' ' . $config['text'] ?>">
                                <?= $config['label'] ?>
                            </span>
                        </div>

                        <div class="mb-4 pl-[3.25rem] space-y-1">
                            <div class="flex items-center text-xs text-gray-600">
                                <span class="font-medium w-12">Role:</span> 
                                <span class="bg-gray-100 px-2 py-0.5 rounded text-gray-700">
                                    <?= ucfirst($u['nama_role'] ?? 'User') ?>
                                </span>
                                <button onclick="changeRole(<?= $u['id'] ?>, <?= $u['id_role'] ?>)" class="ml-2 text-primary-600 hover:text-primary-800">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </div>
                            <div class="flex items-center text-xs text-gray-600">
                                <span class="font-medium w-12">Bagian:</span> 
                                <span class="text-gray-500"><?= htmlspecialchars($bagian) ?></span>
                            </div>
                        </div>

                        <div class="flex gap-2 border-t pt-3">
                            <?php if ($status === 'pending'): ?>
                                <button onclick="updateStatus(<?= $u['id'] ?>, 'approve')" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded-lg text-xs font-medium">
                                    <i class="fas fa-check mr-1"></i> Terima
                                </button>
                                <button onclick="updateStatus(<?= $u['id'] ?>, 'reject')" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg text-xs font-medium">
                                    <i class="fas fa-times mr-1"></i> Tolak
                                </button>
                            <?php elseif ($status === 'active'): ?>
                                <button onclick="deleteUser(<?= $u['id'] ?>)" class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-lg text-xs font-medium transition-colors">
                                    <i class="fas fa-trash-alt mr-1"></i> Hapus User
                                </button>
                            <?php elseif ($status === 'rejected'): ?>
                                <button onclick="updateStatus(<?= $u['id'] ?>, 'approve')" class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-600 py-2 rounded-lg text-xs font-medium">
                                    <i class="fas fa-undo mr-1"></i> Pulihkan
                                </button>
                                <button onclick="deleteUser(<?= $u['id'] ?>)" class="bg-gray-100 hover:bg-gray-200 text-gray-500 py-2 px-3 rounded-lg text-xs font-medium">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex flex-col sm:flex-row justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-100 gap-4">
            <div class="text-sm text-gray-600">
                Halaman <span class="font-medium"><?= $page ?></span> dari <span class="font-medium"><?= $totalPages ?></span>
            </div>
            
            <nav class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?status=<?= $currentStatus ?>&page=<?= $page - 1 ?>" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Previous
                </a>
                <?php endif; ?>

                <div class="hidden sm:flex gap-1">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="px-3 py-1.5 border border-primary-500 bg-primary-50 text-primary-600 rounded-md text-sm font-medium">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <a href="?status=<?= $currentStatus ?>&page=<?= $i ?>" class="px-3 py-1.5 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 rounded-md text-sm font-medium">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <?php if ($page < $totalPages): ?>
                <a href="?status=<?= $currentStatus ?>&page=<?= $page + 1 ?>" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </a>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Data Role dari PHP ke JS
const availableRoles = <?= json_encode($rolesOptions) ?>;

function changeRole(userId, currentRoleId) {
    Swal.fire({
        title: 'Ubah Role Pengguna',
        input: 'select',
        inputOptions: availableRoles,
        inputValue: currentRoleId,
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        confirmButtonColor: '#3B82F6',
        showLoaderOnConfirm: true,
        preConfirm: (newRoleId) => {
            return $.post('../modules/users/users_handler.php', {
                action: 'change_role',
                id: userId,
                role_id: newRoleId
            }, function(response) {
                if (response.status !== 'success') {
                    Swal.showValidationMessage(response.message);
                }
                return response;
            }, 'json').fail(() => {
                Swal.showValidationMessage('Terjadi kesalahan koneksi');
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value.status === 'success') {
            Swal.fire({
                title: 'Berhasil!',
                text: 'Role pengguna berhasil diperbarui.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        }
    });
}

function updateStatus(id, action) {
    let title = action === 'approve' ? 'Setujui Pendaftaran?' : 'Tolak Pendaftaran?';
    let text = action === 'approve' ? 'User akan dapat login ke sistem.' : 'User tidak akan bisa login.';
    let btnColor = action === 'approve' ? '#10B981' : '#EF4444';

    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: btnColor,
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Lakukan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../modules/users/users_handler.php', { 
                action: action, 
                id: id 
            }, function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            }, 'json').fail(function() {
                Swal.fire('Error', 'Terjadi kesalahan koneksi server', 'error');
            });
        }
    });
}

function deleteUser(id) {
    Swal.fire({
        title: 'Hapus User?',
        text: 'Data yang dihapus tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Ya, Hapus Permanen'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../modules/users/users_handler.php', { 
                action: 'delete', 
                id: id 
            }, function(res) {
                if(res.status === 'success') {
                    Swal.fire({
                        title: 'Terhapus!',
                        text: res.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>

<?php include 'partials/footer.php'; ?>