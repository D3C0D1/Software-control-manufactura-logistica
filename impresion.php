<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
require 'php/db_connection.php';
$nombre_usuario = $_SESSION['usuario'];
$rol_id = $_SESSION['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresión - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mensajeria.css">
    <link rel="stylesheet" href="css/prioridad.css">
    <link rel="stylesheet" href="css/diseno.css?v=<?php echo time(); ?>">
</head>
<body class="dashboard-page">
    <div class="dashboard-layout">
        <!-- Header Superior con Hamburger Menu y Perfil -->
        <?php include 'php/dashboard_header.php'; ?>
        
        <!-- Sidebar como cuadrado separado -->
        <?php include 'php/sidebar.php'; ?>
        <main class="main-content">
            <header class="main-header">
                <div class="header-title">
                    <h1>Impresión</h1>
                    <p>Gestiona los trabajos de impresión.</p>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <div class="content-sections">
                <div class="section" id="recepcion">
                    <h2>Recepción</h2>
                    <div class="pedidos-container" id="pedidos-recepcion">
                        <!-- Los pedidos se cargarán aquí dinámicamente -->
                    </div>
                </div>
                <div class="section" id="en-proceso">
                    <h2>En Proceso</h2>
                    <div class="pedidos-container" id="pedidos-en-proceso">
                        <!-- Los pedidos se cargarán aquí dinámicamente -->
                    </div>
                </div>
                <div class="section" id="preparado">
                    <h2>Preparado</h2>
                    <div class="pedidos-container" id="pedidos-preparado">
                        <!-- Los pedidos se cargarán aquí dinámicamente -->
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Modal para visualizar pedido -->
    <div id="view-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Detalles del Pedido</h2>
            <div id="pedido-details"></div>
        </div>
    </div>

    <audio id="audio-new-order" src="sounds/pedido.mp3" preload="auto"></audio>
    <audio id="audio-priority-order" src="sounds/warning.mp3" preload="auto"></audio>

<script src="js/dashboard.js" defer></script>
<script src="js/impresion.js" defer></script>
    <script>
        // Función para actualizar actividad del usuario
        async function updateUserActivity() {
            try {
                await fetch('php/update_user_activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
            } catch (error) {
                console.error('Error al actualizar actividad del usuario:', error);
            }
        }

        // Actualizar actividad del usuario cada 5 minutos
        setInterval(updateUserActivity, 5 * 60 * 1000);
        
        // Actualizar actividad al cargar la página
        updateUserActivity();
    </script>
</body>
</html>