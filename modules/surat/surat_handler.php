<?php
// modules/surat/surat_handler.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/surat_service.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user = getCurrentUser();

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../../uploads/surat/');
}

try {
    switch ($action) {
        case 'create':
            $data = [
                'id_jenis' => $_POST['id_jenis'] ?? '',
                'nomor_surat' => $_POST['nomor_surat'] ?? '', // Boleh kosong
                'tanggal_surat' => $_POST['tanggal_surat'] ?? '',
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? $_POST['tanggal_diterima'] : null,
                'dari_instansi' => $_POST['dari_instansi'] ?? '',
                'ke_instansi' => $_POST['ke_instansi'] ?? '',
                'alamat_surat' => $_POST['alamat_surat'] ?? '',
                'perihal' => $_POST['perihal'] ?? '',
                'dibuat_oleh' => $user['id'],
                'lampiran_file' => null
            ];

            // Validasi input wajib (Nomor Surat DIHAPUS dari validasi wajib)
            if (empty($data['id_jenis']) || empty($data['tanggal_surat']) || empty($data['perihal'])) {
                throw new Exception("Mohon lengkapi data wajib (Jenis, Tanggal, Perihal)");
            }

            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = SuratService::uploadLampiran($_FILES['lampiran_file']);
                if ($uploadResult['success']) {
                    $data['lampiran_file'] = $uploadResult['filename'];
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            if (SuratService::create($data)) {
                logActivity($user['id'], 'tambah_surat', "Menambahkan surat baru");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil ditambahkan']);
            } else {
                throw new Exception("Gagal menyimpan data surat ke database");
            }
            break;

        case 'update':
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            $oldData = SuratService::getById($id);
            if (!$oldData) throw new Exception("Data surat tidak ditemukan");

            $data = [
                'id_jenis' => $_POST['id_jenis'] ?? '',
                'nomor_surat' => $_POST['nomor_surat'] ?? '',
                'tanggal_surat' => $_POST['tanggal_surat'] ?? '',
                'tanggal_diterima' => !empty($_POST['tanggal_diterima']) ? $_POST['tanggal_diterima'] : null,
                'dari_instansi' => $_POST['dari_instansi'] ?? '',
                'ke_instansi' => $_POST['ke_instansi'] ?? '',
                'alamat_surat' => $_POST['alamat_surat'] ?? '',
                'perihal' => $_POST['perihal'] ?? '',
                'lampiran_file' => $oldData['lampiran_file']
            ];

            if (isset($_FILES['lampiran_file']) && $_FILES['lampiran_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = SuratService::uploadLampiran($_FILES['lampiran_file']);
                if ($uploadResult['success']) {
                    $data['lampiran_file'] = $uploadResult['filename'];
                    if ($oldData['lampiran_file'] && file_exists(UPLOAD_PATH . $oldData['lampiran_file'])) {
                        unlink(UPLOAD_PATH . $oldData['lampiran_file']);
                    }
                } else {
                    throw new Exception($uploadResult['message']);
                }
            }

            if (SuratService::update($id, $data)) {
                logActivity($user['id'], 'edit_surat', "Mengupdate surat ID: $id");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil diperbarui']);
            } else {
                throw new Exception("Gagal mengupdate surat");
            }
            break;

        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            $surat = SuratService::getById($id);
            if (!$surat) throw new Exception("Surat tidak ditemukan");

            if (SuratService::delete($id)) {
                if ($surat['lampiran_file'] && file_exists(UPLOAD_PATH . $surat['lampiran_file'])) {
                    unlink(UPLOAD_PATH . $surat['lampiran_file']);
                }
                logActivity($user['id'], 'hapus_surat', "Menghapus surat ID: $id");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil dihapus']);
            } else {
                throw new Exception("Gagal menghapus surat");
            }
            break;

        case 'arsipkan':
            $id = $_POST['id'] ?? 0;
            if (!$id) throw new Exception("ID Surat tidak valid");

            if (SuratService::arsipkan($id)) {
                logActivity($user['id'], 'arsip_surat', "Mengarsipkan surat ID: $id");
                echo json_encode(['status' => 'success', 'message' => 'Surat berhasil diarsipkan']);
            } else {
                throw new Exception("Gagal mengarsipkan surat");
            }
            break;

        default:
            throw new Exception("Aksi tidak valid");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>