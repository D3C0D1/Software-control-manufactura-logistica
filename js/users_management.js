document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('user-modal');
    const addUserBtn = document.getElementById('add-user-btn');
    const closeBtn = document.querySelector('.close-button');
    const userForm = document.getElementById('user-form');
    const modalTitle = document.getElementById('modal-title');
    const tableBody = document.querySelector('.content-table tbody');
    const avatarPreview = document.getElementById('avatar-preview');
    const fotoPerfilInput = document.getElementById('foto_perfil');
    const defaultAvatar = 'https://placehold.co/100x100';

    // --- Funciones para el Modal ---
    function openModal() {
        modal.style.display = 'block';
    }

    function closeModal() {
        modal.style.display = 'none';
        userForm.reset();
        document.getElementById('user-id').value = '';
        avatarPreview.src = defaultAvatar; // Reset avatar
    }

    if(addUserBtn) {
        addUserBtn.addEventListener('click', () => {
            modalTitle.textContent = 'Agregar Usuario';
            populateSelects();
            openModal();
        });
    }

    if(closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    window.addEventListener('click', (event) => {
        if (event.target == modal) {
            closeModal();
        }
    });

    // Preview de la foto de perfil
    if (fotoPerfilInput) {
        fotoPerfilInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                avatarPreview.src = defaultAvatar;
            }
        });
    }

    // --- Lógica del CRUD ---

    async function populateSelects(selectedRole = null, selectedArea = null) {
        try {
            const response = await fetch('php/get_user_data.php');
            const data = await response.json();

            const rolSelect = document.getElementById('rol_id');
            const areaSelect = document.getElementById('area_id');
            rolSelect.innerHTML = '';
            areaSelect.innerHTML = '<option value="">Sin área</option>';

            data.roles.forEach(rol => {
                const option = document.createElement('option');
                option.value = rol.id;
                option.textContent = rol.nombre_rol;
                if(rol.id == selectedRole) option.selected = true;
                rolSelect.appendChild(option);
            });

            data.areas.forEach(area => {
                const option = document.createElement('option');
                option.value = area.id;
                option.textContent = area.nombre;
                if(area.id == selectedArea) option.selected = true;
                areaSelect.appendChild(option);
            });

        } catch (error) {
            console.error('Error al cargar roles y áreas:', error);
        }
    }

    // La función openEditModal ya no es necesaria, la eliminamos.

    // Guardar (Crear)
    if (userForm) { // Asegurarse de que el formulario exista (está en el modal de agregar)
        userForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(userForm);

            try {
                const response = await fetch('php/save_user.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    closeModal();
                    location.reload(); // Recargar para ver los cambios
                }
            } catch (error) {
                console.error('Error al guardar el usuario:', error);
                alert('Ocurrió un error al guardar.');
            }
        });
    }

    // Eliminar
    async function deleteUser(userId) {
        if (!confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id', userId);

            const response = await fetch('php/delete_user.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                location.reload(); // Recargar para ver los cambios
            }
        } catch (error) {
            console.error('Error al eliminar el usuario:', error);
            alert('Ocurrió un error al eliminar.');
        }
    }

    // Delegación de eventos para los botones de la tabla
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            // La lógica para el botón de editar se elimina de aquí
            const deleteButton = e.target.closest('.btn-delete');

            if (deleteButton) {
                const userId = deleteButton.dataset.id;
                if (userId) {
                    deleteUser(userId);
                }
            }
        });
    }
});