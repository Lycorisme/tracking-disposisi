<?php
// public/laporan/laporan_disposisi_pdf.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../modules/disposisi/disposisi_service.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$user = getCurrentUser();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

$query = "SELECT d.*, 
                 s.nomor_agenda, s.nomor_surat, s.perihal,
                 js.nama_jenis,
                 u1.nama_lengkap as dari_user_nama,
                 u2.nama_lengkap as ke_user_nama
          FROM disposisi d
          JOIN surat s ON d.id_surat = s.id
          JOIN jenis_surat js ON s.id_jenis = js.id
          JOIN users u1 ON d.dari_user_id = u1.id
          JOIN users u2 ON d.ke_user_id = u2.id
          WHERE DATE(d.tanggal_disposisi) BETWEEN ? AND ?
          ORDER BY d.tanggal_disposisi DESC";

$disposisiList = dbSelect($query, [$tanggalDari, $tanggalSampai], 'ss');
$totalDisposisi = count($disposisiList);

$byStatus = [];
foreach ($disposisiList as $disp) {
    $status = $disp['status_disposisi'];
    if (!isset($byStatus[$status])) $byStatus[$status] = 0;
    $byStatus[$status]++;
}

$totalResponseTime = 0;
$respondedCount = 0;
foreach ($disposisiList as $disp) {
    if ($disp['tanggal_respon']) {
        $sent = strtotime($disp['tanggal_disposisi']);
        $responded = strtotime($disp['tanggal_respon']);
        $diff = $responded - $sent;
        $totalResponseTime += $diff;
        $respondedCount++;
    }
}
$avgResponseHours = $respondedCount > 0 ? round($totalResponseTime / $respondedCount / 3600, 1) : 0;

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Disposisi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #ea580c; }
        .header h1 { font-size: 18pt; color: #c2410c; margin-bottom: 5px; }
        .header h2 { font-size: 14pt; color: #374151; font-weight: normal; margin-bottom: 3px; }
        .header p { font-size: 9pt; color: #6b7280; }
        .info-box { background: #fed7aa; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #ea580c; }
        .info-box p { margin: 3px 0; font-size: 9pt; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stat-row { display: table-row; }
        .stat-cell { display: table-cell; width: 20%; padding: 10px; text-align: center; background: #f9fafb; border: 1px solid #e5e7eb; }
        .stat-cell .label { font-size: 8pt; color: #6b7280; text-transform: uppercase; margin-bottom: 5px; }
        .stat-cell .value { font-size: 16pt; font-weight: bold; color: #1f2937; }
        .stat-cell.blue .value { color: #2563eb; }
        .stat-cell.yellow .value { color: #d97706; }
        .stat-cell.green .value { color: #059669; }
        .stat-cell.purple .value { color: #7c3aed; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead { background: #c2410c; color: white; }
        thead th { padding: 7px 5px; text-align: left; font-size: 7pt; font-weight: 600; text-transform: uppercase; border: 1px solid #9a3412; }
        tbody td { padding: 5px; border: 1px solid #e5e7eb; font-size: 7pt; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 6pt; font-weight: 600; text-transform: uppercase; }
        .badge.dikirim { background: #dbeafe; color: #1e40af; }
        .badge.diterima { background: #e0e7ff; color: #3730a3; }
        .badge.diproses { background: #fef3c7; color: #92400e; }
        .badge.selesai { background: #d1fae5; color: #065f46; }
        .badge.ditolak { background: #fee2e2; color: #991b1b; }
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
        <h2>LAPORAN DISPOSISI SURAT</h2>
        <p>Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?></p>
    </div>
    
    <div class="info-box">
        <p><strong>Dicetak oleh:</strong> <?= htmlspecialchars($user['nama_lengkap']) ?> (<?= getRoleLabel($user['role']) ?>)</p>
        <p><strong>Tanggal cetak:</strong> <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
        <p><strong>Total data:</strong> <?= $totalDisposisi ?> disposisi</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-row">
            <div class="stat-cell">
                <div class="label">Total</div>
                <div class="value"><?= $totalDisposisi ?></div>
            </div>
            <div class="stat-cell blue">
                <div class="label">Dikirim</div>
                <div class="value"><?= $byStatus['dikirim'] ?? 0 ?></div>
            </div>
            <div class="stat-cell yellow">
                <div class="label">Diproses</div>
                <div class="value"><?= $byStatus['diproses'] ?? 0 ?></div>
            </div>
            <div class="stat-cell green">
                <div class="label">Selesai</div>
                <div class="value"><?= $byStatus['selesai'] ?? 0 ?></div>
            </div>
            <div class="stat-cell purple">
                <div class="label">Avg Response</div>
                <div class="value"><?= $avgResponseHours ?>h</div>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">NO</th>
                <th style="width: 13%;">NO. AGENDA</th>
                <th style="width: 15%;">DARI</th>
                <th style="width: 15%;">KEPADA</th>
                <th style="width: 13%;">TGL DISPOSISI</th>
                <th style="width: 13%;">TGL RESPON</th>
                <th style="width: 10%;">STATUS</th>
                <th style="width: 17%;">CATATAN</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($disposisiList)): ?>
            <tr><td colspan="8" class="text-center">Tidak ada data disposisi untuk periode ini</td></tr>
            <?php else: ?>
                <?php foreach ($disposisiList as $index => $disp): ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td class="font-bold"><?= htmlspecialchars($disp['nomor_agenda']) ?></td>
                    <td><?= htmlspecialchars($disp['dari_user_nama']) ?></td>
                    <td><?= htmlspecialchars($disp['ke_user_nama']) ?></td>
                    <td class="text-center"><?= formatDateTime($disp['tanggal_disposisi']) ?></td>
                    <td class="text-center"><?= $disp['tanggal_respon'] ? formatDateTime($disp['tanggal_respon']) : '-' ?></td>
                    <td class="text-center">
                        <span class="badge <?= $disp['status_disposisi'] ?>">
                            <?= ucfirst($disp['status_disposisi']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars(truncate($disp['catatan'] ?? '-', 50)) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p class="mb-2"><strong>Catatan:</strong></p>
        <p>- Laporan ini dibuat secara otomatis oleh sistem</p>
        <p>- Avg Response = Rata-rata waktu respon disposisi (dalam jam)</p>
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
$dompdf->setPaper('A4', 'landscape'); // Landscape untuk lebih banyak kolom
$dompdf->render();

$filename = 'Laporan_Disposisi_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);