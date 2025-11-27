function mostrarDetallePedido(pedidoId) {
    fetch(`php/get_pedido_detalle.php?id=${pedidoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                crearModalDetalle(data.pedido);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles del pedido');
        });
}

function crearModalDetalle(pedido) {
    const modal = document.createElement('div');
    modal.className = 'modal-detalle-pedido';
    modal.innerHTML = `
        <div class="modal-content-detalle">
            <div class="modal-header">
                <h2>Detalles del Pedido ${pedido.numero_guia}</h2>
                <span class="close-modal" onclick="cerrarModalDetalle()">&times;</span>
            </div>
            
            <div class="modal-body">
                <div class="detalle-grid">
                    <div class="seccion-info">
                        <h3>Información General</h3>
                        <div class="info-item">
                            <label>Número de Guía:</label>
                            <span>${pedido.numero_guia}</span>
                        </div>
                        <div class="info-item">
                            <label>Cliente:</label>
                            <span>${pedido.nombre_cliente}</span>
                        </div>
                        <div class="info-item">
                            <label>Correo:</label>
                            <span>${pedido.correo_cliente}</span>
                        </div>
                        <div class="info-item">
                            <label>Teléfono:</label>
                            <span>${pedido.numero_cliente}</span>
                        </div>
                        <div class="info-item">
                            <label>Prioridad:</label>
                            <span class="prioridad-${pedido.prioridad}">${pedido.prioridad}</span>
                        </div>
                        <div class="info-item">
                            <label>Estado Actual:</label>
                            <span>${pedido.nombre_estado || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Área Actual:</label>
                            <span>${pedido.area_actual || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Creado por:</label>
                            <span>${pedido.creado_por_nombre || 'N/A'}</span>
                        </div>
                        <div class="info-item">
                            <label>Fecha de Creación:</label>
                            <span>${formatearFecha(pedido.fecha_creacion)}</span>
                        </div>
                        <div class="info-item">
                            <label>Última Actualización:</label>
                            <span>${formatearFecha(pedido.fecha_actualizacion)}</span>
                        </div>
                        ${pedido.tiempo_total_sistema ? `
                        <div class="info-item">
                            <label>Tiempo en Sistema:</label>
                            <span>${pedido.tiempo_total_sistema}</span>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="seccion-notas">
                        <h3>Notas del Pedido</h3>
                        <div class="notas-content">
                            ${pedido.notas || 'Sin notas adicionales'}
                        </div>
                    </div>
                    
                    <div class="seccion-archivos">
                        <h3>Archivos Adjuntos</h3>
                        <div class="archivos-list">
                            ${pedido.archivos_adjuntos.length > 0 ? 
                                pedido.archivos_adjuntos.map(archivo => `
                                    <div class="archivo-item">
                                        <i class="fas fa-file"></i>
                                        <a href="${archivo.ruta}" target="_blank">${archivo.nombre}</a>
                                    </div>
                                `).join('') : 
                                '<p>No hay archivos adjuntos</p>'
                            }
                        </div>
                    </div>
                    
                    <div class="seccion-historial">
                        <h3>Áreas Recorridas</h3>
                        <div class="historial-timeline">
                            ${pedido.historial_areas.map((area, index) => `
                                <div class="timeline-item ${area.fecha_salida ? 'completado' : 'en-proceso'}">
                                    <div class="timeline-marker">${index + 1}</div>
                                    <div class="timeline-content">
                                        <h4>${area.nombre_area}</h4>
                                        <div class="timeline-dates">
                                            <div class="fecha-entrada">
                                                <strong>Entrada:</strong> ${formatearFecha(area.fecha_entrada)}
                                            </div>
                                            ${area.fecha_salida ? `
                                                <div class="fecha-salida">
                                                    <strong>Salida:</strong> ${formatearFecha(area.fecha_salida)}
                                                </div>
                                                <div class="duracion">
                                                    <strong>Duración:</strong> ${area.duracion_formateada}
                                                </div>
                                            ` : `
                                                <div class="en-proceso">
                                                    <strong>Estado:</strong> En proceso
                                                </div>
                                            `}
                                        </div>
                                        ${area.usuario_entrada ? `
                                            <div class="usuario-info">
                                                <small>Procesado por: ${area.usuario_entrada}</small>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-cerrar" onclick="cerrarModalDetalle()">Cerrar</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'flex';
}

function cerrarModalDetalle() {
    const modal = document.querySelector('.modal-detalle-pedido');
    if (modal) {
        modal.remove();
    }
}

function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    const date = new Date(fecha);
    return date.toLocaleString('es-CO', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-detalle-pedido')) {
        cerrarModalDetalle();
    }
});