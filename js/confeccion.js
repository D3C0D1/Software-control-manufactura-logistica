document.addEventListener('DOMContentLoaded', function() {
    const areas = {
        recepcion: 4,
        enProceso: 5,
        preparado: 6
    };

    const notificationSound = document.getElementById('notification-sound');
    const warningSound = document.getElementById('warning-sound');
    let loadedPedidos = new Set();
    let isFirstLoad = true;

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



    // Crear modal de devolución
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
                    <div class="form-group">
                        <label for="return-area">Seleccionar área de destino:</label>
                        <select id="return-area" required>
                             <option value="">-- Seleccionar área --</option>
                        <option value="20">Area de Recepción</option>
                        <option value="1">Recepcion de diseño</option>
                        <option value="3">Preparado de diseño</option>
                        <option value="11">Recepcion de mensajeria</option>
                        <option value="13">Preparado de mensajeria</option>
                         <option value="7">Recepcion de sublimado</option>
                        <option value="9">Preparado de sublimado</option>
                        <option value="4">Recepcion de confeccion</option>
                        <option value="5">Proceso de confeccion</option>
                         <option value="14">Recepcion de impresion</option>
                        <option value="15">Preparado de impresion</option>
                        
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="return-message">Mensaje (opcional):</label>
                        <textarea id="return-message" placeholder="Escriba el motivo de la devolución..."></textarea>
                    </div>
                </div>
                <div class="return-modal-footer">
                    <button type="button" class="btn-cancel-return">Cancelar</button>
                    <button type="button" class="btn-confirm-return">Devolver</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    // Obtener o crear modal de devolución
    function getReturnModal() {
        let modal = document.getElementById('return-modal');
        if (!modal) {
            modal = createReturnModal();
        }
        return modal;
    }

    // Mostrar modal de devolución
    function showReturnModal(pedidoId) {
        const modal = getReturnModal();
        const closeBtn = modal.querySelector('.close-return-modal');
        const cancelBtn = modal.querySelector('.btn-cancel-return');
        const confirmBtn = modal.querySelector('.btn-confirm-return');
        const areaSelect = modal.querySelector('#return-area');
        const messageTextarea = modal.querySelector('#return-message');

        // Limpiar formulario
        areaSelect.value = '';
        messageTextarea.value = '';

        // Mostrar modal
        modal.style.display = 'block';

        // Eventos de cierre
        closeBtn.onclick = () => modal.style.display = 'none';
        cancelBtn.onclick = () => modal.style.display = 'none';
        
        // Cerrar al hacer clic fuera del modal
        window.onclick = (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };

        // Evento de confirmación
        confirmBtn.onclick = () => {
            const selectedArea = areaSelect.value;
            const message = messageTextarea.value.trim();

            if (!selectedArea) {
                alert('Por favor seleccione un área de destino');
                return;
            }

            // Determinar estado_id basado en el área de destino
            let estadoId;
            switch (parseInt(selectedArea)) {
                case 1: // Recepción
                case 4: // Recepción de Confección
                case 7: // Recepción de Sublimado
                case 13: // Recepción de Impresión
                    estadoId = 1; // Recibido
                    break;
                case 3: // Diseño
                case 5: // En Proceso de Confección
                case 8: // En Proceso de Sublimado
                case 14: // En Proceso de Impresión
                    estadoId = 2; // En Proceso
                    break;
                case 6: // Preparado de Confección
                case 9: // Preparado de Sublimado
                case 15: // Preparado de Impresión
                case 16: // Preparado de Impresión
                    estadoId = 3; // Preparado
                    break;
                case 11: // Mensajería
                case 12: // Control de Calidad
                    estadoId = 2; // En Proceso
                    break;
                default:
                    estadoId = 1; // Por defecto Recibido
            }

            // Cerrar modal y mover pedido
            modal.style.display = 'none';
            moverPedido(pedidoId, selectedArea, estadoId, message);
        };
    }

    // Event delegation for all buttons
    document.getElementById('confeccion-board').addEventListener('click', function(e) {
        if (e.target.matches('.btn-view')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            verPedido(pedidoId);
        } else if (e.target.matches('.btn-process')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            moverPedido(pedidoId, areas.enProceso);
        } else if (e.target.matches('.btn-ready')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            moverPedido(pedidoId, areas.preparado);
        } else if (e.target.matches('.btn-return')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            showReturnModal(pedidoId);
        } else if (e.target.matches('.btn-send-quality')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            moverPedido(pedidoId, 17); // Area ID for Control de Calidad

        } else if (e.target.matches('.btn-return-process')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            showReturnModal(pedidoId);
        } else if (e.target.matches('.btn-return-sublimado')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            showReturnModal(pedidoId);
        } else if (e.target.matches('.btn-finalizar')) {
            const pedidoId = e.target.closest('.pedido-card').dataset.id;
            moverPedido(pedidoId, 10); // Area ID for Finalizado
        }
    });

    function cargarPedidos() {
            fetch('php/get_pedidos.php?area_id=4,5,6&limit=30&urgentes_first=true')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error al obtener pedidos:', data.error);
                    limpiarColumnas();
                    document.getElementById('recepcion-col').innerHTML += '<p>Error al cargar pedidos.</p>';
                    return;
                }

                limpiarColumnas();

                // Ordenar pedidos: alta prioridad primero, luego por fecha
                data.sort((a, b) => {
                    if (a.prioridad === 'alta' && b.prioridad !== 'alta') return -1;
                    if (a.prioridad !== 'alta' && b.prioridad === 'alta') return 1;
                    return new Date(b.fecha_creacion) - new Date(a.fecha_creacion);
                });

                const currentPedidos = new Set(data.map(p => p.id));

                if (!isFirstLoad) {
                    let newPedidos = data.filter(p => !loadedPedidos.has(p.id) && parseInt(p.area_id, 10) === areas.recepcion);
                    if (newPedidos.length > 0) {
                        const highPriorityNew = newPedidos.some(p => p.prioridad === 'alta');
                        if (highPriorityNew) {
                            warningSound.play().catch(e => console.error("Error al reproducir sonido de advertencia:", e));
                        } else {
                            notificationSound.play().catch(e => console.error("Error al reproducir sonido de notificación:", e));
                        }
                    }
                }

                loadedPedidos = currentPedidos;
                isFirstLoad = false;

                let countRecepcion = 0, countProceso = 0, countPreparado = 0;
                data.forEach(pedido => {
                    const pedidoCard = crearPedidoCard(pedido);
                    if (pedido.prioridad === 'alta') {
                        pedidoCard.classList.add('prioridad-alta');
                    }

                    if (pedido.area_id == areas.recepcion) {
                        document.getElementById('recepcion-col').appendChild(pedidoCard);
                        countRecepcion++;
                    } else if (pedido.area_id == areas.enProceso) {
                        document.getElementById('en-proceso-col').appendChild(pedidoCard);
                        countProceso++;
                    } else if (pedido.area_id == areas.preparado) {
                        document.getElementById('preparado-col').appendChild(pedidoCard);
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

                // Actualizar badge general si está disponible
                try {
                    const totalOrders = (countRecepcion + countProceso + countPreparado) || 0;
                    if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
                        notificationSystem.updateOrdersBadge(totalOrders);
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar el badge de pedidos (confección):', e);
                }
            });
    }











    function limpiarColumnas() {
        document.getElementById('recepcion-col').innerHTML = '<h2>Recepción de Confección <span class="count-badge" id="recepcion-count">0</span></h2>';
        document.getElementById('en-proceso-col').innerHTML = '<h2>En Proceso de Confección <span class="count-badge" id="proceso-count">0</span></h2>';
        document.getElementById('preparado-col').innerHTML = '<h2>Preparado de Confección <span class="count-badge" id="preparado-count">0</span></h2>';
    }

    function crearPedidoCard(pedido) {
        const card = document.createElement('div');
        card.className = 'pedido-card';
        card.setAttribute('data-id', pedido.id);

        let adjuntosHtml = ''; // Inicializar como cadena vacía
        if (pedido.adjuntos && pedido.adjuntos.length > 0) {
            const totalAdjuntos = pedido.adjuntos.length;
            const iconClass = getIconForFileType(pedido.adjuntos[0].ruta_archivo);
            adjuntosHtml = `<p><i class="${iconClass}"></i> ${totalAdjuntos} archivo(s) adjunto(s)</p>`;
        } else {
            // No se agrega nada a adjuntosHtml si no hay archivos
        }

        // Información de notas del pedido
        const notasInfo = pedido.notas ? `<p><i class="fas fa-sticky-note"></i> <strong>Notas:</strong> ${pedido.notas}</p>` : '';
        
        // Indicador de devolución
        const indicadorDevolucion = pedido.tiene_devoluciones ? 
            '<div class="pedido-devolucion-badge"><i class="fas fa-undo-alt"></i><span>DEVOLUCIÓN</span></div>' : '';

        // Información de "Trabajada por" para área 5 (En Proceso de Confección)
        const trabajadaPorInfo = (pedido.area_id == 5 && pedido.usuario_proceso_confeccion) 
            ? `<p style="color: #007bff; font-weight: bold;"><i class="fas fa-user"></i> Trabajada por: ${pedido.usuario_proceso_confeccion}</p>` 
            : '';

        card.innerHTML = `
            <div class="pedido-header">
                <strong>Pedido #${pedido.id}</strong>
                ${indicadorDevolucion}
            </div>
            <p><b>Guía:</b> ${pedido.numero_guia}</p>
            <p><b>Cliente:</b> ${pedido.nombre_cliente}</p>
            ${notasInfo}
            ${adjuntosHtml}
            ${trabajadaPorInfo}
        `;
        const actions = document.createElement('div');
        actions.className = 'pedido-actions';
        actions.innerHTML = crearBotonesAccion(pedido);
        card.appendChild(actions);
        return card;
    }

    function crearBotonesAccion(pedido) {
        let botones = '<button class="btn-view" style="border-radius: 20px;"><i class="fas fa-eye"></i> Ver Pedido</button>';
        const areaId = parseInt(pedido.area_id, 10);

        if (areaId === areas.recepcion) {
            botones += ` <button class="btn-process"><i class="fas fa-cogs"></i> Iniciar Proceso</button>
                       <button class="btn-return" style="background-color: #ffc107;"><i class="fas fa-undo"></i> Devolver</button>`;
        } else if (areaId === areas.enProceso) {
            botones += ` <button class="btn-ready"><i class="fas fa-check-circle"></i> Marcar como Preparado</button>
                       <button class="btn-return"><i class="fas fa-undo"></i> Devolver</button>`;
        } else if (areaId === areas.preparado) {
            botones += ` <button class="btn-send-quality" style="background-color: #007bff;"><i class="fas fa-check-double"></i> Enviar a Control de Calidad</button>
                       <button class="btn-finalizar" style="background-color: #28a745;"><i class="fas fa-flag"></i> Finalizar Pedido</button>

                       <button class="btn-return" style="background-color: #ffc107;"><i class="fas fa-undo"></i> Devolver</button>`;
        }

        return botones;
    }

    function moverPedido(pedidoId, nuevaAreaId, estadoId = null, mensaje = '') {
        // Confirmación antes de mover; si es finalizado (área 10), avisar del SMS
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
        
        // Determinar el estado_id basado en el área de destino si no se proporciona
        if (estadoId === null) {
            if (nuevaAreaId === areas.recepcion) {
                estadoId = 1; // Recepción
            } else if (nuevaAreaId === areas.enProceso) {
                estadoId = 2; // En Proceso
            } else if (nuevaAreaId === areas.preparado) {
                estadoId = 3; // Preparado
            } else if (nuevaAreaId === 9) { // Preparado de Sublimado
                estadoId = 3; // Preparado
            } else if (nuevaAreaId === 17) { // Control de Calidad
                estadoId = 1; // Recepción
            } else if (nuevaAreaId === 10) { // Finalizado
                estadoId = 4; // Finalizado
            }
        }
        
        formData.append('estado_id', estadoId);
        
        if (mensaje) {
            formData.append('mensaje', mensaje);
        }

        fetch('php/update_pedido_area.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Verificar el tipo de contenido de la respuesta
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Respuesta no JSON:', text);
                    throw new Error('La respuesta del servidor no es JSON válido');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
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
                                            console.error('Error al enviar SMS:', smsResponse && (smsResponse.message || smsResponse.error || smsResponse.details));
                                            const detalle = (() => {
                                                try { return smsResponse && (smsResponse.message || smsResponse.error || smsResponse.details || JSON.stringify(smsResponse)); } catch (e) { return 'Error desconocido'; }
                                            })();
                                            mostrarNotificacion('Error al enviar SMS: ' + detalle, 'error');
                                        }
                                    })
                                    .catch(err => {
                                        console.error('Error en la petición de SMS:', err);
                                        const detalle = (err && (err.stack || err.message)) ? (err.stack || err.message) : String(err);
                                        mostrarNotificacion('Error al enviar SMS: ' + detalle, 'error');
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
                console.error('Error del servidor:', data.error);
                mostrarNotificacion('Error al mover el pedido: ' + (data.error || 'Error desconocido'), 'error');
            }
        })
        .catch(error => {
            console.error('Error en la petición:', error);
            mostrarNotificacion('Error de conexión: ' + error.message, 'error');
        });
    }

    // Función para mostrar notificaciones con texto y duración variable
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
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(6px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            max-width: 86vw;
            transition: transform 0.18s ease, opacity 0.18s ease;
        `;

        const icon = document.createElement('i');
        icon.className = tipo === 'success' ? 'fas fa-check-circle' : (tipo === 'warning' ? 'fas fa-exclamation-circle' : 'fas fa-times-circle');
        icon.style.cssText = `font-size: 22px; color: ${tipo === 'success' ? '#10b981' : '#111'};`;
        toast.appendChild(icon);

        if (mensaje) {
            const texto = document.createElement('span');
            texto.textContent = mensaje;
            texto.style.cssText = `
                color: #111;
                font-size: 13px;
                white-space: pre-wrap;
            `;
            toast.appendChild(texto);
        }

        document.body.appendChild(toast);
        requestAnimationFrame(() => {
            toast.style.transform = 'translateX(-50%) scale(1)';
            toast.style.opacity = '1';
        });

        const duration = (tipo === 'error' || tipo === 'warning') ? 5000 : 2000;
        setTimeout(() => {
            toast.style.transform = 'translateX(-50%) scale(0.9)';
            toast.style.opacity = '0';
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 200);
        }, duration);
    }

    function verPedido(id) {
        fetch(`php/get_pedido.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('view-modal');
                    const details = document.getElementById('pedido-details');
                    const pedido = data.pedido;

                    // Separar archivos originales y nuevos
                    let adjuntosOriginalesHtml = '';
                    let adjuntosNuevosHtml = '';
                    
                    if (pedido.adjuntos && pedido.adjuntos.length > 0) {
                        const archivosOriginales = [];
                        const archivosNuevos = [];
                        
                        pedido.adjuntos.forEach(adjunto => {
                            const fileName = adjunto.ruta_archivo.split('/').pop();
                            if (fileName.includes('_nuevo_')) {
                                archivosNuevos.push(adjunto);
                            } else {
                                archivosOriginales.push(adjunto);
                            }
                        });
                        
                        // Archivos originales
                        if (archivosOriginales.length > 0) {
                            adjuntosOriginalesHtml = '<h4><span class="badge-original">Archivo Original (Viejo Archivo)</span></h4><ul>';
                            archivosOriginales.forEach(adjunto => {
                                const fileName = adjunto.ruta_archivo.split('/').pop();
                                const fileExtension = fileName.split('.').pop().toLowerCase();
                                const iconClass = getIconForFileType(adjunto.ruta_archivo);
                                const shortFileName = fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName;
                                adjuntosOriginalesHtml += `
                                    <li>
                                        <a href="${adjunto.ruta_archivo}" target="_blank" title="${fileName}">
                                            <i class="${iconClass}"></i>
                                            <span class="file-name">${shortFileName}</span>
                                            <span class="file-type">${fileExtension.toUpperCase()}</span>
                                        </a>
                                    </li>`;
                            });
                            adjuntosOriginalesHtml += '</ul>';
                        }
                        
                        // Archivos nuevos
                        if (archivosNuevos.length > 0) {
                            adjuntosNuevosHtml = '<h4><span class="badge-nuevo">Archivos Nuevos</span></h4><ul>';
                            archivosNuevos.forEach(adjunto => {
                                const fileName = adjunto.ruta_archivo.split('/').pop();
                                const fileExtension = fileName.split('.').pop().toLowerCase();
                                const iconClass = getIconForFileType(adjunto.ruta_archivo);
                                const shortFileName = fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName;
                                adjuntosNuevosHtml += `
                                    <li>
                                        <a href="${adjunto.ruta_archivo}" target="_blank" title="${fileName}">
                                            <i class="${iconClass}"></i>
                                            <span class="file-name">${shortFileName}</span>
                                            <span class="file-type">${fileExtension.toUpperCase()}</span>
                                        </a>
                                    </li>`;
                            });
                            adjuntosNuevosHtml += '</ul>';
                        }
                    }
                    
                    // Combinar HTML de archivos
                    const adjuntosHtml = (adjuntosOriginalesHtml || adjuntosNuevosHtml) ? 
                        `<div class="archivos-section">${adjuntosOriginalesHtml}${adjuntosNuevosHtml}</div>` : 
                        '<div class="archivos-section"><p>No hay archivos adjuntos.</p></div>';

                    // Indicador de devolución
                    const indicadorDevolucion = pedido.tiene_devoluciones ? 
                        '<div class="return-indicator"><i class="fas fa-undo"></i> Este pedido tiene devoluciones</div>' : '';
                    
                    // Botón de historial si tiene devoluciones
                    const botonHistorial = pedido.tiene_devoluciones ? 
                        `<button class="btn-historial-devoluciones" onclick="mostrarHistorialDevoluciones(${id})"><i class="fas fa-history"></i> Ver Historial de Devoluciones</button>` : '';

                    details.innerHTML = `
                        ${indicadorDevolucion}
                        <p><strong>Guía:</strong> ${pedido.numero_guia}</p>
                        <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                        <p><strong>Email:</strong> ${pedido.correo_cliente}</p>
                        <p><strong>Móvil:</strong> ${pedido.numero_cliente}</p>
                        <p><strong>Notas:</strong> ${pedido.notas || 'Sin notas'}</p>
                        ${adjuntosHtml}
                        ${botonHistorial}
                    `;
                    modal.style.display = 'block';

                    const closeButton = modal.querySelector('.close-button');
                    closeButton.onclick = () => {
                        modal.style.display = 'none';
                    };

                    window.onclick = (event) => {
                        if (event.target == modal) {
                            modal.style.display = 'none';
                        }
                    };
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar el pedido');
            });
    }
});

// Funciones globales para el historial de devoluciones
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
                                <strong>Área:</strong> ${mensaje.area_origen || 'No especificada'}<br>
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
                overlay.style.display = 'flex'; // Forzar display flex
                document.body.appendChild(overlay);
                
                // Agregar evento para cerrar al hacer clic en el overlay
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        cerrarHistorial();
                    }
                });
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

function getIconForFileType(fileName) {
    const extension = fileName.split('.').pop().toLowerCase();
    switch (extension) {
        case 'pdf':
            return 'fas fa-file-pdf';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fas fa-file-image';
        case 'zip':
        case 'rar':
            return 'fas fa-file-archive';
        default:
            return 'fas fa-file-alt';
    }
}
