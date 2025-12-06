<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $pdo = getPDOConnection();
    $stmt = $pdo->query("SELECT phone_number, notify_daily FROM notification_config WHERE id = 1");
    $row = $stmt->fetch();
    
    if (!$row) {
        // Fallback if table empty (should be handled by setup, but just in case)
        echo json_encode(['success' => true, 'config' => ['phone_number' => '', 'notify_daily' => 0]]);
    } else {
        echo json_encode(['success' => true, 'config' => $row]);
    }
} catch (Exception $e) {
    // If table doesn't exist, return default
    echo json_encode(['success' => true, 'config' => ['phone_number' => '', 'notify_daily' => 0]]);
}
?>
