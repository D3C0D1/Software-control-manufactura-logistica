/**
 * Dashboard JavaScript - Manejo del Sidebar y Header
 * Glamcity Dashboard v2.0
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const hamburgerMenu = document.querySelector('.hamburger-menu');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const body = document.body;
    
    // Estado del sidebar
    let sidebarState = {
        mode: 'normal' // 'normal', 'icons-only', 'hidden'
    };

    // Cargar estado del sidebar desde localStorage
    function loadSidebarState() {
        const savedState = localStorage.getItem('sidebarState');
        if (savedState) {
            sidebarState = JSON.parse(savedState);
            applySidebarState();
        }
    }

    // Guardar estado del sidebar en localStorage
    function saveSidebarState() {
        localStorage.setItem('sidebarState', JSON.stringify(sidebarState));
    }

    // Aplicar estado del sidebar
    function applySidebarState() {
        if (!sidebar || !mainContent) {
            console.log('sidebar or mainContent not found');
            return;
        }

        console.log('applySidebarState called with mode:', sidebarState.mode);

        // Limpiar clases existentes
        sidebar.classList.remove('collapsed', 'hidden', 'show', 'icons-only');
        mainContent.classList.remove('expanded', 'full-width', 'icons-only');
        hamburgerMenu.classList.remove('active');

        if (window.innerWidth <= 768) {
            // Modo móvil - solo normal y hidden
            if (sidebarState.mode === 'hidden') {
                sidebar.classList.add('hidden');
                mainContent.classList.add('full-width');
            } else {
                sidebar.classList.add('show');
                hamburgerMenu.classList.add('active');
                sidebarState.mode = 'normal'; // Forzar modo normal en móvil
            }
        } else {
            // Modo desktop - 3 estados
            switch (sidebarState.mode) {
                case 'normal':
                    console.log('applying normal state');
                    // Estado por defecto - mostrar todo
                    break;
                case 'icons-only':
                    console.log('applying icons-only state');
                    sidebar.classList.add('icons-only');
                    mainContent.classList.add('icons-only');
                    break;
                case 'hidden':
                    console.log('applying hidden state');
                    sidebar.classList.add('hidden');
                    mainContent.classList.add('full-width');
                    hamburgerMenu.classList.add('active');
                    break;
            }
        }
        
        console.log('sidebar classes:', sidebar.className);
        console.log('mainContent classes:', mainContent.className);
    }

    // Toggle del sidebar
    function toggleSidebar() {
        console.log('toggleSidebar called, current mode:', sidebarState.mode);
        console.log('window width:', window.innerWidth);
        
        if (window.innerWidth <= 768) {
            // Modo móvil - solo mostrar/ocultar
            sidebarState.mode = sidebarState.mode === 'hidden' ? 'normal' : 'hidden';
        } else {
            // Modo desktop - ciclo de 3 estados
            switch (sidebarState.mode) {
                case 'normal':
                    sidebarState.mode = 'icons-only';
                    break;
                case 'icons-only':
                    sidebarState.mode = 'hidden';
                    break;
                case 'hidden':
                    sidebarState.mode = 'normal';
                    break;
                default:
                    sidebarState.mode = 'normal';
            }
        }

        console.log('new mode:', sidebarState.mode);
        applySidebarState();
        saveSidebarState();
    }

    // Event listener para el hamburger menu
    if (hamburgerMenu) {
        console.log('hamburger menu found and event listener added');
        hamburgerMenu.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('hamburger menu clicked');
            toggleSidebar();
        });
    } else {
        console.log('hamburger menu NOT found');
    }

    // Cerrar sidebar en móvil al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && 
            sidebarState.mode !== 'hidden' && 
            sidebar && 
            !sidebar.contains(e.target) && 
            !hamburgerMenu.contains(e.target)) {
            
            sidebarState.mode = 'hidden';
            applySidebarState();
            saveSidebarState();
        }
    });

    // Manejar cambios de tamaño de ventana
    window.addEventListener('resize', function() {
        // Debounce para evitar múltiples llamadas
        clearTimeout(window.resizeTimeout);
        window.resizeTimeout = setTimeout(function() {
            applySidebarState();
        }, 100);
    });

    // Manejar navegación activa
    function setActiveNavItem() {
        const currentPath = window.location.pathname;
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            item.classList.remove('active');
            
            // Obtener el href del enlace
            const href = item.getAttribute('href');
            if (href && currentPath.includes(href)) {
                item.classList.add('active');
            }
        });
    }

    // Animaciones suaves para las transiciones
    function addSmoothTransitions() {
        if (sidebar) {
            sidebar.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        }
        
        if (mainContent) {
            mainContent.style.transition = 'margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        }
    }

    // Efecto hover para las tarjetas
    function addCardHoverEffects() {
        const cards = document.querySelectorAll('.card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    // Tooltip para elementos colapsados
    function addTooltips() {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            const span = item.querySelector('span');
            if (span) {
                item.setAttribute('title', span.textContent);
            }
        });
    }

    // Animación de carga para las estadísticas
    function animateStats() {
        const statNumbers = document.querySelectorAll('.card-info p');
        
        statNumbers.forEach(stat => {
            const finalValue = parseInt(stat.textContent);
            if (!isNaN(finalValue)) {
                // Modo simple: sin animación; solo mostrar el valor final formateado
                stat.textContent = DashboardUtils.formatNumber(finalValue);
                stat.setAttribute('data-value', finalValue);
            }
        });
    }

    // Chat grupal removido: desactivar notificaciones y SSE
    let eventSource = null;
    let lastUnreadCount = 0;
    let reconnectAttempts = 0;
    let maxReconnectAttempts = 0;

    // Función para inicializar notificaciones de chat con SSE
    function initChatNotifications() {
        // Chat grupal removido: no inicializar notificaciones
        try { if (eventSource) eventSource.close(); } catch(e) {}
        return;
    }
    
    function startSSEConnection() {
        // Notificaciones de chat desactivadas
        try { if (eventSource) eventSource.close(); } catch(e) {}
        console.log('Notificaciones de chat (SSE) desactivadas.');
        return;
    }

    // Utilidad segura para parsear JSON provenientes de SSE
    function safeParse(text) {
        try {
            if (typeof text !== 'string' || text.trim() === '') return null;
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON.parse fallo:', e);
            return null;
        }
    }
    
    function attemptReconnect() {
        // Reconexión desactivada
        console.log('Reconexión SSE desactivada.');
        return;
    }
    
    function fallbackToPolling() {
        // Polling desactivado
        console.log('Polling de notificaciones de chat desactivado.');
        return;
    }

    // Función para verificar notificaciones de chat
    function checkChatNotifications() {
        console.log('Verificando notificaciones de chat...');
        
        fetch('php/get_unread_messages_count.php')
            .then(response => {
                console.log(`Status de respuesta: ${response.status}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log(`Respuesta raw: ${text}`);
                try {
                    const data = JSON.parse(text);
                    console.log('Datos parseados:', data);
                    
                    if (data.success) {
                        const count = data.data.unread_count;
                        console.log(`Mensajes no leídos: ${count}`);
                        updateChatNotificationBadge(count);
                        
                        // Si hay nuevos mensajes, mostrar notificación
                        if (count > lastUnreadCount && lastUnreadCount >= 0) {
                            if (count > 0 && lastUnreadCount === 0) {
                                showChatNotification(count);
                            }
                        }
                        
                        lastUnreadCount = count;
                    } else {
                        console.error('Error en API:', data.error);
                    }
                } catch (parseError) {
                    console.error('Error parsing JSON:', parseError);
                    console.error('Raw text was:', text);
                }
            })
            .catch(error => {
                console.error('Error checking chat notifications:', error);
            });
    }

    // Función para actualizar el badge de notificaciones
    function updateChatNotificationBadge(count) {
        console.log(`Actualizando badges con count: ${count}`);
        
        const badgeAdmin = document.getElementById('chat-notification-badge');
        const badgeEmployee = document.getElementById('chat-notification-badge-employee');
        const headerBadge = document.getElementById('header-chat-badge');
        
        console.log('Badges encontrados:');
        console.log('- badgeAdmin:', badgeAdmin ? 'SÍ' : 'NO');
        console.log('- badgeEmployee:', badgeEmployee ? 'SÍ' : 'NO');
        console.log('- headerBadge:', headerBadge ? 'SÍ' : 'NO');
        
        [badgeAdmin, badgeEmployee, headerBadge].forEach((badge, index) => {
            const badgeNames = ['badgeAdmin', 'badgeEmployee', 'headerBadge'];
            if (badge) {
                console.log(`Actualizando ${badgeNames[index]}...`);
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'flex';
                    console.log(`${badgeNames[index]} visible con texto: ${badge.textContent}`);
                } else {
                    badge.style.display = 'none';
                    console.log(`${badgeNames[index]} oculto`);
                }
            } else {
                console.log(`${badgeNames[index]} NO encontrado`);
            }
        });
    }

    // Función para mostrar notificación de nuevos mensajes
    function showChatNotification(newMessages) {
        // Chat grupal removido: no mostrar notificaciones ni redirigir
        return;
    }

    // Función para actualizar saludo basado en la hora
    function updateGreeting() {
        const greetingElement = document.querySelector('.greeting-text');
        if (greetingElement) {
            greetingElement.textContent = DashboardUtils.getGreeting();
        }
    }

    // Inicialización
    function init() {
        console.log('Dashboard initialized');
        loadSidebarState();
        setActiveNavItem();
        addSmoothTransitions();
        addCardHoverEffects();
        addTooltips();
        updateGreeting();
        
        // Animar estadísticas después de un pequeño delay
        setTimeout(animateStats, 500);
        
        // Chat grupal removido: no inicializar notificaciones de chat
        
        // Inicializar efectos de dashboard si están disponibles
        if (typeof DashboardUtils !== 'undefined') {
            setTimeout(() => {
                DashboardUtils.initDashboardEffects();
            }, 100);
        }
    }

    // Ejecutar inicialización cuando el DOM esté listo
    function ensureInit() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        
        // Chat grupal removido: no inicializar notificaciones de chat
    }
    
    ensureInit();

    // Limpiar conexiones al salir de la página
    window.addEventListener('beforeunload', function() {
        if (eventSource) {
            console.log('Cerrando conexión SSE...');
            eventSource.close();
        }
    });
    
    // También cerrar al cambiar de pestaña
    document.addEventListener('visibilitychange', function() {
        // Sin reconexión de SSE ni polling: chat desactivado
        try { if (document.hidden && eventSource) eventSource.close(); } catch(e) {}
    });

    // Exponer funciones globalmente si es necesario
    window.dashboardUtils = {
        toggleSidebar,
        applySidebarState,
        checkChatNotifications,
        updateChatNotificationBadge
    };

    // Manejar expansión de tarjetas con animaciones mejoradas
    function initCardExpansion() {
        // Agregar event listeners para expansión con efectos corporativos
        document.querySelectorAll('.metric-card.expandable').forEach(card => {
            const expandIcon = card.querySelector('.expand-icon');
            const breakdown = card.querySelector('.metric-breakdown');
            
            card.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Prevenir expansión si se hace clic en el breakdown
                if (breakdown && breakdown.contains(e.target)) {
                    return;
                }
                
                const isExpanded = this.classList.contains('expanded');
                
                // Cerrar otras tarjetas expandidas (acordeón)
                document.querySelectorAll('.metric-card.expandable.expanded').forEach(otherCard => {
                    if (otherCard !== this) {
                        otherCard.classList.remove('expanded');
                        const otherIcon = otherCard.querySelector('.expand-icon');
                        if (otherIcon) {
                            otherIcon.style.transform = 'rotate(0deg)';
                        }
                    }
                });
                
                // Toggle la tarjeta actual
                this.classList.toggle('expanded');
                
                // Animar el icono de expansión
                if (expandIcon) {
                    expandIcon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
                }
                
                // Efecto de pulso en la tarjeta
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                
                // Animar los números del breakdown
                if (!isExpanded && breakdown) {
                    const breakdownNumbers = breakdown.querySelectorAll('.breakdown-number');
                    breakdownNumbers.forEach((num, index) => {
                        setTimeout(() => {
                            num.style.animation = 'numberPulse 0.6s ease-out';
                            setTimeout(() => {
                                num.style.animation = '';
                            }, 600);
                        }, index * 100);
                    });
                }
            });
            
            // Efecto hover mejorado
            card.addEventListener('mouseenter', function() {
                if (!this.classList.contains('expanded')) {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                    this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (!this.classList.contains('expanded')) {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                }
            });
        });
    }

    // Inicializar expansión de tarjetas
    initCardExpansion();
});

// Utilidades adicionales
const DashboardUtils = {
    // Formatear números con separadores de miles
    formatNumber: function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },
    
    // Obtener saludo basado en la hora
    getGreeting: function() {
        const hour = new Date().getHours();
        if (hour < 12) return 'Buenos días';
        if (hour < 18) return 'Buenas tardes';
        return 'Buenas noches';
    },
    
    // Validar si es móvil
    isMobile: function() {
        return window.innerWidth <= 768;
    },
    
    // Smooth scroll
    smoothScrollTo: function(element) {
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    },

    // Animar números con efecto de conteo
    animateNumber: function(element, finalValue, duration = 1000) {
        // Modo simple: sin animación; solo actualizar valor formateado
        if (!element) return;
        element.classList.remove('updating');
        element.textContent = DashboardUtils.formatNumber(finalValue);
        element.setAttribute('data-value', finalValue);
    },

    // Agregar efectos de partículas a un elemento
    addParticleEffect: function(element) {
        const particles = document.createElement('div');
        particles.className = 'particle-container';
        
        for (let i = 0; i < 6; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.cssText = `
                position: absolute;
                width: 4px;
                height: 4px;
                background: radial-gradient(circle, rgba(102, 126, 234, 0.8), transparent);
                border-radius: 50%;
                pointer-events: none;
                animation: floatParticle ${2 + Math.random() * 3}s ease-in-out infinite;
                animation-delay: ${Math.random() * 2}s;
                top: ${Math.random() * 100}%;
                left: ${Math.random() * 100}%;
            `;
            particles.appendChild(particle);
        }
        
        element.style.position = 'relative';
        element.appendChild(particles);
    },

    // Inicializar efectos del dashboard
    initDashboardEffects: function() {
        // Modo simple: desactivar animaciones, partículas y parallax
        const cards = document.querySelectorAll('.metric-card, .card');
        cards.forEach(card => {
            card.style.animation = 'none';
            card.style.animationDelay = '';
            card.style.transform = 'none';
        });

        const numbers = document.querySelectorAll('.metric-number, .card-info p');
        numbers.forEach(number => {
            const value = parseInt(number.textContent) || 0;
            number.textContent = DashboardUtils.formatNumber(value);
            number.classList.remove('updating');
            number.setAttribute('data-value', value);
        });

        // No agregar listeners de scroll ni efectos de partículas
    }
};

// CSS para las partículas flotantes
const particleCSS = `
@keyframes floatParticle {
    0%, 100% {
        transform: translateY(0px) translateX(0px) scale(1);
        opacity: 0.7;
    }
    25% {
        transform: translateY(-20px) translateX(10px) scale(1.2);
        opacity: 1;
    }
    50% {
        transform: translateY(-40px) translateX(-5px) scale(0.8);
        opacity: 0.5;
    }
    75% {
        transform: translateY(-20px) translateX(15px) scale(1.1);
        opacity: 0.8;
    }
}

.particle-container {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: hidden;
}
`;

// Agregar CSS de partículas al documento
if (!document.querySelector('#particle-styles')) {
    const style = document.createElement('style');
    style.id = 'particle-styles';
    style.textContent = particleCSS;
    document.head.appendChild(style);
}

// Inicializar efectos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    DashboardUtils.initDashboardEffects();
});

// Hacer disponible globalmente
window.DashboardUtils = DashboardUtils;