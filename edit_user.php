<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['rol_id'], [1, 2])) {
    header("Location: login.php");
    exit;
}

require 'php/db_connection.php';

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id === 0) {
    header("Location: admin_users.php"); // Redirigir si no hay ID
    exit;
}

// Obtener datos del usuario
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Usuario no encontrado.";
    exit;
}

// Obtener roles y áreas para los selects
$roles_result = $conn->query("SELECT id, nombre_rol FROM roles");
$areas_result = $conn->query("SELECT id, nombre FROM area_empleado");

$nombre_usuario = $_SESSION['usuario'];
$rol_id_session = $_SESSION['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .profile-picture-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        #avatar-preview {
            width: 200px; /* El doble de 100px */
            height: 200px; /* El doble de 100px */
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
        }
        .form-actions {
            display: flex;
            gap: 10px; /* Espacio entre botones */
        }
        .btn-primary, .btn-secondary {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white !important;
            border: none;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body class="dashboard-page">
    <div class="dashboard-layout">
        <?php include 'php/sidebar.php'; ?>
        <main class="main-content">
            <header class="main-header">
                <div class="header-title">
                    <h1>Editar Usuario</h1>
                    <p>Modifica los datos del usuario <?php echo htmlspecialchars($user['usuario']); ?></p>
                </div>
            </header>

            <div class="form-container" style="max-width: 600px; margin: 20px auto; padding: 20px; background: #fff; border-radius: 8px;">
                <form action="php/save_user.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    
                    <div class="profile-picture-container">
                        <img id="avatar-preview" 
                             src="uploads/profiles/<?php echo !empty($user['foto_perfil']) ? htmlspecialchars($user['foto_perfil']) : 'default.png'; ?>" 
                             alt="Avatar">
                    </div>

                    <div class="form-group">
                        <label for="foto_perfil">Cambiar Foto de Perfil:</label>
                        <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="nombre_completo">Nombre Completo:</label>
                        <input type="text" id="nombre_completo" name="nombre_completo" value="<?php echo htmlspecialchars($user['nombre_completo']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="usuario">Usuario:</label>
                        <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($user['usuario']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                        <input type="password" id="password" name="password">
                    </div>
                    <div class="form-group">
                        <label for="rol_id">Rol:</label>
                        <select id="rol_id" name="rol_id" required>
                            <?php while($rol = $roles_result->fetch_assoc()): ?>
                                <option value="<?php echo $rol['id']; ?>" <?php echo ($rol['id'] == $user['rol_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Áreas Asignadas:</label>
                        <div class="areas-checkbox-container" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 10px;">
                            <?php 
                            // Obtener áreas asignadas al usuario desde la tabla usuario_areas
                            $user_areas = [];
                            if ($user_id) {
                                $areas_query = "SELECT area_id FROM usuario_areas WHERE usuario_id = ?";
                                $areas_stmt = $conn->prepare($areas_query);
                                $areas_stmt->bind_param("i", $user_id);
                                $areas_stmt->execute();
                                $user_areas_result = $areas_stmt->get_result();
                                while ($area_row = $user_areas_result->fetch_assoc()) {
                                    $user_areas[] = $area_row['area_id'];
                                }
                                $areas_stmt->close();
                            }
                            
                            // Iterar sobre todas las áreas disponibles
                            while($area = $areas_result->fetch_assoc()): 
                            ?>
                                <div class="checkbox-item" style="display: flex; align-items: center; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <input type="checkbox" 
                                           id="area_<?php echo $area['id']; ?>" 
                                           name="areas[]" 
                                           value="<?php echo $area['id']; ?>"
                                           <?php echo in_array($area['id'], $user_areas) ? 'checked' : ''; ?>
                                           style="margin-right: 8px;">
                                    <label for="area_<?php echo $area['id']; ?>" style="margin: 0; cursor: pointer; flex: 1;">
                                        <?php echo htmlspecialchars($area['nombre']); ?>
                                    </label>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <small style="color: #666; margin-top: 5px; display: block;">Selecciona una o más áreas para este usuario</small>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Guardar Cambios</button>
                        <a href="admin_users.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
    // Script para preview de la imagen
    document.getElementById('foto_perfil').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html>