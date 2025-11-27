// Modal de Área - JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('areaModal');
    const modalTitle = document.getElementById('areaModalTitle');
    const modalClose = document.querySelector('.area-modal-close');
    const areaModalTriggers = document.querySelectorAll('.area-modal-trigger');
    
    // Elementos del modal para mostrar datos
    const modalRecepcionCount = document.getElementById('modalRecepcionCount');
    const modalProcesoCount = document.getElementById('modalProcesoCount');
    const modalPreparadoCount = document.getElementById('modalPreparadoCount');
    const modalTotalCount = document.getElementById('modalTotalCount');
    
    // Abrir modal cuando se hace clic en una métrica de área
    areaModalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const areaName = this.getAttribute('data-area-name');
            const recepcionElement = this.querySelector('.metric-preview .preview-item:nth-child(1) .preview-number');
            const procesoElement = this.querySelector('.metric-preview .preview-item:nth-child(2) .preview-number');
            const preparadoElement = this.querySelector('.metric-preview .preview-item:nth-child(3) .preview-number');
            
            // Obtener los valores de las métricas
            const recepcionCount = recepcionElement ? recepcionElement.textContent : '0';
            const procesoCount = procesoElement ? procesoElement.textContent : '0';
            const preparadoCount = preparadoElement ? preparadoElement.textContent : '0';
            
            // Calcular total
            const total = parseInt(recepcionCount) + parseInt(procesoCount) + parseInt(preparadoCount);
            
            // Actualizar contenido del modal
            modalTitle.textContent = `Detalles de ${areaName}`;
            modalRecepcionCount.textContent = recepcionCount;
            modalProcesoCount.textContent = procesoCount;
            modalPreparadoCount.textContent = preparadoCount;
            modalTotalCount.textContent = total;
            
            // Mostrar modal con animación
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Agregar animación secuencial a las tarjetas
            setTimeout(() => {
                const statCards = modal.querySelectorAll('.area-stat-card');
                statCards.forEach((card, index) => {
                    card.style.animationDelay = `${index * 0.1}s`;
                    card.classList.add('animate-in');
                });
                
                setTimeout(() => {
                    const totalCard = modal.querySelector('.total-card');
                    totalCard.classList.add('animate-in');
                }, 300);
            }, 100);
        });
    });
    
    // Cerrar modal
    function closeModal() {
        modal.classList.remove('show');
        document.body.style.overflow = 'auto';
        
        // Remover clases de animación
        const animatedElements = modal.querySelectorAll('.animate-in');
        animatedElements.forEach(element => {
            element.classList.remove('animate-in');
        });
    }
    
    // Cerrar modal al hacer clic en la X
    modalClose.addEventListener('click', closeModal);
    
    // Cerrar modal al hacer clic fuera del contenido
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Cerrar modal con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });
    
    // Animaciones secuenciales eliminadas para simplificar el dashboard
    
    // Agregar estilos CSS dinámicos para la animación de hover automático
    const style = document.createElement('style');
    style.textContent = `
        .area-modal-trigger.auto-hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
            background: rgba(255, 255, 255, 0.98) !important;
        }
        
        .area-modal-trigger.auto-hover .metric-icon {
            transform: scale(1.1) !important;
        }
        
        .animate-in {
            animation: slideInUp 0.6s ease-out forwards !important;
        }
        
        .pulse-animation {
            animation: pulseNumber 1s ease-in-out !important;
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes pulseNumber {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
                color: #007bff;
            }
            100% {
                transform: scale(1);
            }
        }
    `;
    document.head.appendChild(style);
});