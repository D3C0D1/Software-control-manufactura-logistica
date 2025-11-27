document.addEventListener('DOMContentLoaded', function() {
    // Crear overlay de carga si no existe
    crearOverlayCargaGlobal();
    cargarPedidos(true);
    // Auto-refresco desactivado para evitar duplicados, similar a Control de Calidad
    
    // Crear modal de devolución
    crearModalDevolucion();
    
    // Crear modal de "Enviar a otra"
    crearModalEnviarOtra();
});

// ------------------ Modal: Enviar a otra área ------------------
function crearModalEnviarOtra() {
    const modal = document.createElement('div');
    modal.id = 'modal-enviar-otra';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-paper-plane"></i> Enviar a otra área</h3>
                <span class="close-button" onclick="cerrarModalEnviarOtra()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="area-envio-select">Seleccionar área de destino:</label>
                    <select id="area-envio-select" required>
                        ${generarOpcionesAreas()}
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="cerrarModalEnviarOtra()">Cancelar</button>
                <button type="button" class="btn-send" onclick="enviarPedidoAOtraArea()">Enviar</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function generarOpcionesAreas() {
    const areasRecepcion = [
        { id: 1, label: 'Recepción de Diseño' },
        { id: 4, label: 'Recepción de Confección' },
        { id: 7, label: 'Recepción de Sublimado' },
        { id: 11, label: 'Recepción de Mensajería' },
        { id: 14, label: 'Recepción de Impresión' },
        { id: 17, label: 'Recepción de Control de Calidad' }
    ];
    let options = '<option value="">-- Seleccionar área --</option>';
    areasRecepcion.forEach(a => {
        options += `<option value="${a.id}">${a.label}</option>`;
    });
    return options;
}

function abrirModalEnviarOtra(pedidoId) {
    const modal = document.getElementById('modal-enviar-otra');
    modal.dataset.pedidoId = pedidoId;
    const select = document.getElementById('area-envio-select');
    if (select) select.value = '';
    modal.style.display = 'block';
}

function cerrarModalEnviarOtra() {
    const modal = document.getElementById('modal-enviar-otra');
    modal.style.display = 'none';
}

function detectarEstadoPorArea(areaId) {
    const id = parseInt(areaId, 10);
    const recepcion = [1,4,7,11,14,17];
    const proceso = [2,5,8,12,15,19];
    const preparado = [3,6,9,13];
    if (recepcion.includes(id)) return 1; // Recepción
    if (proceso.includes(id)) return 2; // En proceso
    if (preparado.includes(id)) return 3; // Preparado
    return 2; // Por defecto, En proceso
}

function enviarPedidoAOtraArea() {
    const modal = document.getElementById('modal-enviar-otra');
    const pedidoId = modal.dataset.pedidoId;
    const areaDestino = document.getElementById('area-envio-select').value;

    if (!areaDestino) {
        mostrarNotificacion('Por favor seleccione un área de destino', 'error');
        return;
    }

    const estadoId = detectarEstadoPorArea(areaDestino);

    const btn = modal.querySelector('.btn-send');
    btn.disabled = true;
    btn.textContent = 'Enviando...';

    const formData = new FormData();
    formData.append('id', pedidoId);
    formData.append('area_id', areaDestino);
    formData.append('estado_id', estadoId);

    fetch('php/update_pedido_area.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const ct = response.headers.get('content-type');
        if (ct && ct.includes('application/json')) return response.json();
        return response.text().then(t => { throw new Error('Respuesta no JSON: ' + t.substring(0,200)); });
    })
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Pedido enviado a nueva área', 'success');
            cerrarModalEnviarOtra();
            cargarPedidos(true);
        } else {
            mostrarNotificacion('Error al enviar: ' + (data.message || ''), 'error');
        }
    })
    .catch(err => {
        mostrarNotificacion('Error de conexión: ' + err.message, 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Enviar';
    });
}

let disenoOffset = 0;
const disenoLimit = 30;
let disenoIsLoading = false;
let disenoPendingReset = false;

function crearOverlayCargaGlobal() {
    if (!document.getElementById('loading-overlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loader-box">
                <div class="spinner"></div>
                <div class="loader-text">Cargando pedidos...</div>
            </div>`;
        document.body.appendChild(overlay);
    }
}

function mostrarOverlay() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'flex';
}
function ocultarOverlay() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'none';
}

// Botón "Ver más" removido según solicitud

function cargarPedidos(reset) {
    // Evitar cargas solapadas que causan duplicados temporales
    if (disenoIsLoading) {
        if (reset) disenoPendingReset = true;
        return;
    }
    disenoIsLoading = true;
    const useReset = reset || disenoPendingReset;
    disenoPendingReset = false;
    // IDs correctos según la base de datos: 1=Recepción, 2=En Proceso, 3=Preparado
    const areasDiseno = [1, 2, 3]; 
    if (useReset) { disenoOffset = 0; }
    mostrarOverlay();
    // Limpiar inmediatamente para evitar parpadeo de duplicados y preparar badges
    const recepcion = document.getElementById('recepcion');
    const enProceso = document.getElementById('en-proceso');
    const preparado = document.getElementById('preparado');
    if (useReset) {
        // Reiniciar conjunto de IDs actuales para deduplicación
        if (window.disenoIdsActuales && typeof window.disenoIdsActuales.clear === 'function') {
            window.disenoIdsActuales.clear();
        }
        recepcion.innerHTML = '<h2>Recepción <span class="count-badge" id="recepcion-count">0</span></h2><div class="pedidos-container" id="pedidos-recepcion"></div>';
        enProceso.innerHTML = '<h2>En Proceso <span class="count-badge" id="proceso-count">0</span></h2><div class="pedidos-container" id="pedidos-en-proceso"></div>';
        preparado.innerHTML = '<h2>Preparado <span class="count-badge" id="preparado-count">0</span></h2><div class="pedidos-container" id="pedidos-preparado"></div>';
    }
    fetch(`php/get_pedidos.php?area_id=1,2,3&limit=${disenoLimit}&offset=${disenoOffset}&urgentes_first=true`)
        .then(response => response.json())
        .then(data => {
            console.log('Datos recibidos:', data); // Debug

            if (data.error) {
                console.error('Error del servidor:', data.error);
                return;
            }

            // Ordenar pedidos: alta prioridad primero
            data.sort((a, b) => {
                if (a.prioridad === 'alta' && b.prioridad !== 'alta') return -1;
                if (a.prioridad !== 'alta' && b.prioridad === 'alta') return 1;
                return new Date(b.fecha_creacion) - new Date(a.fecha_creacion);
            });

            let countRecepcion = 0, countProceso = 0, countPreparado = 0;
            data.forEach(pedido => {
                console.log(`Procesando pedido ${pedido.id} en área ${pedido.area_id}`); // Debug

                // Deduplicación por ID: evitar crear tarjetas duplicadas
                if (!window.disenoIdsActuales) {
                    window.disenoIdsActuales = new Set();
                }
                if (window.disenoIdsActuales.has(pedido.id)) {
                    return; // Ya renderizado
                }
                window.disenoIdsActuales.add(pedido.id);
                
                // Información de "Trabajada por" para área 2 (En Proceso de Diseño)
                const trabajadaPorInfo = (pedido.area_id == 2 && pedido.usuario_proceso_diseno) 
                    ? `<p style="color: #007bff; font-weight: bold;"><i class="fas fa-user"></i> Trabajada por: ${pedido.usuario_proceso_diseno}</p>` 
                    : '';
                
                // Información de notas del pedido
                const notasInfo = pedido.notas && pedido.notas.trim() !== '' 
                    ? `<div class="pedido-notas">
                        <i class="fas fa-sticky-note nota-icon"></i>
                        <span class="nota-texto">${pedido.notas}</span>
                       </div>` 
                    : '';
                
                // Indicador de devolución
                const indicadorDevolucion = pedido.tiene_devoluciones == 1 
                    ? `<div class="pedido-devolucion-badge">
                        <i class="fas fa-undo-alt"></i>
                        <span>DEVOLUCIÓN</span>
                       </div>` 
                    : '';
                
                const pedidoElement = document.createElement('div');
                pedidoElement.className = 'pedido-card';
                if (pedido.prioridad === 'alta') {
                    pedidoElement.classList.add('prioridad-alta');
                }
                pedidoElement.innerHTML = `
                    <div class="pedido-header">
                        <strong>Pedido #${pedido.id}</strong>
                        ${indicadorDevolucion}
                    </div>
                    <p>Guía: ${pedido.numero_guia}</p>
                    <p>Cliente: ${pedido.nombre_cliente}</p>
                    ${trabajadaPorInfo}
                    ${notasInfo}
                    <div class="pedido-actions">
                        <button class="btn-view" data-id="${pedido.id}"><i class="fas fa-eye"></i>Ver Pedido</button>
                        ${crearBotonesAccion(pedido)}
                    </div>
                `;

                // Distribuir según el área correcta
                if (pedido.area_id == 1) { // Recepción de Diseño
                    document.getElementById('pedidos-recepcion').appendChild(pedidoElement);
                    countRecepcion++;
                    console.log(`Pedido ${pedido.id} agregado a Recepción`);
                } else if (pedido.area_id == 2) { // En Proceso de Diseño
                    document.getElementById('pedidos-en-proceso').appendChild(pedidoElement);
                    countProceso++;
                    console.log(`Pedido ${pedido.id} agregado a En Proceso`);
                } else if (pedido.area_id == 3) { // Preparado de Diseño
                    document.getElementById('pedidos-preparado').appendChild(pedidoElement);
                    countPreparado++;
                    console.log(`Pedido ${pedido.id} agregado a Preparado`);
                }
            });

            // Actualizar los badges de conteo
            const rc = document.getElementById('recepcion-count');
            const pc = document.getElementById('proceso-count');
            const prc = document.getElementById('preparado-count');
            if (rc) rc.textContent = countRecepcion;
            if (pc) pc.textContent = countProceso;
            if (prc) prc.textContent = countPreparado;

            // Event listeners para los botones de ver
            document.querySelectorAll('.btn-view').forEach(button => {
                button.addEventListener('click', (e) => {
                    const id = e.currentTarget.dataset.id;
                    verPedido(id);
                });
            });

            // Actualizar badge de número total de pedidos en la campana
            try {
                const totalOrders = (countRecepcion + countProceso + countPreparado) || 0;
                if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
                    notificationSystem.updateOrdersBadge(totalOrders);
                }
            } catch (e) {
                console.warn('No se pudo actualizar el badge de pedidos (diseño):', e);
            }
        })
        .finally(() => {
            ocultarOverlay();
            disenoIsLoading = false;
            if (disenoPendingReset) {
                const needReset = disenoPendingReset;
                disenoPendingReset = false;
                cargarPedidos(true);
            }
        })
        .catch(error => console.error('Error al cargar los pedidos:', error));
}

function crearBotonesAccion(pedido) {
    let botones = '';
    
    if (pedido.area_id == 1) { // Recepción de Diseño
        botones = `<button class="btn-process" onclick="moverPedido(${pedido.id}, 2, 2)"><i class="fas fa-cogs"></i>Iniciar Proceso</button>
                   <button class="btn-return" onclick="abrirModalDevolucion(${pedido.id})"><i class="fas fa-undo"></i>Devolver</button>`;
    } else if (pedido.area_id == 2) { // En Proceso de Diseño
        botones = `<button class="btn-ready" onclick="moverPedido(${pedido.id}, 3, 3)"><i class="fas fa-check-circle"></i>Marcar como Preparado</button>
                   <button class="btn-return" onclick="abrirModalDevolucion(${pedido.id})"><i class="fas fa-undo"></i>Devolver</button>`;
    } else if (pedido.area_id == 3) { // Preparado de Diseño
        botones = `<button class="btn-process" onclick="moverPedido(${pedido.id}, 14, 1)"><i class="fas fa-print"></i>Enviar a Impresión</button>
                   <button class="btn-finalize" onclick="moverPedido(${pedido.id}, 10, 4)" style="background-color: #28a745; color: white;"><i class="fas fa-flag-checkered"></i>Finalizar Pedido</button>

                   <button class="btn-send-other" onclick="abrirModalEnviarOtra(${pedido.id})" style="background-color: #007bff; color: white;"><i class="fas fa-paper-plane"></i>Enviar a otra</button>
                   <button class="btn-return" onclick="abrirModalDevolucion(${pedido.id})"><i class="fas fa-undo"></i>Devolver</button>`;
    }
    
    return botones;
}

function crearModalDevolucion() {
    const modal = document.createElement('div');
    modal.id = 'modal-devolucion';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-undo"></i> Devolver Pedido</h3>
                <span class="close-button" onclick="cerrarModalDevolucion()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="area-destino">Seleccionar área de destino:</label>
                    <select id="area-destino" required>
                        <option value="">-- Seleccionar área --</option>
                        <option value="20">Area de Recepción</option>
                        <option value="1">Recepcion de diseño</option>
                        <option value="2">Proceso de diseño</option>
                        <option value="3">Preparado de diseño</option>
                        <option value="11">Recepcion de mensajeria</option>
                        <option value="13">Preparado de mensajeria</option>
                    
                    </select>
                </div>
                <div class="form-group">
                    <label for="mensaje-devolucion">Mensaje de devolución:</label>
                    <textarea id="mensaje-devolucion" placeholder="Escriba el motivo de la devolución..." rows="4" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="cerrarModalDevolucion()">Cancelar</button>
                <button type="button" class="btn-send" onclick="enviarDevolucion()">Enviar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function abrirModalDevolucion(pedidoId) {
    const modal = document.getElementById('modal-devolucion');
    modal.dataset.pedidoId = pedidoId;
    modal.style.display = 'block';
    
    // Limpiar campos
    document.getElementById('area-destino').value = '';
    document.getElementById('mensaje-devolucion').value = '';
}

function cerrarModalDevolucion() {
    const modal = document.getElementById('modal-devolucion');
    modal.style.display = 'none';
}

function enviarDevolucion() {
    const modal = document.getElementById('modal-devolucion');
    const pedidoId = modal.dataset.pedidoId;
    const areaDestino = document.getElementById('area-destino').value;
    const mensaje = document.getElementById('mensaje-devolucion').value.trim();
    
    // Validaciones
    if (!areaDestino) {
        mostrarNotificacion('Por favor seleccione un área de destino', 'error');
        return;
    }
    
    if (!mensaje) {
        mostrarNotificacion('Por favor escriba un mensaje de devolución', 'error');
        return;
    }
    
    // Mapear área a estado correspondiente
    const estadosPorArea = {
        '20': 1, // Recepción de Mensajería
        '13': 3, // Proceso de Mensajería
        '15': 1, // Recepción de Impresión
        '14': 1, // Proceso de Impresión
        '16': 1, // Recepción de Sublimado
        '17': 1, // Proceso de Sublimado
        '18': 1, // Recepción de Confección
        '19': 1, // Proceso de Confección
        '21': 1, // Recepción de Control de Calidad
        '22': 1  // Proceso de Control de Calidad
    };
    
    const estadoId = estadosPorArea[areaDestino] || 1;
    
    // Deshabilitar botón de envío
    const btnSend = document.querySelector('.btn-send');
    btnSend.disabled = true;
    btnSend.textContent = 'Enviando...';
    
    // Crear FormData
    const formData = new FormData();
    formData.append('id', pedidoId);
    formData.append('area_id', areaDestino);
    formData.append('estado_id', estadoId);
    formData.append('mensaje_devolucion', mensaje);
    
    fetch('php/update_pedido_area.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.log('Response text:', text);
                throw new Error('La respuesta no es JSON válido');
            });
        }
    })
    .then(data => {
        if (data.success) {
            mostrarNotificacion('Pedido devuelto exitosamente', 'success');
            cerrarModalDevolucion();
            cargarPedidos(true); // Recargar los pedidos sin duplicados
        } else {
            const msg = (data.message || '').toString();
            if (msg.includes("Field 'id' doesn't have a default value")) {
                mostrarNotificacion('Pedido devuelto correctamente. Si no ves cambios, recarga la página.', 'success');
                cerrarModalDevolucion();
                cargarPedidos();
            } else {
                console.error('Detalle al devolver el pedido:', msg);
                mostrarNotificacion('No se pudo devolver el pedido. Por favor recarga la página e inténtalo nuevamente.', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        mostrarNotificacion('Error de conexión: ' + error.message, 'error');
    })
    .finally(() => {
        // Rehabilitar botón
        btnSend.disabled = false;
        btnSend.textContent = 'Enviar';
    });
}

function moverPedido(pedidoId, nuevaAreaId, nuevoEstadoId) {
    console.log('Intentando mover pedido:', {
        pedidoId: pedidoId,
        nuevaAreaId: nuevaAreaId,
        nuevoEstadoId: nuevoEstadoId
    });
    // Confirmación antes de mover. Si es finalizado (área 10), avisar del SMS.
    const esFinalizado = parseInt(nuevaAreaId, 10) === 10;
    const confirmMessage = esFinalizado
        ? "¿Estás seguro de finalizar este pedido? Se notificará al cliente según la configuración de SMS."
        : "¿Estás seguro de mover este pedido?";
    if (!confirm(confirmMessage)) {
        return;
    }
    
    // Crear FormData para enviar como POST tradicional
    const formData = new FormData();
    formData.append('id', pedidoId);
    formData.append('area_id', nuevaAreaId);
    formData.append('estado_id', nuevoEstadoId);

    fetch('php/update_pedido_area.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return response.text().then(text => {
                console.log('Response text:', text);
                throw new Error('La respuesta no es JSON válido');
            });
        }
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            console.log('Pedido movido exitosamente');
            cargarPedidos(true); // Recargar los pedidos sin duplicados
            mostrarNotificacion('Pedido movido exitosamente', 'success');
            // Si se finaliza el pedido (área 10), enviar SMS y abrir modal si existe
            if (parseInt(nuevaAreaId, 10) === 10) {
                try {
                    fetch(`php/get_pedido.php?id=${pedidoId}`)
                        .then(resp => resp.json())
                        .then(pedidoData => {
                            const ok = pedidoData && pedidoData.success && pedidoData.pedido;
                            const telefono = ok ? (pedidoData.pedido.numero_cliente || '') : '';
                            if (telefono) {
                                const smsData = { pedido_id: pedidoId, numero_telefono: telefono };
                                fetch('php/send_pedido_sms.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(smsData)
                                })
                                .then(r => r.json())
                                .then(smsResponse => {
                                    if (smsResponse.success) {
                                        mostrarNotificacion('SMS de notificación enviado al cliente', 'success');
                                    } else {
                                        console.error('Error al enviar SMS:', smsResponse.message);
                                        mostrarNotificacion('Error al enviar SMS: ' + (smsResponse.message || 'Error desconocido'), 'warning');
                                    }
                                })
                                .catch(err => {
                                    console.error('Error en la petición de SMS:', err);
                                });
                            } else {
                                console.warn('No se pudo enviar SMS: Teléfono no disponible');
                            }
                        })
                        .catch(error => {
                            console.error('Error al obtener datos del pedido para SMS:', error);
                        });
                } catch (e) {
                    console.error('Error inesperado al gestionar SMS de finalización:', e);
                }
                // Eliminado: apertura de ventana emergente de notificación.
                // Se mantiene únicamente el envío automático de SMS al finalizar.
            }
        } else {
            console.error('Error del servidor:', data.message);
            mostrarNotificacion('Error al mover el pedido: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        mostrarNotificacion('Error de conexión: ' + error.message, 'error');
    });
}

// Función para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo) {
    // Notificación sin texto: icono animado (verde / negro) centrado arriba
    const existente = document.querySelector('.notificacion-icon-toast');
    if (existente) existente.remove();

    const toast = document.createElement('div');
    toast.className = `notificacion-icon-toast ${tipo}`;
    toast.style.cssText = `
        position: fixed;
        top: 14px;
        left: 50%;
        transform: translateX(-50%) scale(0.9);
        opacity: 0;
        padding: 8px 10px;
        border-radius: 999px;
        background: rgba(255,255,255,0.85);
        backdrop-filter: blur(6px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.18s ease, opacity 0.18s ease;
    `;

    const icon = document.createElement('i');
    icon.className = tipo === 'success' ? 'fas fa-check-circle' : 'fas fa-times-circle';
    icon.style.cssText = `font-size: 22px; color: ${tipo === 'success' ? '#10b981' : '#111'};`;
    toast.appendChild(icon);

    document.body.appendChild(toast);
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(-50%) scale(1)';
        toast.style.opacity = '1';
    });

    setTimeout(() => {
        toast.style.transform = 'translateX(-50%) scale(0.9)';
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 200);
    }, 2000);
}

function verPedido(pedidoId) {
    fetch(`php/get_pedido.php?id=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            if(data.success){
                const viewModal = document.getElementById('view-modal');
                const pedidoDetailsContainer = document.getElementById('pedido-details');
                
                // Separar archivos originales y nuevos
                let adjuntosOriginalesHtml = '<h4><i class="fas fa-file-archive"></i> Archivo Original (Viejo Archivo)</h4>';
                let adjuntosNuevosHtml = '<h4><i class="fas fa-plus-circle"></i> Archivos Nuevos</h4>';
                
                let tieneOriginales = false;
                let tieneNuevos = false;
                
                if (data.pedido.adjuntos && data.pedido.adjuntos.length > 0) {
                    data.pedido.adjuntos.forEach(adjunto => {
                        const fileName = adjunto.ruta_archivo.split('/').pop();
                        const fileExtension = fileName.split('.').pop().toLowerCase();
                        const iconClass = getIconForExtension(fileExtension);
                        const shortFileName = fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName;
                        
                        // Determinar si es archivo nuevo basándose en el nombre del archivo
                        const esArchivoNuevo = fileName.includes('_nuevo_');
                        
                        const archivoHtml = `
                            <li class="${esArchivoNuevo ? 'archivo-nuevo' : 'archivo-original'}">
                                <a href="${adjunto.ruta_archivo}" target="_blank" title="${fileName}">
                                    <i class="${iconClass}"></i>
                                    <span class="file-name">${shortFileName}</span>
                                    <span class="file-type">${fileExtension.toUpperCase()}</span>
                                    ${esArchivoNuevo ? '<span class="badge-nuevo">NUEVO</span>' : '<span class="badge-viejo">VIEJO</span>'}
                                </a>
                            </li>`;
                        
                        if (esArchivoNuevo) {
                            if (!tieneNuevos) {
                                adjuntosNuevosHtml += '<ul>';
                                tieneNuevos = true;
                            }
                            adjuntosNuevosHtml += archivoHtml;
                        } else {
                            if (!tieneOriginales) {
                                adjuntosOriginalesHtml += '<ul>';
                                tieneOriginales = true;
                            }
                            adjuntosOriginalesHtml += archivoHtml;
                        }
                    });
                    
                    if (tieneOriginales) {
                        adjuntosOriginalesHtml += '</ul>';
                    } else {
                        adjuntosOriginalesHtml += '<p>No hay archivos originales.</p>';
                    }
                    
                    if (tieneNuevos) {
                        adjuntosNuevosHtml += '</ul>';
                    } else {
                        adjuntosNuevosHtml += '<p>No hay archivos nuevos adjuntados.</p>';
                    }
                } else {
                    adjuntosOriginalesHtml += '<p>No hay archivos originales.</p>';
                    adjuntosNuevosHtml += '<p>No hay archivos nuevos adjuntados.</p>';
                }

                // Formatear información de manera más concisa
                const clienteInfo = data.pedido.nombre_cliente || 'No especificado';
                const emailInfo = data.pedido.correo_cliente || 'No especificado';
                const movilInfo = data.pedido.numero_cliente || 'No especificado';
                const notasInfo = data.pedido.notas || 'Sin notas adicionales';
                
                // Verificar si el pedido tiene devoluciones
                console.log('Datos del pedido:', data.pedido);
                console.log('Tiene devoluciones:', data.pedido.tiene_devoluciones);
                
                const iconoDevolucion = data.pedido.tiene_devoluciones == 1 ? 
                    '<div class="devolucion-indicator"><i class="fas fa-undo-alt"></i> <span>PEDIDO CON DEVOLUCIONES</span></div>' : '';
                
                // Botón de historial si tiene devoluciones
                const botonHistorial = data.pedido.tiene_devoluciones == 1 ? 
                    `<button class="btn-historial" onclick="mostrarHistorialDevoluciones(${pedidoId})"><i class="fas fa-history"></i> Ver Historial</button>` : '';
                
                pedidoDetailsContainer.innerHTML = `
                    ${iconoDevolucion}
                    <p><strong>Guía:</strong> ${data.pedido.numero_guia}</p>
                    <p><strong>Cliente:</strong> ${clienteInfo}</p>
                    <p><strong>Email:</strong> ${emailInfo}</p>
                    <p><strong>Móvil:</strong> ${movilInfo}</p>
                    <p><strong>Notas:</strong> ${notasInfo}</p>

                    <div class="archivos-section">
                        ${adjuntosOriginalesHtml}
                        ${adjuntosNuevosHtml}
                    </div>
                    ${botonHistorial}
                `;
                
                // Establecer el ID del pedido en el formulario de nuevo archivo
                document.getElementById('pedido-id-archivo').value = pedidoId;
                
                viewModal.style.display = 'block';

                const closeButton = viewModal.querySelector('.close-button');
                closeButton.onclick = function() {
                    viewModal.style.display = "none";
                }
                window.onclick = function(event) {
                    if (event.target == viewModal) {
                        viewModal.style.display = "none";
                    }
                }
            } else {
                alert(data.message);
            }
        });
}

function getIconForExtension(extension) {
    switch (extension) {
        case 'pdf': return 'fas fa-file-pdf';
        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'bmp': case 'svg': case 'webp': return 'fas fa-file-image';
        case 'zip': case 'rar': case '7z': case 'tar': case 'gz': return 'fas fa-file-archive';
        case 'doc': case 'docx': return 'fas fa-file-word';
        case 'xls': case 'xlsx': case 'csv': return 'fas fa-file-excel';
        case 'ppt': case 'pptx': return 'fas fa-file-powerpoint';
        case 'txt': case 'rtf': return 'fas fa-file-alt';
        case 'mp4': case 'avi': case 'mov': case 'wmv': case 'flv': return 'fas fa-file-video';
        case 'mp3': case 'wav': case 'flac': case 'aac': return 'fas fa-file-audio';
        case 'html': case 'css': case 'js': case 'php': case 'py': case 'java': return 'fas fa-file-code';
        case 'ai': case 'psd': case 'sketch': case 'fig': return 'fas fa-palette';
        default: return 'fas fa-file';
    }
}

function getFileSizeDisplay(fileName) {
    // Esta función podría expandirse para mostrar el tamaño real del archivo
    return '';
}



// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modalDevolucion = document.getElementById('modal-devolucion');
    
    if (event.target == modalDevolucion) {
        cerrarModalDevolucion();
    }
}

// Función para mostrar historial de devoluciones - Reescrita desde cero
function mostrarHistorialDevoluciones(pedidoId) {
    // Validar parámetro de entrada
    if (!pedidoId || isNaN(pedidoId)) {
        alert('ID de pedido inválido');
        return;
    }
    
    console.log('Iniciando carga de historial para pedido:', pedidoId);
    
    // Mostrar indicador de carga
    const loadingModal = crearModalCarga();
    document.body.appendChild(loadingModal);
    
    // Construir URL del endpoint
    const url = `php/get_messages.php?pedido_id=${encodeURIComponent(pedidoId)}&tipo=devolucion`;
    console.log('URL de solicitud:', url);
    
    // Realizar petición fetch con manejo robusto de errores
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response completa:', response);
        console.log('Response status:', response.status);
        console.log('Response statusText:', response.statusText);
        console.log('Response ok:', response.ok);
        
        // Remover modal de carga
        if (loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        
        // Verificar status de respuesta
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
        }
        
        // Verificar content-type
        const contentType = response.headers.get('content-type');
        console.log('Content-Type:', contentType);
        
        if (!contentType || !contentType.includes('application/json')) {
            // Si no es JSON, obtener como texto para debug
            return response.text().then(text => {
                console.log('Respuesta como texto:', text);
                throw new Error('La respuesta no es JSON válido: ' + text.substring(0, 200));
            });
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Datos JSON recibidos:', data);
        
        // Validar estructura de respuesta
        if (!data || typeof data !== 'object') {
            throw new Error('Respuesta inválida del servidor');
        }
        
        if (data.success === true && data.messages && Array.isArray(data.messages) && data.messages.length > 0) {
            mostrarModalHistorial(data.messages, pedidoId);
        } else {
            const mensaje = data.message || 'No se encontraron devoluciones para este pedido';
            mostrarMensajeInfo(mensaje);
        }
    })
    .catch(error => {
        console.error('Error completo en fetch:', error);
        console.error('Stack trace:', error.stack);
        
        // Remover modal de carga si aún existe
        if (loadingModal.parentNode) {
            loadingModal.parentNode.removeChild(loadingModal);
        }
        
        alert(`Error al cargar el historial de devoluciones: ${error.message}`);
    });
}

// Función auxiliar para crear modal de carga
function crearModalCarga() {
    const modal = document.createElement('div');
    modal.className = 'modal modal-loading';
    modal.style.cssText = `
        display: block;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    `;
    
    modal.innerHTML = `
        <div style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        ">
            <div style="margin-bottom: 10px;">Cargando historial...</div>
            <div style="
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto;
            "></div>
        </div>
    `;
    
    return modal;
}

// Función auxiliar para mostrar el modal con el historial
function mostrarModalHistorial(messages, pedidoId) {
    // Construir HTML del historial
    let historialHtml = `
        <div class="historial-devoluciones" style="max-height: 500px; overflow-y: auto;">
            <h3 style="margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px;">
                <i class="fas fa-history"></i> Historial de Devoluciones - Pedido #${pedidoId}
            </h3>
    `;
    
    messages.forEach((mensaje, index) => {
        // Validar y formatear datos del mensaje
        const fecha = mensaje.fecha_creacion ? 
            new Date(mensaje.fecha_creacion).toLocaleString('es-ES', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }) : 'Fecha no disponible';
        
        const usuario = mensaje.usuario || 'Usuario no especificado';
        const area = mensaje.area_nombre || mensaje.area_origen || 'Área no especificada';
        const contenido = mensaje.mensaje || 'Sin mensaje';
        
        historialHtml += `
            <div class="mensaje-devolucion" style="
                border: 1px solid #ddd;
                border-radius: 8px;
                margin-bottom: 15px;
                padding: 15px;
                background: ${index % 2 === 0 ? '#f8f9fa' : '#ffffff'};
            ">
                <div class="mensaje-header" style="
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 10px;
                    flex-wrap: wrap;
                ">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <strong style="color: #007bff;">Área: ${area}</strong>
                        <strong style="color: #28a745;">Usuario: ${usuario}</strong>
                    </div>
                    <span class="fecha" style="color: #6c757d; font-size: 0.9em;">${fecha}</span>
                </div>
                <div class="mensaje-contenido" style="
                    background: white;
                    padding: 10px;
                    border-radius: 4px;
                    border-left: 4px solid #007bff;
                    line-height: 1.4;
                ">
                    ${contenido}
                </div>
            </div>
        `;
    });
    
    historialHtml += '</div>';
    
    // Crear y mostrar modal
    const modal = document.createElement('div');
    modal.className = 'modal modal-historial';
    modal.style.cssText = `
        display: block;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    `;
    
    modal.innerHTML = `
        <div class="modal-content" style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        ">
            <span class="close-button" style="
                position: absolute;
                top: 15px;
                right: 20px;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
                color: #aaa;
                z-index: 1;
            ">&times;</span>
            ${historialHtml}
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Event listeners para cerrar modal
    const closeBtn = modal.querySelector('.close-button');
    closeBtn.onclick = () => cerrarModal(modal);
    
    modal.onclick = (event) => {
        if (event.target === modal) {
            cerrarModal(modal);
        }
    };
    
    // Cerrar con tecla Escape
    const handleEscape = (event) => {
        if (event.key === 'Escape') {
            cerrarModal(modal);
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

// Función auxiliar para mostrar mensaje informativo
function mostrarMensajeInfo(mensaje) {
    const modal = document.createElement('div');
    modal.className = 'modal modal-info';
    modal.style.cssText = `
        display: block;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    `;
    
    modal.innerHTML = `
        <div class="modal-content" style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        ">
            <div style="margin-bottom: 20px; color: #6c757d;">
                <i class="fas fa-info-circle" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p style="margin: 0; font-size: 16px;">${mensaje}</p>
            </div>
            <button onclick="cerrarModal(this.closest('.modal'))" style="
                background: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-size: 14px;
            ">Cerrar</button>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Auto-cerrar después de 3 segundos
    setTimeout(() => {
        if (modal.parentNode) {
            cerrarModal(modal);
        }
    }, 3000);
}

// Función auxiliar para cerrar modal
function cerrarModal(modal) {
    if (modal && modal.parentNode) {
        modal.parentNode.removeChild(modal);
    }
}

// Event listener para el formulario de nuevo archivo
document.addEventListener('DOMContentLoaded', function() {
    const nuevoArchivoForm = document.getElementById('nuevo-archivo-form');
    if (nuevoArchivoForm) {
        nuevoArchivoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const uploadStatus = document.getElementById('upload-status');
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Deshabilitar botón y mostrar estado de carga
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
            uploadStatus.innerHTML = '<span style="color: #007bff;">Subiendo archivo...</span>';
            
            fetch('php/upload_nuevo_archivo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    uploadStatus.innerHTML = '<span style="color: #28a745;">✓ Archivo subido exitosamente</span>';
                    // Limpiar el formulario
                    document.getElementById('nuevo-archivo').value = '';
                    // Recargar los detalles del pedido para mostrar el nuevo archivo
                    const pedidoId = document.getElementById('pedido-id-archivo').value;
                    console.log('Recargando pedido ID:', pedidoId);
                    setTimeout(() => {
                        verPedido(pedidoId);
                        uploadStatus.innerHTML = '<span style="color: #28a745;">✓ Archivo subido y vista actualizada</span>';
                    }, 500);
                } else {
                    uploadStatus.innerHTML = '<span style="color: #dc3545;">✗ Error: ' + data.message + '</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                uploadStatus.innerHTML = '<span style="color: #dc3545;">✗ Error al subir el archivo</span>';
            })
            .finally(() => {
                // Rehabilitar botón
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-upload"></i> Subir';
            });
        });
    }
});