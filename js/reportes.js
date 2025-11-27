document.addEventListener('DOMContentLoaded', function() {
    // --- Normalización estrictamente consecutiva de N° Pedido ---
    let ID_SEQ_MAP = null;
    let TOTAL_PEDIDOS = null;
    let MIN_PEDIDO_ID = null; // Fallback
    async function ensureIdSeqMap() {
        try {
            if (ID_SEQ_MAP && typeof TOTAL_PEDIDOS === 'number') return ID_SEQ_MAP;
            const res = await fetch('php/get_pedidos_index_map.php', { credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.success && data.map) {
                ID_SEQ_MAP = data.map;
                TOTAL_PEDIDOS = data.total;
                return ID_SEQ_MAP;
            }
        } catch (e) {
            console.warn('No se pudo obtener mapa de secuencia:', e);
        }
        if (typeof MIN_PEDIDO_ID !== 'number') {
            try {
                const res2 = await fetch('php/get_pedidos_bounds.php', { credentials: 'same-origin' });
                const data2 = await res2.json();
                if (data2 && typeof data2.min_id === 'number') MIN_PEDIDO_ID = data2.min_id;
            } catch (e2) {
                console.warn('Fallback min_id también falló:', e2);
            }
        }
        return ID_SEQ_MAP;
    }
    function toPublicId(id) {
        const n = parseInt(id, 10);
        if (isNaN(n)) return id;
        if (ID_SEQ_MAP && ID_SEQ_MAP[n]) return ID_SEQ_MAP[n];
        if (typeof MIN_PEDIDO_ID === 'number' && !isNaN(MIN_PEDIDO_ID)) {
            return (n - MIN_PEDIDO_ID + 1);
        }
        return n >= 73 ? (n - 72) : n;
    }
    ensureIdSeqMap();
    const enProcesoList = document.getElementById('pedidos-en-proceso-list');
    const terminadosList = document.getElementById('pedidos-terminados-list');
    const historialModal = document.getElementById('historial-modal');
    const closeModalButton = historialModal.querySelector('.close-button');
    const historialDetails = document.getElementById('historial-details');
    const historialGuia = document.getElementById('historial-guia');
    const searchProcesoInput = document.getElementById('search-proceso-input');
    const searchTerminadosInput = document.getElementById('search-terminados-input');

    let allPedidos = { en_proceso: [], terminados: [] };

    function formatDuration(minutes) {
        if (minutes === null || isNaN(minutes)) {
            return 'En proceso';
        }
        if (minutes < 60) {
            return `${minutes} min`;
        }
        const hours = Math.floor(minutes / 60);
        const mins = Math.round(minutes % 60);
        return `${hours}h ${mins}min`;
    }

    function renderPedidos(pedidos, container, tipo) {
        container.innerHTML = '';
        if (pedidos.length === 0) {
            container.innerHTML = `<p>No hay pedidos ${tipo}.</p>`;
            return;
        }

        pedidos.forEach(pedido => {
            const card = document.createElement('div');
            card.className = 'pedido-card';
            card.dataset.id = pedido.id;

            // Revertido: mostrar ID real del pedido
            const numeroPublico = pedido.id;

            let content = `
                <div class="pedido-card-header">
                    <strong>Pedido:</strong> #${numeroPublico} &nbsp; <strong>Guía:</strong> ${pedido.numero_guia}
                </div>
                <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
            `;

            if (tipo === 'en proceso') {
                content += `<p><strong>Área Actual:</strong> ${pedido.area_actual}</p>`;
            } else {
                content += `
                    <p><strong>Finalizado:</strong> ${new Date(pedido.fecha_finalizacion).toLocaleString()}</p>
                    <p><strong>Finalizado por:</strong> <span class="finalizado-por">${pedido.finalizado_por}</span></p>
                `;
            }

            content += `
                <div class="pedido-card-actions">
                    <button class="btn-view-historial" data-id="${pedido.id}" data-guia="${pedido.numero_guia}">Ver Historial</button>
                    <button class="btn-delete" data-id="${pedido.id}">Eliminar</button>
                </div>
            `;

            card.innerHTML = content;
            container.appendChild(card);
        });
    }

    function cargarReportes() {
        fetch('php/get_reporte_pedidos.php')
            .then(response => response.json())
            .then(data => {
                allPedidos = data;
                renderPedidos(data.en_proceso, enProcesoList, 'en proceso');
                renderPedidos(data.terminados, terminadosList, 'terminados');
            })
            .catch(error => console.error('Error al cargar los reportes:', error));
    }

    function handleVerHistorial(e) {
        if (e.target.classList.contains('btn-view-historial')) {
            const pedidoId = e.target.dataset.id;
            const guia = e.target.dataset.guia;
            historialGuia.textContent = guia;

            fetch(`php/get_historial_pedido.php?id=${pedidoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        historialDetails.innerHTML = '';
                        
                        // Mostrar métricas del pedido
                        if (data.metricas) {
                            const metricas = data.metricas;
                            const metricasDiv = document.createElement('div');
                            metricasDiv.className = 'metricas-container';
                            metricasDiv.innerHTML = `
                                <div class="metricas-grid">
                                    <div class="metrica-card">
                                        <h4>📊 Resumen General</h4>
                                        <p><strong>Estado Actual:</strong> ${metricas.estado_actual}</p>
                                        <p><strong>Área Actual:</strong> ${metricas.area_actual}</p>
                                        <p><strong>Finalizado:</strong> ${metricas.esta_finalizado ? 'Sí' : 'No'}</p>
                                        ${metricas.fecha_finalizacion ? `<p><strong>Fecha Finalización:</strong> ${new Date(metricas.fecha_finalizacion).toLocaleString()}</p>` : ''}
                                    </div>
                                    
                                    <div class="metrica-card">
                                        <h4>⏱️ Tiempos</h4>
                                        <p><strong>Tiempo Total en Proceso:</strong> ${metricas.tiempo_total_formateado}</p>
                                        <p><strong>Tiempo desde Creación:</strong> ${metricas.tiempo_desde_creacion_formateado}</p>
                                        <p><strong>Tiempo Promedio por Área:</strong> ${formatDuration(metricas.tiempo_promedio_por_area)}</p>
                                        ${metricas.area_mas_lenta ? `<p><strong>Área más Lenta:</strong> ${metricas.area_mas_lenta} (${formatDuration(metricas.tiempo_area_mas_lenta)})</p>` : ''}
                                    </div>
                                    
                                    <div class="metrica-card">
                                        <h4>🏢 Recorrido</h4>
                                        <p><strong>Áreas Visitadas:</strong> ${metricas.total_areas_visitadas}</p>
                                        <p><strong>Ruta:</strong> ${metricas.areas_visitadas.join(' → ')}</p>
                                        ${metricas.fecha_inicio_proceso ? `<p><strong>Inicio Proceso:</strong> ${new Date(metricas.fecha_inicio_proceso).toLocaleString()}</p>` : ''}
                                    </div>
                                </div>
                            `;
                            historialDetails.appendChild(metricasDiv);
                        }
                        
                        // Mostrar tabla de historial detallado
                        if (data.historial.length > 0) {
                            const historialDiv = document.createElement('div');
                            historialDiv.innerHTML = '<h3>📋 Historial Detallado</h3>';
                            
                            const table = document.createElement('table');
                            table.className = 'historial-table';
                            table.innerHTML = `
                                <thead>
                                    <tr>
                                        <th>Área</th>
                                        <th>Fecha Entrada</th>
                                        <th>Fecha Salida</th>
                                        <th>Duración</th>
                                        <th>Usuario Entrada</th>
                                        <th>Usuario Salida</th>
                                        <th>Eficiencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.historial.map((h, index) => {
                                        const eficiencia = calcularEficiencia(h.duracion_minutos, h.area_id);
                                        return `
                                            <tr class="${h.area_id == 10 ? 'finalizado' : ''}">
                                                <td>
                                                    <span class="area-badge area-${h.area_id}">${h.nombre_area}</span>
                                                </td>
                                                <td>${new Date(h.fecha_entrada).toLocaleString()}</td>
                                                <td>${h.fecha_salida ? new Date(h.fecha_salida).toLocaleString() : '<span class="en-proceso">En proceso</span>'}</td>
                                                <td>
                                                    <span class="duracion ${getDuracionClass(h.duracion_minutos)}">${formatDuration(h.duracion_minutos)}</span>
                                                </td>
                                                <td>
                                                    <span class="usuario-badge">${h.usuario_entrada}</span>
                                                </td>
                                                <td>
                                                    <span class="usuario-badge">${h.usuario_salida}</span>
                                                </td>
                                                <td>
                                                    <span class="eficiencia ${eficiencia.class}">${eficiencia.texto}</span>
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            `;
                            historialDiv.appendChild(table);
                            historialDetails.appendChild(historialDiv);
                            
                            // Mostrar usuarios por área
                            if (data.metricas && data.metricas.usuarios_por_area) {
                                const usuariosDiv = document.createElement('div');
                                usuariosDiv.className = 'usuarios-por-area';
                                usuariosDiv.innerHTML = `
                                    <h3>👥 Usuarios por Área</h3>
                                    <div class="usuarios-grid">
                                        ${Object.entries(data.metricas.usuarios_por_area).map(([area, usuarios]) => `
                                            <div class="usuario-area-card">
                                                <h4>${area}</h4>
                                                <p><strong>Entrada:</strong> ${usuarios.usuario_entrada}</p>
                                                <p><strong>Salida:</strong> ${usuarios.usuario_salida}</p>
                                            </div>
                                        `).join('')}
                                    </div>
                                `;
                                historialDetails.appendChild(usuariosDiv);
                            }
                        } else {
                            historialDetails.innerHTML += '<p>No hay historial disponible para este pedido.</p>';
                        }
                        historialModal.style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => console.error('Error al obtener el historial:', error));
        }
    }

    function calcularEficiencia(duracion, areaId) {
        // Tiempos esperados por área (en minutos)
        const tiemposEsperados = {
            20: 30,  // Recepción
            1: 120,  // Diseño
            2: 60,   // Sublimado
            3: 90,   // Confección
            4: 45,   // Preparado
            5: 30,   // Control de Calidad
            10: 0    // Finalizado
        };
        
        const tiempoEsperado = tiemposEsperados[areaId] || 60;
        
        if (areaId == 10) {
            return { class: 'finalizado', texto: 'Finalizado' };
        }
        
        if (duracion <= tiempoEsperado) {
            return { class: 'eficiente', texto: 'Eficiente' };
        } else if (duracion <= tiempoEsperado * 1.5) {
            return { class: 'normal', texto: 'Normal' };
        } else {
            return { class: 'lento', texto: 'Lento' };
        }
    }

    function getDuracionClass(duracion) {
        if (duracion <= 60) return 'rapido';
        if (duracion <= 180) return 'normal';
        return 'lento';
    }

    function handleDelete(e) {
        if (e.target.classList.contains('btn-delete')) {
            const pedidoId = e.target.dataset.id;
            if (confirm('¿Estás seguro de que deseas eliminar este pedido? Esta acción no se puede deshacer.')) {
                fetch('php/eliminar_pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: parseInt(pedidoId, 10) || pedidoId })
                })
                .then(response => {
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('La respuesta del servidor no es JSON válido');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Pedido eliminado exitosamente');
                        cargarReportes(); // Recargar la lista de pedidos
                    } else {
                        alert('Error al eliminar el pedido: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error al eliminar el pedido:', error);
                    alert('Ocurrió un error al eliminar el pedido: ' + error.message);
                });
            }
        }
    }

    function filterPedidos(searchTerm, list, pedidos) {
        const lowerCaseSearchTerm = searchTerm.toLowerCase();
        const filtered = pedidos.filter(p => 
            p.numero_guia.toLowerCase().includes(lowerCaseSearchTerm) || 
            p.nombre_cliente.toLowerCase().includes(lowerCaseSearchTerm)
        );
        renderPedidos(filtered, list, list === enProcesoList ? 'en proceso' : 'terminados');
    }

    closeModalButton.onclick = () => historialModal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == historialModal) {
            historialModal.style.display = 'none';
        }
    };

    document.body.addEventListener('click', function(e) {
        handleVerHistorial(e);
        handleDelete(e);
    });
    searchProcesoInput.addEventListener('input', (e) => filterPedidos(e.target.value, enProcesoList, allPedidos.en_proceso));
    searchTerminadosInput.addEventListener('input', (e) => filterPedidos(e.target.value, terminadosList, allPedidos.terminados));

    cargarReportes();
});