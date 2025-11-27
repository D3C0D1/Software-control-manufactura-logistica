<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Finalizados</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/finalizado.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'php/sidebar.php'; ?>
        <div class="main-content">
            <header>
                <h1>Pedidos Finalizados</h1>
            </header>
            <main>
                <div class="finalizado-grid">
                    <div class="board-column" id="columna-finalizado">
                        <h2>Finalizado</h2>
                        <div class="pedidos-lista"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="view-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Detalles del Pedido</h2>
            <div id="pedido-details"></div>
        </div>
    </div>

<script src="js/finalizado.js" defer></script>
</body>
</html>