document.addEventListener('DOMContentLoaded', function () {
    const recepcionCont = document.getElementById('pedidos-recepcion');
    const enProcesoCont = document.getElementById('pedidos-en-proceso');
    const preparadoCont = document.getElementById('pedidos-preparado');
    


    const areas = {
        recepcion: 7,        // Recepción de Sublimado
        enProceso: 8,        // En Proceso de Sublimado
        preparado: 9,        // Preparado de Sublimado
        impresion: 16,       // Preparado de Impresión
        confeccion: 4        // Recepción de Confección
    };

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
                        <option value="8">Preparado de sublimado</option>
                        
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

    function fetchPedidos() {
        // Cargar pedidos de las áreas de sublimado (7, 8, 9)
        fetch(`php/get_pedidos.php?area_id=${areas.recepcion},${areas.enProceso},${areas.preparado}&limit=30&urgentes_first=true`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Asegurar badges en encabezados
                const recepcionHeader = document.querySelector('#recepcion h2');
                const procesoHeader = document.querySelector('#en-proceso h2');
                const preparadoHeader = document.querySelector('#preparado h2');
                if (recepcionHeader) recepcionHeader.innerHTML = 'Recepción <span class="count-badge" id="recepcion-count">0</span>';
                if (procesoHeader) procesoHeader.innerHTML = 'En Proceso <span class="count-badge" id="proceso-count">0</span>';
                if (preparadoHeader) preparadoHeader.innerHTML = 'Preparado <span class="count-badge" id="preparado-count">0</span>';

                recepcionCont.innerHTML = '';
                enProcesoCont.innerHTML = '';
                preparadoCont.innerHTML = '';

                if (data.error) {
                    console.error('Error al obtener pedidos:', data.error);
                    return;
                }

                data.sort((a, b) => {
                    if (a.prioridad === 'alta' && b.prioridad !== 'alta') return -1;
                    if (a.prioridad !== 'alta' && b.prioridad === 'alta') return 1;
                    return new Date(b.fecha_creacion) - new Date(a.fecha_creacion);
                });





                let countRecepcion = 0, countProceso = 0, countPreparado = 0;
                data.forEach(pedido => {
                    const card = crearPedidoCard(pedido);
                    const areaId = parseInt(pedido.area_id, 10);

                    if (areaId === areas.recepcion) {
                        recepcionCont.appendChild(card);
                        countRecepcion++;
                    } else if (areaId === areas.enProceso) {
                        enProcesoCont.appendChild(card);
                        countProceso++;
                    } else if (areaId === areas.preparado) {
                        preparadoCont.appendChild(card);
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

                // Actualizar badge general (notificaciones) si existe
                try {
                    const totalOrders = (countRecepcion + countProceso + countPreparado) || 0;
                    if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
                        notificationSystem.updateOrdersBadge(totalOrders);
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar el badge de pedidos (sublimado):', e);
                }
            })
            .catch(error => console.error('Error en fetchPedidos:', error));
    }

    function crearPedidoCard(pedido) {
        const card = document.createElement('div');
        card.className = 'pedido-card';
        card.setAttribute('data-id', pedido.id);
        if (pedido.prioridad === 'alta') {
            card.classList.add('prioridad-alta');
        }

        // Información de "Trabajada por" para área 8 (En Proceso de Sublimado)
        const trabajadaPorInfo = (pedido.area_id == 8 && pedido.usuario_proceso_sublimado) 
            ? `<p style="color: #007bff; font-weight: bold;"><i class="fas fa-user"></i> Trabajada por: ${pedido.usuario_proceso_sublimado}</p>` 
            : '';

        // Información de notas del pedido
        const notasInfo = pedido.notas ? `<p style="color: #6c757d; font-style: italic;"><i class="fas fa-sticky-note"></i> ${pedido.notas}</p>` : '';

        // Indicador de devolución
        const indicadorDevolucion = pedido.tiene_devoluciones == 1 
            ? `<div class="pedido-devolucion-badge">
                <i class="fas fa-undo-alt"></i>
                <span>DEVOLUCIÓN</span>
               </div>` 
            : '';

        card.innerHTML = `
            <div class="pedido-header">
                <strong>Pedido #${pedido.id}</strong>
                ${indicadorDevolucion}
            </div>
            <p>Guía: ${pedido.numero_guia}</p>
            <p>Cliente: ${pedido.nombre_cliente}</p>
            ${trabajadaPorInfo}
            ${notasInfo}
        `;

        const actions = document.createElement('div');
        actions.className = 'pedido-actions';
        actions.innerHTML = crearBotonesAccion(pedido);
        card.appendChild(actions);

        // Asignar eventos a los botones de la tarjeta
        actions.addEventListener('click', function(e) {
            const button = e.target.closest('button');
            if (!button) return;

            const action = button.dataset.action;
            const areaId = button.dataset.areaId;
            const estadoId = button.dataset.estadoId;

            if (action === 'ver') {
                verPedido(pedido.id);
            } else if (action === 'mover') {
                moverPedido(pedido.id, areaId, estadoId);
            } else if (action === 'return') {
                showReturnModal(pedido.id);
            }
        });

        return card;
    }

    function crearBotonesAccion(pedido) {
        let botones = `<button class="btn-view" data-action="ver"><i class="fas fa-eye"></i>Ver Pedido</button>`;
        const areaId = parseInt(pedido.area_id, 10);

        switch (areaId) {
            case areas.recepcion: // Recepción de Sublimado (7)
                botones += `<button class="btn-process" data-action="mover" data-area-id="${areas.enProceso}" data-estado-id="2"><i class="fas fa-cogs"></i>Iniciar Proceso</button>
                           <button class="btn-return" data-action="return"><i class="fas fa-undo"></i>Devolver</button>`;
                break;
            case areas.enProceso: // En Proceso de Sublimado (8)
                botones += `<button class="btn-ready" data-action="mover" data-area-id="${areas.preparado}" data-estado-id="3"><i class="fas fa-check-circle"></i>Marcar como Preparado</button>
                           <button class="btn-return" data-action="return"><i class="fas fa-undo"></i>Devolver</button>`;
                break;
            case areas.preparado: // Preparado de Sublimado (9)
                botones += `<button class="btn-process" data-action="mover" data-area-id="${areas.confeccion}" data-estado-id="1"><i class="fas fa-cut"></i>Enviar a Recepción de Confección</button>
                           <button class="btn-finalize" data-action="mover" data-area-id="10" data-estado-id="4" style="background-color: #28a745; color: white;"><i class="fas fa-flag"></i>Finalizar Pedido</button>

                           <button class="btn-return" data-action="return"><i class="fas fa-undo"></i>Devolver</button>`;
                break;
        }
        return botones;
    }

    function moverPedido(pedidoId, areaId, estadoId, mensaje = '') {
        console.log('Moviendo pedido:', pedidoId, 'a área:', areaId, 'con estado:', estadoId);
        // Confirmación antes de mover; si es finalizado (área 10), avisar del SMS
        const esFinalizado = parseInt(areaId, 10) === 10;
        const confirmMessage = esFinalizado
            ? "¿Estás seguro de finalizar este pedido? Se notificará al cliente según la configuración de SMS."
            : "¿Estás seguro de mover este pedido?";
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Crear FormData en lugar de JSON
        const formData = new FormData();
        formData.append('id', pedidoId);
        formData.append('area_id', areaId);
        formData.append('estado_id', estadoId);
        if (mensaje) {
            formData.append('mensaje', mensaje);
        }

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
                fetchPedidos();
                mostrarNotificacion('Pedido movido exitosamente', 'success');
                // Si se finaliza el pedido (área 10), enviar SMS al cliente
                if (parseInt(areaId, 10) === 10) {
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
                mostrarNotificacion('Error al mover el pedido: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            mostrarNotificacion('Error de conexión: ' + error.message, 'error');
        });
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
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if(data.success){
                    const viewModal = document.getElementById('view-modal');
                    const pedidoDetailsContainer = document.getElementById('pedido-details');
                    
                    if (!viewModal || !pedidoDetailsContainer) {
                        alert('Error: No se encontraron los elementos del modal');
                        return;
                    }
                    
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
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al cargar el pedido: ' + error.message);
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



    fetchPedidos();
setInterval(fetchPedidos, 15000);
});

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

function cerrarHistorial() {
    const overlay = document.getElementById('historial-overlay');
    if (overlay) {
        overlay.remove();
    }
}