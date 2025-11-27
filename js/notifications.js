// Sistema de notificaciones para pedidos vencidos
class NotificationSystem {
    constructor() {
        this.updateInterval = null;
        this.isInitialized = false;
        this.soundEnabled = true;
        this.lastNotificationCount = 0;
        
        // Sonidos de notificación
        this.sounds = {
            urgent: new Audio('sounds/warning.mp3'),
            normal: new Audio('sounds/pedido.mp3'),
            success: new Audio('sounds/job-done.mp3')
        };
        
        this.init();
    }
    
    init() {
        if (this.isInitialized) return;
        
        this.createNotificationElements();
        this.bindEvents();
        this.startPeriodicUpdates();
        this.isInitialized = true;
    }
    
    createNotificationElements() {
        // Verificar si ya existen los elementos
        if (document.getElementById('notification-bell')) return;
        
        const headerRight = document.querySelector('.header-right');
        if (!headerRight) return;
        
        // Crear campanita de notificaciones
        const notificationBell = document.createElement('div');
        notificationBell.className = 'notification-bell';
        notificationBell.id = 'notification-bell';
        notificationBell.onclick = () => this.toggleNotificationPanel();
        
        notificationBell.innerHTML = `
            <i class="fas fa-bell"></i>
            <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
            <span class="orders-badge" id="orders-badge" style="display: none;">0</span>
        `;
        
        // Crear panel de notificaciones
        const notificationPanel = document.createElement('div');
        notificationPanel.className = 'notification-panel';
        notificationPanel.id = 'notification-panel';
        notificationPanel.style.display = 'none';
        
        notificationPanel.innerHTML = `
            <div class="notification-header">
                <h3>Pedidos Vencidos</h3>
                <button class="close-notifications" onclick="notificationSystem.toggleNotificationPanel()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notification-content" id="notification-content">
                <div class="loading-notifications">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Cargando notificaciones...</span>
                </div>
            </div>
        `;
        
        // Insertar antes del perfil de usuario
        const userProfile = headerRight.querySelector('.user-profile');
        headerRight.insertBefore(notificationBell, userProfile);
        headerRight.insertBefore(notificationPanel, userProfile);
    }
    
    bindEvents() {
        // Cerrar panel al hacer clic fuera
        document.addEventListener('click', (event) => {
            const notificationBell = document.getElementById('notification-bell');
            const notificationPanel = document.getElementById('notification-panel');
            
            if (notificationBell && notificationPanel) {
                if (!notificationBell.contains(event.target) && 
                    !notificationPanel.contains(event.target)) {
                    notificationPanel.style.display = 'none';
                }
            }
        });
        
        // Cerrar con tecla Escape
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const notificationPanel = document.getElementById('notification-panel');
                if (notificationPanel) {
                    notificationPanel.style.display = 'none';
                }
            }
        });
        
        // Pausar actualizaciones cuando la pestaña no está activa
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPeriodicUpdates();
            } else {
                this.updateNotifications();
                this.startPeriodicUpdates();
            }
        });
    }
    
    toggleNotificationPanel() {
        const panel = document.getElementById('notification-panel');
        if (!panel) return;
        
        const isVisible = panel.style.display !== 'none';
        
        if (isVisible) {
            panel.style.display = 'none';
        } else {
            panel.style.display = 'block';
            this.loadNotifications();
        }
    }
    
    async loadNotifications() {
        const content = document.getElementById('notification-content');
        if (!content) return;
        
        content.innerHTML = `
            <div class="loading-notifications">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Cargando notificaciones...</span>
            </div>
        `;
        
        try {
            const response = await fetch('php/get_pedidos_vencidos.php');
            const data = await response.json();
            
            if (data.success) {
                this.displayNotifications(data.pedidos_vencidos, data.contadores);
            } else {
                this.showError('Error al cargar las notificaciones');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showError('Error de conexión');
        }
    }
    
    displayNotifications(pedidos, contadores) {
        const content = document.getElementById('notification-content');
        if (!content) return;
        
        if (pedidos.length === 0) {
            content.innerHTML = `
                <div class="no-notifications">
                    <i class="fas fa-check-circle"></i>
                    <p>No hay pedidos vencidos</p>
                    <small>¡Excelente trabajo!</small>
                </div>
            `;
            return;
        }
        
        let html = '';
        pedidos.forEach((pedido, index) => {
            const prioridad = pedido.prioridad || 'normal';
            const horasVencido = Math.abs(pedido.horas_vencido);
            const tiempoVencido = horasVencido < 24 
                ? `${horasVencido}h vencido`
                : `${Math.floor(horasVencido / 24)}d vencido`;
            
            const urgentClass = horasVencido > 48 ? 'urgent' : '';
            
            html += `
                <div class="notification-item priority-${prioridad} ${urgentClass}" 
                     onclick="notificationSystem.goToPedido(${pedido.id}, '${pedido.area_nombre}')"
                     style="animation-delay: ${index * 0.1}s">
                    <div class="notification-title">
                        Pedido #${pedido.id} - ${pedido.nombre_cliente}
                    </div>
                    <div class="notification-subtitle">
                        Guía: ${pedido.numero_guia} 
                        <span class="priority-badge ${prioridad}">${prioridad}</span>
                    </div>
                    <div class="notification-details">
                        <span class="notification-area">${pedido.area_nombre || 'Sin área'}</span>
                        <span class="notification-time">${tiempoVencido}</span>
                    </div>
                </div>
            `;
        });
        
        content.innerHTML = html;
    }
    
    showError(message) {
        const content = document.getElementById('notification-content');
        if (!content) return;
        
        content.innerHTML = `
            <div class="no-notifications">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    goToPedido(pedidoId, areaNombre) {
        // Cerrar el panel de notificaciones
        this.toggleNotificationPanel();
        
        // Determinar la página según el área
        let targetPage = 'dashboard.php';
        
        if (areaNombre) {
            const areaLower = areaNombre.toLowerCase();
            if (areaLower.includes('recepción')) {
                targetPage = 'recepcion.php';
            } else if (areaLower.includes('diseño')) {
                targetPage = 'diseno.php';
            } else if (areaLower.includes('impresión')) {
                targetPage = 'impresion.php';
            } else if (areaLower.includes('sublimado')) {
                targetPage = 'sublimado.php';
            } else if (areaLower.includes('confección')) {
                targetPage = 'confeccion.php';
            } else if (areaLower.includes('control')) {
                targetPage = 'control_calidad.php';
            } else if (areaLower.includes('mensajería')) {
                targetPage = 'mensajeria.php';
            }
        }
        
        // Redirigir a la página correspondiente
        window.location.href = `${targetPage}?highlight=${pedidoId}`;
    }
    
    async updateNotifications() {
        try {
            const response = await fetch('php/get_pedidos_vencidos.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateBadge(data.contadores);
                this.checkForNewNotifications(data.contadores.total);
            }
        } catch (error) {
            console.error('Error al actualizar notificaciones:', error);
        }
    }
    
    updateBadge(contadores) {
        const badge = document.getElementById('notification-badge');
        const bell = document.querySelector('#notification-bell i');
        
        if (!badge || !bell) return;
        
        const total = contadores.total;
        
        if (total > 0) {
            badge.textContent = total > 99 ? '99+' : total;
            badge.style.display = 'flex';
            
            // Cambiar colores a verde/negro (evitar rojos)
            if (contadores.alta > 0) {
                // Urgente: negro
                bell.style.color = '#111';
                badge.style.background = '#111';
                badge.style.color = '#fff';
                badge.style.animation = 'none';
                document.getElementById('notification-bell').classList.add('has-urgent');
            } else {
                // Normal: verde
                bell.style.color = '#10b981';
                badge.style.background = '#10b981';
                badge.style.color = '#fff';
                badge.style.animation = 'none';
                document.getElementById('notification-bell').classList.remove('has-urgent');
            }
        } else {
            badge.style.display = 'none';
            bell.style.color = 'var(--text-secondary)';
            badge.classList.remove('urgent');
            document.getElementById('notification-bell').classList.remove('has-urgent');
        }
    }

    updateOrdersBadge(totalOrders) {
        const ordersBadge = document.getElementById('orders-badge');
        if (!ordersBadge) return;
        
        if (totalOrders > 0) {
            ordersBadge.textContent = totalOrders > 99 ? '99+' : totalOrders;
            ordersBadge.style.display = 'flex';
        } else {
            ordersBadge.style.display = 'none';
        }
    }
    
    checkForNewNotifications(currentCount) {
        if (this.lastNotificationCount > 0 && currentCount > this.lastNotificationCount) {
            // Hay nuevas notificaciones
            this.playNotificationSound('urgent');
            this.showToast('Nuevos pedidos vencidos detectados', 'warning');
        } else if (this.lastNotificationCount > currentCount && currentCount === 0) {
            // Se resolvieron todas las notificaciones
            this.playNotificationSound('success');
            this.showToast('¡Todos los pedidos están al día!', 'success');
        }
        
        this.lastNotificationCount = currentCount;
    }
    
    playNotificationSound(type) {
        if (!this.soundEnabled) return;
        
        const sound = this.sounds[type];
        if (sound) {
            sound.volume = 0.3;
            sound.play().catch(e => console.log('No se pudo reproducir el sonido:', e));
        }
    }
    
    showToast(message, type = 'info') {
        // Notificación en la parte superior sin texto: solo icono
        const existente = document.querySelector('.notificacion-icon-toast');
        if (existente) existente.remove();

        const toast = document.createElement('div');
        toast.className = `notificacion-icon-toast toast-${type}`;
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
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-times-circle';
        icon.style.cssText = `font-size: 22px; color: ${type === 'success' ? '#10b981' : '#111'};`;
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
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 200);
        }, 2000);
    }
    
    startPeriodicUpdates() {
        if (this.updateInterval) return;
        
        // Actualizar cada 30 segundos
        this.updateInterval = setInterval(() => {
            this.updateNotifications();
        }, 30000);
        
        // Primera actualización inmediata
        this.updateNotifications();
    }
    
    stopPeriodicUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
    
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        this.showToast(
            `Sonidos ${this.soundEnabled ? 'activados' : 'desactivados'}`,
            'info'
        );
    }
    
    destroy() {
        this.stopPeriodicUpdates();
        this.isInitialized = false;
    }
}

// Agregar estilos CSS para las animaciones
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    /* Posicionamiento y estilos para badges en la campana */
    #notification-bell {
        position: relative;
    }
    #notification-bell .notification-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        font-size: 11px;
        font-weight: 700;
        border-radius: 999px;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    #notification-bell .orders-badge {
        position: absolute;
        bottom: -6px;
        right: -6px;
        background: #10b981;
        color: #fff;
        min-width: 16px;
        height: 16px;
        font-size: 10px;
        font-weight: 700;
        border-radius: 999px;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    }
`;
document.head.appendChild(notificationStyles);

// Inicializar el sistema de notificaciones cuando se carga la página
let notificationSystem;

document.addEventListener('DOMContentLoaded', function() {
    // Esperar un poco para asegurar que el DOM esté completamente cargado
    setTimeout(() => {
        notificationSystem = new NotificationSystem();
    }, 500);
});

// Limpiar al salir de la página
window.addEventListener('beforeunload', function() {
    if (notificationSystem) {
        notificationSystem.destroy();
    }
});