<?php
session_start();
require 'db_connection.php';

// Permitir a Admin (1) y Operador (2) crear/editar usuarios
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['rol_id'], [1, 2])) {
    // Para peticiones de formulario, es mejor redirigir
    header('Location: ../login.php?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $nombre_completo = trim($_POST['nombre_completo']);
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    $rol_id = intval($_POST['rol_id']);
    
    // Obtener áreas seleccionadas
    $areas_seleccionadas = [];
    if (isset($_POST['areas']) && is_array($_POST['areas']) && !empty($_POST['areas'])) {
        $areas_seleccionadas = array_map('intval', $_POST['areas']);
    } elseif (isset($_POST['area_id']) && $_POST['area_id'] !== '') {
        // Mantener compatibilidad con el selector único del modal
        $areas_seleccionadas = [intval($_POST['area_id'])];
    }

    // Lógica para la foto de perfil
    $foto_perfil_sql = "";
    $params_foto = [];
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        $upload_dir = '../uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
        $new_filename = 'user_' . uniqid() . '.' . $file_ext;
        $upload_file = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_file)) {
            $foto_perfil_sql = ", foto_perfil = ?";
            $params_foto[] = $new_filename;
        }
    }

    if (!empty($nombre_completo) && !empty($usuario) && !empty($rol_id)) {
        if ($user_id > 0) { // Actualizar usuario
            // Actualizar datos básicos del usuario (sin area_id)
            $sql_parts = ["nombre_completo = ?", "usuario = ?", "rol_id = ?"];
            $types = "ssi";
            $params = [$nombre_completo, $usuario, $rol_id];

            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_parts[] = "password = ?";
                $types .= "s";
                $params[] = $hashed_password;
            }

            if (!empty($params_foto)) {
                $sql_parts[] = "foto_perfil = ?";
                $types .= "s";
                $params = array_merge($params, $params_foto);
            }

            $sql = "UPDATE usuarios SET " . implode(", ", $sql_parts) . " WHERE id = ?";
            $types .= "i";
            $params[] = $user_id;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                // Actualizar áreas del usuario
                // Primero eliminar todas las áreas existentes
                $delete_areas_stmt = $conn->prepare("DELETE FROM usuario_areas WHERE usuario_id = ?");
                $delete_areas_stmt->bind_param("i", $user_id);
                $delete_areas_stmt->execute();
                $delete_areas_stmt->close();
                
                // Insertar las nuevas áreas seleccionadas
                if (!empty($areas_seleccionadas)) {
                    $insert_area_stmt = $conn->prepare("INSERT INTO usuario_areas (usuario_id, area_id) VALUES (?, ?)");
                    foreach ($areas_seleccionadas as $area_id) {
                        $insert_area_stmt->bind_param("ii", $user_id, $area_id);
                        $insert_area_stmt->execute();
                    }
                    $insert_area_stmt->close();
                }
                
                header('Location: ../admin_users.php?success=1');
                exit;
            } else {
                header('Location: ../edit_user.php?id=' . $user_id . '&error=1');
                exit;
            }
            $stmt->close();
        } else { // Crear usuario (desde el modal de agregar)
            // Este bloque se mantiene para la funcionalidad de "Agregar Usuario"
            header('Content-Type: application/json'); // Asegurarse de que la respuesta sea JSON para el modal
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'La contraseña es obligatoria para nuevos usuarios.']);
                exit;
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Para nuevos usuarios, mantener compatibilidad con area_id único por ahora
            $area_id_unico = !empty($areas_seleccionadas) ? $areas_seleccionadas[0] : null;
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre_completo, usuario, password, rol_id, area_id, foto_perfil) VALUES (?, ?, ?, ?, ?, ?)");
            $foto_nombre = !empty($params_foto) ? $params_foto[0] : null;
            $stmt->bind_param("sssiss", $nombre_completo, $usuario, $hashed_password, $rol_id, $area_id_unico, $foto_nombre);
            
            if ($stmt->execute()) {
                $new_user_id = $conn->insert_id;
                
                // Insertar todas las áreas seleccionadas en la tabla usuario_areas
                if (!empty($areas_seleccionadas)) {
                    $insert_area_stmt = $conn->prepare("INSERT INTO usuario_areas (usuario_id, area_id) VALUES (?, ?)");
                    foreach ($areas_seleccionadas as $area_id) {
                        $insert_area_stmt->bind_param("ii", $new_user_id, $area_id);
                        $insert_area_stmt->execute();
                    }
                    $insert_area_stmt->close();
                }
                
                echo json_encode(['success' => true, 'message' => 'Usuario guardado correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al guardar el usuario.']);
            }
            $stmt->close();
        }
        exit;
    }
}

// Si no es un POST o falla la validación inicial
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Es una petición AJAX (del modal de agregar)
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
} else {
    // Es una petición de formulario normal (de la página de editar)
    header("Location: ../admin_users.php?status=invalid_data");
}
$conn->close();
?>