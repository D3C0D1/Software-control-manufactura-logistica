<?php
// Iniciar sesión antes de cualquier output
session_start();

// Verificar que la sesión esté iniciada
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Glamcity</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
 
    <link href="css/dashboard.css" rel="stylesheet">
    <link href="css/area_modal.css" rel="stylesheet">
    <link href="css/dashboard_simple.css" rel="stylesheet">
    <link href="css/dashboard_override.css" rel="stylesheet">
    <link href="css/staff_modals.css" rel="stylesheet">
    <style>
        /* Estilos mínimos para elementos específicos */
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
        }
        
        .refresh-btn:hover {
            background: #0056b3;
        }
        
        .last-update {
            position: fixed;
            bottom: 90px;
            right: 30px;
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            color: #6c757d;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'php/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'php/dashboard_header.php'; ?>
        
        <div class="metrics-container dashboard-container">
            <!-- Título del Dashboard -->
            <h1 class="dashboard-title">✨ Dashboard Glamcity</h1>
            <p class="dashboard-subtitle">Panel de Control Ejecutivo - Gestión Integral de Pedidos</p>
            
            <!-- Métricas Principales -->
            <div class="main-metrics">
                <h2 class="section-title">📊 Métricas Principales</h2>
                <div class="main-metrics-grid">
                    <div class="metric-card total">
                        <div class="metric-header">
                            <h3 class="metric-title">Total Pedidos</h3>
                            <div class="metric-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="metric-value" id="total-orders">0</div>
                        <div class="metric-label">Pedidos Totales</div>
                    </div>
                    
                    <div class="metric-card finished">
                        <div class="metric-header">
                            <h3 class="metric-title">Finalizados</h3>
                            <div class="metric-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="metric-value" id="finished-orders">0</div>
                        <div class="metric-label">Pedidos Completados</div>
                    </div>
                    

                    <div class="metric-card daily">
                        <div class="metric-header">
                            <h3 class="metric-title">Pedidos Hoy</h3>
                            <div class="metric-icon">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                        </div>
                        <div class="metric-value" id="daily-orders">0</div>
                        <div class="metric-label">Pedidos del Día</div>
                    </div>
                </div>
            </div>
            
            <!-- Métricas por Área -->
            <div class="area-metrics-section">
                <h2 class="section-title">🏭 Métricas por Área</h2>
                <div class="area-metrics-grid">
                    <div class="metric-card area-modal-trigger" data-area="diseno" data-area-name="Diseño">
                        <div class="metric-header">
                            <div class="metric-icon" style="background: linear-gradient(45deg, #9b59b6, #8e44ad);">
                                <i class="fas fa-paint-brush"></i>
                            </div>
                            <div class="metric-action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="count-diseno">0</div>
                        <div class="metric-label">Diseño</div>
                        <div class="metric-preview">
                            <div class="preview-item">
                                <span class="preview-label">Recepción:</span>
                                <span class="preview-number" id="diseno-recepcion">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Proceso:</span>
                                <span class="preview-number" id="diseno-proceso">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Preparado:</span>
                                <span class="preview-number" id="diseno-preparado">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="metric-card area-modal-trigger" data-area="confeccion" data-area-name="Confección">
                        <div class="metric-header">
                            <div class="metric-icon" style="background: linear-gradient(45deg, #e67e22, #d35400);">
                                <i class="fas fa-cut"></i>
                            </div>
                            <div class="metric-action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="count-confeccion">0</div>
                        <div class="metric-label">Confección</div>
                        <div class="metric-preview">
                            <div class="preview-item">
                                <span class="preview-label">Recepción:</span>
                                <span class="preview-number" id="confeccion-recepcion">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Proceso:</span>
                                <span class="preview-number" id="confeccion-proceso">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Preparado:</span>
                                <span class="preview-number" id="confeccion-preparado">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="metric-card area-modal-trigger" data-area="sublimado" data-area-name="Sublimado">
                        <div class="metric-header">
                            <div class="metric-icon" style="background: linear-gradient(45deg, #1abc9c, #16a085);">
                                <i class="fas fa-fire"></i>
                            </div>
                            <div class="metric-action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="count-sublimado">0</div>
                        <div class="metric-label">Sublimado</div>
                        <div class="metric-preview">
                            <div class="preview-item">
                                <span class="preview-label">Recepción:</span>
                                <span class="preview-number" id="sublimado-recepcion">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Proceso:</span>
                                <span class="preview-number" id="sublimado-proceso">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Preparado:</span>
                                <span class="preview-number" id="sublimado-preparado">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="metric-card area-modal-trigger" data-area="mensajeria" data-area-name="Mensajería">
                        <div class="metric-header">
                            <div class="metric-icon" style="background: linear-gradient(45deg, #3498db, #2980b9);">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="metric-action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="count-mensajeria">0</div>
                        <div class="metric-label">Mensajería</div>
                        <div class="metric-preview">
                            <div class="preview-item">
                                <span class="preview-label">Recepción:</span>
                                <span class="preview-number" id="mensajeria-recepcion">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Proceso:</span>
                                <span class="preview-number" id="mensajeria-proceso">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Preparado:</span>
                                <span class="preview-number" id="mensajeria-preparado">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="metric-card area-modal-trigger" data-area="impresion" data-area-name="Impresión">
                        <div class="metric-header">
                            <div class="metric-icon" style="background: linear-gradient(45deg, #e74c3c, #c0392b);">
                                <i class="fas fa-print"></i>
                            </div>
                            <div class="metric-action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="count-impresion">0</div>
                        <div class="metric-label">Impresión</div>
                        <div class="metric-preview">
                            <div class="preview-item">
                                <span class="preview-label">Recepción:</span>
                                <span class="preview-number" id="impresion-recepcion">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Proceso:</span>
                                <span class="preview-number" id="impresion-proceso">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Preparado:</span>
                                <span class="preview-number" id="impresion-preparado">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="metric-card area-modal-trigger" data-area="control_calidad" data-area-name="Control de Calidad">
                        <div class="metric-header">
                            <div class="metric-icon" style="background: linear-gradient(45deg, #f39c12, #e67e22);">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="metric-action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="count-control_calidad">0</div>
                        <div class="metric-label">Control de Calidad</div>
                        <div class="metric-preview">
                            <div class="preview-item">
                                <span class="preview-label">Recepción:</span>
                                <span class="preview-number" id="control_calidad-recepcion">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Proceso:</span>
                                <span class="preview-number" id="control_calidad-proceso">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Preparado:</span>
                                <span class="preview-number" id="control_calidad-preparado">0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="metric-card" data-area="recepcion" data-area-name="Recepción">
                        <div class="metric-header">
                            <div class="metric-icon" style="background: linear-gradient(45deg, #6c5ce7, #a29bfe);">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="metric-action-icon">
                                <i class="fas fa-external-link-alt"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="count-recepcion">0</div>
                        <div class="metric-label">Recepción</div>
                        <div class="metric-preview">
                            <div class="preview-item">
                                <span class="preview-label">Recepción:</span>
                                <span class="preview-number" id="recepcion-recepcion">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Proceso:</span>
                                <span class="preview-number" id="recepcion-proceso">0</span>
                            </div>
                            <div class="preview-item">
                                <span class="preview-label">Preparado:</span>
                                <span class="preview-number" id="recepcion-preparado">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Métricas de Empleados -->
            <div class="employee-metrics-section">
                <h2 class="section-title">👥 Métricas de Personal</h2>
                <div class="employee-metrics-grid">
                    <div class="metric-card">
                        <div class="metric-header">
                            <h3 class="metric-title">Empleados Activos</h3>
                            <div class="metric-icon" style="background: linear-gradient(45deg, #27ae60, #229954);">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="active-employees">0</div>
                        <div class="metric-label">Empleados Activos</div>
                    </div>
                    
                    <!-- Tarjeta Total Registrados ahora clicable -->
                    <div class="metric-card clickable" onclick="openRegisteredUsersModal()">
                        <div class="metric-header">
                            <h3 class="metric-title">Total Registrados</h3>
                            <div class="metric-icon" style="background: linear-gradient(45deg, #3498db, #2980b9);">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="registered-employees">0</div>
                        <div class="metric-label">Total Registrados</div>
                        <div class="view-details-btn">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </div>
                    </div>
                    
                    <div class="metric-card clickable" onclick="openUsersModal()">
                        <div class="metric-header">
                            <h3 class="metric-title">Usuarios Conectados</h3>
                            <div class="metric-icon" style="background: linear-gradient(45deg, #e74c3c, #c0392b);">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                        <div class="metric-number" id="logged-users">0</div>
                        <div class="metric-label">Usuarios Conectados</div>
                        <div class="view-details-btn">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </div>
                        <!-- Botón adicional para ver desconectados -->
                        <div class="view-details-btn" onclick="openDisconnectedUsersModal(); event.stopPropagation();">
                            <i class="fas fa-user-times"></i> Ver Desconectados
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botón de actualización manual -->
        <button class="refresh-btn" onclick="updateMetrics()" title="Actualizar métricas">
            <i class="fas fa-sync-alt"></i>
        </button>
        
        <!-- Indicador de última actualización -->
        <div class="last-update" id="last-update">
            Última actualización: --:--
        </div>
    </div>

    <!-- Modal de Usuarios Conectados -->
    <!-- Modal de Usuarios Conectados -->
    <div id="usersModal" class="staff-modal" aria-hidden="true">
        <div class="staff-modal-content">
            <div class="staff-modal-header">
                <h5><i class="fas fa-users-cog staff-icon"></i> Usuarios Conectados en Tiempo Real</h5>
                <button class="staff-modal-close" data-close="usersModal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="staff-modal-body">
                <div class="users-summary">
                    <h6><i class="fas fa-users staff-icon"></i> Total de usuarios conectados: <span id="modal-total-users">0</span></h6>
                </div>
                <div class="filter-bar">
                    <label for="filter-role-users">Rol:</label>
                    <select id="filter-role-users">
                        <option value="todos">Todos</option>
                        <option value="empleado">Empleado</option>
                        <option value="operador">Operador</option>
                        <option value="administrativo">Administrativo</option>
                    </select>
                    <label for="filter-area-users">Área:</label>
                    <select id="filter-area-users">
                        <option value="todas">Todas</option>
                        <option value="diseño">Diseño</option>
                        <option value="impresión">Impresión</option>
                        <option value="confección">Confección</option>
                        <option value="sublimado">Sublimado</option>
                        <option value="recepción">Recepción</option>
                        <option value="mensajería">Mensajería</option>
                    </select>
                </div>
                <div class="users-list" id="modal-users-list"></div>
            </div>
            <div class="staff-modal-footer">
                <div class="last-update-modal">
                    <i class="fas fa-clock staff-icon"></i> <span id="modal-last-update">Actualizando...</span>
                </div>
                <button type="button" class="btn btn-secondary staff-close-btn" data-close="usersModal">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Usuarios Registrados por Rol -->
    <div id="registeredUsersModal" class="staff-modal" aria-hidden="true">
        <div class="staff-modal-content">
            <div class="staff-modal-header">
                <h5><i class="fas fa-user-tag staff-icon"></i> Usuarios Registrados por Rol</h5>
                <button class="staff-modal-close" data-close="registeredUsersModal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="staff-modal-body">
                <div class="users-summary">
                    <h6><i class="fas fa-users staff-icon"></i> Total registrados: <span id="modal-registered-total">0</span></h6>
                </div>
                <div class="chips-row" id="registered-categories"></div>
                <div class="filter-bar">
                    <label for="filter-role-registered">Rol:</label>
                    <select id="filter-role-registered">
                        <option value="todos">Todos</option>
                        <option value="empleado">Empleado</option>
                        <option value="operador">Operador</option>
                        <option value="administrativo">Administrativo</option>
                    </select>
                    <label for="filter-area-registered">Área:</label>
                    <select id="filter-area-registered">
                        <option value="todas">Todas</option>
                        <option value="diseño">Diseño</option>
                        <option value="impresión">Impresión</option>
                        <option value="confección">Confección</option>
                        <option value="sublimado">Sublimado</option>
                        <option value="recepción">Recepción</option>
                        <option value="mensajería">Mensajería</option>
                    </select>
                </div>
                <div id="modal-registered-roles" class="users-list"></div>
                <hr/>
                <div id="modal-registered-list" class="users-list"></div>
            </div>
            <div class="staff-modal-footer">
                <div class="last-update-modal">
                    <i class="fas fa-clock staff-icon"></i> <span id="registered-last-update">Actualizando...</span>
                </div>
                <button type="button" class="btn btn-secondary staff-close-btn" data-close="registeredUsersModal">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Usuarios Desconectados -->
    <div id="disconnectedUsersModal" class="staff-modal" aria-hidden="true">
        <div class="staff-modal-content">
            <div class="staff-modal-header">
                <h5><i class="fas fa-user-slash staff-icon"></i> Usuarios desconectados recientemente</h5>
                <button class="staff-modal-close" data-close="disconnectedUsersModal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="staff-modal-body">
                <div class="users-summary">
                    <h6><i class="fas fa-user-times staff-icon"></i> Total desconectados: <span id="modal-disconnected-total">0</span></h6>
                </div>
                <div class="filter-bar">
                    <label for="filter-role-disconnected">Rol:</label>
                    <select id="filter-role-disconnected">
                        <option value="todos">Todos</option>
                        <option value="empleado">Empleado</option>
                        <option value="operador">Operador</option>
                        <option value="administrativo">Administrativo</option>
                    </select>
                    <label for="filter-area-disconnected">Área:</label>
                    <select id="filter-area-disconnected">
                        <option value="todas">Todas</option>
                        <option value="diseño">Diseño</option>
                        <option value="impresión">Impresión</option>
                        <option value="confección">Confección</option>
                        <option value="sublimado">Sublimado</option>
                        <option value="recepción">Recepción</option>
                        <option value="mensajería">Mensajería</option>
                    </select>
                </div>
                <div id="modal-disconnected-list" class="users-list"></div>
            </div>
            <div class="staff-modal-footer">
                <div class="last-update-modal">
                    <i class="fas fa-clock staff-icon"></i> <span id="disconnected-last-update">Actualizando...</span>
                </div>
                <button type="button" class="btn btn-secondary staff-close-btn" data-close="disconnectedUsersModal">Cerrar</button>
            </div>
        </div>
    </div>

    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        let updateInterval;
        let isTabVisible = true;
        
        // Detectar visibilidad de la pestaña
        document.addEventListener('visibilitychange', function() {
            isTabVisible = !document.hidden;
            if (isTabVisible) {
                updateMetrics();
                startAutoUpdate();
            } else {
                stopAutoUpdate();
            }
        });
        
        // Función para animar números
        function animateNumber(element, newValue) {
            const currentValue = parseInt(element.textContent) || 0;
            if (currentValue !== newValue) {
                element.classList.add('updating');
                element.textContent = newValue;
                setTimeout(() => {
                    element.classList.remove('updating');
                }, 500);
            }
        }
        
        // Función para actualizar métricas
        async function updateMetrics() {
            try {
                const response = await fetch('php/get_metrics.php');
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Datos recibidos:', data);
                
                // Actualizar métricas principales
                animateNumber(document.getElementById('total-orders'), data.total_orders || 0);
                animateNumber(document.getElementById('finished-orders'), data.finished_orders || 0);
                animateNumber(document.getElementById('daily-orders'), data.daily_orders || 0);
                
                // Actualizar métricas de empleados
                animateNumber(document.getElementById('active-employees'), data.active_employees || 0);
                animateNumber(document.getElementById('registered-employees'), data.registered_employees || 0);
                animateNumber(document.getElementById('logged-users'), data.logged_users || 0);
                
                // Actualizar métricas por área con desglose
                if (data.area_counts) {
                    Object.keys(data.area_counts).forEach(area => {
                        const areaData = data.area_counts[area];
                        
                        // Actualizar total del área
                        const totalElement = document.getElementById(`count-${area}`);
                        if (totalElement) {
                            animateNumber(totalElement, areaData.total || 0);
                        }
                        
                        // Actualizar desglose por estado
                        const recepcionElement = document.getElementById(`${area}-recepcion`);
                        const procesoElement = document.getElementById(`${area}-proceso`);
                        const preparadoElement = document.getElementById(`${area}-preparado`);
                        
                        if (recepcionElement) animateNumber(recepcionElement, areaData.recepcion || 0);
                        if (procesoElement) animateNumber(procesoElement, areaData.proceso || 0);
                        if (preparadoElement) animateNumber(preparadoElement, areaData.preparado || 0);
                    });
                }
                
                // Actualizar timestamp
                const now = new Date();
                const timeString = now.toLocaleTimeString('es-ES', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit'
                });
                document.getElementById('last-update').textContent = `Última actualización: ${timeString}`;
                
            } catch (error) {
                console.error('Error al actualizar métricas:', error);
                document.getElementById('last-update').textContent = 'Error en la conexión';
            }
        }
        
        // Función para iniciar actualización automática
        function startAutoUpdate() {
            stopAutoUpdate();
            updateInterval = setInterval(() => {
                if (isTabVisible) {
                    updateMetrics();
                }
            }, 30000); // Actualizar cada 30 segundos
        }
        
        // Función para detener actualización automática
        function stopAutoUpdate() {
            if (updateInterval) {
                clearInterval(updateInterval);
                updateInterval = null;
            }
        }
        
        // Inicializar dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar métricas iniciales
            updateMetrics();
            startAutoUpdate();
            
            // Inicializar efectos de entrada escalonados
            initStaggeredAnimations();
        });
        
        // Función para inicializar animaciones escalonadas
        function initStaggeredAnimations() {
            const cards = document.querySelectorAll('.metric-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
        
        // Utilidades para modales de personal (custom)
        function showStaffModal(modalEl) {
            if (!modalEl) return;
            modalEl.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function hideStaffModal(modalEl) {
            if (!modalEl) return;
            modalEl.classList.remove('show');
            document.body.style.overflow = '';
        }
        // Cierre por botón [data-close], click en overlay y tecla Escape
        document.addEventListener('click', (e) => {
            const closeId = e.target.getAttribute('data-close');
            if (closeId) {
                const el = document.getElementById(closeId);
                hideStaffModal(el);
            }
        });
        ['usersModal','registeredUsersModal','disconnectedUsersModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', (e) => { if (e.target === el) hideStaffModal(el); });
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                ['usersModal','registeredUsersModal','disconnectedUsersModal'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el && el.classList.contains('show')) hideStaffModal(el);
                });
            }
        });

        // Helpers de categorización y render de tarjetas
        let _registeredUsers = [];
        let _loggedUsers = [];
        let _offlineUsers = [];
        function normalizeStr(s) { return (s || '').toString().trim().toLowerCase(); }
        function getCategoryFromRole(rol) {
            const r = normalizeStr(rol);
            if (r.includes('admin') || r.includes('jefe') || r.includes('gerente')) return 'Administrativo';
            if (r.includes('operador')) return 'Operador';
            return 'Empleado';
        }
        function getRoleBadgeClass(category) {
            const c = normalizeStr(category);
            if (c === 'operador') return 'badge-role-operador';
            if (c === 'administrativo') return 'badge-role-administrativo';
            return 'badge-role-empleado';
        }
        function getAreaBadgeClass(area) {
            const a = normalizeStr(area);
            if (a.includes('dise')) return 'badge-area-diseno';
            if (a.includes('impre')) return 'badge-area-impresion';
            if (a.includes('confe')) return 'badge-area-confeccion';
            if (a.includes('subli')) return 'badge-area-sublimado';
            if (a.includes('rece')) return 'badge-area-recepcion';
            if (a.includes('mens')) return 'badge-area-mensajeria';
            return 'badge-area';
        }
        function renderUserCard(u, status) {
            const name = u.nombre_completo || u.nombre || `${u.nombres || ''} ${u.apellidos || ''}`.trim();
            const username = u.usuario || u.username || '';
            const role = u.rol || 'Sin rol';
            const area = u.area || u.area_empleado || 'Sin área';
            const category = getCategoryFromRole(role);
            const ago = u.last_activity ? getTimeAgo(u.last_activity) : (u.ultima_actividad ? getTimeAgo(u.ultima_actividad) : 'N/D');
            const statusClass = status === 'active' ? 'badge-status-active' : 'badge-status-inactive';
            const areaClass = getAreaBadgeClass(area);
            const roleClass = getRoleBadgeClass(category);
            return `
                <div class="user-card${status === 'inactive' ? ' user-stale' : ''}">
                    <div class="user-left">
                        <i class="fas fa-user staff-icon"></i>
                        <div>
                            <div class="user-name">${name}</div>
                            <div class="user-username">${username ? '@' + username : ''}</div>
                        </div>
                    </div>
                    <div class="user-right">
                        <span class="badge ${roleClass}" title="Categoría">${category}</span>
                        <span class="badge" title="Rol">${role}</span>
                        <span class="badge ${areaClass}" title="Área">${area}</span>
                        <span class="badge ${statusClass}" title="Estado">${status === 'active' ? 'Activo' : 'Inactivo'} • ${ago}</span>
                    </div>
                </div>`;
        }
        function matchesRoleCategory(u, filterVal) {
            if (!filterVal || filterVal === 'todos') return true;
            return normalizeStr(getCategoryFromRole(u.rol)) === normalizeStr(filterVal);
        }
        function matchesArea(u, filterVal) {
            if (!filterVal || filterVal === 'todas') return true;
            const area = normalizeStr(u.area || u.area_empleado || '');
            const f = normalizeStr(filterVal);
            if (f.startsWith('dise')) return area.includes('dise');
            if (f.startsWith('impre')) return area.includes('impre');
            if (f.startsWith('confe')) return area.includes('confe');
            if (f.startsWith('subli')) return area.includes('subli');
            if (f.startsWith('rece')) return area.includes('rece');
            if (f.startsWith('mens')) return area.includes('mens');
            return area.includes(f);
        }
        function renderLoggedUsersList() {
            const roleF = document.getElementById('filter-role-users')?.value || 'todos';
            const areaF = document.getElementById('filter-area-users')?.value || 'todas';
            const container = document.getElementById('modal-users-list');
            let list = _loggedUsers.filter(u => matchesRoleCategory(u, roleF) && matchesArea(u, areaF));
            if (list.length === 0) {
                container.innerHTML = `<div class="no-users-message"><i class="fas fa-user-slash"></i><p>No hay usuarios según filtros</p></div>`;
                return;
            }
            container.innerHTML = list.map(u => renderUserCard(u, 'active')).join('');
        }
        function renderRegisteredUsersList() {
            const roleF = document.getElementById('filter-role-registered')?.value || 'todos';
            const areaF = document.getElementById('filter-area-registered')?.value || 'todas';
            const container = document.getElementById('modal-registered-list');
            let list = _registeredUsers.filter(u => matchesRoleCategory(u, roleF) && matchesArea(u, areaF));
            if (list.length === 0) {
                container.innerHTML = `<div class="no-users-message"><i class="fas fa-users"></i><p>No hay usuarios según filtros</p></div>`;
                return;
            }
            container.innerHTML = list.map(u => renderUserCard(u, 'inactive')).join('');
        }
        function renderDisconnectedUsersList() {
            const roleF = document.getElementById('filter-role-disconnected')?.value || 'todos';
            const areaF = document.getElementById('filter-area-disconnected')?.value || 'todas';
            const container = document.getElementById('modal-disconnected-list');
            let list = _offlineUsers.filter(u => matchesRoleCategory(u, roleF) && matchesArea(u, areaF));
            if (list.length === 0) {
                container.innerHTML = `<div class="no-users-message"><i class="fas fa-user-check"></i><p>No hay usuarios desconectados según filtros</p></div>`;
                return;
            }
            container.innerHTML = list.map(u => renderUserCard(u, 'inactive')).join('');
        }
        
        // Función para abrir el modal de usuarios
        function openUsersModal() {
            const modal = document.getElementById('usersModal');
            showStaffModal(modal);
            updateModalUsersList();
        }
        
        // Función para actualizar la lista de usuarios en el modal
        function updateModalUsersList() {
            fetch('php/get_metrics.php')
                .then(response => response.json())
                .then(data => {
                    const users = data.logged_users_details || [];
                    _loggedUsers = users;
                    document.getElementById('modal-total-users').textContent = users.length;
                    // vincular filtros
                    const fr = document.getElementById('filter-role-users');
                    const fa = document.getElementById('filter-area-users');
                    if (fr) fr.onchange = renderLoggedUsersList;
                    if (fa) fa.onchange = renderLoggedUsersList;
                    // render
                    renderLoggedUsersList();
                    // timestamp
                    const now = new Date();
                    const timeString = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    document.getElementById('modal-last-update').textContent = `Última actualización: ${timeString}`;
                })
                .catch(error => {
                    console.error('Error al cargar usuarios:', error);
                    document.getElementById('modal-users-list').innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Error al cargar los usuarios conectados</p>
                        </div>`;
                });
        }
        
        // NUEVO: Modal Usuarios Registrados por Rol
        function openRegisteredUsersModal() {
            const modal = document.getElementById('registeredUsersModal');
            showStaffModal(modal);
            updateRegisteredUsersModal();
        }
        
        async function updateRegisteredUsersModal() {
            try {
                const response = await fetch('php/get_active_users.php');
                const data = await response.json();
                const allUsers = data.all_users || [];
                _registeredUsers = allUsers;
                document.getElementById('modal-registered-total').textContent = allUsers.length;
                // Conteo por categoría
                const categories = { 'Empleado': 0, 'Operador': 0, 'Administrativo': 0 };
                allUsers.forEach(u => { categories[getCategoryFromRole(u.rol)]++; });
                const chips = document.getElementById('registered-categories');
                if (chips) {
                    chips.innerHTML = `
                        <span class="chip">Empleado <span class="count">${categories['Empleado']}</span></span>
                        <span class="chip">Operador <span class="count">${categories['Operador']}</span></span>
                        <span class="chip">Administrativo <span class="count">${categories['Administrativo']}</span></span>`;
                }
                // eventos de filtro
                const fr = document.getElementById('filter-role-registered');
                const fa = document.getElementById('filter-area-registered');
                if (fr) fr.onchange = renderRegisteredUsersList;
                if (fa) fa.onchange = renderRegisteredUsersList;
                renderRegisteredUsersList();
                const now = new Date();
                const timeString = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                document.getElementById('registered-last-update').textContent = `Última actualización: ${timeString}`;
            } catch (error) {
                console.error('Error al cargar usuarios registrados:', error);
                document.getElementById('modal-registered-roles').innerHTML = `
                    <div class="error-message"><i class="fas fa-exclamation-triangle"></i> Error al cargar datos</div>`;
            }
        }
        
        // NUEVO: Modal Usuarios Desconectados
        function openDisconnectedUsersModal() {
            const modal = document.getElementById('disconnectedUsersModal');
            showStaffModal(modal);
            updateDisconnectedUsersModal();
        }
        
        async function updateDisconnectedUsersModal() {
            try {
                const response = await fetch('php/get_active_users.php');
                const data = await response.json();
                const allUsers = data.all_users || [];
                const offlineUsers = allUsers.filter(u => parseInt(u.is_active) === 0);
                _offlineUsers = offlineUsers;
                document.getElementById('modal-disconnected-total').textContent = offlineUsers.length;
                const fr = document.getElementById('filter-role-disconnected');
                const fa = document.getElementById('filter-area-disconnected');
                if (fr) fr.onchange = renderDisconnectedUsersList;
                if (fa) fa.onchange = renderDisconnectedUsersList;
                renderDisconnectedUsersList();
                const now = new Date();
                const timeString = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                document.getElementById('disconnected-last-update').textContent = `Última actualización: ${timeString}`;
            } catch (error) {
                console.error('Error al cargar usuarios desconectados:', error);
                document.getElementById('modal-disconnected-list').innerHTML = `
                    <div class="error-message"><i class="fas fa-exclamation-triangle"></i> Error al cargar datos</div>`;
            }
        }
        
        // Función para calcular tiempo transcurrido
        function getTimeAgo(timestamp) {
            const now = new Date();
            const activityTime = new Date(timestamp);
            const diffInMinutes = Math.floor((now - activityTime) / (1000 * 60));
            
            if (diffInMinutes < 1) return 'Ahora mismo';
            if (diffInMinutes === 1) return 'Hace 1 minuto';
            if (diffInMinutes < 60) return `Hace ${diffInMinutes} minutos`;
            
            const diffInHours = Math.floor(diffInMinutes / 60);
            if (diffInHours === 1) return 'Hace 1 hora';
            if (diffInHours < 24) return `Hace ${diffInHours} horas`;
            
            return 'Hace más de 1 día';
        }
        
        // Función para actualizar actividad del usuario
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
    </script>
    
    <!-- Modal para Detalles de Área -->
    <div id="areaModal" class="area-modal">
        <div class="area-modal-content">
            <div class="area-modal-header">
                <h3 id="areaModalTitle">Detalles del Área</h3>
                <span class="area-modal-close">&times;</span>
            </div>
            <div class="area-modal-body">
                <div class="area-stats-grid">
                    <div class="area-stat-card recepcion-card">
                        <div class="stat-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="modalRecepcionCount">0</div>
                            <div class="stat-label">En Recepción</div>
                        </div>
                    </div>
                    <div class="area-stat-card proceso-card">
                        <div class="stat-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="modalProcesoCount">0</div>
                            <div class="stat-label">En Proceso</div>
                        </div>
                    </div>
                    <div class="area-stat-card preparado-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-number" id="modalPreparadoCount">0</div>
                            <div class="stat-label">Preparado</div>
                        </div>
                    </div>
                </div>
                <div class="area-total-summary">
                    <div class="total-card">
                        <div class="total-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="total-info">
                            <div class="total-number" id="modalTotalCount">0</div>
                            <div class="total-label">Total de Pedidos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dashboard JavaScript para el sidebar y hamburger menu -->
<script src="js/dashboard.js" defer></script>
<script src="js/area_modal.js" defer></script>
</body>
</html>