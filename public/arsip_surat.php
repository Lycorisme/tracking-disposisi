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
$pageTitle = 'Arsip Surat';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalArsip = SuratService::countArsip();
$pagination = new Pagination($totalArsip, $perPage, $page);

$arsipList = SuratService::getArsip($perPage, $offset);
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Arsip Surat</h1>
                <p class="text-gray-600">Daftar surat yang telah diarsipkan</p>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
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
                                    <p>Belum ada surat yang diarsipkan</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($arsipList as $surat): ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= formatTanggal($surat['updated_at']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex space-x-2">
                                            <a href="surat_detail.php?id=<?= $surat['id'] ?>" 
                                               class="text-blue-600 hover:text-blue-800" 
                                               title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if ($surat['lampiran_file']): ?>
                                            <a href="<?= UPLOAD_URL . $surat['lampiran_file'] ?>" 
                                               target="_blank"
                                               class="text-green-600 hover:text-green-800" 
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
                <div class="border-t border-gray-200">
                    <?= $pagination->render('arsip_surat.php') ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>