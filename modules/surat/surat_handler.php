<?php
// modules/surat/surat_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/surat_service.php';

requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user   = getCurrentUser();

// fungsi redirect sederhana
function goBack($fallback = '../../public/surat.php') {
    $redirect = $_SERVER['HTTP_REFERER'] ?? $fallback;
    header("Location: " . $redirect);
    exit;
}

try {
    switch ($action) {
        case 'create':
            // Validasi input wajib
            if (empty($_POST['id_jenis']) || empty($_POST['nomor_surat']) ||
                empty($_POST['tanggal_surat']) || empty($_POST['alamat_surat']) ||
                empty($_POST['perihal'])) {
                throw new Exception('Semua field wajib harus diisi.');
            }

            // Validasi jenis surat
            $jenisId = (int) $_POST['id_jenis'];
            if ($jenisId < 1) {
                throw new Exception('Jenis surat tidak valid.');
            }

            $data = [
                'id_jenis'         => $jenisId,
                'nomor_agenda'     => generateNomorAgenda($jenisId),
                'nomor_surat'      => sanitize($_POST['nomor_surat']),
                'tanggal_surat'    => sanitize($_POST['tanggal_surat']),
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? sanitize($_POST['tanggal_diterima']) : null,
                'dari_instansi'    => !empty($_POST['dari_instansi']) ? sanitize($_POST['dari_instansi']) : null,
                'ke_instansi'      => !empty($_POST['ke_instansi']) ? sanitize($_POST['ke_instansi']) : null,
                'alamat_surat'     => sanitize($_POST['alamat_surat']),
                'perihal'          => sanitize($_POST['perihal']),
                'status_surat'     => 'baru',
                'dibuat_oleh'      => $user['id']
            ];

            // Validasi tanggal
            if (!isValidDate($data['tanggal_surat'])) {
                throw new Exception('Format tanggal surat tidak valid.');
            }

            // Upload file (opsional)
            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['lampiran_file']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadFile($_FILES['lampiran_file']);
                    if (!$upload['success']) {
                        throw new Exception($upload['message']);
                    }
                    $data['lampiran_file'] = $upload['filename'];
                } else {
                    throw new Exception('Terjadi kesalahan saat upload file.');
                }
            }

            $suratId = SuratService::create($data);
            if (!$suratId) {
                throw new Exception('Gagal menyimpan surat ke database.');
            }

            logActivity($user['id'], 'tambah_surat', "Menambah surat: {$data['nomor_agenda']}");

            // tanpa sweetalert: langsung balik ke list
            goBack();
            break;

        case 'update':
            requireRole(['admin', 'superadmin']);

            $id = (int) ($_POST['id'] ?? 0);
            if ($id < 1) {
                throw new Exception('ID surat tidak valid.');
            }

            $surat = SuratService::getById($id);
            if (!$surat) {
                throw new Exception('Surat tidak ditemukan.');
            }

            if (empty($_POST['id_jenis']) || empty($_POST['nomor_surat']) ||
                empty($_POST['tanggal_surat']) || empty($_POST['alamat_surat']) ||
                empty($_POST['perihal'])) {
                throw new Exception('Semua field wajib harus diisi.');
            }

            $data = [
                'id_jenis'         => (int) $_POST['id_jenis'],
                'nomor_surat'      => sanitize($_POST['nomor_surat']),
                'tanggal_surat'    => sanitize($_POST['tanggal_surat']),
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? sanitize($_POST['tanggal_diterima']) : null,
                'dari_instansi'    => !empty($_POST['dari_instansi']) ? sanitize($_POST['dari_instansi']) : null,
                'ke_instansi'      => !empty($_POST['ke_instansi']) ? sanitize($_POST['ke_instansi']) : null,
                'alamat_surat'     => sanitize($_POST['alamat_surat']),
                'perihal'          => sanitize($_POST['perihal']),
                'status_surat'     => $surat['status_surat'],
                'lampiran_file'    => $surat['lampiran_file']
            ];

            if (!isValidDate($data['tanggal_surat'])) {
                throw new Exception('Format tanggal surat tidak valid.');
            }

            // Upload file baru kalau ada
            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['lampiran_file']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadFile($_FILES['lampiran_file'], $surat['lampiran_file']);
                    if (!$upload['success']) {
                        throw new Exception($upload['message']);
                    }
                    $data['lampiran_file'] = $upload['filename'];
                } else {
                    throw new Exception('Terjadi kesalahan saat upload file.');
                }
            }

            SuratService::update($id, $data);
            logActivity($user['id'], 'edit_surat', "Mengedit surat ID: {$id}");

            goBack();
            break;

        case 'delete':
            requireRole(['superadmin']);

            $id = (int) ($_POST['id'] ?? 0);
            if ($id < 1) {
                throw new Exception('ID surat tidak valid.');
            }

            $surat = SuratService::getById($id);
            if (!$surat) {
                throw new Exception('Surat tidak ditemukan.');
            }

            // Hapus file jika ada
            if (!empty($surat['lampiran_file']) && file_exists(UPLOAD_DIR . $surat['lampiran_file'])) {
                @unlink(UPLOAD_DIR . $surat['lampiran_file']);
            }

            SuratService::delete($id);
            logActivity($user['id'], 'hapus_surat', "Menghapus surat ID: {$id}");

            goBack();
            break;

        case 'update_status':
            requireRole(['admin', 'superadmin']);

            $id     = (int) ($_POST['id'] ?? 0);
            $status = sanitize($_POST['status'] ?? '');

            if ($id < 1) {
                throw new Exception('ID surat tidak valid.');
            }

            $allowedStatus = ['baru', 'proses', 'ditolak', 'disetujui', 'arsip'];
            if (!in_array($status, $allowedStatus, true)) {
                throw new Exception('Status tidak valid.');
            }

            SuratService::updateStatus($id, $status);
            logActivity(
                $user['id'],
                'update_status_surat',
                "Mengubah status surat ID {$id} menjadi {$status}"
            );

            goBack();
            break;

        case 'arsipkan':
            requireRole(['admin', 'superadmin']);

            $id = (int) ($_POST['id'] ?? 0);
            if ($id < 1) {
                throw new Exception('ID surat tidak valid.');
            }

            SuratService::updateStatus($id, 'arsip');
            logActivity($user['id'], 'arsip_surat', "Mengarsipkan surat ID: {$id}");

            goBack();
            break;

        default:
            throw new Exception('Action tidak valid.');
    }
} catch (Exception $e) {
    // log teknis saja
    error_log("Surat Handler Error: " . $e->getMessage());
    // untuk development, bisa tampilkan langsung:
    // die('Error: ' . $e->getMessage());
    goBack();
}
