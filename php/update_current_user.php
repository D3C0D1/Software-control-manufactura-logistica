<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$user_id = $_SESSION['user_id'];
$password = trim($_POST['password'] ?? '');

// Validar conexión de base de datos
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

try {
    $transaction_started = false;
    if (method_exists($conn, 'begin_transaction')) {
        $conn->begin_transaction();
        $transaction_started = true;
    }

    // No se actualizan nombre completo ni usuario en este flujo

    // Manejar la subida de foto de perfil
    $foto_perfil_nombre = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profiles/';
        
        // Crear directorio si no existe
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_info = pathinfo($_FILES['foto_perfil']['name']);
        $extension = strtolower($file_info['extension']);
        
        // Validar tipo de archivo
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Use JPG, PNG o GIF']);
            exit;
        }

        // Validar tamaño (máximo 5MB)
        if ($_FILES['foto_perfil']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB']);
            exit;
        }

        // Generar nombre único
        $foto_perfil_nombre = 'user_' . uniqid() . '.' . $extension;
        $upload_path = $upload_dir . $foto_perfil_nombre;

        if (!move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Error al subir la foto de perfil']);
            exit;
        }

        // Eliminar foto anterior si existe
        $stmt = $conn->prepare("SELECT foto_perfil FROM usuarios WHERE id = ?");
        if (!$stmt) {
            if ($transaction_started && method_exists($conn, 'rollback')) { $conn->rollback(); }
            echo json_encode(['success' => false, 'message' => 'Error al preparar consulta de foto anterior']);
            exit;
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            if ($transaction_started && method_exists($conn, 'rollback')) { $conn->rollback(); }
            echo json_encode(['success' => false, 'message' => 'Error al consultar foto anterior']);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();
        $old_user = $result ? $result->fetch_assoc() : null;
        if ($stmt) { $stmt->close(); }

        if ($old_user && !empty($old_user['foto_perfil'])) {
            $old_photo_path = $upload_dir . $old_user['foto_perfil'];
            if (file_exists($old_photo_path)) {
                unlink($old_photo_path);
            }
        }
    }

    // Preparar la consulta de actualización (solo foto y/o contraseña)
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if ($foto_perfil_nombre) {
            $stmt = $conn->prepare("UPDATE usuarios SET password = ?, foto_perfil = ? WHERE id = ?");
            if (!$stmt) {
                if ($transaction_started && method_exists($conn, 'rollback')) { $conn->rollback(); }
                echo json_encode(['success' => false, 'message' => 'Error al preparar actualización de usuario']);
                exit;
            }
            $stmt->bind_param("ssi", $hashed_password, $foto_perfil_nombre, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            if (!$stmt) {
                if ($transaction_started && method_exists($conn, 'rollback')) { $conn->rollback(); }
                echo json_encode(['success' => false, 'message' => 'Error al preparar actualización de contraseña']);
                exit;
            }
            $stmt->bind_param("si", $hashed_password, $user_id);
        }
    } else {
        if ($foto_perfil_nombre) {
            $stmt = $conn->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            if (!$stmt) {
                if ($transaction_started && method_exists($conn, 'rollback')) { $conn->rollback(); }
                echo json_encode(['success' => false, 'message' => 'Error al preparar actualización de foto']);
                exit;
            }
            $stmt->bind_param("si", $foto_perfil_nombre, $user_id);
        } else {
            echo json_encode(['success' => false, 'message' => 'No hay cambios para actualizar']);
            if ($transaction_started && method_exists($conn, 'rollback')) { $conn->rollback(); }
            exit;
        }
    }

    if (isset($stmt) && $stmt->execute()) {
        // Opcionalmente actualizar sesión si se requiere foto en sesión
        // $_SESSION['foto_perfil'] = $foto_perfil_nombre ?? $_SESSION['foto_perfil'] ?? null;
        
        if ($transaction_started && method_exists($conn, 'commit')) { $conn->commit(); }
        echo json_encode(['success' => true, 'message' => 'Perfil actualizado correctamente']);
    } else {
        if ($transaction_started && method_exists($conn, 'rollback')) { $conn->rollback(); }
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil']);
    }
    
    if (isset($stmt) && method_exists($stmt, 'close')) { $stmt->close(); }

} catch (Exception $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) { $conn->rollback(); }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
if (isset($conn) && method_exists($conn, 'close')) { $conn->close(); }
?>