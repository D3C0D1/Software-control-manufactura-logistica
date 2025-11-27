<?php
session_start();
require_once 'php/auth.php';
require_once 'php/db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<?php include 'php/dashboard_header.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Pedidos - GlamCity</title>
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <style>
        .reports-container {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .reports-header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .report-column {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            min-height: 600px;
        }
        
        .column-header {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: white;
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .en-proceso {
            background: linear-gradient(45deg, #ff9a56, #ff6b35);
        }
        
        .finalizados {
            background: linear-gradient(45deg, #4facfe, #00f2fe);
        }
        
        .eliminados {
            background: linear-gradient(45deg, #fa709a, #fee140);
        }
        
        .pedido-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .pedido-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        
        .pedido-numero {
            font-weight: bold;
            color: #007bff;
            font-size: 1.1em;
            margin-bottom: 8px;
        }
        
        .pedido-cliente {
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.95em;
        }
        
        .pedido-fecha {
            color: #6c757d;
            font-size: 0.85em;
        }
        
        .loading {
            text-align: center;
            color: #6c757d;
            padding: 20px;
            font-style: italic;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 10px 0;
        }
        
        .count-badge {
            background: rgba(255,255,255,0.3);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        
        /* Estilos del Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            position: relative;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        
        .close:hover {
            opacity: 0.7;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .detalle-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .detalle-item {
            margin-bottom: 15px;
        }
        
        .detalle-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
            display: block;
        }
        
        .detalle-value {
            color: #6c757d;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #007bff;
        }
        
        .detalle-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .detalle-section h4 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        
        .historial-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        
        .historial-area {
            font-weight: bold;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .historial-fechas {
            color: #6c757d;
        }
        
        .historial-fechas .fas {
            color: #007bff;
            margin-right: 5px;
        }
        
        .historial-fechas small {
            display: block;
            margin-bottom: 2px;
        }
        
        /* Estilos para botones de acción */
        .pedido-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
        }
        
        .pedido-info {
            flex: 1;
        }
        
        .pedido-actions {
            display: flex;
            gap: 8px;
            margin-left: 15px;
        }
        
        .pedido-actions .btn {
            padding: 6px 10px;
            border-radius: 6px;
            border: none;
            transition: all 0.3s ease;
        }
        
        .pedido-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .pedido-actions .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
        }
        
        .pedido-actions .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .pedido-actions .btn i {
            font-size: 0.9em;
        }
        
        .pedido-eliminado-por {
            color: #dc3545;
            font-size: 0.85em;
            margin-top: 5px;
            font-style: italic;
        }
        
        .pedido-eliminado-por i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .reports-container {
                padding: 10px;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
            
            .detalle-grid {
                grid-template-columns: 1fr;
            }
            
            .pedido-card {
                flex-direction: column;
                align-items: stretch;
            }
            
            .pedido-actions {
                margin-left: 0;
                margin-top: 10px;
                justify-content: center;
            }
            
            .pedido-actions .btn {
                flex: 1;
                max-width: 120px;
            }
            
            .modal-body {
                padding: 20px;
            }
        }
        
        /* Estilos para Métricas */
        .metrics-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .metrics-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .metrics-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .metrics-header p {
            color: #6c757d;
            margin: 0;
        }
        
        .metrics-section, .records-section {
            margin-bottom: 30px;
        }
        
        .metrics-card, .records-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e9ecef;
        }
        
        .metrics-card-header, .records-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .metrics-card-header h3, .records-header h3 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.3em;
        }
        
        .metrics-controls, .records-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .metric-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        
        .metric-value {
            font-size: 2.2em;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e9ecef;
        }
        
        .table-responsive {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table th {
            background: #f8f9fa;
            border-top: none;
            color: #495057 !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        
        .table td {
            vertical-align: middle;
            border-color: #e9ecef;
            color: #495057 !important;
        }
        
        .badge {
            color: #fff !important;
        }
        
        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .metrics-card-header, .records-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .metrics-controls, .records-controls {
                justify-content: center;
            }
            
            .metric-value {
                font-size: 1.8em;
            }
            
            .metrics-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'php/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="reports-container">
            <div class="reports-header">
                <h1><i class="fas fa-chart-bar"></i> Reportes de Pedidos</h1>
                <p>Visualización en tiempo real del estado de todos los pedidos</p>
            </div>
            
            <!-- Sección de Métricas Mensuales y Semanales -->
            <div class="metrics-container" style="margin-bottom: 30px;">
                <div class="metrics-header">
                    <h2><i class="fas fa-chart-line"></i> Métricas de Pedidos</h2>
                    <p>Análisis estadístico de pedidos por períodos</p>
                </div>
                
                <!-- Métricas Mensuales -->
                <div class="metrics-section">
                    <div class="metrics-card">
                        <div class="metrics-card-header">
                            <h3><i class="fas fa-calendar-alt"></i> Métricas Mensuales</h3>
                            <div class="metrics-controls">
                                <select id="year-selector" class="form-control" style="width: auto; display: inline-block;">
                                    <option value="2025" selected>2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>
                        </div>
                        <div class="metrics-grid">
                            <div class="metric-item">
                                <div class="metric-value" id="total-mes-actual">0</div>
                                <div class="metric-label">Pedidos Este Mes</div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-value" id="promedio-mensual">0</div>
                                <div class="metric-label">Promedio Mensual</div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-value" id="mejor-mes">-</div>
                                <div class="metric-label">Mejor Mes</div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-value" id="crecimiento-mensual">0%</div>
                                <div class="metric-label">Crecimiento vs Mes Anterior</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Registro de Pedidos por Meses (Solo 3 registros iniciales) -->
                <div class="records-section">
                    <div class="records-card">
                        <div class="records-header">
                            <h3><i class="fas fa-list-alt"></i> Registro de Pedidos por Meses</h3>
                            <div class="records-controls">
                                <button class="btn btn-sm btn-outline-primary" onclick="toggleFullMonthlyRecords()">
                                    <i class="fas fa-expand-arrows-alt"></i> <span id="toggle-monthly-text">Ver Todos</span>
                                </button>

                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-reportes" id="monthly-records-table">
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th>Total Pedidos</th>
                                        <th>En Proceso</th>
                                        <th>Finalizados</th>
                                        <th>Eliminados</th>
                                        <th>Tasa de Finalización</th>
                                    </tr>
                                </thead>
                                <tbody id="monthly-records-body">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Cargando datos mensuales...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Registro de Pedidos Semanales (Solo 3 registros iniciales) -->
                <div class="records-section">
                    <div class="records-card">
                        <div class="records-header">
                            <h3><i class="fas fa-calendar-week"></i> Registro de Pedidos Semanales</h3>
                            <div class="records-controls">
                                <select id="month-selector" class="form-control" style="width: auto; display: inline-block; margin-right: 10px;">
                                    <option value="">Todos los meses</option>
                                    <option value="1">Enero</option>
                                    <option value="2">Febrero</option>
                                    <option value="3">Marzo</option>
                                    <option value="4">Abril</option>
                                    <option value="5">Mayo</option>
                                    <option value="6">Junio</option>
                                    <option value="7">Julio</option>
                                    <option value="8">Agosto</option>
                                    <option value="9" selected>Septiembre</option>
                                    <option value="10">Octubre</option>
                                    <option value="11">Noviembre</option>
                                    <option value="12">Diciembre</option>
                                </select>
                                <button class="btn btn-sm btn-outline-primary" onclick="toggleFullWeeklyRecords()">
                                    <i class="fas fa-expand-arrows-alt"></i> <span id="toggle-weekly-text">Ver Todos</span>
                                </button>

                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-reportes" id="weekly-records-table">
                                <thead>
                                    <tr>
                                        <th>Semana</th>
                                        <th>Período</th>
                                        <th>Total Pedidos</th>
                                        <th>En Proceso</th>
                                        <th>Finalizados</th>
                                        <th>Eliminados</th>
                                        <th>Pedidos por Día</th>
                                    </tr>
                                </thead>
                                <tbody id="weekly-records-body">
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Cargando datos semanales...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="reports-grid">
                <!-- Columna Recepción -->
                <div class="report-column">
                    <div class="column-header recepcion">
                        <i class="fas fa-inbox"></i> Recepción
                        <span class="count-badge" id="count-recepcion">0</span>
                    </div>
                    <div class="search-container" style="padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                        <input type="text" id="search-recepcion" class="form-control" placeholder="🔍 Buscar por #pedido, guía, cliente, fecha..." style="border-radius: 20px; border: 1px solid #ddd;">
                    </div>
                    <div id="pedidos-recepcion" class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Cargando pedidos...
                    </div>
                </div>
                <!-- Columna En Proceso -->
                <div class="report-column">
                    <div class="column-header en-proceso">
                        <i class="fas fa-clock"></i> En Proceso
                        <span class="count-badge" id="count-proceso">0</span>
                    </div>
                    <div class="search-container" style="padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                        <input type="text" id="search-proceso" class="form-control" placeholder="🔍 Buscar por #pedido, guía, cliente, fecha..." style="border-radius: 20px; border: 1px solid #ddd;">
                    </div>
                    <div id="pedidos-proceso" class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Cargando pedidos...
                    </div>
                </div>
                
                <!-- Columna Finalizados -->
                <div class="report-column">
                    <div class="column-header finalizados">
                        <i class="fas fa-check-circle"></i> Finalizados
                        <span class="count-badge" id="count-finalizados">0</span>
                    </div>
                    <div class="search-container" style="padding: 10px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px;">
                        <input type="text" id="search-finalizados" class="form-control" placeholder="🔍 Buscar por #pedido, guía, cliente, fecha..." style="border-radius: 20px; border: 1px solid #ddd;">
                    </div>
                    <div id="pedidos-finalizados" class="loading">
                        <i class="fas fa-spinner fa-spin"></i> Cargando pedidos...
                    </div>
                </div>
                
                
            </div>
        </div>

    </div>
    
    <!-- Modal de Detalles del Pedido -->
    <div id="modalDetalle" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Detalles del Pedido</h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Información Básica -->
                <div class="detalle-grid">
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-hashtag"></i> Número de Guía:</span>
                        <div class="detalle-value" id="detalle-numero">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-user"></i> Cliente:</span>
                        <div class="detalle-value" id="detalle-cliente">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-envelope"></i> Correo:</span>
                        <div class="detalle-value" id="detalle-correo">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-phone"></i> Teléfono:</span>
                        <div class="detalle-value" id="detalle-telefono">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-map-marker-alt"></i> Área Actual:</span>
                        <div class="detalle-value" id="detalle-area">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-flag"></i> Estado:</span>
                        <div class="detalle-value" id="detalle-estado">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-exclamation-triangle"></i> Prioridad:</span>
                        <div class="detalle-value" id="detalle-prioridad">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-user-plus"></i> Creado por:</span>
                        <div class="detalle-value" id="detalle-creado-por">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-calendar-plus"></i> Fecha de Creación:</span>
                        <div class="detalle-value" id="detalle-creacion">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-calendar-check"></i> Última Actualización:</span>
                        <div class="detalle-value" id="detalle-actualizacion">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-clock"></i> Tiempo Total:</span>
                        <div class="detalle-value" id="detalle-tiempo-total">-</div>
                    </div>
                    <div class="detalle-item">
                        <span class="detalle-label"><i class="fas fa-paperclip"></i> Archivo Adjunto:</span>
                        <div class="detalle-value" id="detalle-archivo">-</div>
                    </div>
                </div>
                
                <!-- Notas -->
                <div class="detalle-section">
                    <h4><i class="fas fa-sticky-note"></i> Notas</h4>
                    <div class="detalle-value" id="detalle-notas">Sin notas</div>
                </div>
                
                <!-- Historial de Áreas -->
                <div class="detalle-section">
                    <h4><i class="fas fa-history"></i> Historial de Áreas</h4>
                    <div id="detalle-historial">
                        <div class="text-muted">Cargando historial...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para formatear fecha
        function formatDate(dateString) {
            if (!dateString) return 'No disponible';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        // Normalización dinámica de N° Pedido
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
        // Precargar mapa
        ensureIdSeqMap();
        // Control de auto-actualización de reportes
        let reportsIntervalId = null;
        
        // Función para crear tarjeta de pedido
        function createPedidoCard(pedido, showActions = false) {
            const actionsHTML = showActions ? `
                <div class="pedido-actions">
                    <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); verDetalles(${pedido.id})" title="Ver detalles">
                        <i class="fas fa-eye"></i>
                    </button>
                    <?php if (in_array($_SESSION['rol_id'], [1, 2])): ?>
                    <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); eliminarPedido(${pedido.id}, '${pedido.numero_guia || 'Sin número'}')" title="Eliminar pedido">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </div>
            ` : '';
            
            return `
                <div class="pedido-card" ${!showActions ? `onclick="verDetalles(${pedido.id})"` : ''}>
                    <div class="pedido-info">
                        <div class="pedido-id">
                            <i class="fas fa-id-badge"></i> Pedido #${pedido.id}
                        </div>
                        <div class="pedido-numero">
                            <i class="fas fa-hashtag"></i> ${pedido.numero_guia || 'Sin número'}
                        </div>
                        <div class="pedido-cliente">
                            <i class="fas fa-user"></i> ${pedido.nombre_cliente || 'Sin nombre'}
                        </div>
                        <div class="pedido-fecha">
                            <i class="fas fa-calendar"></i> ${formatDate(pedido.fecha_creacion)}
                        </div>
                    </div>
                    ${actionsHTML}
                </div>
            `;
        }
        
        // createPedidoEliminadoCard removido: ya no se muestran eliminados
        
        // Función para cargar reportes
        async function loadReports() {
            try {
                console.log('Cargando reportes...');
                
                const response = await fetch('php/get_reporte_pedidos.php');
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('Datos recibidos:', data);
                
                if (!data.pedidos || !Array.isArray(data.pedidos)) {
                    throw new Error('Formato de datos inválido');
                }
                
                // Separar pedidos por estado/área
                const recepcion = [];
                const enProceso = [];
                const finalizados = [];

                data.pedidos.forEach(pedido => {
                    // Recepción: área_id = 20 y estado_id = 1 (Guía generada)
                    if (pedido.area_id == 20 && pedido.estado_id == 1) {
                        recepcion.push(pedido);
                        return;
                    }
                    // Finalizados: área_id = 10
                    if (pedido.area_id == 10) {
                        finalizados.push(pedido);
                        return;
                    }
                    // En proceso: áreas operativas (no 10/20) con estados 1 o 2
                    if (pedido.area_id != 10 && pedido.area_id != 20 && (pedido.estado_id == 1 || pedido.estado_id == 2)) {
                        enProceso.push(pedido);
                    }
                });
                
                // Actualizar contadores
                const recepCountEl = document.getElementById('count-recepcion');
                if (recepCountEl) recepCountEl.textContent = recepcion.length;
                document.getElementById('count-proceso').textContent = enProceso.length;
                document.getElementById('count-finalizados').textContent = finalizados.length;
                // Conteo de eliminados removido

                // Renderizar pedidos en recepción
                const recepcionContainer = document.getElementById('pedidos-recepcion');
                if (recepcion.length > 0) {
                    recepcionContainer.innerHTML = recepcion.map(pedido => createPedidoCard(pedido, true)).join('');
                } else {
                    recepcionContainer.innerHTML = '<div class="loading">No hay pedidos en recepción</div>';
                }

                // Renderizar pedidos en proceso
                const procesoContainer = document.getElementById('pedidos-proceso');
                if (enProceso.length > 0) {
                    procesoContainer.innerHTML = enProceso.map(pedido => createPedidoCard(pedido, true)).join('');
                } else {
                    procesoContainer.innerHTML = '<div class="loading">No hay pedidos en proceso</div>';
                }
                
                // Renderizar pedidos finalizados
                const finalizadosContainer = document.getElementById('pedidos-finalizados');
                if (finalizados.length > 0) {
                    finalizadosContainer.innerHTML = finalizados.map(pedido => createPedidoCard(pedido, true)).join('');
                } else {
                    finalizadosContainer.innerHTML = '<div class="loading">No hay pedidos finalizados</div>';
                }
                
                // Render de eliminados removido
                
                console.log(`Reportes cargados: ${recepcion.length} en recepción, ${enProceso.length} en proceso, ${finalizados.length} finalizados`);
                
            } catch (error) {
                console.error('Error al cargar reportes:', error);
                
                const errorMessage = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error al cargar los datos: ${error.message}
                    </div>
                `;
                
                document.getElementById('pedidos-proceso').innerHTML = errorMessage;
                document.getElementById('pedidos-finalizados').innerHTML = errorMessage;
                // Mensaje de error para eliminados removido
            }
        }
        
        // Función para ver detalles del pedido
        async function verDetalles(pedidoId) {
            try {
                const response = await fetch(`php/get_pedido_detalle.php?id=${pedidoId}`);
                const data = await response.json();
                
                if (data.success) {
                    mostrarModalDetalle(data.pedido);
                } else {
                    alert('Error al cargar los detalles: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los detalles del pedido');
            }
        }
        
        // Función para mostrar el modal con detalles
        function mostrarModalDetalle(pedido) {
            const modal = document.getElementById('modalDetalle');
            
            // Llenar información básica
            document.getElementById('detalle-numero').textContent = pedido.numero_guia || 'Sin número';
            document.getElementById('detalle-cliente').textContent = pedido.nombre_cliente || 'Sin nombre';
            document.getElementById('detalle-correo').textContent = pedido.correo_cliente || 'Sin correo';
            document.getElementById('detalle-telefono').textContent = pedido.numero_cliente || 'Sin teléfono';
            document.getElementById('detalle-area').textContent = pedido.area_actual || 'Sin área';
            document.getElementById('detalle-estado').textContent = pedido.estado_actual || 'Sin estado';
            document.getElementById('detalle-prioridad').textContent = pedido.prioridad || 'Normal';
            document.getElementById('detalle-creacion').textContent = pedido.fecha_creacion_formateada || 'Sin fecha';
            document.getElementById('detalle-actualizacion').textContent = pedido.fecha_actualizacion_formateada || 'Sin fecha';
            document.getElementById('detalle-tiempo-total').textContent = pedido.tiempo_total_sistema || 'No calculado';
            document.getElementById('detalle-notas').textContent = pedido.notas || 'Sin notas';
            document.getElementById('detalle-creado-por').textContent = pedido.creado_por_nombre || 'Desconocido';
            
            // Manejar archivo adjunto
            const archivoContainer = document.getElementById('detalle-archivo');
            if (pedido.tiene_archivo && pedido.archivo_adjunto) {
                archivoContainer.innerHTML = `<a href="uploads/pedidos/${pedido.archivo_adjunto}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Descargar archivo</a>`;
            } else {
                archivoContainer.innerHTML = '<span class="text-muted">Sin archivo adjunto</span>';
            }
            
            // Llenar historial de áreas
            const historialContainer = document.getElementById('detalle-historial');
            if (pedido.historial_areas && pedido.historial_areas.length > 0) {
                let historialHTML = '';
                pedido.historial_areas.forEach(area => {
                    historialHTML += `
                        <div class="historial-item">
                            <div class="historial-area">${area.nombre_area}</div>
                            <div class="historial-fechas">
                                <small>Entrada: ${area.fecha_entrada_formateada}</small><br>
                                <small>Salida: ${area.fecha_salida_formateada}</small><br>
                                <small>Tiempo: ${area.tiempo_formateado}</small><br>
                                ${area.usuario_entrada ? `<small><i class="fas fa-user"></i> Recibido por: ${area.usuario_entrada}</small><br>` : ''}
                                ${area.usuario_salida ? `<small><i class="fas fa-user-check"></i> Finalizado por: ${area.usuario_salida}</small>` : ''}
                            </div>
                        </div>
                    `;
                });
                historialContainer.innerHTML = historialHTML;
            } else {
                historialContainer.innerHTML = '<div class="text-muted">Sin historial disponible</div>';
            }
            
            modal.style.display = 'block';
        }
        
        // Función para cerrar el modal
        function cerrarModal() {
            document.getElementById('modalDetalle').style.display = 'none';
        }
        
        // Función para eliminar pedido
        async function eliminarPedido(pedidoId, numeroGuia) {
            if (!confirm(`¿Estás seguro de que deseas eliminar el pedido ${numeroGuia}?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }
            
            try {
                const response = await fetch('php/eliminar_pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ pedido_id: pedidoId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`Pedido ${data.numero_guia} eliminado correctamente`);
                    // Recargar los reportes
                    loadReports();
                } else {
                    alert('Error al eliminar el pedido: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al eliminar el pedido');
            }
        }
        
        // Cerrar modal al hacer click fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('modalDetalle');
            if (event.target === modal) {
                cerrarModal();
            }
        }
        
        // Funciones de búsqueda: pausar/retomar auto-actualización según texto
        function isAnySearchActive() {
            const rec = document.getElementById('search-recepcion')?.value.trim();
            const proc = document.getElementById('search-proceso')?.value.trim();
            const fin = document.getElementById('search-finalizados')?.value.trim();
            return (rec && rec.length) || (proc && proc.length) || (fin && fin.length);
        }

        function handleSearchChange(containerId, value) {
            filterPedidos(containerId, value);
            const hasText = value && value.trim().length > 0;
            if (hasText) {
                if (reportsIntervalId) {
                    clearInterval(reportsIntervalId);
                    reportsIntervalId = null;
                }
            } else {
                if (!isAnySearchActive() && !reportsIntervalId) {
                    reportsIntervalId = setInterval(loadReports, 30000);
                    loadReports();
                }
            }
        }

        // Función para filtrar pedidos en tiempo real
        function setupSearchFilters() {
            // Filtro para recepción
            const sr = document.getElementById('search-recepcion');
            if (sr) {
                sr.addEventListener('input', function(e) {
                    handleSearchChange('pedidos-recepcion', e.target.value);
                });
            }

            // Filtro para pedidos en proceso
            const sp = document.getElementById('search-proceso');
            if (sp) {
                sp.addEventListener('input', function(e) {
                    handleSearchChange('pedidos-proceso', e.target.value);
                });
            }
            
            // Filtro para pedidos finalizados
            const sf = document.getElementById('search-finalizados');
            if (sf) {
                sf.addEventListener('input', function(e) {
                    handleSearchChange('pedidos-finalizados', e.target.value);
                });
            }
        }
        
        // Función para filtrar pedidos por texto
        function filterPedidos(containerId, searchText) {
            const container = document.getElementById(containerId);
            const pedidoCards = container.querySelectorAll('.pedido-card');
            const searchLower = searchText.toLowerCase();
            
            pedidoCards.forEach(card => {
                const pedidoId = card.querySelector('.pedido-id')?.textContent || '';
                const numeroGuia = card.querySelector('.pedido-numero')?.textContent || '';
                const cliente = card.querySelector('.pedido-cliente')?.textContent || '';
                const fecha = card.querySelector('.pedido-fecha')?.textContent || '';
                const area = card.querySelector('.pedido-area')?.textContent || '';
                
                const matchesSearch = pedidoId.toLowerCase().includes(searchLower) ||
                                    numeroGuia.toLowerCase().includes(searchLower) ||
                                    cliente.toLowerCase().includes(searchLower) ||
                                    fecha.toLowerCase().includes(searchLower) ||
                                    area.toLowerCase().includes(searchLower);
                
                card.style.display = matchesSearch ? 'flex' : 'none';
            });
        }
        
        // Funciones para métricas mensuales y semanales
        async function loadMetricas() {
            try {
                // Cargar métricas mensuales
                await loadMetricasMensuales();
                
                // Cargar registros mensuales
                await loadRegistrosMensuales();
                
                // Cargar registros semanales
                await loadRegistrosSemanales();
                
            } catch (error) {
                console.error('Error cargando métricas:', error);
            }
        }
        
        async function loadMetricasMensuales() {
            const year = document.getElementById('year-selector').value;
            
            try {
                const response = await fetch(`php/get_metricas_mensuales.php?year=${year}`);
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar métricas
                    document.getElementById('total-mes-actual').textContent = data.total_mes_actual || 0;
                    document.getElementById('promedio-mensual').textContent = data.promedio_mensual || 0;
                    document.getElementById('mejor-mes').textContent = data.mejor_mes || '-';
                    document.getElementById('crecimiento-mensual').textContent = data.crecimiento_mensual || '0%';
                    
                    // Actualizar gráfico (si tienes Chart.js incluido)
                    if (typeof Chart !== 'undefined') {
                        updateMonthlyChart(data.chart_data);
                    }
                } else {
                    console.error('Error cargando métricas mensuales:', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Variables globales para controlar la visualización
        let showFullMonthlyRecords = false;
        let showFullWeeklyRecords = false;
        
        // Función para alternar vista completa de registros mensuales
        function toggleFullMonthlyRecords() {
            showFullMonthlyRecords = !showFullMonthlyRecords;
            const toggleText = document.getElementById('toggle-monthly-text');
            toggleText.textContent = showFullMonthlyRecords ? 'Ver Menos' : 'Ver Todos';
            loadRegistrosMensuales();
        }
        
        // Función para alternar vista completa de registros semanales
        function toggleFullWeeklyRecords() {
            showFullWeeklyRecords = !showFullWeeklyRecords;
            const toggleText = document.getElementById('toggle-weekly-text');
            toggleText.textContent = showFullWeeklyRecords ? 'Ver Menos' : 'Ver Todos';
            loadRegistrosSemanales();
        }
        
        async function loadRegistrosMensuales() {
            const year = document.getElementById('year-selector').value;
            const limit = showFullMonthlyRecords ? '' : '&limit=3';
            
            try {
                const response = await fetch(`php/get_registros_mensuales.php?year=${year}${limit}`);
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('monthly-records-body');
                    
                    if (data.records && data.records.length > 0) {
                        let html = '';
                        data.records.forEach(record => {
                            const tasaFinalizacion = record.total > 0 ? 
                                ((record.finalizados / record.total) * 100).toFixed(1) + '%' : '0%';
                            
                            html += `
                                <tr>
                                    <td><strong>${record.mes_nombre}</strong></td>
                                    <td>${record.total}</td>
                                    <td>${record.en_proceso}</td>
                                    <td>${record.finalizados}</td>
                                    <td>${record.eliminados}</td>
                                    <td>${tasaFinalizacion}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay datos disponibles</td></tr>';
                    }
                } else {
                    console.error('Error cargando registros mensuales:', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        async function loadRegistrosSemanales() {
            const year = document.getElementById('year-selector').value;
            const month = document.getElementById('month-selector').value;
            const limit = showFullWeeklyRecords ? '' : '&limit=3';
            
            try {
                const response = await fetch(`php/get_registros_semanales.php?year=${year}&month=${month}${limit}`);
                const data = await response.json();
                
                if (data.success) {
                    const tbody = document.getElementById('weekly-records-body');
                    
                    if (data.records && data.records.length > 0) {
                        let html = '';
                        data.records.forEach(record => {
                            const promedioDiario = record.total > 0 ? 
                                (record.total / 7).toFixed(1) : '0';
                            
                            html += `
                                <tr>
                                    <td><strong>Semana ${record.semana}</strong></td>
                                    <td>${record.rango_fechas}</td>
                                    <td>${record.total}</td>
                                    <td>${record.en_proceso}</td>
                                    <td>${record.finalizados}</td>
                                    <td>${record.eliminados}</td>
                                    <td>${promedioDiario}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay datos disponibles</td></tr>';
                    }
                } else {
                    console.error('Error cargando registros semanales:', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function updateMonthlyChart(chartData) {
            const ctx = document.getElementById('monthly-chart').getContext('2d');
            
            if (window.monthlyChart) {
                window.monthlyChart.destroy();
            }
            
            window.monthlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: 'Pedidos por Mes',
                        data: chartData.data,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        function exportarDatosMensuales() {
            const year = document.getElementById('year-selector').value;
            window.open(`php/export_datos_mensuales.php?year=${year}`, '_blank');
        }
        
        function exportarDatosSemanales() {
            const year = document.getElementById('year-selector').value;
            const month = document.getElementById('month-selector').value;
            window.open(`php/export_datos_semanales.php?year=${year}&month=${month}`, '_blank');
        }
        
        // Event listeners para los selectores
        function setupMetricsEventListeners() {
            document.getElementById('year-selector').addEventListener('change', function() {
                loadMetricasMensuales();
                loadRegistrosMensuales();
                loadRegistrosSemanales();
            });
            
            document.getElementById('month-selector').addEventListener('change', function() {
                loadRegistrosSemanales();
            });
        }
        
        // Cargar reportes al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadReports();
            setupSearchFilters();
            setupMetricsEventListeners();
            
            // Cargar métricas
            loadMetricas();
            
            // Actualizar cada 30 segundos
            reportsIntervalId = setInterval(loadReports, 30000);
            
            // Actualizar métricas cada 5 minutos
            setInterval(loadMetricas, 5 * 60 * 1000);
        });
    </script>
    
    <!-- Dashboard JavaScript para el sidebar y hamburger menu -->
<script src="js/dashboard.js" defer></script>
    <script>
        // Función para actualizar la actividad del usuario
        function updateUserActivity() {
            fetch('php/update_user_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error updating user activity:', data.message);
                }
            })
            .catch(error => {
                console.error('Error updating user activity:', error);
            });
        }

        // Actualizar actividad cada 5 minutos
        setInterval(updateUserActivity, 5 * 60 * 1000);
        
        // Actualizar actividad al cargar la página
        updateUserActivity();
    </script>
</body>
</html>