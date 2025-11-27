document.addEventListener('DOMContentLoaded', function() {
    let loadedPedidoIds = new Set();
    let isFirstLoad = true;
    const movingCards = new Map();

    // Crear modal de devolución
    createReturnModal();

    function cargarPedidos() {
        // IDs de área para Recepción de Impresión, En Proceso de Impresión, Preparado de Impresión
        const areasImpresion = [14, 15, 16];

    fetch(`php/get_pedidos.php?area_id=${areasImpresion.join(',')}&limit=30&urgentes_first=true`)
            .then(response => response.json())
            .then(data => {
                const recepcion = document.getElementById('recepcion');
                const enProceso = document.getElementById('en-proceso');
                const preparado = document.getElementById('preparado');

                recepcion.innerHTML = '<h2>Recepción <span class="count-badge" id="recepcion-count">0</span></h2>';
                enProceso.innerHTML = '<h2>En Proceso <span class="count-badge" id="proceso-count">0</span></h2>';
                preparado.innerHTML = '<h2>Preparado <span class="count-badge" id="preparado-count">0</span></h2>';

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


                let countRecepcion = 0, countProceso = 0, countPreparado = 0;
                data.forEach(pedido => {
                    currentPedidoIds.add(pedido.id);
                    if (!isFirstLoad && !loadedPedidoIds.has(pedido.id)) {
                        if (pedido.prioridad === 'alta') {
                            hasNewPriorityOrder = true;
                        }
                    }

                    // Información de "Trabajada por" para área 15 (En Proceso de Impresión)
                    const trabajadaPorInfo = (pedido.area_id == 15 && pedido.usuario_proceso_impresion) 
                        ? `<p style="color: #007bff; font-weight: bold;"><i class="fas fa-user"></i> Trabajada por: ${pedido.usuario_proceso_impresion}</p>` 
                        : '';

                    // Información de notas del pedido
                    const notasInfo = pedido.notas ? `<p style="color: #6c757d; font-style: italic;"><i class="fas fa-sticky-note"></i> ${pedido.notas}</p>` : '';

                    // Indicador de devolución
                    const indicadorDevolucion = pedido.tiene_devoluciones ? 
                        '<span class="pedido-devolucion-badge"><i class="fas fa-exclamation-triangle"></i> Devuelto</span>' : '';

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

                    if (pedido.area_id == 14) {
                        recepcion.appendChild(pedidoElement);
                        countRecepcion++;
                    } else if (pedido.area_id == 15) {
                        enProceso.appendChild(pedidoElement);
                        countProceso++;
                    } else if (pedido.area_id == 16) {
                        preparado.appendChild(pedidoElement);
                        countPreparado++;
                    }
                });

                // Actualizar badges de conteo
                const rc = document.getElementById('recepcion-count');
                const pc = document.getElementById('proceso-count');
                const prc = document.getElementById('preparado-count');
                if (rc) rc.textContent = countRecepcion;
                if (pc) pc.textContent = countProceso;
                if (prc) prc.textContent = countPreparado;

                // Actualizar badge general de notificaciones
                try {
                    const totalOrders = (countRecepcion + countProceso + countPreparado) || 0;
                    if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
                        notificationSystem.updateOrdersBadge(totalOrders);
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar el badge de pedidos (impresión):', e);
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

                loadedPedidoIds = new Set(currentPedidoIds);
                isFirstLoad = false;
            })
            .catch(error => console.error('Error al cargar los pedidos:', error));
    }

    function crearBotonesAccion(pedido) {
        let botones = '';
        if (pedido.area_id == 14) { // Recepción de Impresión
            botones = `<button class="btn-process" data-id="${pedido.id}" data-area="15"><i class="fas fa-cogs"></i>Iniciar Proceso</button>
                       <button class="btn-return" data-id="${pedido.id}"><i class="fas fa-undo"></i>Devolver</button>`;
        } else if (pedido.area_id == 15) { // En Proceso de Impresión
            botones = `<button class="btn-ready" data-id="${pedido.id}" data-area="16"><i class="fas fa-check-circle"></i>Marcar como Preparado</button>
                       <button class="btn-return" data-id="${pedido.id}"><i class="fas fa-undo"></i>Devolver</button>`;
        } else if (pedido.area_id == 16) { // Preparado para Sublimado
            botones = `<button class="btn-send" data-id="${pedido.id}" data-area="7" style="background-color: #007bff; color: white;"><i class="fas fa-paper-plane"></i>Enviar a Sublimado</button>
                       <button class="btn-finalize" data-id="${pedido.id}" data-area="10" style="background-color: #28a745; color: white;"><i class="fas fa-flag"></i>Finalizar Pedido</button>
                       <button class="btn-return" data-id="${pedido.id}"><i class="fas fa-undo"></i>Devolver</button>`;
        }
        return botones;
    }

    function createReturnModal() {
        const modal = document.createElement('div');
        modal.id = 'return-modal';
        modal.className = 'return-modal';
        modal.innerHTML = `
            <div class="return-modal-content">
                <div class="return-modal-header">
                    <h3>Devolver Pedido</h3>
                    <span class="close-return-modal">&times;</span>
                </div>
                <div class="return-modal-body">
                    <form id="return-form">
                        <div class="form-group">
                            <label for="return-area">Área de destino:</label>
                            <select id="return-area" name="area_id" required>
                                <option value="20">Area de Recepción</option>
                        <option value="1">Recepcion de diseño</option>
                        <option value="3">Preparado de diseño</option>
                        <option value="11">Recepcion de mensajeria</option>
                        <option value="13">Preparado de mensajeria</option>
                        
                        
                         <option value="14">Recepcion de impresion</option>
                        <option value="15">Proceso de impresion</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="return-message">Motivo de devolución:</label>
                            <textarea id="return-message" name="mensaje" rows="4" placeholder="Describe el motivo de la devolución..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="return-modal-footer">
                    <button type="button" class="btn-cancel-return">Cancelar</button>
                    <button type="button" class="btn-confirm-return">Devolver</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Event listeners para el modal
        const closeModal = modal.querySelector('.close-return-modal');
        const cancelBtn = modal.querySelector('.btn-cancel-return');
        const confirmBtn = modal.querySelector('.btn-confirm-return');

        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Cerrar modal al hacer clic fuera
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        confirmBtn.addEventListener('click', () => {
            const areaSelect = document.getElementById('return-area');
            const messageTextarea = document.getElementById('return-message');
            
            if (!areaSelect.value) {
                mostrarNotificacion('Por favor selecciona un área de destino', 'error');
                return;
            }
            
            if (!messageTextarea.value.trim()) {
                mostrarNotificacion('Por favor ingresa el motivo de la devolución', 'error');
                return;
            }

            const pedidoId = modal.dataset.pedidoId;
            const areaId = areaSelect.value;
            const mensaje = messageTextarea.value.trim();

            devolverPedido(pedidoId, areaId, mensaje);
            modal.style.display = 'none';
        });
    }

    function devolverPedido(pedidoId, areaId, mensaje) {
        // Determinar el estado_id basado en el área de destino
        let estadoId;
        switch(parseInt(areaId)) {
            case 1: // Recepción
                estadoId = 1; // En espera
                break;
            case 3: // Preparado de Diseño
                estadoId = 3; // Preparado
                break;
            case 11: // Mensajería
                estadoId = 1; // En espera
                break;
            case 14: // Recepción de Impresión
                estadoId = 1; // En espera
                break;
            case 15: // En Proceso de Impresión
                estadoId = 2; // En proceso
                break;
            default:
                estadoId = 1; // Por defecto en espera
        }

        const formData = new FormData();
        formData.append('id', pedidoId);
        formData.append('area_id', areaId);
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
                cargarPedidos();
                mostrarNotificacion('Pedido devuelto exitosamente', 'success');
                
                // Limpiar formulario
                document.getElementById('return-area').value = '';
                document.getElementById('return-message').value = '';
            } else {
                const msg = (data.message || '').toString();
                if (msg.includes("Field 'id' doesn't have a default value")) {
                    cargarPedidos();
                    mostrarNotificacion('Pedido devuelto correctamente. Si no ves cambios, recarga la página.', 'success');
                } else {
                    console.error('Detalle al devolver el pedido:', msg);
                    mostrarNotificacion('No se pudo devolver el pedido. Por favor recarga la página e inténtalo nuevamente.', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            mostrarNotificacion('Error de conexión: ' + error.message, 'error');
        });
    }

    function moverPedido(pedidoId, nuevaAreaId, cardEl) {
        console.log('Moviendo pedido:', pedidoId, 'a área:', nuevaAreaId);
        // Confirmación antes de mover; si es finalizado (área 10), avisar del SMS
        const esFinalizado = parseInt(nuevaAreaId, 10) === 10;
        const confirmMessage = esFinalizado
            ? "¿Estás seguro de finalizar este pedido? Se notificará al cliente según la configuración de SMS."
            : "¿Estás seguro de mover este pedido?";
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Determinar el estado_id basado en el área de destino
        let estadoId;
        switch(parseInt(nuevaAreaId)) {
            case 3: // Preparado de Diseño
                estadoId = 3; // Preparado
                break;
            case 7: // Sublimado
                estadoId = 1; // En espera
                break;
            case 10: // Finalizado
                estadoId = 4; // Finalizado
                break;
            case 14: // Recepción de Impresión
                estadoId = 1; // En espera
                break;
            case 15: // En Proceso de Impresión
                estadoId = 2; // En proceso
                break;
            case 16: // Preparado de Impresión
                estadoId = 3; // Preparado
                break;
            default:
                estadoId = 1; // Por defecto en espera
        }

        // Crear FormData en lugar de JSON
        const formData = new FormData();
        formData.append('id', pedidoId);
        formData.append('area_id', nuevaAreaId);
        formData.append('estado_id', estadoId);

        fetch('php/update_pedido_area.php', {
            method: 'POST',
            body: formData // Enviar FormData en lugar de JSON
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
                finalizarCardMovida(pedidoId, cardEl);
                cargarPedidos();
                mostrarNotificacion('Pedido movido exitosamente', 'success');
                // Si se finaliza el pedido (área 10), enviar SMS al cliente
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
                }
            } else {
                console.error('Error del servidor:', data.message);
                revertirCardMoviendo(pedidoId, cardEl);
                mostrarNotificacion('Error al mover el pedido: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            revertirCardMoviendo(pedidoId, cardEl);
            mostrarNotificacion('Error de conexión: ' + error.message, 'error');
        });
    }

    function marcarCardMoviendo(pedidoId, cardEl) {
        if (!cardEl) return;
        movingCards.set(String(pedidoId), cardEl);
        cardEl.style.opacity = '0.5';
        cardEl.style.pointerEvents = 'none';
        const overlay = document.createElement('div');
        overlay.className = 'card-moving-overlay';
        overlay.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,0.05);display:flex;align-items:center;justify-content:center;font-weight:bold;color:#007bff;';
        overlay.textContent = 'Moviendo...';
        cardEl.style.position = 'relative';
        cardEl.appendChild(overlay);
    }

    function revertirCardMoviendo(pedidoId, cardEl) {
        const el = cardEl || movingCards.get(String(pedidoId));
        if (!el) return;
        el.style.opacity = '1';
        el.style.pointerEvents = '';
        const overlay = el.querySelector('.card-moving-overlay');
        if (overlay) overlay.remove();
        movingCards.delete(String(pedidoId));
    }

    function finalizarCardMovida(pedidoId, cardEl) {
        const el = cardEl || movingCards.get(String(pedidoId));
        if (!el) return;
        if (el.parentNode) {
            el.parentNode.removeChild(el);
        }
        movingCards.delete(String(pedidoId));
    }

    // Función para mostrar notificaciones (solo icono, sin texto)
    function mostrarNotificacion(mensaje, tipo) {
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
                    viewModal.style.display = 'block';
                } else {
                    alert(data.message);
                }
            });
    }

    cargarPedidos();

    // Polling sensible a visibilidad, intervalo más rápido (6s)
    let pedidosIntervalId = null;
    function startPolling() {
        if (pedidosIntervalId) return;
        pedidosIntervalId = setInterval(cargarPedidos, 6000);
    }
    function stopPolling() {
        if (pedidosIntervalId) {
            clearInterval(pedidosIntervalId);
            pedidosIntervalId = null;
        }
    }
    startPolling();

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            cargarPedidos();
            startPolling();
        } else {
            stopPolling();
        }
    });

    window.addEventListener('beforeunload', stopPolling);

    const viewModal = document.getElementById('view-modal');
    const closeButton = viewModal.querySelector('.close-button');

    closeButton.addEventListener('click', () => {
        viewModal.style.display = 'none';
    });

    // Event listener para cerrar modal al hacer click fuera
    window.addEventListener('click', (event) => {
        if (event.target == viewModal) {
            viewModal.style.display = 'none';
        }
    });

    // Event listeners para botones dinámicos usando delegación de eventos
    document.addEventListener('click', function(e) {
        // Botón Ver Pedido
        if (e.target.closest('.btn-view')) {
            const pedidoId = e.target.closest('.btn-view').dataset.id;
            verPedido(pedidoId);
        }
        
        // Botón Procesar
        if (e.target.closest('.btn-process')) {
            const btn = e.target.closest('.btn-process');
            const pedidoId = btn.dataset.id;
            const areaId = btn.dataset.area;
            const card = btn.closest('.pedido-card');
            marcarCardMoviendo(pedidoId, card);
            moverPedido(pedidoId, areaId, card);
        }
        
        // Botón Marcar como Preparado
        if (e.target.closest('.btn-ready')) {
            const btn = e.target.closest('.btn-ready');
            const pedidoId = btn.dataset.id;
            const areaId = btn.dataset.area;
            const card = btn.closest('.pedido-card');
            marcarCardMoviendo(pedidoId, card);
            moverPedido(pedidoId, areaId, card);
        }
        
        // Botón Enviar
        if (e.target.closest('.btn-send')) {
            const btn = e.target.closest('.btn-send');
            const pedidoId = btn.dataset.id;
            const areaId = btn.dataset.area;
            const card = btn.closest('.pedido-card');
            marcarCardMoviendo(pedidoId, card);
            moverPedido(pedidoId, areaId, card);
        }
        
        // Botón Finalizar
        if (e.target.closest('.btn-finalize')) {
            const btn = e.target.closest('.btn-finalize');
            const pedidoId = btn.dataset.id;
            const areaId = btn.dataset.area;
            const card = btn.closest('.pedido-card');
            marcarCardMoviendo(pedidoId, card);
            moverPedido(pedidoId, areaId, card);
        }
        
        // Botón Devolver
        if (e.target.closest('.btn-return')) {
            const pedidoId = e.target.closest('.btn-return').dataset.id;
            const modal = document.getElementById('return-modal');
            modal.dataset.pedidoId = pedidoId;
            modal.style.display = 'block';
        }
    });


});

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
    // Esta función podría implementarse para mostrar el tamaño del archivo
    return '';
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
            <i class="fas fa-info-circle" style="font-size: 48px; color: #17a2b8; margin-bottom: 15px;"></i>
            <p style="margin: 0; font-size: 16px; color: #333;">${mensaje}</p>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Auto-cerrar después de 3 segundos
    setTimeout(() => {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
    }, 3000);
    
    // Permitir cerrar haciendo clic
    modal.onclick = () => {
        if (modal.parentNode) {
            modal.parentNode.removeChild(modal);
        }
    };
}

// Función auxiliar para cerrar modales
function cerrarModal(modal) {
    if (modal && modal.parentNode) {
        modal.parentNode.removeChild(modal);
    }
}

// Función para cerrar el historial
function cerrarHistorial() {
    const overlay = document.getElementById('historial-overlay');
    if (overlay) {
        overlay.remove();
    }
}