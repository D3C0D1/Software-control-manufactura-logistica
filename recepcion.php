<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require 'php/db_connection.php';

// Lógica para obtener los pedidos existentes (se implementará más adelante)
$pedidos = [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción de Pedidos - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/recepcion.css">

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
                    <h1>Recepción de Pedidos</h1>
                    <p>Crea y gestiona nuevos pedidos de clientes.</p>
                </div>
            </header>

            <!-- Sección de Estadísticas -->
            <div class="stats-section">
                <div class="stats-grid">
                    <!-- En Proceso -->
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="proceso-count">0</h3>
                            <p>En Proceso</p>
                        </div>
                    </div>
                    <!-- Finalizados -->
                    <div class="stat-card finalizados">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="finalizados-count">0</h3>
                            <p>Finalizados</p>
                        </div>
                    </div>
                    
                    <!-- Eliminados -->
                    <div class="stat-card eliminados">
                        <div class="stat-icon">
                            <i class="fas fa-trash-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="eliminados-count">0</h3>
                            <p>Eliminados</p>
                        </div>
                    </div>
                    
                    <!-- Total -->
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-count">0</h3>
                            <p>Total</p>
                        </div>
                    </div>
                    <!-- Recepción (Guía generada) -->
                    <div class="stat-card recepcion">
                        <div class="stat-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="recepcion-count">0</h3>
                            <p>Recepción (Guía generada)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botón Crear Pedido -->
            <div class="create-order-section">
                <button id="create-order-btn" class="btn-create-order">
                    <i class="fas fa-plus-circle"></i>
                    <span>Crear Nuevo Pedido</span>
                </button>
                <button id="manual-sms-btn" class="btn-create-order" style="margin-left:10px;">
                    <i class="fas fa-sms"></i>
                    <span>Enviar SMS manual</span>
                </button>
            </div>

            <div class="reception-grid">
                <div class="pedidos-section">
                    <div class="search-container" style="display:flex; align-items:center; gap:8px;">
                        <input type="text" id="search-recientes-input" placeholder="Buscar por #pedido, guía, cliente, email..." style="flex:1;">
                        <span id="recientes-search-count" title="Resultados de búsqueda" style="display:none; background:#343a40; color:#fff; border-radius:12px; padding:2px 8px; font-size:12px;">0</span>
                    </div>
                    <div class="pedidos-container">
                        <h2>Pedidos Recientes</h2>
                        <div id="pedidos-list">
                            <!-- Los pedidos se cargarán aquí dinámicamente -->
                            <p>No hay pedidos recientes.</p>
                        </div>
                    </div>
                </div>

                <div class="pedidos-section">
                    <div class="search-container" style="display:flex; align-items:center; gap:8px;">
                        <input type="text" id="search-proceso-input" placeholder="Buscar por #pedido, guía, cliente, email..." style="flex:1;">
                        <span id="proceso-search-count" title="Resultados de búsqueda" style="display:none; background:#343a40; color:#fff; border-radius:12px; padding:2px 8px; font-size:12px;">0</span>
                    </div>
                    <div class="pedidos-container">
                        <h2>Pedidos en Proceso</h2>
                        <div id="pedidos-proceso-list">
                            <!-- Los pedidos en proceso se cargarán aquí dinámicamente -->
                            <p>No hay pedidos en proceso.</p>
                        </div>
                    </div>
                </div>

                <div class="pedidos-section">
                    <div class="search-container" style="display:flex; align-items:center; gap:8px;">
                        <input type="text" id="search-finalizados-input" placeholder="Buscar por #pedido, guía, cliente, email..." style="flex:1;">
                        <span id="finalizados-search-count" title="Resultados de búsqueda" style="display:none; background:#343a40; color:#fff; border-radius:12px; padding:2px 8px; font-size:12px;">0</span>
                    </div>
                    <div class="pedidos-container">
                        <h2>Pedidos Finalizados</h2>
                        <div id="pedidos-finalizados-list">
                            <!-- Los pedidos finalizados se cargarán aquí dinámicamente -->
                            <p>No hay pedidos finalizados.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <audio id="generate-sound" src="sounds/generate.mp3" preload="auto"></audio>

    <!-- Modal para crear pedido -->
    <div id="create-order-modal" class="modal">
        <div class="modal-content create-order-modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Crear Nuevo Pedido</h2>
                <span class="close-button create-close-button">&times;</span>
            </div>
            <div class="modal-body">
                <form id="create-order-form" action="php/create_pedido.php" method="POST" enctype="multipart/form-data">
                    <div id="form-messages"></div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre_cliente"><i class="fas fa-user"></i> Nombre Completo del Cliente</label>
                            <input type="text" id="nombre_completo" name="nombre_completo" required placeholder="Ingrese el nombre completo">
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Correo Electrónico</label>
                            <input type="email" id="email" name="email" required placeholder="ejemplo@correo.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="movil"><i class="fas fa-phone"></i> Móvil</label>
                            <input type="tel" id="movil" name="movil" required placeholder="Número de teléfono">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notas"><i class="fas fa-sticky-note"></i> Notas del Pedido</label>
                        <textarea id="notas" name="notas" rows="4" maxlength="600" placeholder="Describa los detalles del pedido..."></textarea>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Prioridad del Pedido</label>
                        <div class="priority-options">
                            <label class="priority-option priority-alta">
                                <input type="radio" name="prioridad" value="alta" required>
                                <span class="priority-label">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Prioridad</strong>
                                    <small>(1 día)</small>
                                </span>
                            </label>
                            <label class="priority-option priority-normal">
                                <input type="radio" name="prioridad" value="normal" checked>
                                <span class="priority-label">
                                    <i class="fas fa-clock"></i>
                                    <strong>Normal</strong>
                                    <small>(3 días)</small>
                                </span>
                            </label>
                            <label class="priority-option priority-baja">
                                <input type="radio" name="prioridad" value="baja">
                                <span class="priority-label">
                                    <i class="fas fa-calendar-alt"></i>
                                    <strong>Largo</strong>
                                    <small>(1 semana o más)</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="adjuntos"><i class="fas fa-paperclip"></i> Archivos Adjuntos</label>
                        <div class="file-upload-area">
                            <input type="file" id="adjuntos" name="adjuntos[]" multiple>
                            <div class="file-upload-text">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Arrastra archivos aquí o haz clic para seleccionar</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-container" for="enviar_sms">
                            <input type="checkbox" id="enviar_sms" name="enviar_sms" checked>
                            <span class="checkmark" aria-hidden="true"></span>
                            <span class="checkbox-text"><i class="fas fa-sms"></i> ¿Enviar SMS de confirmación al cliente?</span>
                        </label>
                        <small class="form-help">Se enviará un mensaje con el número de guía del pedido</small>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel" onclick="document.getElementById('create-order-modal').style.display='none'">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> Crear Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para enviar SMS manual -->
    <div id="manual-sms-modal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width:600px;">
            <div class="modal-header">
                <h2><i class="fas fa-sms"></i> Enviar SMS Manual</h2>
                <span class="close-button manual-sms-close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="manual-sms-form">
                    <div id="manual-sms-messages"></div>
                    <div class="form-group">
                        <label for="manual-phone"><i class="fas fa-phone"></i> Número de Teléfono</label>
                        <input type="tel" id="manual-phone" name="phone" required placeholder="573151234567" pattern="^[0-9]+$">
                        <small>Formato: 57 + número, solo dígitos.</small>
                    </div>
                    <div class="form-group">
                        <label for="manual-message"><i class="fas fa-comment"></i> Mensaje (máx. 160)</label>
                        <textarea id="manual-message" name="sms" rows="3" maxlength="160" required placeholder="Escribe tu mensaje..."></textarea>
                        <small>Caracteres: <span id="manual-char-count">0</span>/160</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-cancel manual-sms-cancel">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-submit" id="manual-sms-submit">
                            <i class="fas fa-paper-plane"></i> Enviar SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para visualizar pedido -->
    <div id="view-modal" class="modal">
        <div class="modal-content view-modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-eye"></i> Detalles del Pedido</h2>
                <span class="close-button view-close-button">&times;</span>
            </div>
            <div class="modal-body">
                <div id="pedido-details">
                    <!-- Los detalles del pedido se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar pedido -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Editar Pedido</h2>
            <form id="edit-order-form">
                <input type="hidden" id="edit-pedido-id" name="id">
                <div class="form-group">
                    <label for="edit-nombre-completo">Nombre Completo del Cliente</label>
                    <input type="text" id="edit-nombre-completo" name="nombre_completo" required>
                </div>
                <div class="form-group">
                    <label for="edit-email">Correo Electrónico</label>
                    <input type="email" id="edit-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit-movil">Móvil</label>
                    <input type="tel" id="edit-movil" name="movil" required>
                </div>
                <div class="form-group">
                    <label for="edit-notas">Notas del Pedido</label>
                    <textarea id="edit-notas" name="notas" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Archivos Adjuntos Actuales</label>
                    <div id="current-attachments"></div>
                </div>
                <div class="form-group">
                    <label for="edit-adjuntos">Subir Nuevos Archivos</label>
                    <input type="file" id="edit-adjuntos" name="adjuntos[]" multiple>
                    <p>Si no selecciona archivos nuevos, se conservarán los actuales.</p>
                </div>
                <button type="submit" class="btn-primary">Actualizar Pedido</button>
            </form>
        </div>
    </div>

    <script src="js/recepcion.js?v=1.1"></script>
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

        // --- Envío de SMS manual ---
        const manualSmsBtn = document.getElementById('manual-sms-btn');
        const manualSmsModal = document.getElementById('manual-sms-modal');
        const manualSmsClose = document.querySelector('.manual-sms-close');
        const manualSmsCancel = document.querySelector('.manual-sms-cancel');
        const manualSmsForm = document.getElementById('manual-sms-form');
        const manualSmsMessages = document.getElementById('manual-sms-messages');
        const manualMessageInput = document.getElementById('manual-message');
        const manualCharCount = document.getElementById('manual-char-count');

        function openManualSmsModal() { manualSmsModal.style.display = 'block'; }
        function closeManualSmsModal() { manualSmsModal.style.display = 'none'; manualSmsForm.reset(); manualSmsMessages.innerHTML = ''; manualCharCount.textContent = '0'; }

        if (manualSmsBtn) manualSmsBtn.addEventListener('click', openManualSmsModal);
        if (manualSmsClose) manualSmsClose.addEventListener('click', closeManualSmsModal);
        if (manualSmsCancel) manualSmsCancel.addEventListener('click', closeManualSmsModal);

        manualMessageInput.addEventListener('input', () => {
            manualCharCount.textContent = manualMessageInput.value.length;
        });

        manualSmsForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            manualSmsMessages.innerHTML = '';
            const submitBtn = document.getElementById('manual-sms-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            try {
                const formData = new FormData(manualSmsForm);
                const res = await fetch('php/send_manual_sms.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    manualSmsMessages.innerHTML = '<div class="alert-success">' + (data.message || 'SMS enviado correctamente.') + '</div>';
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion(data.message || 'SMS enviado correctamente.', 'success');
                    }
                    // Cerrar el modal automáticamente al enviar correctamente
                    closeManualSmsModal();
                } else {
                    manualSmsMessages.innerHTML = '<div class="alert-error">' + (data.message || 'Error al enviar SMS') + '</div>';
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion(data.message || 'Error al enviar SMS', 'error');
                    }
                }
            } catch (err) {
                const msg = 'Error de conexión: ' + (err && err.message ? err.message : 'desconocido');
                manualSmsMessages.innerHTML = '<div class="alert-error">' + msg + '</div>';
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(msg, 'error');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar SMS';
            }
        });
    </script>
</body>
</html>