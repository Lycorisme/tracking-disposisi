<?php
// config/database.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_tracking_disposisi');

// Database connection
function getConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Koneksi database gagal: " . $conn->connect_error);
            }
            
            // Set charset
            $conn->set_charset('utf8mb4');
            
        } catch (Exception $e) {
            die("Error koneksi database: " . $e->getMessage());
        }
    }
    
    return $conn;
}

// Helper function untuk prepared statement
function dbQuery($query, $params = [], $types = '') {
    $conn = getConnection();
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare statement gagal: " . $conn->error);
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

// Helper untuk SELECT query
function dbSelect($query, $params = [], $types = '') {
    $stmt = dbQuery($query, $params, $types);
    $result = $stmt->get_result();
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}

// Helper untuk SELECT single row
function dbSelectOne($query, $params = [], $types = '') {
    $data = dbSelect($query, $params, $types);
    return !empty($data) ? $data[0] : null;
}

// Helper untuk INSERT/UPDATE/DELETE
function dbExecute($query, $params = [], $types = '') {
    $stmt = dbQuery($query, $params, $types);
    $affected = $stmt->affected_rows;
    $lastId = $stmt->insert_id;
    $stmt->close();
    
    return [
        'affected_rows' => $affected,
        'insert_id' => $lastId
    ];
}

// Helper untuk mendapatkan last insert ID
function dbLastInsertId() {
    $conn = getConnection();
    return $conn->insert_id;
}