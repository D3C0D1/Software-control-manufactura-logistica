<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diseño de Pedidos - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mensajeria.css">
    <link rel="stylesheet" href="css/prioridad.css"> <!-- Estilos de prioridad añadidos -->
    <link rel="stylesheet" href="css/diseno.css?v=<?php echo time(); ?>"> <!-- Estilos del modal mejorado -->
    <link rel="stylesheet" href="css/diseno_archivos.css?v=<?php echo time(); ?>"> <!-- Estilos para archivos -->

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
                    <h1>Área de Diseño</h1>
                    <p>Gestiona los pedidos en las diferentes fases de diseño.</p>
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
            
            <!-- Sección para adjuntar nuevo archivo -->
            <div class="nuevo-archivo-section" style="margin-top: 20px; padding: 15px; border-top: 2px solid #e0e0e0;">
                <h4><i class="fas fa-plus-circle"></i> Adjuntar Nuevo Archivo</h4>
                <form id="nuevo-archivo-form" enctype="multipart/form-data" style="margin-top: 10px;">
                    <input type="hidden" id="pedido-id-archivo" name="pedido_id">
                    <div class="file-upload-container" style="display: flex; align-items: center; gap: 10px;">
                        <input type="file" id="nuevo-archivo" name="nuevo_archivo" accept=".zip,.rar,.7z,.pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.ai,.psd" style="flex: 1;">
                        <button type="submit" class="btn-upload" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-upload"></i> Subir
                        </button>
                    </div>
                    <div id="upload-status" style="margin-top: 10px; font-size: 14px;"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de notificación al cliente al finalizar -->
    <div id="ready-notify-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sms"></i> ¿Desea notificarle al cliente?</h3>
                <span class="close-button" id="ready-notify-close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="ready-notify-form">
                    <input type="hidden" id="ready-notify-pedido-id" name="pedido_id" />
                    <div class="form-group">
                        <label for="ready-notify-phone">Teléfono del cliente</label>
                        <input type="text" id="ready-notify-phone" name="numero_telefono" placeholder="Ej: +573001234567" required />
                    </div>
                    <div class="form-group">
                        <label for="ready-notify-message">Mensaje (máx. 160 caracteres)</label>
                        <textarea id="ready-notify-message" name="mensaje" rows="3" maxlength="160" required></textarea>
                        <div class="char-counter">Caracteres: <span id="ready-notify-count">0</span>/160</div>
                    </div>
                    <div id="ready-notify-messages"></div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" id="ready-notify-cancel">No, gracias</button>
                        <button type="submit" class="btn-primary" id="ready-notify-submit"><i class="fas fa-paper-plane"></i> Enviar SMS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="js/dashboard.js" defer></script>
<script src="js/diseno.js" defer></script>
    <script>
        // --- Modal de notificación al finalizar ---
        const readyNotifyModal = document.getElementById('ready-notify-modal');
        const readyNotifyClose = document.getElementById('ready-notify-close');
        const readyNotifyCancel = document.getElementById('ready-notify-cancel');
        const readyNotifyForm = document.getElementById('ready-notify-form');
        const readyNotifyCount = document.getElementById('ready-notify-count');
        const readyNotifyMessage = document.getElementById('ready-notify-message');
        const readyNotifyMessages = document.getElementById('ready-notify-messages');

        function openReadyNotifyModal(pedidoId) {
            // Obtener datos del pedido para prellenar
            fetch(`php/get_pedido.php?id=${pedidoId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('ready-notify-pedido-id').value = pedidoId;
                        const telefono = data.pedido.numero_cliente || '';
                        const guia = data.pedido.numero_guia || '';
                        const mensaje = `Tu pedido #${guia} está listo para recoger.`;
                        document.getElementById('ready-notify-phone').value = telefono;
                        readyNotifyMessage.value = mensaje;
                        readyNotifyCount.textContent = mensaje.length;
                        readyNotifyMessages.innerHTML = '';
                        readyNotifyModal.style.display = 'block';
                    } else {
                        if (typeof mostrarNotificacion === 'function') {
                            mostrarNotificacion('No se pudo obtener el pedido para notificar: ' + (data.message || ''), 'error');
                        }
                    }
                })
                .catch(err => {
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion('Error de conexión al preparar notificación: ' + err.message, 'error');
                    }
                });
        }

        // Desactivar apertura automática del modal: cualquier llamada global será no-op
        window.openReadyNotifyModal = function() { return false; };

        if (readyNotifyClose) readyNotifyClose.addEventListener('click', () => { readyNotifyModal.style.display = 'none'; });
        if (readyNotifyCancel) readyNotifyCancel.addEventListener('click', () => { readyNotifyModal.style.display = 'none'; });

        readyNotifyMessage.addEventListener('input', () => {
            readyNotifyCount.textContent = readyNotifyMessage.value.length;
        });

        readyNotifyForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            readyNotifyMessages.innerHTML = '';
            const submitBtn = document.getElementById('ready-notify-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            try {
                const formData = new FormData(readyNotifyForm);
                const res = await fetch('php/send_manual_sms.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    readyNotifyMessages.innerHTML = '<div class="alert-success">' + (data.message || 'SMS enviado correctamente.') + '</div>';
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion(data.message || 'SMS enviado correctamente.', 'success');
                    }
                    readyNotifyModal.style.display = 'none';
                } else {
                    readyNotifyMessages.innerHTML = '<div class="alert-error">' + (data.message || 'Error al enviar SMS') + '</div>';
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion(data.message || 'Error al enviar SMS', 'error');
                    }
                }
            } catch (err) {
                const msg = 'Error de conexión: ' + (err && err.message ? err.message : 'desconocido');
                readyNotifyMessages.innerHTML = '<div class="alert-error">' + msg + '</div>';
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion(msg, 'error');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar SMS';
            }
        });
        // Función para actualizar actividad del usuario (temporalmente deshabilitada)
        /*
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
        */
    </script>
</body>
</html>