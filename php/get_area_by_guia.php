<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connection.php';

// Respuesta base
$response = ['success' => false];

// Validar parámetro
if (!isset($_GET['numero_guia']) || trim($_GET['numero_guia']) === '') {
    echo json_encode(['success' => false, 'message' => 'Número de guía no proporcionado']);
    exit;
}

$numero_guia = trim($_GET['numero_guia']);

try {
    if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
        throw new Exception('Error de conexión a la base de datos');
    }

    $sql = "SELECT p.area_id, a.nombre_area FROM pedidos p LEFT JOIN areas a ON p.area_id = a.id WHERE p.numero_guia = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar la consulta: ' . $conn->error);
    }

    $stmt->bind_param('s', $numero_guia);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response = [
            'success' => true,
            'area_id' => (int)$row['area_id'],
            'nombre_area' => $row['nombre_area'] ?? 'Área no definida'
        ];
    } else {
        $response = ['success' => false, 'message' => 'Pedido no encontrado'];
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;
?>