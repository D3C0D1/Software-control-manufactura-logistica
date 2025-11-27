// Configuración JavaScript para la página de configuración - Glamcity
document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM
    const smsForm = document.getElementById('smsForm');
    const configForm = document.getElementById('configForm');
    
    // Función para mostrar alertas
    function showAlert(message, type = 'success') {
        // Remover alertas existentes
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Crear nueva alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
        `;
        
        // Insertar al inicio del contenedor
        const container = document.querySelector('.config-container');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
    
    // Función para validar número de teléfono
    function validatePhoneNumber(phone) {
        // Formato internacional: +57XXXXXXXXXX (Colombia)
        const phoneRegex = /^\+\d{10,15}$/;
        return phoneRegex.test(phone);
    }
    
    // Función para validar campos requeridos
    function validateRequiredFields(form) {
        const requiredFields = form.querySelectorAll('input[required], textarea[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#ef4444';
                isValid = false;
            } else {
                field.style.borderColor = '#e5e7eb';
            }
        });
        
        return isValid;
    }
    
    // Función para mostrar estado de carga
    function setLoadingState(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
            button.classList.add('loading');
        } else {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar SMS de Prueba';
            button.classList.remove('loading');
        }
    }
    
    // Manejo del formulario SMS
    if (smsForm) {
        smsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar campos requeridos
            if (!validateRequiredFields(this)) {
                showAlert('Por favor, completa todos los campos requeridos.', 'error');
                return;
            }
            
            // Validar número de teléfono
            const phoneNumber = document.getElementById('tuNumeroDeCelular').value;
            if (!validatePhoneNumber(phoneNumber)) {
                showAlert('El número de teléfono debe estar en formato internacional (+57XXXXXXXXXX).', 'error');
                return;
            }
            
            const submitButton = this.querySelector('button[type="submit"]');
            setLoadingState(submitButton, true);
            
            // Crear FormData
            const formData = new FormData(this);
            
            // Enviar datos via AJAX
            fetch('config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Buscar mensajes de éxito o error en la respuesta
                if (data.includes('¡ÉXITO!') || data.includes('enviado')) {
                    showAlert('¡SMS enviado exitosamente! Revisa tu teléfono.', 'success');
                    // Limpiar solo el campo del mensaje
                    document.getElementById('mensaje').value = '';
                } else if (data.includes('ERROR') || data.includes('error')) {
                    showAlert('Error al enviar el SMS. Verifica la configuración de Sinch.', 'error');
                } else {
                    showAlert('SMS procesado. Revisa la consola para más detalles.', 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión. Inténtalo de nuevo.', 'error');
            })
            .finally(() => {
                setLoadingState(submitButton, false);
            });
        });
    }
    
    // Manejo del formulario de configuración
    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar campos requeridos
            if (!validateRequiredFields(this)) {
                showAlert('Por favor, completa todos los campos requeridos.', 'error');
                return;
            }
            
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            
            // Crear FormData
            const formData = new FormData(this);
            
            // Enviar datos via AJAX
            fetch('config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('actualizada') || data.includes('guardada')) {
                    showAlert('Configuración actualizada correctamente.', 'success');
                } else {
                    showAlert('Error al actualizar la configuración.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión. Inténtalo de nuevo.', 'error');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        });
    }
    
    // Función para formatear número de teléfono automáticamente
    const phoneInput = document.getElementById('tuNumeroDeCelular');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remover caracteres no numéricos
            
            // Si no empieza con +, agregarlo
            if (value && !e.target.value.startsWith('+')) {
                // Si empieza con 57 (Colombia), agregar +
                if (value.startsWith('57')) {
                    e.target.value = '+' + value;
                } else if (value.startsWith('3')) {
                    // Si empieza con 3 (número móvil colombiano), agregar +57
                    e.target.value = '+57' + value;
                } else {
                    e.target.value = '+' + value;
                }
            }
        });
        
        // Placeholder dinámico
        phoneInput.setAttribute('placeholder', '+573001234567');
    }
    
    // Función para copiar información del sistema
    function copySystemInfo() {
        const systemInfo = document.querySelector('.info-grid');
        if (systemInfo) {
            const infoText = Array.from(systemInfo.querySelectorAll('.info-item'))
                .map(item => {
                    const label = item.querySelector('label').textContent;
                    const value = item.querySelector('span').textContent;
                    return `${label}: ${value}`;
                })
                .join('\n');
            
            navigator.clipboard.writeText(infoText).then(() => {
                showAlert('Información del sistema copiada al portapapeles.', 'success');
            }).catch(() => {
                showAlert('No se pudo copiar la información.', 'error');
            });
        }
    }
    
    // Agregar botón de copiar información del sistema
    const systemInfoCard = document.querySelector('.config-card:first-child');
    if (systemInfoCard) {
        const copyButton = document.createElement('button');
        copyButton.type = 'button';
        copyButton.className = 'btn-secondary';
        copyButton.innerHTML = '<i class="fas fa-copy"></i> Copiar Info';
        copyButton.style.marginTop = '1rem';
        copyButton.addEventListener('click', copySystemInfo);
        
        systemInfoCard.querySelector('.card-body').appendChild(copyButton);
    }
    
    // Validación en tiempo real para campos de entrada
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });
        
        input.addEventListener('focus', function() {
            this.style.borderColor = '#667eea';
        });
    });
    
    // Función para actualizar el reloj (si existe en el header)
    function updateClock() {
        const clockElement = document.getElementById('reloj');
        if (clockElement) {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-CO', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            clockElement.textContent = timeString;
        }
    }
    
    // Actualizar reloj cada segundo
    setInterval(updateClock, 1000);
    updateClock(); // Llamada inicial
    
    // Animaciones de entrada para las tarjetas
    const cards = document.querySelectorAll('.config-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1
    });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
    
    console.log('Config-page.js cargado correctamente - Glamcity Dashboard');
});