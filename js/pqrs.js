document.addEventListener('DOMContentLoaded', function() {
    const consultaForm = document.getElementById('consulta-form');
    const modal = document.getElementById('pqrs-modal');
    const modalBody = document.getElementById('modal-body');
    const closeModal = document.querySelector('.close-button');

    if (consultaForm) {
        consultaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const radicado = document.getElementById('radicado').value;

            if (radicado) {
                fetch(`php/get_pqrs_details.php?radicado=${radicado}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            displayPqrsDetails(data);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al consultar la PQRS.');
                    });
            }
        });
    }

    function displayPqrsDetails(data) {
        modalBody.innerHTML = `
            <h3>Detalles de la PQRS - Radicado: ${data.radicado}</h3>
            <p><strong>Fecha:</strong> ${data.fecha}</p>
            <p><strong>Nombre:</strong> ${data.nombre}</p>
            <p><strong>Email:</strong> ${data.email}</p>
            <p><strong>Teléfono:</strong> ${data.telefono}</p>
            <p><strong>Tipo:</strong> ${data.tipo_pqrs}</p>
            <p><strong>Asunto:</strong> ${data.asunto}</p>
            <p><strong>Mensaje:</strong> ${data.mensaje}</p>
            <p><strong>Estado:</strong> ${data.estado}</p>
            <p><strong>Respuesta:</strong> ${data.respuesta || 'Aún no hay respuesta.'}</p>
        `;
        if (data.archivo) {
            modalBody.innerHTML += `<p><strong>Archivo Adjunto:</strong> <a href="uploads/pqrs/${data.archivo}" target="_blank">Ver Archivo</a></p>`;
        }
        modal.style.display = 'block';
    }

    if (closeModal) {
        closeModal.onclick = function() {
            modal.style.display = 'none';
        }
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});