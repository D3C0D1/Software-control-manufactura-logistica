<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$phone = $data['phone'] ?? '';
$notify = $data['notify'] ? 1 : 0;

try {
    $pdo = getPDOConnection();
    $stmt = $pdo->prepare("UPDATE notification_config SET phone_number = ?, notify_daily = ? WHERE id = 1");
    $stmt->execute([$phone, $notify]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
