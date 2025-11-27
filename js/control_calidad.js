document.addEventListener('DOMContentLoaded', function() {
    const recepcionCol = document.getElementById('recepcion-col');
    const enProcesoCol = document.getElementById('en-proceso-col');
    const preparadoCol = document.getElementById('preparado-col');
    const audioPedido = document.getElementById('audio-pedido');
    const audioWarning = document.getElementById('audio-warning');

    const areaRecepcion = 17;
    const areaEnProceso = 18;
    const areaPreparado = 19;
    const areaFinalizado = 10;
    const areaPreparadoConfeccion = 6; // 'Preparado de Confección' para devoluciones

    let idsPedidosActuales = new Set();
    let primeraCarga = true;

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
                        <option value="6">Preparado de confeccion</option>
                         <option value="14">Recepcion de impresion</option>
                        <option value="16">Preparado de impresion</option>
                        <option value="17">Recepcion de control de calidad</option>
                        <option value="18">Proceso de control de calidad</option>
                    
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
                case 17: // Recepción de Control de Calidad
                    estadoId = 1; // Recibido
                    break;
                case 3: // Diseño
                case 5: // En Proceso de Confección
                case 8: // En Proceso de Sublimado
                case 14: // En Proceso de Impresión
                case 18: // En Proceso de Control de Calidad
                    estadoId = 2; // En Proceso
                    break;
                case 6: // Preparado de Confección
                case 9: // Preparado de Sublimado
                case 15: // Preparado de Impresión
                case 16: // Preparado de Impresión
                case 19: // Preparado de Control de Calidad
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

    function cargarPedidos() {
        // Cargar todas las columnas de Control de Calidad: Recepción (17), En Proceso (18), Preparado (19)
        fetch('php/get_pedidos.php?area_id=17,18,19&limit=30&urgentes_first=true')
            .then(response => response.json())
            .then(data => {
                const nuevosPedidos = [];
                const idsPedidosNuevos = new Set();

                // Limpiar columnas y añadir badges de conteo en los encabezados
                recepcionCol.innerHTML = '<h2>Recepción <span class="count-badge" id="recepcion-count">0</span></h2>';
                enProcesoCol.innerHTML = '<h2>En Proceso <span class="count-badge" id="proceso-count">0</span></h2>';
                preparadoCol.innerHTML = '<h2>Preparado <span class="count-badge" id="preparado-count">0</span></h2>';

                // Ordenar pedidos: alta prioridad primero, luego por fecha
                data.sort((a, b) => {
                    if (a.prioridad === 'alta' && b.prioridad !== 'alta') return -1;
                    if (a.prioridad !== 'alta' && b.prioridad === 'alta') return 1;
                    return new Date(b.fecha_creacion) - new Date(a.fecha_creacion);
                });

                let countRecepcion = 0, countProceso = 0, countPreparado = 0;
                data.forEach(pedido => {
                    idsPedidosNuevos.add(pedido.id.toString());
                    if (!idsPedidosActuales.has(pedido.id.toString())) {
                        nuevosPedidos.push(pedido);
                    }

                    const pedidoCard = document.createElement('div');
                    pedidoCard.className = 'pedido-card'; // Cambiado de board-item a pedido-card
                    if (pedido.prioridad === 'alta') {
                        pedidoCard.classList.add('prioridad-alta');
                    }

                    pedidoCard.dataset.id = pedido.id; // <-- AÑADIDO: Asignar el ID del pedido a la tarjeta

                    // Información de "Trabajada por" para área 17 (En Proceso de Control de Calidad)
                    const trabajadaPorInfo = (pedido.area_id == areaEnProceso && pedido.usuario_proceso_control_calidad) 
                        ? `<p style="color: #007bff; font-weight: bold;"><i class="fas fa-user"></i> Trabajada por: ${pedido.usuario_proceso_control_calidad}</p>` 
                        : '';

                    // Información de notas del pedido
                    const notasInfo = pedido.notas && pedido.notas.trim() !== '' 
                        ? `<p style="color: #6c757d; font-size: 0.9em; margin-top: 5px;"><i class="fas fa-sticky-note"></i> ${pedido.notas}</p>` 
                        : '';

                    // Indicador de devolución
                    const indicadorDevolucion = pedido.tiene_devoluciones 
                        ? `<div class="pedido-devolucion-badge" onclick="mostrarHistorialDevoluciones(${pedido.id})" style="cursor: pointer;"><i class="fas fa-undo-alt"></i><span>DEVOLUCIÓN</span></div>` 
                        : '';

                    pedidoCard.innerHTML = `
                        <div class="board-item-content">
                            <div class="pedido-header">
                                <strong>Pedido #${pedido.id}</strong>
                                ${indicadorDevolucion}
                            </div>
                            <p><b>Guía:</b> ${pedido.numero_guia}</p>
                            <p><b>Cliente:</b> ${pedido.nombre_cliente}</p>
                            ${trabajadaPorInfo}
                            ${notasInfo}
                        </div>
                        <div class="botones-container">
                            <!-- Los botones se generan dinámicamente -->
                        </div>
                    `;

                    crearBotonesAccion(pedidoCard.querySelector('.botones-container'), pedido);

                    if (pedido.area_id == areaRecepcion) {
                        recepcionCol.appendChild(pedidoCard);
                        countRecepcion++;
                    } else if (pedido.area_id == areaEnProceso) {
                        enProcesoCol.appendChild(pedidoCard);
                        countProceso++;
                    } else if (pedido.area_id == areaPreparado) {
                        preparadoCol.appendChild(pedidoCard);
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

                // Actualizar badge global de pedidos en notificaciones
                try {
                    const totalOrders = (countRecepcion + countProceso + countPreparado) || 0;
                    if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
                        notificationSystem.updateOrdersBadge(totalOrders);
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar el badge de pedidos (control_calidad):', e);
                }

                idsPedidosActuales = idsPedidosNuevos;

                if (!primeraCarga) {
                    const hayPrioridadAlta = nuevosPedidos.some(p => p.prioridad === 'alta' && p.area_id == areaRecepcion);
                    if (hayPrioridadAlta) {
                        audioWarning.play().catch(e => console.error("Error al reproducir sonido de advertencia:", e));
                    } else if (nuevosPedidos.length > 0) {
                        const hayNuevosEnRecepcion = nuevosPedidos.some(p => p.area_id == areaRecepcion);
                        if (hayNuevosEnRecepcion) {
                           audioPedido.play().catch(e => console.error("Error al reproducir sonido de pedido:", e));
                        }
                    }
                } else {
                    primeraCarga = false;
                }
            })
            .catch(error => console.error('Error al cargar los pedidos de control de calidad:', error));
    }

    function crearBotonesAccion(container, pedido) {
        container.innerHTML = ''; // Limpiar botones existentes

        // Estilo base para todos los botones
        const baseStyle = {
            color: 'white',
            border: 'none',
            padding: '8px 12px',
            borderRadius: '20px',
            cursor: 'pointer',
            margin: '2px',
            display: 'inline-flex',
            alignItems: 'center',
            gap: '5px'
        };

        // Botón Visualizar (siempre presente)
        const verButton = document.createElement('button');
        verButton.innerHTML = '<i class="fas fa-eye"></i> Visualizar';
        verButton.className = 'btn-ver';
        verButton.dataset.action = 'ver';
        Object.assign(verButton.style, baseStyle, { backgroundColor: '#6c757d' });
        container.appendChild(verButton);

        if (pedido.area_id == areaRecepcion) {
            // Estado: Recepción -> Botones: Iniciar Proceso, Devolver
            const iniciarButton = document.createElement('button');
            iniciarButton.innerHTML = '<i class="fas fa-play"></i> Iniciar';
            iniciarButton.className = 'btn-iniciar';
            iniciarButton.dataset.action = 'mover';
            iniciarButton.dataset.area = areaEnProceso;
            Object.assign(iniciarButton.style, baseStyle, { backgroundColor: '#28a745' });
            container.appendChild(iniciarButton);

            const devolverButton = document.createElement('button');
            devolverButton.innerHTML = '<i class="fas fa-undo"></i> Devolver';
            devolverButton.className = 'btn-devolver';
            devolverButton.dataset.action = 'return';
            Object.assign(devolverButton.style, baseStyle, { backgroundColor: '#ffc107', color: 'black' });
            container.appendChild(devolverButton);

        } else if (pedido.area_id == areaEnProceso) {
            // Estado: En Proceso -> Botones: Marcar como Preparado, Devolver
            const preparadoButton = document.createElement('button');
            preparadoButton.innerHTML = '<i class="fas fa-check"></i> Preparado';
            preparadoButton.className = 'btn-preparado';
            preparadoButton.dataset.action = 'mover';
            preparadoButton.dataset.area = areaPreparado;
            Object.assign(preparadoButton.style, baseStyle, { backgroundColor: '#007bff' });
            container.appendChild(preparadoButton);

            const devolverButton = document.createElement('button');
            devolverButton.innerHTML = '<i class="fas fa-undo"></i> Devolver';
            devolverButton.className = 'btn-devolver';
            devolverButton.dataset.action = 'return';
            Object.assign(devolverButton.style, baseStyle, { backgroundColor: '#ffc107', color: 'black' });
            container.appendChild(devolverButton);

        } else if (pedido.area_id == areaPreparado) {
            // Estado: Preparado -> Botones: Finalizar Pedido, Enviar a Otro, Devolver
            const finalizarButton = document.createElement('button');
            finalizarButton.innerHTML = '<i class="fas fa-flag-checkered"></i> Finalizar';
            finalizarButton.className = 'btn-finalizar';
            finalizarButton.dataset.action = 'mover';
            finalizarButton.dataset.area = areaFinalizado;
            Object.assign(finalizarButton.style, baseStyle, { backgroundColor: '#dc3545' });
            container.appendChild(finalizarButton);



            const devolverButton = document.createElement('button');
            devolverButton.innerHTML = '<i class="fas fa-undo"></i> Devolver';
            devolverButton.className = 'btn-devolver';
            devolverButton.dataset.action = 'return';
            Object.assign(devolverButton.style, baseStyle, { backgroundColor: '#ffc107', color: 'black' });
            container.appendChild(devolverButton);
        }
    }

    function handleAction(e) {
        const button = e.target.closest('button');
        if (!button) return;

        const card = button.closest('.pedido-card');
        const pedidoId = card.dataset.id;
        const action = button.dataset.action;

        if (action === 'ver') {
            verPedido(pedidoId);
        } else if (action === 'mover') {
            const nuevaAreaId = button.dataset.area;
            moverPedido(pedidoId, nuevaAreaId);
        } else if (action === 'return') {
            showReturnModal(pedidoId);
        }
    }

    [recepcionCol, enProcesoCol, preparadoCol].forEach(col => {
        col.addEventListener('click', handleAction);
    });

    function moverPedido(pedidoId, nuevaAreaId, estadoId = null, mensaje = '') {
        // Verificar si es el área finalizado (ID 10)
        const confirmMessage = nuevaAreaId == areaFinalizado ? 
            "¿Estás seguro de finalizar este pedido? Se notificará al cliente según la configuración de SMS." : 
            "¿Estás seguro de mover este pedido?";
            
        if (!confirm(confirmMessage)) {
            return; // Si el usuario cancela, no hacer nada
        }
        
        const formData = new FormData();
        formData.append('id', pedidoId);
        formData.append('area_id', nuevaAreaId);
        
        // Determinar el estado_id basado en el área de destino si no se proporciona
        if (estadoId === null) {
            if (nuevaAreaId == areaRecepcion) { // 17 - Recepción de Control de Calidad
                estadoId = 1; // Recepción
            } else if (nuevaAreaId == areaEnProceso) { // 18 - En Proceso de Control de Calidad
                estadoId = 2; // En Proceso
            } else if (nuevaAreaId == areaPreparado) { // 19 - Preparado de Control de Calidad
                estadoId = 3; // Preparado
            } else if (nuevaAreaId == areaPreparadoConfeccion) { // 6 - Preparado de Confección
                estadoId = 3; // Preparado
            } else if (nuevaAreaId == areaFinalizado) { // 10 - Finalizado
                estadoId = 4; // Finalizado
            }
        }
        
        formData.append('estado_id', estadoId);
        
        if (mensaje) {
            // Estandarizar nombre de parámetro esperado por backend
            formData.append('mensaje_devolucion', mensaje);
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
                
                // Si el pedido se está moviendo al área de finalizado, enviar SMS de notificación
                if (nuevaAreaId == areaFinalizado) {
                    // Obtener información del pedido para el SMS
                    fetch(`php/get_pedido.php?id=${pedidoId}`)
                        .then(response => response.json())
                        .then(pedidoData => {
                            const ok = pedidoData && pedidoData.success && pedidoData.pedido;
                            const telefono = ok ? (pedidoData.pedido.numero_cliente || '') : '';
                            const numeroGuia = ok ? (pedidoData.pedido.numero_guia || '') : '';

                            if (telefono) {
                                // Enviar SMS notificando que el pedido está listo para recoger
                                const smsData = {
                                    pedido_id: pedidoId,
                                    numero_telefono: telefono
                                };

                                fetch('php/send_pedido_sms.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify(smsData)
                                })
                                .then(response => response.json())
                                .then(smsResponse => {
                                    if (smsResponse.success) {
                                        mostrarNotificacion('SMS de notificación enviado al cliente', 'success');
                                    } else {
                                        console.error('Error al enviar SMS:', smsResponse && (smsResponse.message || smsResponse.error || smsResponse.details));
                                        const detalle = (() => {
                                            try {
                                                return smsResponse && (smsResponse.message || smsResponse.error || smsResponse.details || JSON.stringify(smsResponse));
                                            } catch (e) {
                                                return 'Error desconocido';
                                            }
                                        })();
                                        mostrarNotificacion('Error al enviar SMS: ' + detalle, 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error en la petición de SMS:', error);
                                    const detalle = (error && (error.stack || error.message)) ? (error.stack || error.message) : String(error);
                                    mostrarNotificacion('Error al enviar SMS: ' + detalle, 'error');
                                });
                            } else {
                                console.warn('No se pudo enviar SMS: Teléfono no disponible');
                            }
                        })
                        .catch(error => {
                            console.error('Error al obtener datos del pedido para SMS:', error);
                        });
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

    function verPedido(pedidoId) {
        fetch(`php/get_pedido.php?id=${pedidoId}`)
            .then(response => response.json())
            .then(data => {
                if(data.success){
                    const viewModal = document.getElementById('view-modal');
                    const pedidoDetailsContainer = document.getElementById('pedido-details');
                    // Separar archivos originales y nuevos
                    let adjuntosOriginalesHtml = '';
                    let adjuntosNuevosHtml = '';
                    
                    if (data.pedido.adjuntos && data.pedido.adjuntos.length > 0) {
                        const archivosOriginales = [];
                        const archivosNuevos = [];
                        
                        data.pedido.adjuntos.forEach(adjunto => {
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
                                const iconClass = getIconForExtension(fileExtension);
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
                                const iconClass = getIconForExtension(fileExtension);
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
                        '<div class="archivos-section"><p>No hay archivos adjuntos en este pedido.</p></div>';

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

                        ${adjuntosHtml}
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
            background-color: rgba(0,0,0,0.7);
        `;
        
        modal.innerHTML = `
            <div style="
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
                <div style="
                    width: 40px;
                    height: 40px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #007bff;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 15px;
                "></div>
                <p style="margin: 0; color: #333; font-weight: 500;">Cargando historial...</p>
            </div>
        `;
        
        return modal;
    }

    // Función auxiliar para mostrar mensaje informativo
    function mostrarMensajeInfo(mensaje) {
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.style.display = 'block';
        
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 400px; text-align: center;">
                <span class="close-button" onclick="this.closest('.modal').remove()">&times;</span>
                <div style="padding: 20px;">
                    <i class="fas fa-info-circle" style="font-size: 48px; color: #17a2b8; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 15px; color: #333;">Información</h3>
                    <p style="color: #666; line-height: 1.5;">${mensaje}</p>
                    <button onclick="this.closest('.modal').remove()" style="
                        background: #17a2b8;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 5px;
                        cursor: pointer;
                        margin-top: 15px;
                    ">Cerrar</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Cerrar al hacer clic fuera del modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
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
                        padding-bottom: 8px;
                        border-bottom: 1px solid #eee;
                    ">
                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <strong style="color: #007bff; font-size: 14px;">
                                <i class="fas fa-user"></i> ${usuario}
                            </strong>
                            <span style="color: #6c757d; font-size: 12px;">
                                <i class="fas fa-building"></i> ${area}
                            </span>
                        </div>
                        <span style="color: #6c757d; font-size: 12px;">
                            <i class="fas fa-clock"></i> ${fecha}
                        </span>
                    </div>
                    <div class="mensaje-texto" style="
                        color: #333;
                        line-height: 1.5;
                        font-size: 14px;
                        background: #fff;
                        padding: 10px;
                        border-radius: 4px;
                        border-left: 3px solid #dc3545;
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
        modal.style.display = 'block';
        
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 700px; max-height: 80vh; overflow: hidden;">
                <span class="close-button" onclick="this.closest('.modal').remove()">&times;</span>
                ${historialHtml}
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Cerrar al hacer clic fuera del modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    // Función para mostrar historial de devoluciones - Implementación robusta
    window.mostrarHistorialDevoluciones = function(pedidoId) {
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
    };

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
    

});