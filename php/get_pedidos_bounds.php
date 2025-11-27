<?php
session_start();
require_once __DIR__ . '/db_connection.php';
header('Content-Type: application/json');

try {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('Conexión no disponible');
    }

    $sql = "SELECT MIN(id) AS min_id, MAX(id) AS max_id, COUNT(*) AS total FROM pedidos";
    $row = null;
    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
    }

    // Además devolver el primer registro por fecha si existe columna creada
    $first_by_date = null;
    $has_date_col = false;
    try {
        $checkCols = $conn->query("SHOW COLUMNS FROM pedidos LIKE 'fecha_creacion'");
        if ($checkCols && $checkCols->num_rows > 0) {
            $has_date_col = true;
        }
        if ($checkCols) { $checkCols->close(); }
    } catch (Exception $e) {}

    if ($has_date_col) {
        $sql2 = "SELECT id, numero_guia, nombre_cliente, fecha_creacion FROM pedidos ORDER BY fecha_creacion ASC LIMIT 1";
        if ($stmt2 = $conn->prepare($sql2)) {
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $first_by_date = $res2->fetch_assoc();
            $stmt2->close();
        }
    }

    echo json_encode([
        'success' => true,
        'min_id' => isset($row['min_id']) ? intval($row['min_id']) : null,
        'max_id' => isset($row['max_id']) ? intval($row['max_id']) : null,
        'total' => isset($row['total']) ? intval($row['total']) : 0,
        'first_by_date' => $first_by_date,
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} finally {
    if (isset($conn) && ($conn instanceof mysqli)) {
        $conn->close();
    }
}
?>