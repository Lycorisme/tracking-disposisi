<?php
// public/partials/footer.php
?>

<!-- Footer -->
<footer class="bg-white border-t border-gray-200 py-4 mt-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500">
            &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>. Bank Kalsel.
        </p>
    </div>
</footer>

<!-- Helper Functions -->
<script>
// SweetAlert2 helpers
function showSuccess(message, title = 'Berhasil') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        timer: 3000,
        showConfirmButton: false
    });
}

function showError(message, title = 'Error') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        showConfirmButton: true
    });
}

function showWarning(message, title = 'Peringatan') {
    Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        showConfirmButton: true
    });
}

function showInfo(message, title = 'Informasi') {
    Swal.fire({
        icon: 'info',
        title: title,
        text: message,
        showConfirmButton: true
    });
}

function confirmAction(message, callback, title = 'Yakin?') {
    Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

function confirmDelete(callback, itemName = 'data ini') {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: `${itemName} akan dihapus secara permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

// Loading overlay
function showLoading(message = 'Memproses...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoading() {
    Swal.close();
}

// Form validation helpers
function validateRequired(value, fieldName) {
    if (!value || value.trim() === '') {
        showError(`${fieldName} harus diisi`);
        return false;
    }
    return true;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!re.test(email)) {
        showError('Format email tidak valid');
        return false;
    }
    return true;
}

function validateFile(file, maxSize = 5242880, allowedExts = ['pdf', 'jpg', 'jpeg', 'png']) {
    if (!file) return true; // Optional file
    
    // Check size (default 5MB)
    if (file.size > maxSize) {
        showError('Ukuran file maksimal ' + (maxSize / 1048576) + 'MB');
        return false;
    }
    
    // Check extension
    const ext = file.name.split('.').pop().toLowerCase();
    if (!allowedExts.includes(ext)) {
        showError('Format file harus: ' + allowedExts.join(', '));
        return false;
    }
    
    return true;
}

// Date formatting
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return '-';
    const date = new Date(dateTimeString);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const time = date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
    return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear() + ' ' + time;
}

// Utility functions
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showSuccess('Berhasil disalin ke clipboard');
    }).catch(() => {
        showError('Gagal menyalin ke clipboard');
    });
}

function printPage() {
    window.print();
}

// Auto-close alerts after certain time
document.addEventListener('DOMContentLoaded', function() {
    // Auto dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
});
</script>

</body>
</html>