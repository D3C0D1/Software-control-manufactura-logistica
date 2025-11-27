<?php
session_start();
require_once __DIR__ . '/db_connection.php';

header('Content-Type: application/json');

try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Conexión no disponible');
    }

    // Obtener todos los IDs de pedidos ordenados por ID ascendente
    $sql = "SELECT id FROM pedidos ORDER BY id ASC";
    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Error en la consulta: ' . $conn->error);
    }

    $map = [];
    $ids = [];
    $seq = 0;
    while ($row = $result->fetch_assoc()) {
        $id = (int)$row['id'];
        $seq++;
        $map[$id] = $seq;
        $ids[] = $id;
    }
    if ($result instanceof mysqli_result) {
        $result->close();
    }

    echo json_encode([
        'success' => true,
        'total' => $seq,
        'map' => $map,
        'ids' => $ids,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} finally {
    if (isset($conn) && ($conn instanceof mysqli)) {
        $conn->close();
    }
}
?>