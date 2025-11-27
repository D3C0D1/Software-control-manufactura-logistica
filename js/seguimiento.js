document.addEventListener('DOMContentLoaded', function() {
    const trackingForm = document.getElementById('tracking-form');
    const numeroGuiaInput = document.getElementById('numero_guia');
    const resultadoBusqueda = document.getElementById('resultado-busqueda');
    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));

    trackingForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const numeroGuia = numeroGuiaInput.value.trim();

        if (numeroGuia === '') {
            alert('Por favor, ingrese un número de guía.');
            return;
        }

        fetch(`php/get_estado_pedido.php?guia=${numeroGuia}`)
            .then(response => response.json())
            .then(data => {
                resultadoBusqueda.innerHTML = '';
                if (data.error) {
                    resultadoBusqueda.innerHTML = `<p>${data.error}</p>`;
                } else {
                    displayPedidoInfo(data);
                    showModal(data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultadoBusqueda.innerHTML = '<p>Ocurrió un error al buscar el pedido.</p>';
            });
    });

    function displayPedidoInfo(data) {
        const infoDiv = document.createElement('div');
        infoDiv.className = 'pedido-info';
        infoDiv.innerHTML = `
            <p><strong><i class="fas fa-box"></i> Nombre:</strong> ${data.nombre_pedido}</p>
            <p><strong><i class="fas fa-info-circle"></i> Estado:</strong> ${data.estado}</p>
            <p><strong><i class="fas fa-map-marker-alt"></i> Área Actual:</strong> ${data.area}</p>
        `;
        resultadoBusqueda.appendChild(infoDiv);
    }

    function showModal(data) {
        const timeline = document.querySelector('#statusModal .timeline');
        timeline.innerHTML = createTimelineHTML(data.area_id);

        const modalMessage = document.querySelector('#statusModal #modal-message');
        const isFinalizado = parseInt(data.area_id, 10) === 10;
        if (isFinalizado) {
            const finalMessage = 'Ya puede venir a recojer su pedido en las instalaciones';
            modalMessage.textContent = finalMessage;
            modalMessage.classList.add('finalizado-text');
            // Añadir banner visual destacado con el mismo texto
            const banner = document.createElement('div');
            banner.className = 'ready-banner';
            banner.innerHTML = `<i class="fas fa-check-circle"></i> ${finalMessage}`;
            modalMessage.parentElement.appendChild(banner);
        } else {
            modalMessage.textContent = 'Tu pedido está en camino.';
        }

        statusModal.show();
    }

    function createTimelineHTML(currentAreaId) {
        const id = parseInt(currentAreaId, 10);
        const stages = [
            { name: 'Recepción', icon: 'fa-receipt', ids: [1,4,7,14,17,20] },
            { name: 'Mensajería', icon: 'fa-truck', ids: [11,12,13] },
            { name: 'Diseño', icon: 'fa-paint-brush', ids: [2,3] },
            { name: 'Impresión', icon: 'fa-print', ids: [14,15,16] },
            { name: 'Sublimación', icon: 'fa-tshirt', ids: [7,8,9] },
            { name: 'Confección', icon: 'fa-cut', ids: [4,5,6] },
            { name: 'Control Calidad', icon: 'fa-check-circle', ids: [17,21,22] },
            { name: 'Finalizado', icon: 'fa-box-open', ids: [10] }
        ];

        // Determinar el índice de etapa actual según el area_id
        let currentStageIndex = 0;
        for (let i = 0; i < stages.length; i++) {
            if (stages[i].ids.includes(id)) {
                currentStageIndex = i;
                break;
            }
        }

        // Generar HTML con animaciones secuenciales (delays incrementales)
        let html = '';
        stages.forEach((stage, index) => {
            const isActive = index <= currentStageIndex ? 'active' : '';
            const delay = index * 0.25; // 250ms entre pasos
            html += `
                <li class="timeline-item ${isActive}" style="animation-delay: ${delay}s">
                    <div class="timeline-icon"><i class="fas ${stage.icon}"></i></div>
                    <p>${stage.name}</p>
                </li>
            `;
        });
        return html;
    }

    // Cerrar modal ya no es necesario con Bootstrap
});