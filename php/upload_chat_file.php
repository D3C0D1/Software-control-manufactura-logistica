<?php
/**
 * API para subir archivos adjuntos al chat
 * Endpoint: POST /php/upload_chat_file.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Verificar que se hayan enviado archivos
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('No se enviaron archivos');
    }
    
    // Obtener datos del usuario
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    
    if (!$user_id || !$username) {
        throw new Exception('Datos de usuario requeridos');
    }
    
    // Configuración de archivos
    $upload_dir = '../uploads/chat/';
    $max_file_size = 10 * 1024 * 1024; // 10MB
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar'];
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $uploaded_files = [];
    $files = $_FILES['files'];
    
    // Procesar cada archivo
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue; // Saltar archivos con errores
        }
        
        $file_name = $files['name'][$i];
        $file_tmp = $files['tmp_name'][$i];
        $file_size = $files['size'][$i];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validar extensión
        if (!in_array($file_ext, $allowed_extensions)) {
            throw new Exception("Tipo de archivo no permitido: {$file_name}");
        }
        
        // Validar tamaño
        if ($file_size > $max_file_size) {
            throw new Exception("Archivo muy grande: {$file_name}");
        }
        
        // Generar nombre único
        $unique_name = time() . '_' . $user_id . '_' . uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $unique_name;
        
        // Mover archivo
        if (move_uploaded_file($file_tmp, $file_path)) {
            $uploaded_files[] = [
                'original_name' => $file_name,
                'file_name' => $unique_name,
                'file_path' => 'uploads/chat/' . $unique_name,
                'file_size' => $file_size,
                'file_type' => $file_ext
            ];
        }
    }
    
    if (empty($uploaded_files)) {
        throw new Exception('No se pudo subir ningún archivo');
    }
    
    // Conectar a la base de datos para guardar información de archivos
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insertar mensaje con archivos adjuntos
    $message_text = count($uploaded_files) === 1 
        ? "📎 Archivo adjunto: {$uploaded_files[0]['original_name']}"
        : "📎 " . count($uploaded_files) . " archivos adjuntos";
    
    $stmt = $pdo->prepare("
        INSERT INTO chat_messages (user_id, username, message, message_type, attachments, created_at) 
        VALUES (?, ?, ?, 'file', ?, NOW())
    ");
    
    $attachments_json = json_encode($uploaded_files);
    $stmt->execute([$user_id, $username, $message_text, $attachments_json]);
    
    $message_id = $pdo->lastInsertId();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Archivos subidos correctamente',
        'data' => [
            'message_id' => $message_id,
            'files' => $uploaded_files,
            'message_text' => $message_text
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al subir archivos',
        'message' => $e->getMessage()
    ]);
}
?>