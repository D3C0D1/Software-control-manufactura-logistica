<?php
session_start();
require_once 'php/db_connection.php';

// Obtener datos del pedido si se envió el formulario o viene por URL
$pedido_data = null;
$error_message = '';
$numero_guia = null;

// 1) POST
if (isset($_POST['numero_guia'])) {
    $numero_guia = trim($_POST['numero_guia']);
}

// 2) GET estándar (?numero_guia=...)
if (!$numero_guia && isset($_GET['numero_guia'])) {
    $numero_guia = trim($_GET['numero_guia']);
}

// 3) Patrón personalizado: consulta_pedido.php=GUIA...
if (!$numero_guia && isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/consulta_pedido\.php=([^?#]+)/', $uri, $m)) {
        $numero_guia = trim(urldecode($m[1]));
    }
}

// 4) PATH_INFO estilo consulta_pedido.php/GUIA...
if (!$numero_guia && !empty($_SERVER['PATH_INFO'])) {
    $numero_guia = trim(ltrim($_SERVER['PATH_INFO'], '/'));
}

if (!empty($numero_guia)) {
    try {
        // Verificar que la conexión existe y es válida
        if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
            throw new Exception('Error de conexión a la base de datos');
        }

        $sql = "SELECT p.numero_guia, p.area_id, a.nombre_area
                FROM pedidos p
                LEFT JOIN areas a ON p.area_id = a.id
                WHERE p.numero_guia = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Error al preparar la consulta: ' . $conn->error);
        }

        $stmt->bind_param('s', $numero_guia);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $pedido_data = $result->fetch_assoc();
        } else {
            $error_message = 'No se encontró ningún pedido con el número de guía: ' . htmlspecialchars($numero_guia);
        }

        $stmt->close();
    } catch (Exception $e) {
        $error_message = 'Error al consultar el pedido: ' . $e->getMessage();
    }
}
// Mapeo de nombre base de área por ID (evita mostrar "Recepción de X")
function base_area_name_from_id($id, $fallback = null) {
    $id = (int)$id;
    if (in_array($id, [10], true)) return 'Finalizado';
    if (in_array($id, [1,2,3], true)) return 'Diseño';
    if (in_array($id, [4,5,6], true)) return 'Confección';
    if (in_array($id, [7,8,9], true)) return 'Sublimado';
    if (in_array($id, [11,12,13], true)) return 'Mensajería';
    if (in_array($id, [14,15,16], true)) return 'Impresión';
    if (in_array($id, [17,18,19], true)) return 'Control de Calidad';
    if (in_array($id, [20,21,22], true)) return 'Recepción';
    // Heurística de respaldo por nombre_area, por si IDs varían
    $fb = $fallback ? mb_strtolower($fallback, 'UTF-8') : '';
    if ($fb) {
        if (strpos($fb, 'diseño') !== false) return 'Diseño';
        if (strpos($fb, 'confección') !== false || strpos($fb, 'confeccion') !== false) return 'Confección';
        if (strpos($fb, 'sublimado') !== false) return 'Sublimado';
        if (strpos($fb, 'mensajería') !== false || strpos($fb, 'mensajeria') !== false) return 'Mensajería';
        if (strpos($fb, 'impresión') !== false || strpos($fb, 'impresion') !== false) return 'Impresión';
        if (strpos($fb, 'control de calidad') !== false) return 'Control de Calidad';
        if (strpos($fb, 'recepción') !== false || strpos($fb, 'recepcion') !== false) return 'Recepción';
        if (strpos($fb, 'finalizado') !== false) return 'Finalizado';
    }
    return $fallback ?? 'Área no definida';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Pedido - GlamCity</title>
    <link rel="stylesheet" href="css/consulta_pedido.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sección superior con nombre del área y animación -->
        <div class="area-showcase">
            <div class="area-animation-container">
                <div class="area-title">
        <img src="Public/logo.png" alt="Glamcity Logo" class="brand-logo">
                    <p class="area-subtitle">Seguimiento en tiempo real de tu pedido</p>
                    <div class="status-message">
                        <?php if ($pedido_data): ?>
                            <?php if (isset($pedido_data['area_id']) && (int)$pedido_data['area_id'] === 10): ?>
                                <h2 class="finalizado-text">Ya puede venir a recojer su pedido en las instalaciones</h2>
                            <?php else: ?>
                                <h2>Su pedido se encuentra en <span class="current-area"><?php echo htmlspecialchars(base_area_name_from_id($pedido_data['area_id'] ?? null, $pedido_data['nombre_area'] ?? null)); ?></span></h2>
                            <?php endif; ?>
                        <?php else: ?>
                            <h2>Consulta de áreas: ingrese su número de guía</h2>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Animación principal del área -->
                <div class="main-area-animation">
                    <div class="animation-circle">
                        <div class="rotating-elements">
                            <div class="element element-1"><i class="fas fa-inbox"></i></div>
                            <div class="element element-2"><i class="fas fa-envelope"></i></div>
                            <div class="element element-3"><i class="fas fa-paint-brush"></i></div>
                            <div class="element element-4"><i class="fas fa-print"></i></div>
                            <div class="element element-5"><i class="fas fa-fire"></i></div>
                            <div class="element element-6"><i class="fas fa-cut"></i></div>
                            <div class="element element-7"><i class="fas fa-clipboard-check"></i></div>
                            <div class="element element-8"><i class="fas fa-flag-checkered"></i></div>
                        </div>
                        <div class="center-logo">
                            <i class="fas fa-gem"></i>
                            <span>GlamCity</span>
                        </div>
                    </div>
                </div>
                
                <!-- Indicadores de áreas -->
                <div class="areas-indicator">
                    <div class="area-dot" data-area="recepcion">
                        <i class="fas fa-inbox"></i>
                        <span>Recepción</span>
                    </div>
                    <div class="area-dot" data-area="mensajeria">
                        <i class="fas fa-envelope"></i>
                        <span>Mensajería</span>
                    </div>
                    <div class="area-dot" data-area="diseno">
                        <i class="fas fa-paint-brush"></i>
                        <span>Diseño</span>
                    </div>
                    <div class="area-dot" data-area="impresion">
                        <i class="fas fa-print"></i>
                        <span>Impresión</span>
                    </div>
                    <div class="area-dot" data-area="sublimado">
                        <i class="fas fa-fire"></i>
                        <span>Sublimado</span>
                    </div>
                    <div class="area-dot" data-area="confeccion">
                        <i class="fas fa-cut"></i>
                        <span>Confección</span>
                    </div>
                    <div class="area-dot" data-area="control_calidad">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Control de Calidad</span>
                    </div>
                    <div class="area-dot" data-area="finalizado">
                        <i class="fas fa-flag-checkered"></i>
                        <span>Finalizado</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de búsqueda movido hacia abajo -->
        <div class="search-section">
            <!-- Partículas flotantes -->
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            
            <h2><i class="fas fa-search"></i> Consulta tu Pedido</h2>
            <form class="search-form" method="POST" action="">
                <input type="text" 
                       name="numero_guia" 
                       class="search-input" 
                       placeholder="Ingresa tu número de guía..." 
                       value="<?php echo htmlspecialchars($numero_guia ?? ''); ?>"
                       required
                       autocomplete="off">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </form>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($pedido_data): ?>
            <div class="result-section">
                <div class="pedido-card">
                    <div class="pedido-header">
                        <h2><i class="fas fa-package"></i> Pedido #<?php echo htmlspecialchars($pedido_data['numero_guia']); ?></h2>
                        <div class="status-badge status-<?php echo $pedido_data['area_id']; ?>">
                            <?php echo htmlspecialchars(base_area_name_from_id($pedido_data['area_id'] ?? null, $pedido_data['nombre_area'] ?? null)); ?>
                        </div>
                    </div>

                    <!-- Animación del estado según el área -->
                    <div class="status-animation">
                        <div class="animation-container area-<?php echo $pedido_data['area_id']; ?>">
                            <div class="animation-icon">
                                <?php
                                // Definir iconos según el área (corregidos por grupos)
                                $area_icons = [
                                    // Diseño
                                    1 => 'fas fa-inbox',           // Recepción de Diseño
                                    2 => 'fas fa-paint-brush',     // En Proceso de Diseño
                                    3 => 'fas fa-check-circle',    // Preparado de Diseño
                                    // Confección
                                    4 => 'fas fa-inbox',           // Recepción de Confección
                                    5 => 'fas fa-cut',             // En Proceso de Confección
                                    6 => 'fas fa-check-circle',    // Preparado de Confección
                                    // Sublimado
                                    7 => 'fas fa-inbox',           // Recepción de Sublimado
                                    8 => 'fas fa-fire',            // En Proceso de Sublimado
                                    9 => 'fas fa-check-circle',    // Preparado de Sublimado
                                    // Finalizado
                                    10 => 'fas fa-flag-checkered', // Finalizado
                                    // Mensajería
                                    11 => 'fas fa-truck',          // Recepción de Mensajería
                                    12 => 'fas fa-truck',          // En Proceso de Mensajería
                                    13 => 'fas fa-check-circle',   // Preparado de Mensajería
                                    // Impresión
                                    14 => 'fas fa-print',          // En Proceso de Impresión
                                    15 => 'fas fa-check-circle',   // Preparado de Impresión
                                    16 => 'fas fa-check-circle',   // Posible estado adicional de Impresión
                                    // Control de Calidad
                                    17 => 'fas fa-shield-alt',     // Control de Calidad
                                    18 => 'fas fa-shield-alt',     // Control de Calidad
                                    19 => 'fas fa-shield-alt',     // Control de Calidad
                                    // Recepción general (Guía generada)
                                    20 => 'fas fa-inbox',
                                    21 => 'fas fa-inbox',
                                    22 => 'fas fa-inbox'
                                ];
                                
                                $icon = $area_icons[$pedido_data['area_id']] ?? 'fas fa-question-circle';
                                echo '<i class="' . $icon . '"></i>';
                                ?>
                            </div>
                            <div class="animation-text">
                                <?php if (isset($pedido_data['area_id']) && (int)$pedido_data['area_id'] === 10): ?>
                                    <h3>Finalizado</h3>
                                    <p class="finalizado-text">Ya puede venir a recojer su pedido en las instalaciones</p>
                                <?php elseif (isset($pedido_data['area_id']) && (int)$pedido_data['area_id'] === 20): ?>
                                    <h3>Guía generada</h3>
                                    <p>Su pedido fue creado y está en Recepción.</p>
                                <?php else: ?>
                                    <h3><?php echo htmlspecialchars(base_area_name_from_id($pedido_data['area_id'] ?? null, $pedido_data['nombre_area'] ?? null)); ?></h3>
                                    <p>Su pedido se encuentra actualmente en esta área</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>&copy; 2024 Glamcity</p>
        </div>
    </div>

    <script src="js/consulta_pedido.js" defer></script>
</body>
</html>