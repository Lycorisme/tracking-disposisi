<?php
// public/pengaturan.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../modules/settings/settings_service.php';

requireLogin();
requireRole('superadmin');

$user = getCurrentUser();
$pageTitle = 'Pengaturan Sistem';

// Get current settings
$settings = SettingsService::getSettings();

if (!$settings) {
    SettingsService::initializeDefaults();
    $settings = SettingsService::getSettings();
}
?>

<?php include 'partials/header.php'; ?>

<div class="flex min-h-screen">
    <?php include 'partials/sidebar.php'; ?>
    
    <div class="flex-1 lg:ml-64">
        <main class="p-6 lg:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Pengaturan Sistem</h1>
                <p class="text-gray-600">Kelola pengaturan aplikasi, instansi, dan tanda tangan</p>
            </div>
            
            <form id="settingsForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px" aria-label="Tabs">
                            <button type="button" onclick="showTab('aplikasi')" id="tab-aplikasi" class="tab-button border-b-2 border-primary-600 py-4 px-6 text-sm font-medium text-primary-600">
                                <i class="fas fa-cog mr-2"></i>Aplikasi
                            </button>
                            <button type="button" onclick="showTab('instansi')" id="tab-instansi" class="tab-button border-b-2 border-transparent py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-building mr-2"></i>Instansi
                            </button>
                            <button type="button" onclick="showTab('ttd')" id="tab-ttd" class="tab-button border-b-2 border-transparent py-4 px-6 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                <i class="fas fa-signature mr-2"></i>Tanda Tangan
                            </button>
                        </nav>
                    </div>
                    
                    <div id="content-aplikasi" class="tab-content p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Aplikasi</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Aplikasi *</label>
                                <input type="text" name="app_name" value="<?= htmlspecialchars($settings['app_name']) ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Deskripsi Aplikasi</label>
                                <textarea name="app_description" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?= htmlspecialchars($settings['app_description']) ?></textarea>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Tema Warna Aplikasi</label>
                                <div class="flex flex-wrap gap-4">
                                    <?php
                                    $colors = [
                                        'blue' => 'bg-blue-600',
                                        'indigo' => 'bg-indigo-600',
                                        'red' => 'bg-red-600',
                                        'emerald' => 'bg-emerald-600',
                                        'orange' => 'bg-orange-600',
                                        'purple' => 'bg-purple-600',
                                        'cyan' => 'bg-cyan-600',
                                        'slate' => 'bg-slate-600'
                                    ];
                                    $currentTheme = $settings['theme_color'] ?? 'blue';
                                    ?>
                                    
                                    <?php foreach($colors as $name => $bgClass): ?>
                                    <label class="cursor-pointer group relative">
                                        <input type="radio" name="theme_color" value="<?= $name ?>" class="peer sr-only" <?= $currentTheme == $name ? 'checked' : '' ?>>
                                        <div class="w-10 h-10 rounded-full <?= $bgClass ?> peer-checked:ring-4 peer-checked:ring-offset-2 peer-checked:ring-gray-300 transition-all shadow-sm hover:scale-110 flex items-center justify-center text-white">
                                            <i class="fas fa-check opacity-0 peer-checked:opacity-100"></i>
                                        </div>
                                        <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-xs text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity capitalize"><?= $name ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Warna ini akan diterapkan pada sidebar, tombol, dan link aktif. (Perlu refresh halaman setelah simpan)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logo Aplikasi</label>
                                <div id="current_app_logo_container">
                                    <?php if ($settings['app_logo']): ?>
                                    <div class="mb-2">
                                        <img src="<?= SETTINGS_UPLOAD_URL . $settings['app_logo'] ?>" alt="Logo" class="h-16 border rounded" id="preview_app_logo_img">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="app_logo" accept=".png,.jpg,.jpeg,.svg" id="app_logo_input"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <p class="text-xs text-gray-500 mt-1">Format: PNG, JPG, SVG (Max 2MB)</p>
                                <div id="app_logo_preview" class="mt-2"></div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Favicon</label>
                                <div id="current_favicon_container">
                                    <?php if ($settings['app_favicon']): ?>
                                    <div class="mb-2">
                                        <img src="<?= SETTINGS_UPLOAD_URL . $settings['app_favicon'] ?>" alt="Favicon" class="h-8 border rounded" id="preview_favicon_img">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="app_favicon" accept=".ico,.png" id="app_favicon_input"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <p class="text-xs text-gray-500 mt-1">Format: ICO, PNG (Max 2MB)</p>
                                <div id="app_favicon_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="content-instansi" class="tab-content p-6 hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Instansi</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Instansi *</label>
                                <textarea name="instansi_nama" rows="2" required
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?= htmlspecialchars($settings['instansi_nama']) ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">Gunakan Enter untuk baris baru</p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alamat Instansi</label>
                                <textarea name="instansi_alamat" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"><?= htmlspecialchars($settings['instansi_alamat']) ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Telepon</label>
                                <input type="text" name="instansi_telepon" value="<?= htmlspecialchars($settings['instansi_telepon']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="(0511) 1234567">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="instansi_email" value="<?= htmlspecialchars($settings['instansi_email']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="admin@instansi.go.id">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logo Instansi (untuk KOP Surat)</label>
                                <div id="current_instansi_logo_container">
                                    <?php if ($settings['instansi_logo']): ?>
                                    <div class="mb-2">
                                        <img src="<?= SETTINGS_UPLOAD_URL . $settings['instansi_logo'] ?>" alt="Logo Instansi" class="h-20 border rounded" id="preview_instansi_logo_img">
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="instansi_logo" accept=".png,.jpg,.jpeg,.svg" id="instansi_logo_input"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <p class="text-xs text-gray-500 mt-1">Format: PNG, JPG, SVG (Max 2MB) - Logo ini akan muncul di laporan PDF</p>
                                <div id="instansi_logo_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="content-ttd" class="tab-content p-6 hidden">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pengaturan Tanda Tangan</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nama Penandatangan</label>
                                <input type="text" name="ttd_nama_penandatangan" value="<?= htmlspecialchars($settings['ttd_nama_penandatangan']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="Nama Lengkap">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">NIP</label>
                                <input type="text" name="ttd_nip" value="<?= htmlspecialchars($settings['ttd_nip']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="19XXXXX XXXXXX X XXX">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jabatan</label>
                                <input type="text" name="ttd_jabatan" value="<?= htmlspecialchars($settings['ttd_jabatan']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="Kepala Dinas">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Kota</label>
                                <input type="text" name="ttd_kota" value="<?= htmlspecialchars($settings['ttd_kota']) ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                       placeholder="Banjarmasin">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Scan Tanda Tangan (Gambar)</label>
                                <div id="current_ttd_image_container">
                                    <?php if (!empty($settings['ttd_image'])): ?>
                                    <div class="mb-2">
                                        <img src="<?= SETTINGS_UPLOAD_URL . $settings['ttd_image'] ?>" alt="TTD" class="h-24 border rounded" id="preview_ttd_image_img">
                                        <p class="text-xs text-gray-500 mt-1">File saat ini: <?= $settings['ttd_image'] ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="ttd_image" accept=".png,.jpg,.jpeg,.svg" id="ttd_image_input"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <p class="text-xs text-gray-500 mt-1">Format: PNG, JPG (Disarankan background transparan/PNG). Kosongkan jika ingin menggunakan TTD manual/teks saja.</p>
                                <div id="ttd_image_preview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <a href="<?= BASE_URL ?>/index.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" id="btnSave" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">
                        <i class="fas fa-save mr-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </main>
        
        <?php include 'partials/footer.php'; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Tab switching
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('border-primary-600', 'text-primary-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    document.getElementById('content-' + tabName).classList.remove('hidden');
    
    const activeButton = document.getElementById('tab-' + tabName);
    activeButton.classList.remove('border-transparent', 'text-gray-500');
    activeButton.classList.add('border-primary-600', 'text-primary-600');
}

// Image preview
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById(previewId);
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" class="h-20 border rounded mt-2" alt="Preview">';
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    });
}

setupImagePreview('app_logo_input', 'app_logo_preview');
setupImagePreview('app_favicon_input', 'app_favicon_preview');
setupImagePreview('instansi_logo_input', 'instansi_logo_preview');
setupImagePreview('ttd_image_input', 'ttd_image_preview');

// AJAX Submission
$(document).ready(function() {
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault(); 
        
        const btn = $('#btnSave');
        const originalText = btn.html();
        
        const appName = document.querySelector('[name="app_name"]').value.trim();
        const instansiNama = document.querySelector('[name="instansi_nama"]').value.trim();
        
        if (!appName) {
            Swal.fire('Error', 'Nama aplikasi harus diisi', 'error');
            showTab('aplikasi');
            return false;
        }
        
        if (!instansiNama) {
            Swal.fire('Error', 'Nama instansi harus diisi', 'error');
            showTab('instansi');
            return false;
        }

        const formData = new FormData(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');
        
        $.ajax({
            url: '<?= BASE_URL ?>/../modules/settings/settings_handler.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalText);
                
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Reload agar warna tema berubah
                        location.reload(); 
                    });
                } else {
                    Swal.fire('Gagal', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html(originalText);
                console.error(xhr.responseText);
                Swal.fire('Error', 'Terjadi kesalahan sistem. Cek console log.', 'error');
            }
        });
    });
});
</script>

<?php include 'partials/footer.php'; ?>