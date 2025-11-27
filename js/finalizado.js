document.addEventListener('DOMContentLoaded', function() {
    const columnaFinalizado = document.querySelector('#columna-finalizado .pedidos-lista');
    const modal = document.getElementById('view-modal');
    const closeModal = document.querySelector('#view-modal .close-button');
    const pedidoDetails = document.getElementById('pedido-details');

    function cargarPedidosFinalizados() {
        fetch('php/get_pedidos_finalizados.php') // This script needs to be created
            .then(response => response.json())
            .then(data => {
                columnaFinalizado.innerHTML = '';
                data.forEach(pedido => {
                    const pedidoCard = document.createElement('div');
                    pedidoCard.classList.add('pedido-card', 'ready-pickup');
                    pedidoCard.dataset.id = pedido.id;
                    pedidoCard.innerHTML = `
                        <h3>Pedido #${pedido.id}</h3>
                        <p><strong>Cliente:</strong> ${pedido.nombre_cliente}</p>
                        <p><strong>Estado:</strong> ${pedido.area}</p>
                        <div class="pickup-banner"><i class="fas fa-store"></i> Disponible para recoger</div>
                        <div class="pedido-actions">
                            <button class="btn-visualizar">Visualizar</button>
                        </div>
                    `;
                    columnaFinalizado.appendChild(pedidoCard);
                });
            });
    }

    document.body.addEventListener('click', function(e) {
        const target = e.target;
        const card = target.closest('.pedido-card');
        if (!card) return;

        const id = card.dataset.id;

        if (target.classList.contains('btn-visualizar')) {
            fetch(`php/get_pedido.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let adjuntosHtml = '';
                        if (data.pedido.adjuntos && data.pedido.adjuntos.length > 0) {
                            adjuntosHtml += '<h4>Archivos Adjuntos:</h4><ul>';
                            data.pedido.adjuntos.forEach(adjunto => {
                                const fileName = adjunto.ruta_archivo.split('/').pop();
                                adjuntosHtml += `<li><a href="${adjunto.ruta_archivo}" target="_blank">${fileName}</a></li>`;
                            });
                            adjuntosHtml += '</ul>';
                        } else {
                            adjuntosHtml += '<p>No hay archivos adjuntos.</p>';
                        }

                        pedidoDetails.innerHTML = `
                            <p><strong>Guía:</strong> ${data.pedido.numero_guia}</p>
                            <p><strong>Cliente:</strong> ${data.pedido.nombre_cliente}</p>
                            <p><strong>Email:</strong> ${data.pedido.correo_cliente}</p>
                            <p><strong>Móvil:</strong> ${data.pedido.numero_cliente}</p>
                            <p><strong>Notas:</strong> ${data.pedido.notas}</p>
                            ${adjuntosHtml}
                        `;
                        viewModal.style.display = 'block';
                    } else {
                        alert(data.message);
                    }
                });
        }
    });

    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    cargarPedidosFinalizados();
setInterval(cargarPedidosFinalizados, 15000);
});