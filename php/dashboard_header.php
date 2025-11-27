<?php
// Verificar que la sesión esté iniciada
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener información del usuario desde la base de datos
require_once 'db_connection.php';

$usuario = [
    'nombre_completo' => 'Usuario',
    'foto_perfil' => null
];

try {
    if (isset($conn) && ($conn instanceof mysqli)) {
        $stmt = $conn->prepare("SELECT nombre_completo, foto_perfil FROM usuarios WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (is_array($row)) {
                        $usuario = $row;
                    }
                }
            }
            $stmt->close();
        }
    }
} catch (Exception $e) {
    // En caso de error, usar valores por defecto
    error_log("Error al obtener datos del usuario: " . $e->getMessage());
}

// Determinar la ruta de la foto de perfil
$foto_perfil_url = 'assets/images/default-avatar.svg'; // Imagen por defecto
if (!empty($usuario['foto_perfil'])) {
    // Si el usuario tiene foto de perfil, construir la ruta
    $foto_path = 'uploads/profiles/' . $usuario['foto_perfil'];
    if (file_exists($foto_path)) {
        $foto_perfil_url = $foto_path;
    }
}

// Obtener solo el primer nombre para mostrar
$primer_nombre = explode(' ', $usuario['nombre_completo'])[0];

// Función para detectar el entorno de hosting usando la función de config.php
function obtenerEntornoDisplay() {
    if (function_exists('isLocalEnvironment') && isLocalEnvironment()) {
        // Detectar AMPPS vs MAMP en entorno local
        $esAMPPS = function_exists('isAMPPS') && isAMPPS();
        if ($esAMPPS) {
            return [
                'tipo' => 'local',
                'nombre' => 'AMPPS',
                'icono' => 'fas fa-server',
                'color' => '#1abc9c',
                'bg_color' => 'rgba(26, 188, 156, 0.15)'
            ];
        }
        // MAMP por defecto
        return [
            'tipo' => 'local',
            'nombre' => 'MAMP',
            'icono' => 'fas fa-server',
            'color' => '#FF6B35',
            'bg_color' => 'rgba(255, 107, 53, 0.1)'
        ];
    } else {
        // Entorno de producción Hostinger
        return [
            'tipo' => 'production',
            'nombre' => 'Hostinger',
            'icono' => 'fas fa-cloud-upload-alt',
            'color' => '#673AB7',
            'bg_color' => 'rgba(103, 58, 183, 0.1)'
        ];
    }
}

$entorno = obtenerEntornoDisplay();

// Determinar el título dinámico según la página actual
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'dashboard' => 'Glamcity Dashboard',
    'recepcion' => 'Recepción De Glamcity',
    'mensajeria' => 'Mensajería De Glamcity',
    'diseno' => 'Diseño De Glamcity',
    'impresion' => 'Impresión De Glamcity',
    'sublimado' => 'Sublimado De Glamcity',
    'confeccion' => 'Confección De Glamcity',
    'control_calidad' => 'Control De Calidad De Glamcity',
    'pqrs_management' => 'PQRS De Glamcity',
    'admin_users' => 'Gestión De Usuarios De Glamcity',
    'reportes' => 'Reportes De Glamcity'
];

$page_title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'Glamcity Dashboard';
?>

<!-- Header Superior del Dashboard -->
<header class="top-header">
    <div class="header-left">
        <!-- Hamburger Menu -->
        <button class="hamburger-menu" id="hamburger-menu" aria-label="Toggle sidebar">
            <span></span>
            <span></span>
            <span></span>
        </button>
        

        
        <!-- Logo o título opcional -->
        <div class="header-brand">
            <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: var(--primary-color);">
                <?php echo htmlspecialchars($page_title); ?>
            </h2>
        </div>
    </div>
    
    <div class="header-center">
        <!-- Reloj en tiempo real en el header -->
        <div class="header-clock">
            <div class="clock-widget">
                <div class="clock-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="time-info">
                    <div id="header-time" class="current-time"></div>
                    <div id="header-date" class="current-date"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="header-right">
        <!-- Indicador de entorno -->
        <div class="environment-indicator">
            <div class="env-status env-<?php echo $entorno['tipo']; ?>" title="Entorno: <?php echo $entorno['nombre']; ?>" style="background: <?php echo $entorno['bg_color']; ?>; border-color: <?php echo $entorno['color']; ?>">
                <div class="env-icon" style="color: <?php echo $entorno['color']; ?>; background: rgba(255, 255, 255, 0.9);">
                    <i class="<?php echo $entorno['icono']; ?>"></i>
                </div>
                <div class="env-info">
                    <div class="env-text" style="color: <?php echo $entorno['color']; ?>"><?php echo $entorno['nombre']; ?></div>
                    <div class="env-type"><?php echo ucfirst($entorno['tipo']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Indicador de conexión movido aquí -->
        <div class="connection-indicator">
            <div id="header-connection-status" class="connection-status">
                <div class="connection-icon">
                    <i class="fas fa-wifi"></i>
                </div>
                <div class="connection-info">
                    <div class="connection-text">Conectado</div>
                    <div class="connection-signal">
                        <span class="signal-bar"></span>
                        <span class="signal-bar"></span>
                        <span class="signal-bar"></span>
                    </div>
                </div>
            </div>
        </div>
        
        
        
        <!-- Botón de Auditoría -->
        <div class="audit-button" style="margin-left:8px;">
            <button id="header-audit-btn" class="notification-btn" onclick="window.location.href='auditoria.php'" title="Auditoría del Sistema">
                <i class="fas fa-clipboard-list"></i>
            </button>
        </div>
        
        <!-- Información del usuario -->
        <div class="user-profile" onclick="toggleUserMenu()">
            <span class="user-name"><?php echo htmlspecialchars($primer_nombre); ?></span>
            <img src="<?php echo htmlspecialchars($foto_perfil_url); ?>" 
                 alt="Foto de perfil de <?php echo htmlspecialchars($usuario['nombre_completo']); ?>" 
                 class="profile-picture"
                 onerror="this.src='assets/images/default-avatar.svg'">
        </div>
        
        <!-- Menú desplegable del usuario (opcional) -->
        <div class="user-dropdown" id="user-dropdown" style="display: none;">
            <div class="dropdown-content">
                <div class="user-info">
                    <img src="<?php echo htmlspecialchars($foto_perfil_url); ?>" 
                         alt="Foto de perfil" 
                         class="dropdown-avatar"
                         onerror="this.src='assets/images/default-avatar.svg'">
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($usuario['nombre_completo']); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['rol'] ?? 'Usuario'); ?></p>
                    </div>
                </div>
                <hr>
                <a href="#" class="dropdown-item" onclick="openEditProfileModal()">
                    <i class="fas fa-user-edit"></i>
                    <span>Editar Perfil</span>
                </a>
                <a href="dev.php" class="dropdown-item">
                    <i class="fas fa-key"></i>
                    <span>Configuración</span>
                </a>
                <a href="config.php" class="dropdown-item">
                    <i class="fas fa-cogs"></i>
                    <span>Config. Sistema</span>
                </a>
                <hr>
                <a href="php/logout.php" class="dropdown-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Modal para Editar Perfil -->
<div id="editProfileModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3>Editar Mi Perfil</h3>
            <span class="close" onclick="closeEditProfileModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editProfileForm" enctype="multipart/form-data">
                <div class="profile-picture-container" style="text-align: center; margin-bottom: 20px;">
                    <img id="profilePreview" 
                         src="<?php echo htmlspecialchars($foto_perfil_url); ?>" 
                         alt="Foto de perfil" 
                         style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-light);"
                         onerror="this.src='assets/images/default-avatar.svg'">
                </div>

                <div class="form-group">
                    <label for="editFotoPerfil">Cambiar Foto de Perfil:</label>
                    <input type="file" id="editFotoPerfil" name="foto_perfil" accept="image/*">
                </div>

                <!-- Campos de nombre y usuario removidos: solo se permite cambiar foto y contraseña -->

                <div class="form-group">
                    <label for="editPassword">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                    <input type="password" id="editPassword" name="password">
                </div>

                <div class="form-actions" style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeEditProfileModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos adicionales para el dropdown del usuario y modal -->
<style>
.header-brand h2 {
    display: none;
}

@media (min-width: 768px) {
    .header-brand h2 {
        display: block;
    }
    
    .user-name {
        display: block !important;
    }
}



.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid var(--border-light);
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    min-width: 280px;
    z-index: 1001;
    margin-top: 8px;
}

.dropdown-content {
    padding: 16px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.dropdown-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-light);
}

.user-details h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
}

.user-details p {
    margin: 4px 0 0 0;
    font-size: 14px;
    color: var(--text-secondary);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    text-decoration: none;
    color: var(--text-primary);
    border-radius: 8px;
    transition: var(--transition-fast);
    margin: 4px 0;
    cursor: pointer;
}

.dropdown-item:hover {
    background-color: var(--bg-primary);
}

.dropdown-item.logout {
    color: #ef4444;
}

.dropdown-item.logout:hover {
    background-color: #fef2f2;
}

.dropdown-item i {
    width: 16px;
    text-align: center;
}

hr {
    border: none;
    border-top: 1px solid var(--border-light);
    margin: 12px 0;
}

/* Estilos del Modal */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    background-color: var(--bg-secondary);
    margin: 5% auto;
    padding: 0;
    border: 1px solid var(--border-light);
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    animation: fadeInUp 0.3s ease;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-light);
}

.modal-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 18px;
    font-weight: 600;
}

.close {
    color: var(--text-secondary);
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    transition: var(--transition-fast);
}

.close:hover {
    color: var(--text-primary);
}

.modal-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    font-size: 14px;
    transition: var(--transition-fast);
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn-primary, .btn-secondary {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-fast);
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background-color: var(--bg-primary);
    color: var(--text-secondary);
    border: 1px solid var(--border-light);
}

.btn-secondary:hover {
    background-color: var(--border-light);
    color: var(--text-primary);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<!-- JavaScript para el menú del usuario y modal -->
<script>


// Cerrar paneles al hacer clic fuera
document.addEventListener('click', function(event) {
    const userProfile = document.querySelector('.user-profile');
    const userDropdown = document.getElementById('user-dropdown');
    
    // Cerrar menú de usuario
    if (!userProfile.contains(event.target) && !userDropdown.contains(event.target)) {
        userDropdown.style.display = 'none';
    }
});

function toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    const isVisible = dropdown.style.display !== 'none';
    
    if (isVisible) {
        dropdown.style.display = 'none';
    } else {
        dropdown.style.display = 'block';
    }
}

// Cerrar el menú al hacer clic fuera
document.addEventListener('click', function(event) {
    const userProfile = document.querySelector('.user-profile');
    const dropdown = document.getElementById('user-dropdown');
    
    if (!userProfile.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

// Cerrar el menú con la tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.getElementById('user-dropdown').style.display = 'none';
        closeEditProfileModal();
    }
});

// Funciones del Modal de Editar Perfil
function openEditProfileModal() {
    // Cerrar el dropdown del usuario
    document.getElementById('user-dropdown').style.display = 'none';
    
    // Cargar datos del usuario actual
    fetch('php/get_current_user.php')
        .then(response => response.json())
        .then(data => {
            if (!data || !data.success || !data.user) {
                alert('Error al cargar los datos del usuario');
                return;
            }

            const user = data.user;
            document.getElementById('editPassword').value = '';
            
            // Actualizar preview de la foto
            const profilePreview = document.getElementById('profilePreview');
            if (user.foto_perfil) {
                profilePreview.src = 'uploads/profiles/' + user.foto_perfil;
            } else {
                profilePreview.src = 'assets/images/default-avatar.svg';
            }
            
            // Mostrar el modal
            document.getElementById('editProfileModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos del usuario');
        });
}

function closeEditProfileModal() {
    document.getElementById('editProfileModal').style.display = 'none';
    document.getElementById('editProfileForm').reset();
}

// Preview de la imagen seleccionada
document.getElementById('editFotoPerfil').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profilePreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Manejar el envío del formulario
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('php/update_current_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Perfil actualizado correctamente');
            closeEditProfileModal();
            // Recargar la página para mostrar los cambios
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el perfil');
    });
});

// Cerrar modal al hacer clic fuera
document.getElementById('editProfileModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeEditProfileModal();
    }
});
</script>







<script>
// Función para actualizar el reloj del header
function updateHeaderClock() {
    const now = new Date();
    const timeOptions = {
        timeZone: 'America/Bogota',
        hour12: true,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    const dateOptions = {
        timeZone: 'America/Bogota',
        weekday: 'short',
        day: '2-digit',
        month: 'short'
    };
    
    const timeElement = document.getElementById('header-time');
    const dateElement = document.getElementById('header-date');
    
    if (timeElement) {
        timeElement.textContent = now.toLocaleTimeString('es-CO', timeOptions);
    }
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('es-CO', dateOptions);
    }
}

// Inicializar el reloj cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded ejecutado');
    updateHeaderClock();
    setInterval(updateHeaderClock, 1000);
    

});


</script>