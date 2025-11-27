<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

try {
    // Considerar usuarios activos si han tenido actividad en los últimos 30 minutos
    // Si last_activity es NULL, usar la fecha de creación como referencia
    $sql = "SELECT u.id, u.nombre_completo, u.usuario, u.foto_perfil, 
                   r.nombre_rol as rol, a.nombre as area_empleado,
                   COALESCE(u.last_activity, NOW()) as ultima_actividad,
                   CASE 
                       WHEN u.last_activity IS NULL THEN 1
                       WHEN TIMESTAMPDIFF(MINUTE, u.last_activity, NOW()) <= 30 THEN 1 
                       ELSE 0 
                   END as is_active
            FROM usuarios u 
            JOIN roles r ON u.rol_id = r.id 
            LEFT JOIN area_empleado a ON u.area_id = a.id
            ORDER BY u.nombre_completo";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $all_users = [];
    $active_users = [];
    $active_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $all_users[] = $row;
        if ($row['is_active'] == 1) {
            $active_users[] = [
                'id' => $row['id'],
                'nombre_completo' => $row['nombre_completo'],
                'usuario' => $row['usuario'],
                'rol' => $row['rol'],
                'area_empleado' => $row['area_empleado'] ?? 'Sin área',
                'foto_perfil' => $row['foto_perfil'],
                'ultima_actividad' => $row['ultima_actividad']
            ];
            $active_count++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'active_count' => $active_count,
        'active_users' => $active_users,
        'all_users' => $all_users
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener usuarios activos: ' . $e->getMessage()
    ]);
}

$conn->close();
?>