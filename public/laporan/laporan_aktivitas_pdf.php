<?php
// public/laporan/laporan_aktivitas_pdf.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();
requireRole('superadmin');

$user = getCurrentUser();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-d');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? '';

$query = "SELECT l.*, u.nama_lengkap, u.email
          FROM log_aktivitas l
          JOIN users u ON l.user_id = u.id
          WHERE DATE(l.created_at) BETWEEN ? AND ?";

$params = [$tanggalDari, $tanggalSampai];
$types = 'ss';

if (!empty($userId)) {
    $query .= " AND l.user_id = ?";
    $params[] = $userId;
    $types .= 'i';
}

$query .= " ORDER BY l.created_at DESC LIMIT 500";

$logList = dbSelect($query, $params, $types);

$byActivity = [];
foreach ($logList as $log) {
    $act = $log['aktivitas'];
    if (!isset($byActivity[$act])) $byActivity[$act] = 0;
    $byActivity[$act]++;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Aktivitas Sistem</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #7c3aed; }
        .header h1 { font-size: 18pt; color: #6d28d9; margin-bottom: 5px; }
        .header h2 { font-size: 14pt; color: #374151; font-weight: normal; margin-bottom: 3px; }
        .header p { font-size: 9pt; color: #6b7280; }
        .info-box { background: #ede9fe; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #7c3aed; }
        .info-box p { margin: 3px 0; font-size: 9pt; }
        .stats-container { margin-bottom: 20px; }
        .stats-title { font-size: 10pt; font-weight: bold; margin-bottom: 10px; color: #374151; }
        .stats-grid { display: table; width: 100%; }
        .stats-row { display: table-row; }
        .stat-item { display: table-cell; width: 16.66%; padding: 8px; text-align: center; background: #f9fafb; border: 1px solid #e5e7eb; }
        .stat-item .label { font-size: 7pt; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; }
        .stat-item .value { font-size: 14pt; font-weight: bold; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead { background: #6d28d9; color: white; }
        thead th { padding: 7px 5px; text-align: left; font-size: 7pt; font-weight: 600; text-transform: uppercase; border: 1px solid #5b21b6; }
        tbody td { padding: 5px; border: 1px solid #e5e7eb; font-size: 7pt; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 6pt; font-weight: 600; text-transform: uppercase; background: #dbeafe; color: #1e40af; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #e5e7eb; font-size: 8pt; color: #6b7280; }
        .footer .signature { margin-top: 50px; text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= APP_NAME ?></h1>
        <h2>LOG AKTIVITAS SISTEM</h2>
        <p>Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?></p>
    </div>
    
    <div class="info-box">
        <p><strong>Dicetak oleh:</strong> <?= htmlspecialchars($user['nama_lengkap']) ?> (<?= getRoleLabel($user['role']) ?>)</p>
        <p><strong>Tanggal cetak:</strong> <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
        <p><strong>Total log:</strong> <?= count($logList) ?> aktivitas (Max 500 terbaru)</p>
    </div>
    
    <div class="stats-container">
        <div class="stats-title">Ringkasan Aktivitas</div>
        <div class="stats-grid">
            <div class="stats-row">
                <?php 
                $count = 0;
                foreach ($byActivity as $activity => $total): 
                    if ($count > 0 && $count % 6 == 0): ?>
                        </div><div class="stats-row">
                    <?php endif; ?>
                    <div class="stat-item">
                        <div class="label"><?= ucfirst(str_replace('_', ' ', $activity)) ?></div>
                        <div class="value"><?= $total ?></div>
                    </div>
                <?php 
                    $count++;
                endforeach; 
                ?>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 15%;">WAKTU</th>
                <th style="width: 18%;">USER</th>
                <th style="width: 13%;">AKTIVITAS</th>
                <th style="width: 50%;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logList)): ?>
            <tr><td colspan="5" class="text-center">Tidak ada log aktivitas untuk periode ini</td></tr>
            <?php else: ?>
                <?php foreach ($logList as $index => $log): ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td class="text-center"><?= formatDateTime($log['created_at']) ?></td>
                    <td>
                        <div class="font-bold"><?= htmlspecialchars($log['nama_lengkap']) ?></div>
                        <div style="font-size: 6pt; color: #6b7280;"><?= htmlspecialchars($log['email']) ?></div>
                    </td>
                    <td class="text-center">
                        <span class="badge">
                            <?= ucfirst(str_replace('_', ' ', $log['aktivitas'])) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['keterangan'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p class="mb-2"><strong>Catatan:</strong></p>
        <p>- Laporan ini dibuat secara otomatis oleh sistem</p>
        <p>- Maksimal 500 log aktivitas terbaru yang ditampilkan</p>
        <p>- Data ini bersifat rahasia dan hanya untuk keperluan audit internal</p>
        <div class="signature">
            <p>Banjarmasin, <?= formatTanggal(date('Y-m-d')) ?></p>
            <p style="margin-top: 60px;">
                <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong><br>
                <?= getRoleLabel($user['role']) ?>
            </p>
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Log_Aktivitas_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);