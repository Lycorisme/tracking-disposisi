<?php
// modules/users/users_service.php

require_once __DIR__ . '/../../config/database.php';

class UsersService {
    
    // Get all active users
    public static function getAll($excludeId = null) {
        $query = "SELECT u.*, r.nama_role, b.nama_bagian
                  FROM users u
                  JOIN roles r ON u.id_role = r.id
                  LEFT JOIN bagian b ON u.id_bagian = b.id
                  WHERE u.status_aktif = 1";
        
        $params = [];
        $types = '';
        
        if ($excludeId) {
            $query .= " AND u.id != ?";
            $params[] = $excludeId;
            $types = 'i';
        }
        
        $query .= " ORDER BY r.id ASC, u.nama_lengkap ASC";
        
        return dbSelect($query, $params, $types);
    }
    
    // Get user by ID
    public static function getById($id) {
        $query = "SELECT u.*, r.nama_role, b.nama_bagian
                  FROM users u
                  JOIN roles r ON u.id_role = r.id
                  LEFT JOIN bagian b ON u.id_bagian = b.id
                  WHERE u.id = ?";
        
        return dbSelectOne($query, [$id], 'i');
    }
    
    // Update profile
    public static function updateProfile($id, $data) {
        $query = "UPDATE users SET nama_lengkap = ?, email = ? WHERE id = ?";
        
        $params = [
            $data['nama_lengkap'],
            $data['email'],
            $id
        ];
        
        return dbExecute($query, $params, 'ssi');
    }
    
    // Change password
    public static function changePassword($id, $newPassword) {
        $query = "UPDATE users SET password = ? WHERE id = ?";
        return dbExecute($query, [$newPassword, $id], 'si');
    }
    
    // Check if email exists (for validation)
    public static function emailExists($email, $excludeId = null) {
        $query = "SELECT COUNT(*) as total FROM users WHERE email = ?";
        $params = [$email];
        $types = 's';
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
            $types .= 'i';
        }
        
        $result = dbSelectOne($query, $params, $types);
        return ($result['total'] ?? 0) > 0;
    }
    
    // Get users by role
    public static function getByRole($role) {
        $query = "SELECT u.* FROM users u
                  JOIN roles r ON u.id_role = r.id
                  WHERE r.nama_role = ? AND u.status_aktif = 1
                  ORDER BY u.nama_lengkap ASC";
        
        return dbSelect($query, [$role], 's');
    }
}