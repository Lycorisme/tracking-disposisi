<?php
// includes/auth.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current logged in user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'id_role' => $_SESSION['id_role'] ?? 0,
        'id_bagian' => $_SESSION['id_bagian'] ?? null
    ];
}

// Require login - redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php?error=not_logged_in');
        exit;
    }
}

// Check if user has specific role
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? '';
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

// Require specific role - redirect if not authorized
function requireRole($roles) {
    requireLogin();
    
    if (!hasRole($roles)) {
        redirect('index.php?error=unauthorized');
        exit;
    }
}

// Login user
function loginUser($email, $password) {
    $query = "SELECT u.*, r.nama_role 
              FROM users u 
              JOIN roles r ON u.id_role = r.id 
              WHERE u.email = ? AND u.status_aktif = 1 
              LIMIT 1";
    
    $user = dbSelectOne($query, [$email], 's');
    
    if (!$user) {
        return ['success' => false, 'message' => 'Email tidak ditemukan atau akun tidak aktif'];
    }
    
    // Password tidak di-hash sesuai permintaan
    if ($password !== $user['password']) {
        return ['success' => false, 'message' => 'Password salah'];
    }
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['nama_role'];
    $_SESSION['id_role'] = $user['id_role'];
    $_SESSION['id_bagian'] = $user['id_bagian'];
    
    // Log aktivitas
    logActivity($user['id'], 'login', 'User login ke sistem');
    
    return ['success' => true, 'message' => 'Login berhasil'];
}

// Logout user
function logoutUser() {
    if (isLoggedIn()) {
        logActivity($_SESSION['user_id'], 'logout', 'User logout dari sistem');
    }
    
    session_destroy();
    redirect('login.php?status=logged_out');
}

// Log user activity
function logActivity($userId, $aktivitas, $keterangan = null) {
    $query = "INSERT INTO log_aktivitas (user_id, aktivitas, keterangan) 
              VALUES (?, ?, ?)";
    
    try {
        dbExecute($query, [$userId, $aktivitas, $keterangan], 'iss');
    } catch (Exception $e) {
        // Silent fail untuk log
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Get role label for display
function getRoleLabel($role) {
    $labels = [
        'superadmin' => 'Kepala Bagian',
        'admin' => 'Karyawan',
        'user' => 'Anak Magang'
    ];
    
    return $labels[$role] ?? $role;
}