<?php
// Obtener el nombre del script actual para saber qué página está activa
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <nav class="sidebar-nav">
            <?php if (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] != 3): // Menú para Admin y Operador ?>
                <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="recepcion.php" class="nav-item <?php echo ($current_page == 'recepcion.php') ? 'active' : ''; ?>"><i class="fas fa-concierge-bell"></i><span>Recepción</span></a>
                <a href="mensajeria.php" class="nav-item <?php echo ($current_page == 'mensajeria.php') ? 'active' : ''; ?>"><i class="fas fa-comments"></i><span>Mensajería</span></a>
                <a href="diseno.php" class="nav-item <?php echo ($current_page == 'diseno.php') ? 'active' : ''; ?>"><i class="fas fa-paint-brush"></i><span>Diseño</span></a>
                <a href="impresion.php" class="nav-item <?php echo ($current_page == 'impresion.php') ? 'active' : ''; ?>"><i class="fas fa-print"></i><span>Impresión</span></a>
                <a href="sublimado.php" class="nav-item <?php echo ($current_page == 'sublimado.php') ? 'active' : ''; ?>"><i class="fas fa-paint-brush"></i><span>Sublimado-Instalación</span></a>
                <a href="confeccion.php" class="nav-item <?php echo ($current_page == 'confeccion.php') ? 'active' : ''; ?>"><i class="fas fa-tshirt"></i><span>Confección</span></a>
                <a href="control_calidad.php" class="nav-item <?php echo ($current_page == 'control_calidad.php') ? 'active' : ''; ?>"><i class="fas fa-check-double"></i><span>Control de Calidad</span></a>
                <a href="pqrs_management.php" class="nav-item <?php echo ($current_page == 'pqrs_management.php') ? 'active' : ''; ?>"><i class="fas fa-envelope-open-text"></i><span>Gestión PQRS</span></a>
                <a href="admin_users.php" class="nav-item <?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>Usuarios</span></a>
                <a href="reportes.php" class="nav-item <?php echo ($current_page == 'reportes.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i><span>Reportes</span></a>
            <?php else: // Menú para Empleado ?>
                <?php
                $area_menu_map = [
                    1 => ['url' => 'recepcion.php', 'icon' => 'fa-concierge-bell', 'text' => 'Recepción'],
                    2 => ['url' => 'mensajeria.php', 'icon' => 'fa-paint-brush', 'text' => 'Mensajeria'],
                    3 => ['url' => 'diseno.php', 'icon' => 'fa-paint-brush', 'text' => 'Diseño'],
                    4 => ['url' => 'impresion.php', 'icon' => 'fa-tshirt', 'text' => 'Impresion'],
                    5 => ['url' => 'sublimado.php', 'icon' => 'fa-check-double', 'text' => 'sublimacion'],
                    6 => ['url' => 'confeccion.php', 'icon' => 'fa-box-open', 'text' => 'Confecciòn'],
                    7 => ['url' => 'control_calidad.php', 'icon' => 'fa-print', 'text' => 'Control Calidad'],
                ];
                
                $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
                
                // Obtener áreas asignadas desde la nueva tabla usuario_areas
                if ($user_id) {
                    $query = "SELECT DISTINCT ua.area_id FROM usuario_areas ua WHERE ua.usuario_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($row = $result->fetch_assoc()) {
                        $area_id = $row['area_id'];
                        if (array_key_exists($area_id, $area_menu_map)) {
                            $item = $area_menu_map[$area_id];
                            echo "<a href='{$item['url']}' class='nav-item'><i class='fas {$item['icon']}'></i><span>{$item['text']}</span></a>";
                        }
                    }
                    $stmt->close();
                }
                ?>
                <!-- Agregar acceso a reportes para empleados también -->
                <a href="reportes.php" class="nav-item <?php echo ($current_page == 'reportes.php') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i><span>Reportes</span></a>
                
            <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <a href="php/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a>
    </div>
</aside>