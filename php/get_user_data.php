<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

// Permitir acceso a admin (1) y operador (2)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['rol_id'], [1, 2])) {
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

$response = [
    'roles' => [],
    'areas' => [],
    'user' => null
];

// Obtener roles
$roles_result = $conn->query("SELECT id, nombre_rol FROM roles");
while ($row = $roles_result->fetch_assoc()) {
    $response['roles'][] = $row;
}

// Obtener áreas de empleado
$areas_result = $conn->query("SELECT id, nombre FROM area_empleado");
while ($row = $areas_result->fetch_assoc()) {
    $response['areas'][] = $row;
}

// Obtener datos de un usuario si se proporciona un ID
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT u.id, u.nombre_completo, u.usuario, u.rol_id, u.area_id, u.foto_perfil, r.nombre_rol as rol_nombre, a.nombre as area_nombre 
                             FROM usuarios u 
                             JOIN roles r ON u.rol_id = r.id 
                             LEFT JOIN area_empleado a ON u.area_id = a.id 
                             WHERE u.id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response['user'] = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();

echo json_encode($response);
?>