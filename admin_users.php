<?php
session_start();
// Permitir acceso a admin (1) y operador (2)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['rol_id'], [1, 2])) {
    header("Location: login.php");
    exit;
}

require 'php/db_connection.php';

// Fetch users from the database with their assigned areas
$sql = "SELECT u.id, u.nombre_completo, u.usuario, r.nombre_rol,
        GROUP_CONCAT(a.nombre SEPARATOR ', ') as areas_asignadas
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id
        LEFT JOIN usuario_areas ua ON u.id = ua.usuario_id
        LEFT JOIN area_empleado a ON ua.area_id = a.id
        GROUP BY u.id, u.nombre_completo, u.usuario, r.nombre_rol
        ORDER BY u.id";
$result = $conn->query($sql);

$nombre_usuario = $_SESSION['usuario'];
$rol_id = $_SESSION['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/admin_users.css">
</head>
<body class="dashboard-page">
    <div class="dashboard-layout">
        
        <?php include 'php/sidebar.php'; ?>
        <main class="main-content">
            <header class="main-header">
                <div class="header-title">
                    <h1>Gestión de Usuarios</h1>
                    <p>Administra las cuentas de los empleados y sus roles.</p>
                </div>
                <?php if (in_array($rol_id, [1, 2])): // Admin y Operador pueden ver el botón ?>
                <button class="btn-primary" id="add-user-btn">+ Agregar Usuario</button>
                <?php endif; ?>
            </header>

            <div class="content-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Área</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($usuario = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_rol']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['areas_asignadas'] ?? 'Sin áreas asignadas'); ?></td>
                            <td class="actions">
                                <a href="edit_user.php?id=<?php echo htmlspecialchars($usuario['id']); ?>" class="btn-icon btn-edit"><i class="fas fa-pencil-alt"></i></a>
                                <button class="btn-icon btn-delete" data-id="<?php echo htmlspecialchars($usuario['id']); ?>"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Modal para agregar/editar usuario -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2 id="modal-title">Agregar Usuario</h2>
            <form id="user-form" enctype="multipart/form-data">
                <input type="hidden" id="user-id" name="user-id">
                <div class="form-group">
                    <label for="foto_perfil">Foto de Perfil:</label>
                    <img id="avatar-preview" src="https://placehold.co/100x100" alt="Avatar Preview" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; display: block; margin-bottom: 10px;">
                    <input type="file" id="foto_perfil" name="foto_perfil" accept="image/jpeg, image/png, image/gif">
                </div>
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo:</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" required>
                </div>
                <div class="form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña (dejar en blanco para no cambiar):</label>
                    <input type="password" id="password" name="password">
                </div>
                <div class="form-group">
                    <label for="rol_id">Rol:</label>
                    <select id="rol_id" name="rol_id" required>
                        <!-- Opciones de roles se cargarán dinámicamente -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="area_id">Área:</label>
                    <select id="area_id" name="area_id">
                        <!-- Opciones de áreas se cargarán dinámicamente -->
                    </select>
                </div>
                <button type="submit" class="btn-primary">Guardar</button>
            </form>
        </div>
    </div>

    <script src="js/users_management.js"></script>
</body>
</html>