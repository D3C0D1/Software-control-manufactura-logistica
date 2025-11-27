<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}
require 'php/db_connection.php';
$nombre_usuario = $_SESSION['usuario'];
$rol_id = $_SESSION['rol_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de PQRS - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/pqrs_management.css">
</head>
<body class="dashboard-page">
    <div class="dashboard-layout">
        <!-- Header Superior con Hamburger Menu y Perfil -->
        <?php include 'php/dashboard_header.php'; ?>
        
        <!-- Sidebar como cuadrado separado -->
        <?php include 'php/sidebar.php'; ?>
        <main class="main-content">
            <header class="main-header">
                <div class="header-title">
                    <h1>Gestión de PQRS</h1>
                    <p>Administra las peticiones, quejas, reclamos y sugerencias.</p>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>

            <section class="content-section">
                <div class="pqrs-toolbar">
                    <form action="" method="GET">
                        <input type="text" name="search" placeholder="Buscar por asunto o usuario..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <select name="status">
                            <option value="">Todos los estados</option>
                            <option value="Abierto" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Abierto') ? 'selected' : ''; ?>>Abierto</option>
                            <option value="En Proceso" <?php echo (isset($_GET['status']) && $_GET['status'] == 'En Proceso') ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="Cerrado" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                        </select>
                        <button type="submit" class="btn">Filtrar</button>
                    </form>
                </div>

                <div class="pqrs-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Radicado</th>
                                <th>Usuario</th>
                                <th>Tipo</th>
                                <th>Mensaje</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $search = $_GET['search'] ?? '';
                            $status = $_GET['status'] ?? '';

                            $sql = "SELECT p.id, p.radicado, p.nombre_completo, p.tipo_solicitud, p.mensaje, p.estado, p.fecha_creacion FROM pqrs p WHERE 1=1";

                            $params = [];
                            $types = '';

                            if (!empty($search)) {
                                $sql .= " AND (mensaje LIKE ? OR nombre_completo LIKE ? OR radicado LIKE ?)";
                                $searchTerm = "%{$search}%";
                                $params = [$searchTerm, $searchTerm, $searchTerm];
                                $types .= 'sss';
                            }
                            if (!empty($status)) {
                                $sql .= " AND estado = ?";
                                $params[] = $status;
                                $types .= 's';
                            }
                            $sql .= " ORDER BY fecha_creacion DESC";

                            $stmt = $conn->prepare($sql);

                            if ($stmt === false) {
                                // Muestra el error si la preparación de la consulta falla
                                die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error));
                            }

                            if (!empty($types)) {
                                $stmt->bind_param($types, ...$params);
                            }

                            $stmt->execute();
                            $result = $stmt->get_result();

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row["radicado"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["nombre_completo"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["tipo_solicitud"]) . "</td>";
                                    echo "<td>" . htmlspecialchars(substr($row["mensaje"], 0, 50)) . "...</td>";
                                    echo "<td><span class='status-" . strtolower(str_replace(' ', '-', htmlspecialchars($row["estado"]))) . "'>" . htmlspecialchars($row["estado"]) . "</span></td>";
                                    echo "<td>" . $row["fecha_creacion"] . "</td>";
                                    echo "<td class='actions'><a href='#' class='btn-view' data-id='" . $row['id'] . "'><i class='fas fa-eye'></i></a></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7'>No se encontraron PQRS.</td></tr>";
                            }
                            $stmt->close();
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal para ver y responder PQRS -->
    <div id="pqrs-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Detalles de la PQRS</h2>
            <div id="pqrs-details"></div>
            <form id="pqrs-response-form">
                <input type="hidden" id="pqrs_id" name="pqrs_id">
                <div class="form-group">
                    <label for="respuesta">Respuesta:</label>
                    <textarea id="respuesta" name="respuesta" rows="4" required></textarea>
                </div>
                <!-- Se elimina el selector de estado -->
                <button type="submit" class="btn">Enviar Respuesta</button>
            </form>
        </div>
    </div>

<script src="js/dashboard.js" defer></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('pqrs-modal');
        const closeButton = document.querySelector('.close-button');
        const viewButtons = document.querySelectorAll('.btn-view');

        viewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const pqrsId = this.dataset.id;
                fetch(`php/get_pqrs_details.php?id=${pqrsId}`)
                    .then(response => response.json())
                    .then(data => {
                        if(data.error) {
                            alert(data.error);
                            return;
                        }
                        document.getElementById('pqrs_id').value = data.id;
                        const detailsDiv = document.getElementById('pqrs-details');
                        detailsDiv.innerHTML = `
                            <p><strong>Radicado:</strong> ${data.radicado}</p>
                            <p><strong>Nombre:</strong> ${data.nombre_completo}</p>
                            <p><strong>Email:</strong> ${data.email}</p>
                            <p><strong>Teléfono:</strong> ${data.telefono}</p>
                            <p><strong>Tipo:</strong> ${data.tipo_solicitud}</p>
                            <p><strong>Mensaje:</strong> ${data.mensaje}</p>
                            <p><strong>Estado:</strong> ${data.estado}</p>
                            <p><strong>Fecha Creación:</strong> ${data.fecha_creacion}</p>
                            <p><strong>Respuesta:</strong> ${data.respuesta || 'Sin respuesta'}</p>
                            <p><strong>Respondido por:</strong> ${data.respondido_por || 'N/A'}</p>
                            ${data.archivo_adjunto ? `<p><strong>Archivo Adjunto:</strong> <a href="${data.archivo_adjunto}" target="_blank">Descargar Archivo</a></p>` : ''}
                        `;
                        document.getElementById('respuesta').value = data.respuesta || '';
                        modal.style.display = 'block';
                    });
            });
        });

        closeButton.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });

        const responseForm = document.getElementById('pqrs-response-form');
        responseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('php/update_pqrs.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.success);
                    modal.style.display = 'none';
                    location.reload(); // Recargar la página para ver los cambios
                } else {
                    alert(data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al enviar la respuesta.');
            });
        });
    });
    
    // Función para actualizar la actividad del usuario
    function updateUserActivity() {
        fetch('php/update_user_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error updating user activity:', data.message);
            }
        })
        .catch(error => {
            console.error('Error updating user activity:', error);
        });
    }

    // Actualizar actividad cada 5 minutos
    setInterval(updateUserActivity, 5 * 60 * 1000);
    
    // Actualizar actividad al cargar la página
    updateUserActivity();
    </script>
</body>
</html>