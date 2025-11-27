<?php
session_start();
require 'db_connection.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

if (!isset($_GET['area'])) {
    echo json_encode(['success' => false, 'message' => 'Área no especificada']);
    exit;
}

$area_name = $_GET['area'];
$current_user_id = $_SESSION['user_id'];

try {
    // Mapear nombres de área a IDs
    $area_mapping = [
        'recepcion' => 1,
        'mensajeria' => 2,
        'diseño' => 3,
        'impresion' => 4,
        'sublimado' => 5,
        'confeccion' => 6,
        'control_calidad' => 7
    ];
    
    if (!isset($area_mapping[$area_name])) {
        echo json_encode(['success' => false, 'message' => 'Área no válida']);
        exit;
    }
    
    $area_id = $area_mapping[$area_name];
    
    // Obtener usuarios del área específica + admin y operadores (que pertenecen a todas las áreas)
    $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.foto_perfil as foto,
                   r.nombre_rol as rol, a.nombre as area_empleado,
                   CASE WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, NOW()) <= 5 THEN 1 ELSE 0 END as is_online
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            LEFT JOIN area_empleado a ON u.area_id = a.id 
            WHERE u.id != ? 
            AND (u.area_id = ? OR r.nombre_rol IN ('admin', 'operador'))
            ORDER BY r.nombre_rol DESC, u.nombre_completo";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $current_user_id, $area_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = [
            'id' => $row['id'],
            'nombre_completo' => $row['nombre_completo'],
            'usuario' => $row['usuario'],
            'foto' => $row['foto'],
            'rol' => $row['rol'],
            'area_empleado' => $row['area_empleado'],
            'is_online' => $row['is_online']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'area' => $area_name,
        'members' => $members
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?>