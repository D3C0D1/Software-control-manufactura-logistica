// Consulta de Pedido - JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('input[name="numero_guia"]');
    const searchButton = document.querySelector('.btn-search');
    const animationContainer = document.querySelector('.animation-container');

    // Mejorar la experiencia del formulario
    if (searchForm && searchInput && searchButton) {
        // Auto-focus en el campo de búsqueda
        searchInput.focus();

        // Validación en tiempo real
        searchInput.addEventListener('input', function() {
            const value = this.value.trim();
            
            if (value.length === 0) {
                searchButton.disabled = true;
                searchButton.style.opacity = '0.6';
            } else {
                searchButton.disabled = false;
                searchButton.style.opacity = '1';
            }
        });

        // Efecto de loading al enviar el formulario
        searchForm.addEventListener('submit', function() {
            searchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consultando...';
            searchButton.disabled = true;
        });

        // Limpiar campo con doble clic
        searchInput.addEventListener('dblclick', function() {
            this.value = '';
            this.focus();
            searchButton.disabled = true;
            searchButton.style.opacity = '0.6';
        });
    }

    // Animaciones mejoradas para el contenedor de estado
    if (animationContainer) {
        // Agregar efecto de entrada
        animationContainer.style.opacity = '0';
        animationContainer.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            animationContainer.style.transition = 'all 0.6s ease';
            animationContainer.style.opacity = '1';
            animationContainer.style.transform = 'translateY(0)';
        }, 300);

        // Efecto hover mejorado
        animationContainer.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'transform 0.3s ease';
        });

        animationContainer.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    }

    // Efectos para los badges de estado
    const statusBadges = document.querySelectorAll('.status-badge');
    statusBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.transition = 'transform 0.2s ease';
        });

        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Animación de aparición para los elementos de información
    const infoItems = document.querySelectorAll('.info-item');
    infoItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.4s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, 100 * index);
    });

    // Efecto de typing para el número de guía
    const pedidoHeader = document.querySelector('.pedido-header h2');
    if (pedidoHeader) {
        const originalText = pedidoHeader.textContent;
        pedidoHeader.textContent = '';
        
        let i = 0;
        const typeWriter = () => {
            if (i < originalText.length) {
                pedidoHeader.textContent += originalText.charAt(i);
                i++;
                setTimeout(typeWriter, 50);
            }
        };
        
        setTimeout(typeWriter, 500);
    }

    // Función para copiar número de guía al clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showNotification('Número de guía copiado al portapapeles', 'success');
        }).catch(() => {
            // Fallback para navegadores que no soportan clipboard API
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('Número de guía copiado al portapapeles', 'success');
        });
    }

    // Agregar funcionalidad de copia al hacer clic en el número de guía
    if (pedidoHeader) {
        pedidoHeader.style.cursor = 'pointer';
        pedidoHeader.title = 'Clic para copiar el número de guía';
        
        pedidoHeader.addEventListener('click', function() {
            const numeroGuia = this.textContent.replace('Pedido #', '').trim();
            copyToClipboard(numeroGuia);
        });
    }

    // Sistema de notificaciones
    function showNotification(message, type = 'info') {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        // Estilos de la notificación
        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            background: type === 'success' ? '#27ae60' : '#3498db',
            color: 'white',
            padding: '15px 20px',
            borderRadius: '10px',
            boxShadow: '0 5px 15px rgba(0,0,0,0.3)',
            zIndex: '9999',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease'
        });

        document.body.appendChild(notification);

        // Animación de entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Remover después de 3 segundos
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Mejorar accesibilidad con navegación por teclado
    document.addEventListener('keydown', function(e) {
        // Enfocar campo de búsqueda con Ctrl+F
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Enviar formulario con Enter
        if (e.key === 'Enter' && document.activeElement === searchInput) {
            if (searchInput.value.trim()) {
                searchForm.submit();
            }
        }
    });

    // Efecto parallax suave en el scroll
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const header = document.querySelector('.header');
        
        if (header) {
            header.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    });

    // Lazy loading para animaciones pesadas
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);

    // Observar elementos animados
    document.querySelectorAll('.animation-container, .pedido-card, .search-section').forEach(el => {
        observer.observe(el);
    });

    // Agregar clase CSS para animaciones lazy
    const style = document.createElement('style');
    style.textContent = `
        .animate-in {
            animation-play-state: running !important;
        }
        
        .animation-container:not(.animate-in) .animation-icon {
            animation-play-state: paused;
        }
    `;
    document.head.appendChild(style);

    console.log('Consulta de Pedido - JavaScript cargado correctamente');

    // --- Animación según el área actual ---
    // Detectar area_id desde la clase de status-badge (status-<id>)
    const getCurrentAreaId = () => {
        const badge = document.querySelector('.status-badge');
        if (!badge) return null;
        const cls = Array.from(badge.classList).find(c => /^status-\d+$/.test(c));
        if (!cls) return null;
        const id = parseInt(cls.replace('status-', ''), 10);
        return Number.isNaN(id) ? null : id;
    };

    // Mapear area_id a la clave de indicador y al ícono del círculo
    const areaKeyFromId = (id) => {
        // Finalizado
        if ([10].includes(id)) return 'finalizado';
        // Recepción general / Guía generada
        if ([20, 21, 22].includes(id)) return 'recepcion';
        // Diseño (Recepción/Proceso/Preparado)
        if ([1, 2, 3].includes(id)) return 'diseno';
        // Confección (Recepción/Proceso/Preparado)
        if ([4, 5, 6].includes(id)) return 'confeccion';
        // Sublimado (Recepción/Proceso/Preparado)
        if ([7, 8, 9].includes(id)) return 'sublimado';
        // Mensajería (Recepción/Proceso/Preparado)
        if ([11, 12, 13].includes(id)) return 'mensajeria';
        // Impresión (Recepción/Proceso/Preparado) - contempla posible 16
        if ([14, 15, 16].includes(id)) return 'impresion';
        // Control de Calidad (Proceso/Preparado/Final)
        if ([17, 18, 19].includes(id)) return 'control_calidad';
        return null;
    };

    // Nombre base del área según su grupo (evita mostrar "Recepción de X")
    const baseAreaNameFromId = (id) => {
        if ([10].includes(id)) return 'Finalizado';
        if ([1, 2, 3].includes(id)) return 'Diseño';
        if ([4, 5, 6].includes(id)) return 'Confección';
        if ([7, 8, 9].includes(id)) return 'Sublimado';
        if ([11, 12, 13].includes(id)) return 'Mensajería';
        if ([14, 15, 16].includes(id)) return 'Impresión';
        if ([17, 18, 19].includes(id)) return 'Control de Calidad';
        if ([20, 21, 22].includes(id)) return 'Recepción';
        return null;
    };

    const elementIndexFromKey = (key) => ({
        recepcion: 1,
        diseno: 2,
        confeccion: 3,
        sublimado: 4,
        impresion: 5,
        mensajeria: 6,
        control_calidad: 7,
        finalizado: 8,
    })[key] || null;

    const currentAreaId = getCurrentAreaId();
    const currentAreaKey = currentAreaId ? areaKeyFromId(currentAreaId) : null;
    const currentElementIndex = currentAreaKey ? elementIndexFromKey(currentAreaKey) : null;

    // Resaltar indicador de área
    if (currentAreaKey) {
        document.querySelectorAll('.areas-indicator .area-dot').forEach(dot => {
            dot.classList.remove('active');
            dot.classList.remove('pulse');
        });
        const targetDot = document.querySelector(`.areas-indicator .area-dot[data-area="${currentAreaKey}"]`);
        if (targetDot) {
            targetDot.classList.add('active');
            targetDot.classList.add('pulse');
        }
    }

    // Resaltar el ícono correspondiente en el círculo giratorio
    if (currentElementIndex) {
        document.querySelectorAll('.rotating-elements .element').forEach(el => el.classList.remove('highlight'));
        const targetEl = document.querySelector(`.rotating-elements .element-${currentElementIndex}`);
        if (targetEl) {
            targetEl.classList.add('highlight');
        }
    }

    // --- Actualización en tiempo real cada 3 segundos ---
    const numeroGuiaInput = document.querySelector('input[name="numero_guia"]');

    // Mapa de iconos por área (por si necesitamos actualizar el icono del estado)
    const areaIcons = {
        1: 'fas fa-inbox',
        2: 'fas fa-paint-brush',
        3: 'fas fa-check-circle',
        4: 'fas fa-inbox',
        5: 'fas fa-cut',
        6: 'fas fa-check-circle',
        7: 'fas fa-inbox',
        8: 'fas fa-fire',
        9: 'fas fa-check-circle',
        10: 'fas fa-flag-checkered',
        11: 'fas fa-truck',
        12: 'fas fa-truck',
        13: 'fas fa-check-circle',
        14: 'fas fa-print',
        15: 'fas fa-check-circle',
        16: 'fas fa-check-circle',
        17: 'fas fa-shield-alt',
        18: 'fas fa-shield-alt',
        19: 'fas fa-shield-alt',
        20: 'fas fa-inbox',
        21: 'fas fa-inbox',
        22: 'fas fa-inbox'
    };

    const updateAreaUI = (areaId, areaName) => {
        const baseName = baseAreaNameFromId(areaId);
        const displayName = (areaId === 10)
            ? 'Ya puede venir a recojer su pedido en las instalaciones'
            : (areaId === 20)
                ? 'Guía generada'
                : (baseName || areaName || 'Área no definida');
        // Actualizar mensaje de estado en la parte superior
        const currentAreaSpan = document.querySelector('.status-message .current-area');
        if (currentAreaSpan) {
            currentAreaSpan.textContent = displayName;
        }

        // Actualizar badge de estado (texto + clase status-<id>)
        const badge = document.querySelector('.status-badge');
        if (badge) {
            // Remover clases previas status-<id>
            Array.from(badge.classList).forEach(c => {
                if (/^status-\d+$/.test(c)) badge.classList.remove(c);
            });
            if (areaId) badge.classList.add(`status-${areaId}`);
            badge.textContent = displayName;
        }

        // Actualizar icono del contenedor de animación de estado
        const animationIcon = document.querySelector('.animation-container .animation-icon i');
        if (animationIcon) {
            const cls = areaIcons[areaId] || 'fas fa-question-circle';
            animationIcon.className = cls;
        }

        // Actualizar highlight de indicadores y círculo
        const key = areaKeyFromId(areaId);
        const idx = elementIndexFromKey(key);

        // Indicadores
        document.querySelectorAll('.areas-indicator .area-dot').forEach(dot => {
            dot.classList.remove('active');
            dot.classList.remove('pulse');
        });
        if (key) {
            const targetDot = document.querySelector(`.areas-indicator .area-dot[data-area="${key}"]`);
            if (targetDot) {
                targetDot.classList.add('active');
                targetDot.classList.add('pulse');
            }
        }

        // Círculo
        document.querySelectorAll('.rotating-elements .element').forEach(el => el.classList.remove('highlight'));
        if (idx) {
            const targetEl = document.querySelector(`.rotating-elements .element-${idx}`);
            if (targetEl) targetEl.classList.add('highlight');
        }
    };

    const startPolling = () => {
        const guia = (numeroGuiaInput && numeroGuiaInput.value.trim()) || '';
        if (!guia) return; // No hay guía, no se inicia polling

        const fetchAndUpdate = () => {
            fetch(`php/get_area_by_guia.php?numero_guia=${encodeURIComponent(guia)}`, { cache: 'no-store' })
                .then(resp => resp.json())
                .then(data => {
                    if (data && data.success) {
                        updateAreaUI(data.area_id, data.nombre_area);
                    }
                })
                .catch(() => {
                    // Silenciar errores de red para no molestar al usuario
                });
        };

        // Primera actualización inmediata, luego cada 3s
        fetchAndUpdate();
        setInterval(fetchAndUpdate, 3000);
    };

    startPolling();
});