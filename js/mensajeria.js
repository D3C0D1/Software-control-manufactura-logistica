document.addEventListener('DOMContentLoaded', function() {
    let loadedPedidoIds = new Set();
    let isFirstLoad = true;
    crearOverlayCargaGlobal();

    let mensaOffset = 0;
    const mensaLimit = 30;

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

    function crearBotonVerMas() {
        const btnId = 'ver-mas-mensajeria';
        if (document.getElementById(btnId)) return;
        const btn = document.createElement('button');
        btn.id = btnId;
        btn.textContent = 'Ver más';
        btn.style.margin = '16px auto';
        btn.style.display = 'block';
        btn.className = 'btn-view';
        btn.addEventListener('click', () => {
            mensaOffset += mensaLimit;
            cargarPedidos(false);
        });
        const main = document.querySelector('.content-sections') || document.body;
        main.appendChild(btn);
    }

    function cargarPedidos(reset) {
        const areasMensajeria = [11, 12, 13];
        if (reset) { mensaOffset = 0; }
        mostrarOverlay();
        fetch(`php/get_pedidos.php?area_id=${areasMensajeria.join(',')}&limit=${mensaLimit}&offset=${mensaOffset}&urgentes_first=true`)
            .then(response => response.json())
            .then(data => {
                const recepcion = document.getElementById('recepcion');
                const enProceso = document.getElementById('en-proceso');
                const preparado = document.getElementById('preparado');

                if (reset) {
                    recepcion.innerHTML = '<h2>Recepción de Mensajería <span class="count-badge" id="recepcion-count">0</span></h2>';
                    enProceso.innerHTML = '<h2>En Proceso de Mensajería <span class="count-badge" id="proceso-count">0</span></h2>';
                    preparado.innerHTML = '<h2>Preparado de Mensajería <span class="count-badge" id="preparado-count">0</span></h2>';
                }

                if (data.error) {
                    console.error('Error del servidor:', data.error);
                    return;
                }

                const currentPedidoIds = new Set();
                let hasNewPriorityOrder = false;

                data.sort((a, b) => {
                    if (a.prioridad === 'alta' && b.prioridad !== 'alta') return -1;
                    if (a.prioridad !== 'alta' && b.prioridad === 'alta') return 1;
                    return new Date(b.fecha_creacion) - new Date(a.fecha_creacion);
                });

                // Contadores por área de Mensajería
                let countRecepcion = 0, countProceso = 0, countPreparado = 0;

                data.forEach(pedido => {
                    currentPedidoIds.add(pedido.id);
                    if (!loadedPedidoIds.has(pedido.id)) {
                        if (pedido.prioridad === 'alta') {
                            hasNewPriorityOrder = true;
                        }
                    }

                    const pedidoElement = document.createElement('div');
                    pedidoElement.className = 'pedido-card';
                    if (pedido.prioridad === 'alta') {
                        pedidoElement.classList.add('prioridad-alta');
                    }

                    // Información de "Trabajada por" para área 12 (En Proceso de Mensajería)
                    const trabajadaPorInfo = (pedido.area_id == 12 && pedido.usuario_proceso_mensajeria) 
                        ? `<p style="color: #007bff; font-weight: bold;"><i class="fas fa-user"></i> Trabajada por: ${pedido.usuario_proceso_mensajeria}</p>` 
                        : '';
                    
                    // Información de notas del pedido
                    const notasInfo = pedido.notas && pedido.notas.trim() !== '' 
                        ? `<div class="pedido-notas">
                            <i class="fas fa-sticky-note nota-icon"></i>
                            <span class="nota-texto">${pedido.notas}</span>
                           </div>` 
                        : '';
                    
                    // Indicador de devolución (robusto ante '1', 1, true)
                    const hasDevolucion = Number(pedido.tiene_devoluciones) === 1 || pedido.tiene_devoluciones === true || pedido.tiene_devoluciones === 'true';
                    const indicadorDevolucion = hasDevolucion 
                        ? `<div class="pedido-devolucion-badge">
                            <i class="fas fa-undo-alt"></i>
                            <span>DEVOLUCIÓN</span>
                           </div>` 
                        : '';

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

                    if (pedido.area_id == 11) { 
                        recepcion.appendChild(pedidoElement);
                        countRecepcion++;
                    } else if (pedido.area_id == 12) { 
                        enProceso.appendChild(pedidoElement);
                        countProceso++;
                    } else if (pedido.area_id == 13) { 
                        preparado.appendChild(pedidoElement);
                        countPreparado++;
                    }
                });

                // Actualizar los números de pedidos por área
                const rc = document.getElementById('recepcion-count');
                const pc = document.getElementById('proceso-count');
                const prc = document.getElementById('preparado-count');
                if (rc) rc.textContent = countRecepcion;
                if (pc) pc.textContent = countProceso;
                if (prc) prc.textContent = countPreparado;

                // Actualizar badge de número total de pedidos en la campana
                try {
                    const totalOrders = (countRecepcion + countProceso + countPreparado) || 0;
                    if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
                        notificationSystem.updateOrdersBadge(totalOrders);
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar el badge de pedidos:', e);
                }

                if (!isFirstLoad) {
                    const newIds = [...currentPedidoIds].filter(id => !loadedPedidoIds.has(id));
                    if (newIds.length > 0) {
                        if (hasNewPriorityOrder) {
                            document.getElementById('audio-priority-order').play().catch(e => console.error("Error al reproducir sonido de prioridad:", e));
                        } else {
                            document.getElementById('audio-new-order').play().catch(e => console.error("Error al reproducir sonido de nuevo pedido:", e));
                        }
                    }
                }

                loadedPedidoIds = currentPedidoIds;
                isFirstLoad = false;
            })
            .finally(() => ocultarOverlay())
            .catch(error => console.error('Error al cargar los pedidos:', error));
    }

    cargarPedidos(true);
setInterval(() => cargarPedidos(true), 10000);

    const viewModal = document.getElementById('view-modal');
    const viewCloseButton = document.querySelector('.view-close-button');
    const pedidoDetailsContainer = document.getElementById('pedido-details');

    if(viewCloseButton) {
        viewCloseButton.addEventListener('click', () => {
            viewModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target == viewModal) {
            viewModal.style.display = 'none';
        }
        // Cerrar modal de devolución al hacer clic fuera
        if (event.target == document.getElementById('return-modal')) {
            document.getElementById('return-modal').style.display = 'none';
        }
    });

    // Delegación de eventos para los botones 'Ver Pedido'
    document.querySelector('.content-sections').addEventListener('click', function(e) {
        if (e.target && e.target.matches('button.btn-view')) {
            const id = e.target.dataset.id;
            verPedido(id);
        }
    });


});

function crearBotonesAccion(pedido) {
    let botones = '';
    if (pedido.area_id == 11) { // Recepción de Mensajería (ID 11)
        botones = `<button class="btn-process" onclick="moverPedido(${pedido.id}, 12, 2)"><i class="fas fa-cogs"></i>Iniciar Proceso</button>`;
        botones += `<button class="btn-return" onclick="abrirModalDevolucion(${pedido.id})" style="background-color: #dc3545; color: white; margin-left: 5px;"><i class="fas fa-undo"></i>Devolver</button>`;

    } else if (pedido.area_id == 12) { // En Proceso de Mensajería (ID 12)
        botones = `<button class="btn-ready" onclick="moverPedido(${pedido.id}, 13, 2)"><i class="fas fa-check-circle"></i>Marcar como Preparado</button>`;
        botones += `<button class="btn-return" onclick="abrirModalDevolucion(${pedido.id})" style="background-color: #dc3545; color: white; margin-left: 5px;"><i class="fas fa-undo"></i>Devolver</button>`;

    } else if (pedido.area_id == 13) { // Preparado de Mensajería (ID 13)
        botones = `<button class="btn-send-design" onclick="moverPedido(${pedido.id}, 1, 1)" style="background-color: #007bff;"><i class="fas fa-paint-brush"></i>Enviar a Diseño</button>`;
        botones += `<button class="btn-finalize" onclick="moverPedido(${pedido.id}, 10, 4)" style="background-color: #28a745; color: white;"><i class="fas fa-flag-checkered"></i>Finalizar Pedido</button>`;
        botones += `<button class="btn-return" onclick="abrirModalDevolucion(${pedido.id})" style="background-color: #dc3545; color: white; margin-left: 5px;"><i class="fas fa-undo"></i>Devolver</button>`;

    }
    return botones;
}









function moverPedido(pedidoId, nuevaAreaId, nuevoEstadoId) {
    console.log(`Moviendo pedido ${pedidoId} a área ${nuevaAreaId} con estado ${nuevoEstadoId}`);
    // Confirmación antes de mover, replicando la experiencia de control_calidad
    const esFinalizado = parseInt(nuevaAreaId, 10) === 10;
    const confirmMessage = esFinalizado
        ? "¿Estás seguro de finalizar este pedido? Se notificará al cliente según la configuración de SMS."
        : "¿Estás seguro de mover este pedido?";
    if (!confirm(confirmMessage)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', pedidoId);
    formData.append('area_id', nuevaAreaId);
    formData.append('estado_id', nuevoEstadoId);

    fetch('php/update_pedido_area.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta del servidor:', data);
        if (data.success) {
            mostrarNotificacion('Pedido movido exitosamente', 'success');
            cargarPedidos();
            // Si se finaliza el pedido (área 10), abrir modal para notificar al cliente
            if (parseInt(nuevaAreaId, 10) === 10) {
                // Enviar SMS al cliente notificando que el pedido está listo (igual que control_calidad)
                try {
                    fetch(`php/get_pedido.php?id=${pedidoId}`)
                        .then(response => response.json())
                        .then(pedidoData => {
                            const ok = pedidoData && pedidoData.success && pedidoData.pedido;
                            const telefono = ok ? (pedidoData.pedido.numero_cliente || '') : '';
                            if (telefono) {
                                const smsData = {
                                    pedido_id: pedidoId,
                                    numero_telefono: telefono
                                };
                                fetch('php/send_pedido_sms.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify(smsData)
                                })
                                .then(resp => resp.json())
                                .then(smsResponse => {
                                    if (smsResponse && smsResponse.success) {
                                        mostrarNotificacion('SMS de notificación enviado al cliente', 'success');
                                    } else {
                                        console.error('Error al enviar SMS:', smsResponse && smsResponse.message);
                                        mostrarNotificacion('Error al enviar SMS', 'warning');
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
            mostrarNotificacion('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        // Mostrar mensaje más específico pero tranquilizador
        mostrarNotificacion('Procesando... Si el pedido no se actualiza, recargue la página', 'error');
        // Intentar recargar los pedidos después de un breve delay por si la operación fue exitosa
        setTimeout(() => {
            cargarPedidos();
        }, 2000);
    });
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

                // Indicador de devolución
                const indicadorDevolucion = (Number(data.pedido.tiene_devoluciones) === 1 || data.pedido.tiene_devoluciones === true || data.pedido.tiene_devoluciones === 'true') ? 
                    '<div class="return-indicator"><i class="fas fa-undo"></i> Este pedido tiene devoluciones</div>' : '';
                
                // Botón de historial (restaurado): se muestra solo si hay devoluciones
                const botonHistorial = (Number(data.pedido.tiene_devoluciones) === 1 || data.pedido.tiene_devoluciones === true || data.pedido.tiene_devoluciones === 'true')
                    ? `<button class="btn-historial" onclick="mostrarHistorialDevoluciones(${pedidoId})"><i class="fas fa-history"></i> Ver Historial de Devoluciones</button>`
                    : '';

                pedidoDetailsContainer.innerHTML = `
                    ${indicadorDevolucion}
                    <p><strong>Guía:</strong> ${data.pedido.numero_guia}</p>
                    <p><strong>Cliente:</strong> ${data.pedido.nombre_cliente}</p>
                    <p><strong>Email:</strong> ${data.pedido.correo_cliente}</p>
                    <p><strong>Móvil:</strong> ${data.pedido.numero_cliente}</p>
                    <p><strong>Notas:</strong> ${data.pedido.notas}</p>
                    <div class="archivos-section">
                        ${adjuntosOriginalesHtml}
                        ${adjuntosNuevosHtml}
                    </div>
                    ${botonHistorial}
                `;
                viewModal.style.display = 'block';
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error al cargar pedido:', error);
            alert('Error al cargar los detalles del pedido');
        });
}

function getIconForExtension(extension) {
    switch (extension) {
        case 'pdf': return 'fas fa-file-pdf';
        case 'jpg': case 'jpeg': case 'png': case 'gif': return 'fas fa-file-image';
        case 'zip': case 'rar': return 'fas fa-file-archive';
        case 'doc': case 'docx': return 'fas fa-file-word';
        case 'xls': case 'xlsx': return 'fas fa-file-excel';
        case 'ppt': case 'pptx': return 'fas fa-file-powerpoint';
        default: return 'fas fa-file';
    }
}

function verDetallesPedido(pedidoId) {
    fetch(`php/get_pedido.php?id=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('view-modal');
                const detailsContainer = document.getElementById('pedido-details');
                
                detailsContainer.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <p><strong>Guía:</strong> ${data.pedido.numero_guia}</p>
                            <p><strong>Cliente:</strong> ${data.pedido.nombre_cliente}</p>
                            <p><strong>Email:</strong> ${data.pedido.correo_cliente}</p>
                            <p><strong>Móvil:</strong> ${data.pedido.numero_cliente}</p>
                        </div>
                        <div>
                            <p><strong>Estado Actual:</strong> ${data.pedido.area_nombre || 'N/A'}</p>
                            <p><strong>Fecha Creación:</strong> ${new Date(data.pedido.fecha_creacion).toLocaleString('es-ES')}</p>
                            <p><strong>Última Actualización:</strong> ${new Date(data.pedido.fecha_actualizacion).toLocaleString('es-ES')}</p>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <p><strong>Notas:</strong></p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 10px;">
                            ${data.pedido.notas || 'Sin notas'}
                        </div>
                    </div>
                    ${data.pedido.archivo_adjunto ? `
                        <div style="margin-top: 20px;">
                            <p><strong>Archivo Adjunto:</strong></p>
                            <a href="${data.pedido.archivo_adjunto}" target="_blank" class="btn-action btn-view">
                                <i class="fas fa-download"></i> Descargar ZIP
                            </a>
                        </div>
                    ` : ''}
                    <div style="margin-top: 20px; text-align: center;">
                        <!-- Botón SMS removido - usar chat interno en su lugar -->
                    </div>
                `;
                
                modal.style.display = 'block';
            } else {
                mostrarNotificacion('Error al cargar los detalles del pedido', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
           
        });
}

function filtrarPedidos(termino) {
    const pedidos = document.querySelectorAll('.pedido-card');
    const terminoLower = termino.toLowerCase();
    
    pedidos.forEach(pedido => {
        const guia = pedido.getAttribute('data-guia') || '';
        const cliente = pedido.getAttribute('data-cliente') || '';
        
        if (guia.includes(terminoLower) || cliente.includes(terminoLower)) {
            pedido.style.display = 'block';
        } else {
            pedido.style.display = 'none';
        }
    });
}

function actualizarContadores(recepcion, proceso, preparado) {
    document.getElementById('total-recepcion').textContent = recepcion;
    document.getElementById('total-proceso').textContent = proceso;
    
    // Actualizar el segundo contador para mostrar preparados
    const statCards = document.querySelectorAll('.stat-card');
    if (statCards.length > 1) {
        statCards[1].querySelector('.stat-number').textContent = preparado;
        statCards[1].querySelector('.stat-label').textContent = 'Preparados';
    }

    // Actualizar badge total de pedidos
    try {
        const totalOrders = (parseInt(recepcion || 0, 10) + parseInt(proceso || 0, 10) + parseInt(preparado || 0, 10)) || 0;
        if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
            notificationSystem.updateOrdersBadge(totalOrders);
        }
    } catch (e) {
        console.warn('No se pudo actualizar el badge de pedidos:', e);
    }
}

function mostrarEstadoVacio() {
    const containers = ['recepcion-pedidos', 'proceso-pedidos', 'preparado-pedidos'];
    const icons = ['inbox', 'cogs', 'check-circle'];
    const messages = ['No hay pedidos en recepción', 'No hay pedidos en proceso', 'No hay pedidos preparados'];
    
    containers.forEach((containerId, index) => {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-${icons[index]}" style="font-size: 3em; margin-bottom: 10px; opacity: 0.3;"></i>
                    <p>${messages[index]}</p>
                </div>
            `;
        }
    });
}

function mostrarNotificacion(mensaje, tipo) {
    // Notificación sin texto, solo icono animado en la parte superior
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
    icon.style.cssText = `
        font-size: 22px;
        color: ${tipo === 'success' ? '#10b981' : '#111'};
    `;
    toast.appendChild(icon);

    document.body.appendChild(toast);
    // Animación de entrada
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(-50%) scale(1)';
        toast.style.opacity = '1';
    });

    // Desaparece después de 2 segundos
    setTimeout(() => {
        toast.style.transform = 'translateX(-50%) scale(0.9)';
        toast.style.opacity = '0';
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 200);
    }, 2000);
}

// Agregar estilos de animación
function mostrarHistorialDevoluciones(pedidoId) {
    fetch(`php/get_messages.php?pedido_id=${pedidoId}&tipo=devolucion`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                let historialHtml = '<div class="historial-modal"><div class="historial-header"><h3>Historial de Devoluciones</h3><button onclick="cerrarHistorial()">×</button></div><div class="historial-content">';
                
                data.messages.forEach(mensaje => {
                    historialHtml += `
                        <div class="mensaje-devolucion">
                            <div class="mensaje-info">
                                <strong>Área:</strong> ${mensaje.area_nombre || 'No especificada'}<br>
                                <strong>Usuario:</strong> ${mensaje.usuario || 'Sistema'}<br>
                                <strong>Fecha:</strong> ${new Date(mensaje.fecha_creacion).toLocaleString()}
                            </div>
                            <div class="mensaje-texto">${mensaje.mensaje}</div>
                        </div>
                    `;
                });
                
                historialHtml += '</div></div>';
                
                // Crear overlay
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay';
                overlay.id = 'historial-overlay';
                overlay.innerHTML = historialHtml;
                document.body.appendChild(overlay);
            } else {
                alert('No se encontraron mensajes de devolución para este pedido');
            }
        })
        .catch(error => {
            console.error('Error en mostrarHistorialDevoluciones:', error);
            alert('Error al cargar el historial de devoluciones');
        });
}

function cerrarHistorial() {
    const overlay = document.getElementById('historial-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Fallback para abrir el modal de notificación si la función global no está disponible
function openReadyNotifyModalFallback(pedidoId) {
    try {
        fetch(`php/get_pedido.php?id=${pedidoId}`)
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    const modal = document.getElementById('ready-notify-modal');
                    if (!modal) {
                        console.warn('No se encontró el modal ready-notify-modal en el DOM');
                        return;
                    }
                    const telefono = (data.pedido && data.pedido.numero_cliente) ? data.pedido.numero_cliente : '';
                    const guia = (data.pedido && data.pedido.numero_guia) ? data.pedido.numero_guia : '';
                    const mensaje = `Tu pedido #${guia} está listo para recoger.`;

                    const inputId = document.getElementById('ready-notify-pedido-id');
                    const phoneInput = document.getElementById('ready-notify-phone');
                    const msgInput = document.getElementById('ready-notify-message');
                    const count = document.getElementById('ready-notify-count');
                    const messages = document.getElementById('ready-notify-messages');

                    if (inputId) inputId.value = pedidoId;
                    if (phoneInput) phoneInput.value = telefono;
                    if (msgInput) msgInput.value = mensaje;
                    if (count) count.textContent = mensaje.length;
                    if (messages) messages.innerHTML = '';

                    modal.style.display = 'block';
                } else {
                    if (typeof mostrarNotificacion === 'function') {
                        mostrarNotificacion('No se pudo preparar el modal de notificación', 'error');
                    }
                }
            })
            .catch(err => {
                if (typeof mostrarNotificacion === 'function') {
                    mostrarNotificacion('Error al preparar notificación: ' + err.message, 'error');
                }
                console.error('Fallback notify modal error:', err);
            });
    } catch (e) {
        console.error('Error inesperado en openReadyNotifyModalFallback:', e);
    }
}

// Funciones para modal de devolución
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
                        <option value="20">Área de Recepción</option>
                        <option value="1">Recepción de Diseño</option>
                        <option value="2">Proceso de Diseño</option>
                        <option value="3">Preparado de Diseño</option>
                        <option value="4">Recepción de Confección</option>
                        <option value="5">Proceso de Confección</option>
                        <option value="6">Preparado de Confección</option>
                        <option value="7">Recepción de Sublimado</option>
                        <option value="8">Proceso de Sublimado</option>
                        <option value="9">Preparado de Sublimado</option>
                        <option value="11">Recepción de Mensajería</option>
                        <option value="12">Proceso de Mensajería</option>
                        <option value="13">Preparado de Mensajería</option>
                        <option value="14">Recepción de Impresión</option>
                        <option value="15">Proceso de Impresión</option>
                        <option value="16">Preparado de Impresión</option>
                        <option value="21">Recepción de Control de Calidad</option>
                        <option value="22">Proceso de Control de Calidad</option>
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
    let modal = document.getElementById('modal-devolucion');
    if (!modal) {
        crearModalDevolucion();
        modal = document.getElementById('modal-devolucion');
    }
    modal.dataset.pedidoId = pedidoId;
    modal.style.display = 'block';
    
    // Limpiar campos
    document.getElementById('area-destino').value = '';
    document.getElementById('mensaje-devolucion').value = '';
}

function cerrarModalDevolucion() {
    const modal = document.getElementById('modal-devolucion');
    if (modal) {
        modal.style.display = 'none';
    }
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
        '20': 1, // Área de Recepción
        '1': 1,  // Recepción de Diseño
        '2': 2,  // Proceso de Diseño
        '3': 3,  // Preparado de Diseño
        '4': 1,  // Recepción de Confección
        '5': 2,  // Proceso de Confección
        '6': 3,  // Preparado de Confección
        '7': 1,  // Recepción de Sublimado
        '8': 2,  // Proceso de Sublimado
        '9': 3,  // Preparado de Sublimado
        '11': 1, // Recepción de Mensajería
        '12': 2, // Proceso de Mensajería
        '13': 3, // Preparado de Mensajería
        '14': 1, // Recepción de Impresión
        '15': 2, // Proceso de Impresión
        '16': 3, // Preparado de Impresión
        '21': 1, // Recepción de Control de Calidad
        '22': 2  // Proceso de Control de Calidad
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
            cargarPedidos(); // Recargar los pedidos
        } else {
            const msg = (data.message || '').toString();
            // Caso conocido: el backend puede devolver este error aunque se aplique fallback
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

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    .historial-modal {
        background: white;
        border-radius: 8px;
        max-width: 600px;
        width: 90%;
        max-height: 80%;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    .historial-header {
        background: #007bff;
        color: white;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .historial-header h3 {
        margin: 0;
    }
    .historial-header button {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
    }
    .historial-content {
        padding: 20px;
        max-height: 400px;
        overflow-y: auto;
    }
    .mensaje-devolucion {
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 15px;
        padding: 15px;
        background: #f8f9fa;
    }
    .mensaje-info {
        margin-bottom: 10px;
        font-size: 14px;
        color: #666;
    }
    .mensaje-texto {
        background: white;
        padding: 10px;
        border-radius: 4px;
        border-left: 4px solid #007bff;
    }
    .return-indicator {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        color: #856404;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 15px;
        text-align: center;
    }
    .btn-historial {
        background: #17a2b8;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
    }
    .btn-historial:hover {
        background: #138496;
    }
`;
document.head.appendChild(style);