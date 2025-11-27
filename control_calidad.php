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
    <title>Control de Calidad - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mensajeria.css">
    <link rel="stylesheet" href="css/prioridad.css">
    <link rel="stylesheet" href="css/control_calidad.css?v=<?php echo time(); ?>">
    <style>
    /* Estilos inline para asegurar que se apliquen */
    .pedido-devolucion-badge {
        background: linear-gradient(135deg, #dc3545, #c82333) !important;
        color: white !important;
        padding: 4px 8px !important;
        border-radius: 12px !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        margin-left: 8px !important;
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3) !important;
        animation: pulse-devolucion 2s infinite !important;
        cursor: pointer !important;
    }
    
    .pedido-devolucion-badge i {
        font-size: 10px !important;
    }
    
    @keyframes pulse-devolucion {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.5);
        }
    }
    </style>

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
                    <h1>Control de Calidad</h1>
                    <p>Supervisa la calidad de los productos.</p>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <div class="content-sections">
                <div class="section" id="recepcion-col">
                    <h2>Recepción</h2>
                    <!-- Contenido de recepción -->
                </div>
                <div class="section" id="en-proceso-col">
                    <h2>En Proceso</h2>
                    <!-- Contenido de en proceso -->
                </div>
                <div class="section" id="preparado-col">
                    <h2>Preparado</h2>
                    <!-- Contenido de preparado -->
                </div>
            </div>

        </main>
    </div>

    <!-- Modal para ver el pedido -->
    <div id="view-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Detalles del Pedido</h2>
            <div id="pedido-details"></div>
        </div>
    </div>

    <audio id="audio-pedido" src="sounds/pedido.mp3" preload="auto"></audio>
    <audio id="audio-warning" src="sounds/warning.mp3" preload="auto"></audio>

<script src="js/dashboard.js" defer></script>
<script src="js/control_calidad.js" defer></script>
    <script>
        // Función para actualizar la actividad del usuario
        function updateUserActivity() {
            fetch('php/update_user_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error updating user activity:', data.message);
                }
            })
            .catch(error => {
                console.error('Error updating user activity:', error);
            });
        }

        // Actualizar actividad cada 5 minutos
        setInterval(updateUserActivity, 5 * 60 * 1000);
        
        // Actualizar actividad al cargar la página
        updateUserActivity();
    </script>
</body>
</html>