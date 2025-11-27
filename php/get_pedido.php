<?php
// Desactivar visualización de errores para evitar contaminar JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 0);

// Limpiar completamente cualquier salida previa
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once 'db_connection.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no proporcionado.']);
    exit;
}

$id = $_GET['id'];

// Validar conexión mysqli
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Obtener detalles del pedido calculando el campo tiene_devoluciones
$sql_pedido = "SELECT p.id, p.numero_guia, p.nombre_cliente, p.correo_cliente, p.numero_cliente, p.notas, p.prioridad,
               p.fecha_creacion, p.fecha_actualizacion,
               CASE WHEN md.pedido_id IS NOT NULL THEN 1 ELSE 0 END as tiene_devoluciones
               FROM pedidos p
               LEFT JOIN (SELECT DISTINCT pedido_id FROM mensajes_devolucion) md ON p.id = md.pedido_id
               WHERE p.id = ?";
$stmt_pedido = $conn->prepare($sql_pedido);
if (!$stmt_pedido) {
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar la consulta del pedido.']);
    exit;
}
$stmt_pedido->bind_param("i", $id);
$stmt_pedido->execute();
$result_pedido = $stmt_pedido->get_result();
if (!$result_pedido) {
    // Evitar continuar si get_result falla
    $stmt_pedido->close();
    echo json_encode(['success' => false, 'message' => 'Error al obtener resultado del pedido.']);
    exit;
}

if ($pedido = $result_pedido->fetch_assoc()) {
    // El campo tiene_devoluciones ya viene de la consulta principal
    
    // Obtener archivos adjuntos
    $sql_adjuntos = "SELECT id, ruta_archivo FROM pedido_adjuntos WHERE pedido_id = ?";
    $stmt_adjuntos = $conn->prepare($sql_adjuntos);
    if (!$stmt_adjuntos) {
        // Si no se puede preparar adjuntos, devolver sin adjuntos
        $pedido['adjuntos'] = [];
        $response = json_encode(['success' => true, 'pedido' => $pedido]);
        $stmt_pedido->close();
        ob_clean();
        echo $response;
        ob_end_flush();
        $conn->close();
        exit;
    }
    $stmt_adjuntos->bind_param("i", $id);
    $stmt_adjuntos->execute();
    $result_adjuntos = $stmt_adjuntos->get_result();
    if (!$result_adjuntos) {
        // Si get_result falla, continuar sin adjuntos
        $pedido['adjuntos'] = [];
        $response = json_encode(['success' => true, 'pedido' => $pedido]);
        $stmt_pedido->close();
        $stmt_adjuntos->close();
        ob_clean();
        echo $response;
        ob_end_flush();
        $conn->close();
        exit;
    }
    
    $adjuntos = [];
    while ($row = $result_adjuntos->fetch_assoc()) {
        $adjuntos[] = $row;
    }
    $pedido['adjuntos'] = $adjuntos;

    $response = json_encode(['success' => true, 'pedido' => $pedido]);
} else {
    $response = json_encode(['success' => false, 'message' => 'Pedido no encontrado.']);
}

$stmt_pedido && $stmt_pedido->close();
if (isset($stmt_adjuntos) && $stmt_adjuntos) { $stmt_adjuntos->close(); }
$conn->close();

// Limpiar buffer y enviar solo la respuesta JSON
ob_clean();
echo $response;
ob_end_flush();
exit;
?>
