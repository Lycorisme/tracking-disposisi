<?php
// public/laporan/laporan_surat_keluar_pdf.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../modules/surat/surat_service.php';

use Dompdf\Dompdf;
use Dompdf\Options;

requireLogin();

$user = getCurrentUser();
$tanggalDari = $_GET['tanggal_dari'] ?? date('Y-m-01');
$tanggalSampai = $_GET['tanggal_sampai'] ?? date('Y-m-d');

$filters = [
    'id_jenis' => 2, // Surat Keluar
    'tanggal_dari' => $tanggalDari,
    'tanggal_sampai' => $tanggalSampai,
    'include_arsip' => true
];

$suratList = SuratService::getAll($filters, 1000, 0);
$totalSurat = count($suratList);

$byStatus = [];
foreach ($suratList as $surat) {
    $status = $surat['status_surat'];
    if (!isset($byStatus[$status])) {
        $byStatus[$status] = 0;
    }
    $byStatus[$status]++;
}

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Surat Keluar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #333; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #059669; }
        .header h1 { font-size: 18pt; color: #047857; margin-bottom: 5px; }
        .header h2 { font-size: 14pt; color: #374151; font-weight: normal; margin-bottom: 3px; }
        .header p { font-size: 9pt; color: #6b7280; }
        .info-box { background: #d1fae5; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #059669; }
        .info-box p { margin: 3px 0; font-size: 9pt; }
        .stats-grid { display: table; width: 100%; margin-bottom: 20px; }
        .stat-row { display: table-row; }
        .stat-cell { display: table-cell; width: 25%; padding: 10px; text-align: center; background: #f9fafb; border: 1px solid #e5e7eb; }
        .stat-cell .label { font-size: 8pt; color: #6b7280; text-transform: uppercase; margin-bottom: 5px; }
        .stat-cell .value { font-size: 18pt; font-weight: bold; color: #1f2937; }
        .stat-cell.blue .value { color: #2563eb; }
        .stat-cell.yellow .value { color: #d97706; }
        .stat-cell.green .value { color: #059669; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead { background: #047857; color: white; }
        thead th { padding: 8px 6px; text-align: left; font-size: 8pt; font-weight: 600; text-transform: uppercase; border: 1px solid #065f46; }
        tbody td { padding: 6px; border: 1px solid #e5e7eb; font-size: 8pt; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 7pt; font-weight: 600; text-transform: uppercase; }
        .badge.baru { background: #dbeafe; color: #1e40af; }
        .badge.proses { background: #fef3c7; color: #92400e; }
        .badge.disetujui { background: #d1fae5; color: #065f46; }
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
        <h2>LAPORAN SURAT KELUAR</h2>
        <p>Periode: <?= formatTanggal($tanggalDari) ?> - <?= formatTanggal($tanggalSampai) ?></p>
    </div>
    
    <div class="info-box">
        <p><strong>Dicetak oleh:</strong> <?= htmlspecialchars($user['nama_lengkap']) ?> (<?= getRoleLabel($user['role']) ?>)</p>
        <p><strong>Tanggal cetak:</strong> <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
        <p><strong>Total data:</strong> <?= $totalSurat ?> surat</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-row">
            <div class="stat-cell">
                <div class="label">Total Surat</div>
                <div class="value"><?= $totalSurat ?></div>
            </div>
            <div class="stat-cell blue">
                <div class="label">Baru</div>
                <div class="value"><?= $byStatus['baru'] ?? 0 ?></div>
            </div>
            <div class="stat-cell yellow">
                <div class="label">Diproses</div>
                <div class="value"><?= $byStatus['proses'] ?? 0 ?></div>
            </div>
            <div class="stat-cell green">
                <div class="label">Selesai</div>
                <div class="value"><?= $byStatus['disetujui'] ?? 0 ?></div>
            </div>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">NO</th>
                <th style="width: 15%;">NO. AGENDA</th>
                <th style="width: 20%;">KE INSTANSI</th>
                <th style="width: 35%;">PERIHAL</th>
                <th style="width: 15%;">TGL SURAT</th>
                <th style="width: 10%;">STATUS</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($suratList)): ?>
            <tr><td colspan="6" class="text-center">Tidak ada data surat keluar untuk periode ini</td></tr>
            <?php else: ?>
                <?php foreach ($suratList as $index => $surat): ?>
                <tr>
                    <td class="text-center"><?= $index + 1 ?></td>
                    <td class="font-bold"><?= htmlspecialchars($surat['nomor_agenda']) ?></td>
                    <td><?= htmlspecialchars($surat['ke_instansi'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(truncate($surat['perihal'], 80)) ?></td>
                    <td class="text-center"><?= formatTanggal($surat['tanggal_surat']) ?></td>
                    <td class="text-center">
                        <span class="badge <?= $surat['status_surat'] ?>">
                            <?= ucfirst($surat['status_surat']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p class="mb-2"><strong>Catatan:</strong></p>
        <p>- Laporan ini dibuat secara otomatis oleh sistem</p>
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

$filename = 'Laporan_Surat_Keluar_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, ["Attachment" => false]);