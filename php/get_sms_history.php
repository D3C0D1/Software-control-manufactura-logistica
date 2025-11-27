<?php
session_start();
require_once 'db_connection.php';
require_once 'get_sms_config.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_area_id = $_SESSION['area_id'] ?? null;
$user_role = $_SESSION['rol_id'] ?? null;

// Para empleados (rol_id = 3), requieren área asignada
if ($user_role == 3 && !$user_area_id) {
    echo json_encode(['error' => 'Usuario no tiene área asignada']);
    exit;
}

try {
    // Asegurar conexión PDO válida
    $pdo = getPDOConnection();
    $sms_history = [];
    
    // Para administradores y operadores: mostrar SMS enviados y recibidos
    if ($user_role == 1 || $user_role == 2) {
        // SMS ENVIADOS por el usuario
        $sql_sent = "SELECT 
                        sa.id,
                        sa.message as mensaje,
                        sa.created_at as fecha_envio,
                        u.nombre_completo as sender_name,
                        COALESCE(ae_sender.nombre, 'Administración') as sender_area,
                        ae_dest.nombre as destination_area,
                        1 as is_read,
                        'enviado' as tipo_mensaje
                    FROM sms_areas sa
                    JOIN usuarios u ON sa.sender_id = u.id
                    LEFT JOIN area_empleado ae_sender ON sa.sender_area_id = ae_sender.id
                    JOIN area_empleado ae_dest ON sa.destination_area_id = ae_dest.id
                    WHERE sa.sender_id = ? AND sa.is_active = 1
                    ORDER BY sa.created_at DESC
                    LIMIT 25";
        
        $stmt_sent = $pdo->prepare($sql_sent);
        $stmt_sent->execute([$user_id]);
        $sent_messages = $stmt_sent->fetchAll(PDO::FETCH_ASSOC);
        
        // SMS RECIBIDOS (si tienen área asignada)
        $received_messages = [];
        if ($user_area_id) {
            $sql_received = "SELECT 
                                sa.id,
                                sa.message as mensaje,
                                sa.created_at as fecha_envio,
                                u.nombre_completo as sender_name,
                                COALESCE(ae_sender.nombre, 'Administración') as sender_area,
                                ae_dest.nombre as destination_area,
                                CASE WHEN srs.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                                'recibido' as tipo_mensaje
                            FROM sms_areas sa
                            JOIN usuarios u ON sa.sender_id = u.id
                            LEFT JOIN area_empleado ae_sender ON sa.sender_area_id = ae_sender.id
                            JOIN area_empleado ae_dest ON sa.destination_area_id = ae_dest.id
                            LEFT JOIN sms_areas_read_status srs ON sa.id = srs.sms_id AND srs.user_id = ?
                            WHERE sa.destination_area_id = ? AND sa.is_active = 1
                            ORDER BY sa.created_at DESC
                            LIMIT 25";
            
            $stmt_received = $pdo->prepare($sql_received);
            $stmt_received->execute([$user_id, $user_area_id]);
            $received_messages = $stmt_received->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Combinar y ordenar por fecha
        $sms_history = array_merge($sent_messages, $received_messages);
        usort($sms_history, function($a, $b) {
            return strtotime($b['fecha_envio']) - strtotime($a['fecha_envio']);
        });
        
        // Limitar a 50 mensajes más recientes
        $sms_history = array_slice($sms_history, 0, 50);
        
    } else {
        // Para empleados: solo SMS recibidos en su área
        $sql = "SELECT 
                    sa.id,
                    sa.message as mensaje,
                    sa.created_at as fecha_envio,
                    u.nombre_completo as sender_name,
                    COALESCE(ae_sender.nombre, 'Administración') as sender_area,
                    ae_dest.nombre as destination_area,
                    CASE WHEN srs.id IS NOT NULL THEN 1 ELSE 0 END as is_read,
                    'recibido' as tipo_mensaje
                FROM sms_areas sa
                JOIN usuarios u ON sa.sender_id = u.id
                LEFT JOIN area_empleado ae_sender ON sa.sender_area_id = ae_sender.id
                JOIN area_empleado ae_dest ON sa.destination_area_id = ae_dest.id
                LEFT JOIN sms_areas_read_status srs ON sa.id = srs.sms_id AND srs.user_id = ?
                WHERE sa.destination_area_id = ? AND sa.is_active = 1
                ORDER BY sa.created_at DESC
                LIMIT 50";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $user_area_id]);
        $sms_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Formatear fechas y ajustar nombres de campos para el frontend
    foreach ($sms_history as &$sms) {
        $sms['fecha_formateada'] = date('d/m/Y H:i', strtotime($sms['fecha_envio']));
        $sms['remitente'] = $sms['sender_name'];
        $sms['area_remitente'] = $sms['sender_area'];
        $sms['leido'] = $sms['is_read'];
        $sms['es_enviado'] = ($sms['tipo_mensaje'] == 'enviado');
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $sms_history,
        'total_count' => count($sms_history)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>