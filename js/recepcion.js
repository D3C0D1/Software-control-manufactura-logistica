document.addEventListener('DOMContentLoaded', function() {
    // --- Overlay de carga global ---
    function getOrCreateLoadingOverlay() {
        let overlay = document.getElementById('global-loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'global-loading-overlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-content">
                    <div class="spinner"></div>
                    <p class="loading-text">Cargando pedidos…</p>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        return overlay;
    }
    function showLoadingOverlay() {
        const overlay = getOrCreateLoadingOverlay();
        overlay.classList.add('visible');
    }
    function hideLoadingOverlay() {
        const overlay = getOrCreateLoadingOverlay();
        overlay.classList.remove('visible');
    }
    // Loader de sección para contenedores específicos
    function ensureSectionLoader(containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) return null;
        let loader = container.querySelector('.section-loader');
        if (!loader) {
            loader = document.createElement('div');
            loader.className = 'section-loader';
            loader.style.display = 'none';
            loader.style.alignItems = 'center';
            loader.style.justifyContent = 'center';
            loader.style.gap = '8px';
            loader.style.padding = '8px';
            loader.style.margin = '8px 0';
            loader.style.border = '1px dashed #ddd';
            loader.style.borderRadius = '8px';
            loader.style.color = '#555';
            loader.style.background = '#f9fafb';
            loader.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span style="margin-left:6px;">Cargando…</span>';
            container.prepend(loader);
        }
        return loader;
    }
    function showSectionLoading(containerSelector) {
        const loader = ensureSectionLoader(containerSelector);
        if (loader) loader.style.display = 'flex';
    }
    function hideSectionLoading(containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) return;
        const loader = container.querySelector('.section-loader');
        if (loader) loader.style.display = 'none';
    }
    
    // Utilidades de búsqueda: normalización de acentos y debounce
    function normalizeText(str) {
        try {
            return (str || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        } catch (e) {
            // Fallback simple si normalize no está disponible
            return (str || '').toLowerCase();
        }
    }
    function debounce(fn, delay = 250) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }
    
    // --- Normalización estrictamente consecutiva de N° Pedido ---
    let ID_SEQ_MAP = null;
    let TOTAL_PEDIDOS = null;
    let MIN_PEDIDO_ID = null; // Fallback si mapa no disponible
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
        // Intentar obtener min_id como fallback
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
    // Precargar mapa para minimizar parpadeos
    ensureIdSeqMap();
    const createOrderModal = document.getElementById('create-order-modal');
    const createOrderBtn = document.getElementById('create-order-btn');
    const createOrderForm = document.getElementById('create-order-form');
    const editOrderForm = document.getElementById('edit-order-form');
    const pedidosContainer = document.getElementById('pedidos-list');
    const pedidosProcesoContainer = document.getElementById('pedidos-proceso-list');
    const pedidosFinalizadosContainer = document.getElementById('pedidos-finalizados-list');
    const editModal = document.getElementById('edit-modal');
    const viewModal = document.getElementById('view-modal');
    const closeButtons = document.querySelectorAll('.close-button');

    // Helper para formatear fechas de forma robusta en navegadores que no aceptan
    // el formato MySQL "YYYY-MM-DD HH:mm:ss" (por ejemplo, Safari).
    function formatDateES(input) {
        if (!input) return 'No disponible';

        if (input instanceof Date) {
            return isNaN(input.getTime()) ? 'No disponible' : input.toLocaleString('es-ES');
        }

        const s = String(input).trim();
        // Si viene en formato "YYYY-MM-DD HH:mm:ss", convertir el espacio por 'T' para ISO
        let candidate = s;
        if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/.test(s)) {
            candidate = s.replace(' ', 'T');
        }

        let d = new Date(candidate);
        if (isNaN(d.getTime())) {
            // Parse manual como fecha local si sigue sin ser válido
            const m = s.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/);
            if (m) {
                d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), Number(m[4]), Number(m[5]), Number(m[6]));
            }
        }

        return isNaN(d.getTime()) ? s : d.toLocaleString('es-ES');
    }

    // --- MANEJO DE MODALES ---

    // Abrir modal de creación
    if (createOrderBtn) {
        createOrderBtn.addEventListener('click', () => {
            createOrderModal.style.display = 'block';
        });
    }

    // Cerrar modales
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
        // Cerrar modal de envío al hacer clic fuera
        if (event.target == document.getElementById('send-area-modal')) {
            document.getElementById('send-area-modal').style.display = 'none';
        }
    });

    // --- MANEJO DE BÚSQUEDA EN TIEMPO REAL ---
    const searchRecientesInput = document.getElementById('search-recientes-input');
    const searchProcesoInput = document.getElementById('search-proceso-input');
    const searchFinalizadosInput = document.getElementById('search-finalizados-input');

    function setSearchCountForContainer(containerSelector, searchTerm) {
        const map = {
            '#pedidos-list': 'recientes-search-count',
            '#pedidos-proceso-list': 'proceso-search-count',
            '#pedidos-finalizados-list': 'finalizados-search-count'
        };
        const badgeId = map[containerSelector];
        if (!badgeId) return;
        const visible = Array.from(document.querySelectorAll(`${containerSelector} .pedido-card`)).filter(el => el.style.display !== 'none').length;
        const badge = document.getElementById(badgeId);
        if (!badge) return;
        // Ocultar permanentemente el badge de búsqueda
        badge.textContent = '';
        badge.style.display = 'none';
    }
    function setupSearch(searchInput, containerSelector) {
        if (!searchInput) return;
        const handler = debounce(function() {
            const term = searchInput.value.trim();
            let promise;
            if (containerSelector === '#pedidos-list') {
                promise = (term.length >= 2) ? buscarPedidosRecientes(term) : cargarPedidos();
            } else if (containerSelector === '#pedidos-proceso-list') {
                promise = (term.length >= 2) ? buscarPedidosEnProceso(term) : cargarPedidosEnProceso();
            } else if (containerSelector === '#pedidos-finalizados-list') {
                promise = (term.length >= 2) ? buscarPedidosFinalizados(term) : cargarPedidosFinalizados();
            }
            Promise.resolve(promise)
                .catch(() => {
                    // Ignorar errores de red y continuar con filtrado local
                })
                .finally(() => {
                    applyFilter(containerSelector, term);
                    setSearchCountForContainer(containerSelector, term);
                });
        }, 250);
        searchInput.addEventListener('input', handler);
    }

    function buscarPedidosRecientes(term) {
        ensureIdSeqMap();
        showLoadingOverlay();
        const q = encodeURIComponent(term);
        return fetch(`php/get_pedidos.php?area_id=20&estado_id=1&q=${q}&limit=500&urgentes_first=true&ts=${Date.now()}` , { cache: 'no-store', credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                pedidosContainer.innerHTML = '';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(pedido => {
                        const pedidoCard = document.createElement('div');
                        pedidoCard.className = 'pedido-card';
                        if (pedido.prioridad === 'alta') {
                            pedidoCard.classList.add('prioridad-alta');
                        } else if (pedido.prioridad === 'largo') {
                            pedidoCard.classList.add('prioridad-baja');
                        }
                        pedidoCard.dataset.id = pedido.id;
                        pedidoCard.dataset.nombre = pedido.nombre_cliente;
                        pedidoCard.dataset.guia = pedido.numero_guia;
                        pedidoCard.dataset.movil = pedido.numero_cliente;
                        pedidoCard.dataset.email = pedido.correo_cliente;
                        pedidoCard.dataset.notas = pedido.notas || '';
                        pedidoCard.dataset.prioridad = pedido.prioridad || 'normal';
                        pedidoCard.dataset.tieneDevoluciones = pedido.tiene_devoluciones || '0';
                        const devolucionIndicator = pedido.tiene_devoluciones == 1 ? 
                            '<span class="devolucion-indicator devuelto" title="Pedido devuelto"><i class="fas fa-undo-alt"></i></span>' : 
                            '<span class="devolucion-indicator no-devuelto" title="Sin devoluciones"><i class="fas fa-check-circle"></i></span>';
                        const smsIndicator = '';
                        const _idNum = parseInt(pedido.id, 10);
                        const numeroPublico = isNaN(_idNum) ? pedido.id : (_idNum >= 73 ? (_idNum - 72) : _idNum);
                        pedidoCard.innerHTML = `
                            <div class="pedido-header">
                                <h3>Pedido</h3>
                                <div class="indicators">
                                    ${devolucionIndicator}
                                    ${smsIndicator}
                                </div>
                            </div>
                            <p><strong>Guía:</strong> ${pedido.numero_guia}</p>
                            <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                            <p><strong>Email:</strong> ${pedido.correo_cliente}</p>
                            <p class="estado-line"><strong>Estado:</strong> Guia generada <span class="estado-anim guia-generada" title="Recepción"><i class="fas fa-inbox"></i></span></p>
                            <p><strong>Fecha:</strong> ${formatDateES(pedido.fecha_creacion)}</p>
                            <div class="pedido-actions">
                                <button class="btn-view" data-id="${pedido.id}" style="background-color: #6c757d; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-eye"></i> Visualizar</button>
                                <button class="btn-edit" data-id="${pedido.id}" style="background-color: #ffc107; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-edit"></i> Editar</button>
                                <button class="btn-delete" data-id="${pedido.id}" style="background-color: #dc3545; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-trash"></i> Eliminar</button>
                                <button class="btn-send-to-messaging" data-id="${pedido.id}" style="background-color: #007bff; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-paper-plane"></i> Enviar</button>
                            </div>
                        `;
                        pedidosContainer.appendChild(pedidoCard);
                    });
                } else {
                    pedidosContainer.innerHTML = '';
                }
                hideLoadingOverlay();
            })
            .catch(error => { console.error('Error al buscar pedidos recientes:', error); hideLoadingOverlay(); });
    }

    function buscarPedidosFinalizados(term) {
        showLoadingOverlay();
        showSectionLoading('#pedidos-finalizados-list');
        const minDelayFinalizados = new Promise(resolve => setTimeout(resolve, 3000));
        const q = encodeURIComponent(term);
        return fetch(`php/get_pedidos.php?area_id=10&q=${q}&limit=500&urgentes_first=true&ts=${Date.now()}` , { cache: 'no-store', credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                pedidosFinalizadosContainer.innerHTML = '';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(pedido => {
                        const card = document.createElement('div');
                        card.className = 'pedido-card';
                        card.dataset.id = pedido.id;
                        card.dataset.nombre = pedido.nombre_cliente;
                        card.dataset.guia = pedido.numero_guia;
                        card.dataset.movil = pedido.numero_cliente;
                        card.dataset.email = pedido.correo_cliente;
                        card.dataset.prioridad = pedido.prioridad || 'normal';
                        card.dataset.tieneDevoluciones = pedido.tiene_devoluciones || '0';
                        card.dataset.notas = pedido.notas || '';

                        let fechaFinalizacionHTML = '';
                        if (pedido.fecha_finalizacion && !isNaN(new Date(pedido.fecha_finalizacion))) {
                            fechaFinalizacionHTML = `<p><strong>Finalizado:</strong> ${formatDateES(pedido.fecha_finalizacion)}</p>`;
                        }

                        const devolucionIndicator = pedido.tiene_devoluciones == 1 ? 
                            '<span class="devolucion-indicator devuelto" title="Pedido devuelto"><i class="fas fa-undo-alt"></i></span>' : 
                            '<span class="devolucion-indicator no-devuelto" title="Sin devoluciones"><i class="fas fa-check-circle"></i></span>';

                        const numeroPublico3 = pedido.id;

                        card.innerHTML = `
                            <div class="pedido-card-header">
                                <div class="pedido-header">
                                    <span><strong>Pedido:</strong> #${numeroPublico3}</span>
                                    <span><strong>Guía:</strong> ${pedido.numero_guia}</span>
                                    ${devolucionIndicator}
                                </div>
                            </div>
                            <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                            ${fechaFinalizacionHTML}
                            <div class="pedido-card-actions">
                                <button class="btn-view-finalizado" data-id="${pedido.id}" style="background-color: #007bff; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-eye"></i> Ver</button>
                            </div>
                        `;
                        pedidosFinalizadosContainer.appendChild(card);
                    });
                } else {
                    pedidosFinalizadosContainer.innerHTML = '<p>No hay pedidos finalizados.</p>';
                }
            })
            .catch(error => { console.error('Error al buscar pedidos finalizados:', error); })
            .finally(() => { hideLoadingOverlay(); minDelayFinalizados.then(() => hideSectionLoading('#pedidos-finalizados-list')); });
    }

    function buscarPedidosEnProceso(term) {
        ensureIdSeqMap();
        showLoadingOverlay();
        showSectionLoading('#pedidos-proceso-list');
        const minDelay = new Promise(resolve => setTimeout(resolve, 3000));
        const q = encodeURIComponent(term);
        return fetch(`php/get_pedidos.php?area_id_not_in=10,20&estado_id=1,2&q=${q}&limit=500&urgentes_first=true&ts=${Date.now()}`, { cache: 'no-store', credentials: 'same-origin' })
            .then(response => response.json())
            .then(data => {
                pedidosProcesoContainer.innerHTML = '';
                const enProceso = data.filter(p => p.area_id != 20 && p.area_id != 10 && (p.estado_id === 1 || p.estado_id === 2));
                if (enProceso.length > 0) {
                    enProceso.forEach(pedido => {
                        const pedidoCard = document.createElement('div');
                        pedidoCard.className = 'pedido-card';
                        if (pedido.prioridad === 'alta') {
                            pedidoCard.classList.add('prioridad-alta');
                        } else if (pedido.prioridad === 'largo') {
                            pedidoCard.classList.add('prioridad-baja');
                        }
                        pedidoCard.dataset.id = pedido.id;
                        pedidoCard.dataset.nombre = pedido.nombre_cliente;
                        pedidoCard.dataset.guia = pedido.numero_guia;
                        pedidoCard.dataset.movil = pedido.numero_cliente;
                        pedidoCard.dataset.email = pedido.correo_cliente;
                        pedidoCard.dataset.prioridad = pedido.prioridad || '';
                        pedidoCard.dataset.tieneDevoluciones = (pedido.tiene_devoluciones ? '1' : '0');
                        pedidoCard.dataset.notas = pedido.notas || '';
                        // Crear indicador de devolución
                        const devolucionIndicator = pedido.tiene_devoluciones == 1 ? 
                            '<span class="devolucion-indicator devuelto" title="Pedido devuelto"><i class="fas fa-undo-alt"></i></span>' : 
                            '<span class="devolucion-indicator no-devuelto" title="Sin devoluciones"><i class="fas fa-check-circle"></i></span>';

                        // SMS indicator temporalmente deshabilitado
                        const smsIndicator = '';

                        // Calcular número público (arranca en 1 para el primer pedido real)
                        const _idNum = parseInt(pedido.id, 10);
                        const numeroPublico = isNaN(_idNum) ? pedido.id : (_idNum >= 73 ? (_idNum - 72) : _idNum);

                        pedidoCard.innerHTML = `
                            <div class="pedido-header">
                                <h3>Pedido en Proceso</h3>
                                ${devolucionIndicator}
                            </div>
                            <p><strong>Guía:</strong> ${pedido.numero_guia}</p>
                            <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                            <p><strong>Estado:</strong> ${pedido.area_nombre}</p>
                            <p><strong>Fecha:</strong> ${formatDateES(pedido.fecha_creacion)}</p>
                            <div class="pedido-actions">
                                <button class="btn-view" data-id="${pedido.id}" style="background-color: #6c757d; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-eye"></i> Visualizar</button>
                            </div>
                        `;
                        pedidosProcesoContainer.appendChild(pedidoCard);
                    });
                }
                hideLoadingOverlay();
            })
            .catch(error => { console.error('Error al cargar los pedidos:', error); hideLoadingOverlay(); })
            .finally(() => { minDelay.then(() => hideSectionLoading('#pedidos-proceso-list')); });
    }

    function applyFilter(containerSelector, searchTerm) {
        const term = normalizeText(searchTerm).trim();
        const pedidos = document.querySelectorAll(`${containerSelector} .pedido-card`);
        pedidos.forEach(pedido => {
            const nombre = normalizeText(pedido.dataset.nombre || '');
            const guia = normalizeText(pedido.dataset.guia || '');
            const movil = normalizeText(pedido.dataset.movil || '');
            const email = normalizeText(pedido.dataset.email || '');
            const pedidoId = normalizeText(pedido.dataset.id || '');
            const notas = normalizeText(pedido.dataset.notas || '');
            const prioridad = normalizeText(pedido.dataset.prioridad || '');
            const tieneDevoluciones = normalizeText(pedido.dataset.tieneDevoluciones || '');
            let isVisible = false;
            if (term === 'urgente') {
                isVisible = prioridad === 'alta';
            } else if (term === 'devolucion') {
                isVisible = tieneDevoluciones === '1' || tieneDevoluciones === 'true';
            } else {
                isVisible = nombre.includes(term) || guia.includes(term) || movil.includes(term) || email.includes(term) || pedidoId.includes(term) || notas.includes(term);
            }
            pedido.style.display = isVisible ? '' : 'none';
        });
    }

    setupSearch(searchRecientesInput, '#pedidos-list');
    setupSearch(searchProcesoInput, '#pedidos-proceso-list');
    setupSearch(searchFinalizadosInput, '#pedidos-finalizados-list');

    // Función para cargar estadísticas
    function cargarEstadisticas() {
        fetch('php/get_pedidos_stats.php', { credentials: 'same-origin' })
            .then(async (response) => {
                const raw = await response.text();
                let data = null;
                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    console.error('Error al cargar estadísticas:', e);
                    console.debug('Respuesta cruda:', raw);
                    return;
                }
                return data;
            })
            .then(data => {
                if (!data || data.error) {
                    console.error('Error al cargar estadísticas:', data && data.error ? data.error : 'Respuesta inválida');
                    // Fallback inmediato para 'Total'
                    const totalEl = document.getElementById('total-count');
                    if (totalEl) {
                        fetch('php/get_pedidos_bounds.php', { credentials: 'same-origin' })
                            .then(r => r.json())
                            .then(bounds => {
                                if (bounds && bounds.success && typeof bounds.total === 'number') {
                                    totalEl.textContent = bounds.total || 0;
                                }
                            })
                            .catch(err => console.warn('Fallback total failed:', err));
                    }
                    return;
                }

                // Actualizar contadores
                const totalEl = document.getElementById('total-count');
                if (totalEl) totalEl.textContent = data.total || 0;
                const procesoEl = document.getElementById('proceso-count');
                if (procesoEl) procesoEl.textContent = data.pedidos_proceso || 0;
                const eliminadosEl = document.getElementById('eliminados-count');
                if (eliminadosEl) eliminadosEl.textContent = data.pedidos_eliminados || 0;
                const finalizadosEl = document.getElementById('finalizados-count');
                if (finalizadosEl) finalizadosEl.textContent = data.finalizados || 0;
                const recepcionEl = document.getElementById('recepcion-count');
                if (recepcionEl) recepcionEl.textContent = data.guias_generadas || 0;

                // Actualizar badge de número total de pedidos en la campana
                try {
                    const totalEl2 = document.getElementById('total-count');
                    const totalOrders = parseInt(totalEl2 ? totalEl2.textContent : (data.total || 0), 10) || 0;
                    if (window.notificationSystem && typeof notificationSystem.updateOrdersBadge === 'function') {
                        notificationSystem.updateOrdersBadge(totalOrders);
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar el badge de pedidos (recepción):', e);
                }

                // Fallback: si 'total' no viene o es inválido, consultar bounds
                if (totalEl && (typeof data.total !== 'number' || isNaN(data.total))) {
                    fetch('php/get_pedidos_bounds.php', { credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(bounds => {
                            if (bounds && bounds.success && typeof bounds.total === 'number') {
                                totalEl.textContent = bounds.total || 0;
                            }
                        })
                        .catch(err => console.warn('Fallback total failed:', err));
                }
            })
            .catch(error => {
                console.error('Error al cargar estadísticas:', error);
            });
    }

    // Actualizar estadísticas cada 30 segundos (controlado)
    let statsTimer = setInterval(cargarEstadisticas, 30000);

    // Carga inicial y polling sensible a visibilidad
    cargarPedidos();
    cargarPedidosEnProceso();
    cargarPedidosFinalizados();
    cargarEstadisticas();
    let recepTimer = null;
    function startRecepPolling() {
        if (recepTimer) return;
        recepTimer = setInterval(() => {
            const searchRecientes = searchRecientesInput ? searchRecientesInput.value : '';
            const searchProceso = searchProcesoInput ? searchProcesoInput.value : '';
            const searchFinalizados = searchFinalizadosInput ? searchFinalizadosInput.value : '';
            cargarPedidos().then(() => {
                if (searchRecientes) applyFilter('#pedidos-list', searchRecientes);
            });
            cargarPedidosEnProceso().then(() => {
                if (searchProceso) applyFilter('#pedidos-proceso-list', searchProceso);
            });
            cargarPedidosFinalizados().then(() => {
                if (searchFinalizados) applyFilter('#pedidos-finalizados-list', searchFinalizados);
            });
        }, 10000);
    }
    function stopRecepPolling() {
        if (recepTimer) {
            clearInterval(recepTimer);
            recepTimer = null;
        }
        if (statsTimer) {
            clearInterval(statsTimer);
            statsTimer = null;
        }
    }
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopRecepPolling();
        } else {
            startRecepPolling();
            if (!statsTimer) statsTimer = setInterval(cargarEstadisticas, 30000);
        }
    });
    window.addEventListener('beforeunload', stopRecepPolling);
    startRecepPolling();

    // --- MANEJADORES DE EVENTOS GLOBALES ---

    // Crear Pedido
    if (createOrderForm) {
        createOrderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(createOrderForm);
            fetch('php/create_pedido.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('generate-sound').play();
                    alert('Pedido creado exitosamente. Guía: ' + data.guia);
                    createOrderForm.reset();
                    createOrderModal.style.display = 'none';
                    Promise.all([
                        cargarPedidos(),
                        cargarPedidosEnProceso(),
                        cargarPedidosFinalizados()
                    ]).finally(() => cargarEstadisticas());
                } else {
                    alert('Error al crear el pedido: ' + data.message);
                }
            })
            .catch(error => console.error('Error en la solicitud fetch:', error));
        });
    }

    // Editar Pedido (Listener único)
    if (editOrderForm) {
        editOrderForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Crea un nuevo FormData directamente desde el formulario en el momento del envío.
            // Esto evita añadir archivos duplicados que pudieran existir en un objeto FormData persistente.
            const formData = new FormData(editOrderForm);
            
            // Adjuntar los IDs de los adjuntos a eliminar
            const adjuntosAEliminar = [];
            document.querySelectorAll('#current-attachments .delete-attachment-checkbox:checked').forEach(checkbox => {
                adjuntosAEliminar.push(checkbox.value);
            });
            // Es importante usar 'append' aquí para añadir datos que no son campos de formulario estándar.
            formData.append('delete_adjuntos', JSON.stringify(adjuntosAEliminar));

            fetch('php/update_pedido.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pedido actualizado exitosamente.');
                    editModal.style.display = 'none';
                    cargarPedidos();
                } else {
                    alert('Error al actualizar el pedido: ' + data.message);
                }
            })
            .catch(error => console.error('Error en la solicitud fetch:', error));
        });
    }

    // Cerrar modales
    closeButtons.forEach(button => button.addEventListener('click', () => {
        editModal.style.display = 'none';
        viewModal.style.display = 'none';
    }));

    window.addEventListener('click', (event) => {
        if (event.target == editModal) editModal.style.display = 'none';
        if (event.target == viewModal) viewModal.style.display = 'none';
    });

    // Búsqueda
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.pedido-card').forEach(pedido => {
                const nombre = pedido.dataset.nombre.toLowerCase();
                const guia = pedido.dataset.guia.toLowerCase();
                pedido.style.display = (nombre.includes(searchTerm) || guia.includes(searchTerm)) ? 'block' : 'none';
            });
        });
    }

    // --- DELEGACIÓN DE EVENTOS PARA ACCIONES DE PEDIDOS ---
    if (pedidosContainer) {
        pedidosContainer.addEventListener('click', handlePedidoActions);
    }
    if (pedidosProcesoContainer) {
        pedidosProcesoContainer.addEventListener('click', handlePedidoActions);
    }
    if (pedidosFinalizadosContainer) {
        pedidosFinalizadosContainer.addEventListener('click', handlePedidoActions);
    }

    function handlePedidoActions(e) {
        const target = e.target.closest('button');
        if (!target) return;

        // Resolver ID de forma robusta desde botón o tarjeta
        const card = target.closest('.pedido-card');
        const candidates = [
            target.dataset?.id,
            card?.dataset?.id,
            target.getAttribute('data-id'),
            card ? card.getAttribute('data-id') : null
        ].filter(v => v !== undefined && v !== null && String(v).trim() !== '');
        const id = candidates.length ? candidates[0] : null;

        if (target.classList.contains('btn-view') || target.classList.contains('btn-view-finalizado')) {
            handleView(id);
        } else if (target.classList.contains('btn-edit')) {
            handleEdit(id);
        } else if (target.classList.contains('btn-delete') || target.classList.contains('btn-delete-finalizado')) {
            handleDelete(id);
        } else if (target.classList.contains('btn-send-to-messaging')) {
            mostrarModalSeleccionArea(id);
        }
    }

    // Crear modal de selección de área
    crearModalSeleccionArea();

    // --- FUNCIONES DE MANEJO DE ACCIONES ---

    function handleView(id) {
        fetch(`php/get_pedido.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pedido = data.pedido;
                    // Mapear el valor de prioridad a un texto legible
                    let prioridadTexto = 'Normal (3 días)'; // Valor por defecto
                    if (pedido.prioridad === 'alta') {
                        prioridadTexto = 'Prioridad (1 día)';
                    } else if (pedido.prioridad === 'largo') {
                        prioridadTexto = 'Largo (1 semana o más)';
                    }

                    let adjuntosHtml = '<h4>Archivos Adjuntos:</h4>';
                    if (pedido.adjuntos && pedido.adjuntos.length > 0) {
                        adjuntosHtml += '<ul>';
                        pedido.adjuntos.forEach(adjunto => {
                            const fileName = adjunto.ruta_archivo.split('/').pop();
                            adjuntosHtml += `<li><a href="${adjunto.ruta_archivo}" target="_blank">${fileName}</a></li>`;
                        });
                        adjuntosHtml += '</ul>';
                    } else {
                        adjuntosHtml += '<p>No hay archivos adjuntos.</p>';
                    }
                    document.getElementById('pedido-details').innerHTML = `
                        <p><strong>Guía:</strong> ${pedido.numero_guia}</p>
                        <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                        <p><strong>Email:</strong> ${pedido.correo_cliente}</p>
                        <p><strong>Móvil:</strong> ${pedido.numero_cliente}</p>
                        <p><strong>Duración:</strong> ${prioridadTexto}</p>
                        <p><strong>Notas:</strong> ${pedido.notas}</p>
                        ${adjuntosHtml}
                    `;
                    viewModal.style.display = 'block';
                } else {
                    alert(data.message);
                }
            });
    }

    function handleEdit(id) {
        fetch(`php/get_pedido.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pedido = data.pedido;
                    document.getElementById('edit-pedido-id').value = pedido.id;
                    document.getElementById('edit-nombre-completo').value = pedido.nombre_cliente;
                    document.getElementById('edit-email').value = pedido.correo_cliente;
                    document.getElementById('edit-movil').value = pedido.numero_cliente;
                    document.getElementById('edit-notas').value = pedido.notas;

                    const attachmentsContainer = document.getElementById('current-attachments');
                    attachmentsContainer.innerHTML = '';
                    if (pedido.adjuntos && pedido.adjuntos.length > 0) {
                        let adjuntosHtml = '<ul>';
                        pedido.adjuntos.forEach(adjunto => {
                            const fileName = adjunto.ruta_archivo.split('/').pop();
                            adjuntosHtml += `<li>
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="delete-attachment-checkbox" value="${adjunto.id}">
                                    <span class="checkmark"></span>
                                </label>
                                <a href="${adjunto.ruta_archivo}" target="_blank">${fileName}</a>
                            </li>`;
                        });
                        adjuntosHtml += '</ul>';
                        attachmentsContainer.innerHTML = adjuntosHtml;
                    } else {
                        attachmentsContainer.innerHTML = '<p>No hay archivos adjuntos.</p>';
                    }

                    document.getElementById('edit-adjuntos').value = '';
                    editModal.style.display = 'block';
                } else {
                    alert(data.message);
                }
            });
    }

    function handleDelete(id) {
        const idStr = (id !== undefined && id !== null) ? String(id).trim() : '';
        if (!idStr) {
            alert('No se pudo identificar el pedido.');
            return;
        }
        if (confirm('¿Estás seguro de que quieres eliminar este pedido? Esta acción no se puede deshacer.')) {
            fetch('php/eliminar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pedido_id: parseInt(idStr, 10) || idStr }),
                credentials: 'include',
                cache: 'no-store'
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    throw new Error('La respuesta del servidor no es JSON válido');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Pedido eliminado correctamente.');
                    Promise.all([
                        cargarPedidos(),
                        cargarPedidosEnProceso(),
                        cargarPedidosFinalizados()
                    ]).finally(() => cargarEstadisticas());
                } else {
                    alert('Error al eliminar el pedido: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al eliminar el pedido: ' + error.message);
            });
        }
    }

    function crearModalSeleccionArea() {
        const modalHTML = `
            <div id="send-area-modal" class="send-area-modal">
                <div class="send-area-modal-content">
                    <div class="send-area-modal-header">
                        <h3><i class="fas fa-paper-plane"></i> Enviar Pedido</h3>
                        <span class="send-area-close">&times;</span>
                    </div>
                    <div class="send-area-modal-body">
                        <div class="send-area-form-group">
                            <label for="area-select">Seleccionar área de destino:</label>
                            <select id="area-select" class="send-area-select">
                                <option value="">-- Seleccionar área --</option>
                                <option value="11">Recepción de Mensajería</option>
                                <option value="1">Recepción de Diseño</option>
                                <option value="14">Recepción de Impresión</option>
                                <option value="7">Recepción de Sublimado</option>
                                <option value="4">Recepción de Confección</option>
                                <option value="17">Recepción de Control de Calidad</option>
                            </select>
                        </div>
                    </div>
                    <div class="send-area-modal-footer">
                        <button type="button" class="send-area-btn send-area-btn-cancel">Cancelar</button>
                        <button type="button" class="send-area-btn send-area-btn-submit" disabled>Enviar</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Event listeners para el modal
        const modal = document.getElementById('send-area-modal');
        const closeBtn = document.querySelector('.send-area-close');
        const cancelBtn = document.querySelector('.send-area-btn-cancel');
        const submitBtn = document.querySelector('.send-area-btn-submit');
        const areaSelect = document.getElementById('area-select');
        
        // Cerrar modal
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            limpiarFormularioEnvio();
        });
        
        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            limpiarFormularioEnvio();
        });
        
        // Validar selección
        areaSelect.addEventListener('change', () => {
            submitBtn.disabled = !areaSelect.value;
        });
        
        // Enviar pedido
        submitBtn.addEventListener('click', () => {
            enviarPedidoAArea();
        });
    }

    function mostrarModalSeleccionArea(pedidoId) {
        const modal = document.getElementById('send-area-modal');
        modal.dataset.pedidoId = pedidoId;
        modal.style.display = 'block';
    }

    function limpiarFormularioEnvio() {
        document.getElementById('area-select').value = '';
        document.querySelector('.send-area-btn-submit').disabled = true;
    }

    function enviarPedidoAArea() {
        const modal = document.getElementById('send-area-modal');
        const pedidoId = modal.dataset.pedidoId;
        const areaId = document.getElementById('area-select').value;
        
        if (!areaId) {
            mostrarNotificacion('Por favor seleccione un área', 'error');
            return;
        }
        
        const submitBtn = document.querySelector('.send-area-btn-submit');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';
        
        const formData = new FormData();
        formData.append('id', pedidoId);
        formData.append('area_id', areaId);
        formData.append('estado_id', 2); // Estado "En proceso"
        
        fetch('php/update_pedido_area.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('Pedido enviado exitosamente', 'success');
                modal.style.display = 'none';
                limpiarFormularioEnvio();
                cargarPedidos(); // Recargar la lista de pedidos
                cargarPedidosEnProceso();
            } else {
                mostrarNotificacion('Error al enviar el pedido: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('Error de conexión', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Enviar';
        });
    }

    function mostrarNotificacion(mensaje, tipo) {
        // Notificación sin texto: icono animado en la parte superior
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

    // Exponer la notificación de forma global para otros scripts inline
    window.mostrarNotificacion = mostrarNotificacion;

    // --- FUNCIONES DE CARGA DE DATOS ---

    function cargarPedidos() {
        // Aseguramos mapa en paralelo (no bloqueante)
        ensureIdSeqMap();
        showLoadingOverlay();
        return fetch(`php/get_pedidos.php?area_id=20&estado_id=1&limit=30&urgentes_first=true&ts=${Date.now()}` , { cache: 'no-store', credentials: 'same-origin' }) // Recepción activa (guía generada), sin eliminados
            .then(response => response.json())
            .then(data => {
                pedidosContainer.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(pedido => {
                        const pedidoCard = document.createElement('div');
                        pedidoCard.className = 'pedido-card';
                        // Asegurarse de que la clase se aplica correctamente
                        if (pedido.prioridad === 'alta') {
                            pedidoCard.classList.add('prioridad-alta');
                        } else if (pedido.prioridad === 'largo') {
                            pedidoCard.classList.add('prioridad-baja');
                        }

                        pedidoCard.dataset.id = pedido.id;
                        pedidoCard.dataset.nombre = pedido.nombre_cliente;
                        pedidoCard.dataset.guia = pedido.numero_guia;
                        pedidoCard.dataset.movil = pedido.numero_cliente;
                        pedidoCard.dataset.email = pedido.correo_cliente;
                        pedidoCard.dataset.notas = pedido.notas || '';
                        pedidoCard.dataset.prioridad = pedido.prioridad || 'normal';
                        pedidoCard.dataset.tieneDevoluciones = pedido.tiene_devoluciones || '0';
                        // Crear indicador de devolución
                        const devolucionIndicator = pedido.tiene_devoluciones == 1 ? 
                            '<span class="devolucion-indicator devuelto" title="Pedido devuelto"><i class="fas fa-undo-alt"></i></span>' : 
                            '<span class="devolucion-indicator no-devuelto" title="Sin devoluciones"><i class="fas fa-check-circle"></i></span>';

                        // SMS indicator temporalmente deshabilitado
                        const smsIndicator = '';

                        // Calcular número público (arranca en 1 para el primer pedido real)
                        const _idNum = parseInt(pedido.id, 10);
                        const numeroPublico = isNaN(_idNum) ? pedido.id : (_idNum >= 73 ? (_idNum - 72) : _idNum);

                        pedidoCard.innerHTML = `
                            <div class="pedido-header">
                                <h3>Pedido</h3>
                                <div class="indicators">
                                    ${devolucionIndicator}
                                    ${smsIndicator}
                                </div>
                            </div>
                            <p><strong>Guía:</strong> ${pedido.numero_guia}</p>
                            <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                            <p><strong>Email:</strong> ${pedido.correo_cliente}</p>
                            <p class="estado-line"><strong>Estado:</strong> Guia generada <span class="estado-anim guia-generada" title="Recepción"><i class="fas fa-inbox"></i></span></p>
                            <p><strong>Fecha:</strong> ${formatDateES(pedido.fecha_creacion)}</p>
                            <div class="pedido-actions">
                                <button class="btn-view" data-id="${pedido.id}" style="background-color: #6c757d; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-eye"></i> Visualizar</button>
                                <button class="btn-edit" data-id="${pedido.id}" style="background-color: #ffc107; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-edit"></i> Editar</button>
                                <button class="btn-delete" data-id="${pedido.id}" style="background-color: #dc3545; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-trash"></i> Eliminar</button>
                                <button class="btn-send-to-messaging" data-id="${pedido.id}" style="background-color: #007bff; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-paper-plane"></i> Enviar</button>
                            </div>
                        `;
                        pedidosContainer.appendChild(pedidoCard);
                    });
                }
                hideLoadingOverlay();
            })
            .catch(error => { console.error('Error al cargar los pedidos:', error); hideLoadingOverlay(); });
    }

    function cargarPedidosFinalizados() {
        showLoadingOverlay();
        return fetch(`php/get_pedidos.php?area_id=10&limit=30&urgentes_first=true&ts=${Date.now()}` , { cache: 'no-store', credentials: 'same-origin' }) // Pedimos solo los Finalizados, sin caché
            .then(response => response.json())
            .then(data => {
                pedidosFinalizadosContainer.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(pedido => {
                        const card = document.createElement('div');
                        card.className = 'pedido-card';
                        card.dataset.id = pedido.id;
                        card.dataset.nombre = pedido.nombre_cliente;
                        card.dataset.guia = pedido.numero_guia;
                        card.dataset.movil = pedido.numero_cliente;
                        card.dataset.email = pedido.correo_cliente;
                        card.dataset.prioridad = pedido.prioridad || 'normal';
                        card.dataset.tieneDevoluciones = pedido.tiene_devoluciones || '0';
                        card.dataset.notas = pedido.notas || '';

                        let fechaFinalizacionHTML = '';
                        if (pedido.fecha_finalizacion && !isNaN(new Date(pedido.fecha_finalizacion))) {
                            fechaFinalizacionHTML = `<p><strong>Finalizado:</strong> ${formatDateES(pedido.fecha_finalizacion)}</p>`;
                        }

                        // Crear indicador de devolución
                        const devolucionIndicator = pedido.tiene_devoluciones == 1 ? 
                            '<span class="devolucion-indicator devuelto" title="Pedido devuelto"><i class="fas fa-undo-alt"></i></span>' : 
                            '<span class="devolucion-indicator no-devuelto" title="Sin devoluciones"><i class="fas fa-check-circle"></i></span>';

                        // Revertido: mostrar ID real
                        const numeroPublico3 = pedido.id;

                        card.innerHTML = `
                            <div class="pedido-card-header">
                                <div class="pedido-header">
                                    <span><strong>Pedido:</strong> #${numeroPublico3}</span>
                                    <span><strong>Guía:</strong> ${pedido.numero_guia}</span>
                                    ${devolucionIndicator}
                                </div>
                            </div>
                            <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                            ${fechaFinalizacionHTML}
                            <div class="pedido-card-actions">
                                <button class="btn-view-finalizado" data-id="${pedido.id}" style="background-color: #007bff; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-eye"></i> Ver</button>
                            </div>
                        `;
                        pedidosFinalizadosContainer.appendChild(card);
                    });
                } else {
                    pedidosFinalizadosContainer.innerHTML = '<p>No hay pedidos finalizados.</p>';
                }
                hideLoadingOverlay();
            })
            .catch(error => { console.error('Error al cargar pedidos finalizados:', error); hideLoadingOverlay(); });
    }

    function cargarPedidosEnProceso() {
        // Aseguramos mapa en paralelo (no bloqueante)
        ensureIdSeqMap();
        showLoadingOverlay();
        showSectionLoading('#pedidos-proceso-list');
        const minDelayProceso = new Promise(resolve => setTimeout(resolve, 3000));
        return fetch(`php/get_pedidos.php?area_id_not_in=10,20&estado_id=1,2&urgentes_first=true&ts=${Date.now()}` , { cache: 'no-store', credentials: 'same-origin' }) // Excluimos Recepción y Finalizados, y filtramos estados 1/2
            .then(response => response.json())
            .then(data => {
                pedidosProcesoContainer.innerHTML = '';
                // Filtramos en el cliente para excluir Recepción (area_id 20) y Finalizados (area_id 10)
                const enProceso = data.filter(p => p.area_id != 20 && p.area_id != 10 && (p.estado_id === 1 || p.estado_id === 2));

                if (enProceso.length > 0) {
                    enProceso.forEach(pedido => {
                        const pedidoCard = document.createElement('div');
                        pedidoCard.className = 'pedido-card';
                        // Asegurarse de que la clase se aplica correctamente
                        if (pedido.prioridad === 'alta') {
                            pedidoCard.classList.add('prioridad-alta');
                        } else if (pedido.prioridad === 'largo') {
                            pedidoCard.classList.add('prioridad-baja');
                        }

                        pedidoCard.dataset.id = pedido.id;
                        pedidoCard.dataset.nombre = pedido.nombre_cliente;
                        pedidoCard.dataset.guia = pedido.numero_guia;
                        pedidoCard.dataset.movil = pedido.numero_cliente;
                        pedidoCard.dataset.email = pedido.correo_cliente;
                        pedidoCard.dataset.notas = pedido.notas || '';
                        pedidoCard.dataset.prioridad = pedido.prioridad || 'normal';
                        pedidoCard.dataset.tieneDevoluciones = pedido.tiene_devoluciones || '0';
                        // Crear indicador de devolución
                        const devolucionIndicator = pedido.tiene_devoluciones == 1 ? 
                            '<span class="devolucion-indicator devuelto" title="Pedido devuelto"><i class="fas fa-undo-alt"></i></span>' : 
                            '<span class="devolucion-indicator no-devuelto" title="Sin devoluciones"><i class="fas fa-check-circle"></i></span>';

                        // Calcular número con la regla original (IDs >=73 se muestran como id-72)
                        const _idNum2 = parseInt(pedido.id, 10);
                        const numeroPublico2 = isNaN(_idNum2) ? pedido.id : (_idNum2 >= 73 ? (_idNum2 - 72) : _idNum2);

                        pedidoCard.innerHTML = `
                            <div class="pedido-header">
                                <h3>Pedido en Proceso</h3>
                                ${devolucionIndicator}
                            </div>
                            <p><strong>Guía:</strong> ${pedido.numero_guia}</p>
                            <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                            <p><strong>Estado:</strong> ${pedido.area_nombre}</p>
                            <p><strong>Fecha:</strong> ${formatDateES(pedido.fecha_creacion)}</p>
                            <div class="pedido-actions">
                                <button class="btn-view" data-id="${pedido.id}" style="background-color: #6c757d; color: white; border: none; border-radius: 20px; padding: 10px 15px; cursor: pointer;"><i class="fas fa-eye"></i> Visualizar</button>
                            </div>
                        `;
                        pedidosProcesoContainer.appendChild(pedidoCard);
                    });
                } else {
                    pedidosProcesoContainer.innerHTML = '<p>No hay pedidos en proceso.</p>';
                }
            })
            .catch(error => { console.error('Error al cargar los pedidos en proceso:', error); })
            .finally(() => { hideLoadingOverlay(); minDelayProceso.then(() => hideSectionLoading('#pedidos-proceso-list')); });
    }

    // --- FUNCIÓN PARA VER DETALLES DEL PEDIDO ---
    function verPedido(pedidoId) {
        fetch(`php/get_pedido.php?id=${pedidoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pedidoDetailsContainer = document.getElementById('pedido-details');
                    
                    // Crear HTML para adjuntos con iconos, separando archivos viejos y nuevos
                    let adjuntosHtml = '';
                    if (data.pedido.adjuntos && data.pedido.adjuntos.length > 0) {
                        // Separar archivos en viejos y nuevos
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
                        
                        // HTML para archivos originales
                        let adjuntosOriginalesHtml = '';
                        if (archivosOriginales.length > 0) {
                            adjuntosOriginalesHtml = `
                                <div class="archivos-section">
                                    <h4><i class="fas fa-file"></i> Archivos Originales <span class="badge badge-original">Original</span></h4>
                                    <div class="adjuntos-grid">
                            `;
                            archivosOriginales.forEach(adjunto => {
                                const fileName = adjunto.ruta_archivo.split('/').pop();
                                const fileExtension = fileName.split('.').pop().toLowerCase();
                                const iconClass = getIconForExtension(fileExtension);
                                adjuntosOriginalesHtml += `
                                    <div class="adjunto-item">
                                        <i class="${iconClass} file-icon"></i>
                                        <a href="${adjunto.ruta_archivo}" target="_blank" class="file-link">
                                            ${fileName}
                                        </a>
                                    </div>
                                `;
                            });
                            adjuntosOriginalesHtml += `
                                    </div>
                                </div>
                            `;
                        }
                        
                        // HTML para archivos nuevos
                        let adjuntosNuevosHtml = '';
                        if (archivosNuevos.length > 0) {
                            adjuntosNuevosHtml = `
                                <div class="archivos-section">
                                    <h4><i class="fas fa-file-plus"></i> Archivos Nuevos <span class="badge badge-nuevo">Nuevo</span></h4>
                                    <div class="adjuntos-grid">
                            `;
                            archivosNuevos.forEach(adjunto => {
                                const fileName = adjunto.ruta_archivo.split('/').pop();
                                // Mostrar nombre sin el prefijo "_nuevo_"
                                const displayName = fileName.replace('_nuevo_', '');
                                const fileExtension = fileName.split('.').pop().toLowerCase();
                                const iconClass = getIconForExtension(fileExtension);
                                adjuntosNuevosHtml += `
                                    <div class="adjunto-item">
                                        <i class="${iconClass} file-icon"></i>
                                        <a href="${adjunto.ruta_archivo}" target="_blank" class="file-link">
                                            ${displayName}
                                        </a>
                                    </div>
                                `;
                            });
                            adjuntosNuevosHtml += `
                                    </div>
                                </div>
                            `;
                        }
                        
                        adjuntosHtml = `
                            <div class="pedido-section">
                                <h3><i class="fas fa-paperclip"></i> Archivos Adjuntos</h3>
                                ${adjuntosOriginalesHtml}
                                ${adjuntosNuevosHtml}
                            </div>
                        `;
                    }
                    
                    // Indicador de devolución
                    const indicadorDevolucion = data.pedido.tiene_devoluciones == 1 ? 
                        '<div class="return-indicator-modal"><i class="fas fa-exclamation-triangle"></i> Este pedido tiene devoluciones</div>' : '';
                    
                    // Botón de historial si tiene devoluciones
                    const botonHistorial = data.pedido.tiene_devoluciones == 1 ? 
                        `<button class="btn-historial" onclick="mostrarHistorialDevoluciones(${pedidoId})"><i class="fas fa-history"></i> Ver Historial de Devoluciones</button>` : '';
                    
                    pedidoDetailsContainer.innerHTML = `
                        ${indicadorDevolucion}
                        <div class="pedido-info-grid">
                            <div class="pedido-section">
                                <h3><i class="fas fa-info-circle"></i> Información General</h3>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-barcode"></i> Guía:</span>
                                    <span class="info-value">${data.pedido.numero_guia}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-calendar-plus"></i> Fecha de Creación:</span>
                                    <span class="info-value">${formatDateES(data.pedido.fecha_creacion)}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-clock"></i> Prioridad:</span>
                                    <span class="info-value">${data.pedido.prioridad}</span>
                                </div>
                            </div>
                            
                            <div class="pedido-section">
                                <h3><i class="fas fa-user"></i> Información del Cliente</h3>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-user-circle"></i> Nombre:</span>
                                    <span class="info-value">${data.pedido.nombre_cliente}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-envelope"></i> Email:</span>
                                    <span class="info-value">${data.pedido.correo_cliente}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label"><i class="fas fa-phone"></i> Móvil:</span>
                                    <span class="info-value">${data.pedido.numero_cliente}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pedido-section">
                            <h3><i class="fas fa-sticky-note"></i> Notas del Pedido</h3>
                            <div class="notas-content">
                                ${data.pedido.notas || 'Sin notas adicionales'}
                            </div>
                        </div>
                        
                        ${adjuntosHtml}
                        
                        <div class="modal-actions">
                            ${botonHistorial}
                        </div>
                    `;
                    viewModal.style.display = 'block';
                } else {
                    alert('Error al cargar los detalles del pedido: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error al cargar pedido:', error);
                alert('Error al cargar los detalles del pedido');
            });
    }
    
    // Función para obtener icono según extensión de archivo
    function getIconForExtension(extension) {
        const iconMap = {
            // Imágenes
            'jpg': 'fas fa-image',
            'jpeg': 'fas fa-image',
            'png': 'fas fa-image',
            'gif': 'fas fa-image',
            'bmp': 'fas fa-image',
            'svg': 'fas fa-image',
            'webp': 'fas fa-image',
            // Documentos
            'pdf': 'fas fa-file-pdf',
            'doc': 'fas fa-file-word',
            'docx': 'fas fa-file-word',
            'xls': 'fas fa-file-excel',
            'xlsx': 'fas fa-file-excel',
            'ppt': 'fas fa-file-powerpoint',
            'pptx': 'fas fa-file-powerpoint',
            'txt': 'fas fa-file-alt',
            // Archivos comprimidos
            'zip': 'fas fa-file-archive',
            'rar': 'fas fa-file-archive',
            '7z': 'fas fa-file-archive',
            // Videos
            'mp4': 'fas fa-file-video',
            'avi': 'fas fa-file-video',
            'mov': 'fas fa-file-video',
            'wmv': 'fas fa-file-video',
            // Audio
            'mp3': 'fas fa-file-audio',
            'wav': 'fas fa-file-audio',
            'flac': 'fas fa-file-audio',
            // Código
            'html': 'fas fa-file-code',
            'css': 'fas fa-file-code',
            'js': 'fas fa-file-code',
            'php': 'fas fa-file-code',
            'py': 'fas fa-file-code'
        };
        return iconMap[extension] || 'fas fa-file';
    }
    
    // Función para obtener icono de prioridad
    function getPriorityIcon(prioridad) {
        switch(prioridad) {
            case 'alta': return '<i class="fas fa-exclamation-triangle"></i>';
            case 'normal': return '<i class="fas fa-clock"></i>';
            case 'baja': return '<i class="fas fa-calendar-alt"></i>';
            default: return '<i class="fas fa-question-circle"></i>';
        }
    }
    
    // Función para obtener texto de prioridad
    function getPriorityText(prioridad) {
        switch(prioridad) {
            case 'alta': return 'Prioridad (1 día)';
            case 'normal': return 'Normal (3 días)';
            case 'baja': return 'Largo (1 semana o más)';
            default: return 'No especificada';
        }
    }
    
    // Event listeners para botones de ver pedido
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-view') || e.target.closest('.btn-view-finalizado')) {
            const pedidoId = e.target.closest('button').dataset.id;
            verPedido(pedidoId);
        }
    });

    // Agregar estilos de animación
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
    `;
    document.head.appendChild(style);
    
    // Hacer la función verPedido global
    window.verPedido = verPedido;
});
