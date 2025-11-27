<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PQRS - Glamcity</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/pqrs_new.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>

        <header>
    <!-- Navbar Starts -->
    <div class="navbar">
      <div class="nav-logo border">
        <div class="logo"></div>
      </div>
      <div class="nav-address border">
        <p class="add-first">Tiendas en</p>
        <div class="add-icon">
          <i class="fa-solid fa-location-dot"></i>
          <p class="add-second">Corozal, Sucre</p>
        </div>
      </div>
      <div class="nav-search">
  <select id="categorySelect" class="search-select">
    <option value="index.php">Todo</option>
    <option value="tienda.php?category=19">Mobiliaria</option>
    <option value="tienda.php?category=1">Boutique</option>
    <option value="tienda.php?category=2">Estetica</option>
    <option value="tienda.php?category=3">Drogerias</option>
    <option value="tienda.php?category=5">Tiendas Naturistas</option>
    <option value="tienda.php?category=4">Restaurantes</option>
    <option value="tienda.php?category=9">Carniceria</option>
    <option value="tienda.php?category=16">Heladerías</option>
    <!-- Agrega las demás categorías que necesites -->
  </select>
  <input id="searchInput" placeholder="Buscar tiendas,restaurantes,productos...." class="search-input">
  <div class="search-icon" id="searchIcon">
  <i class="fa-solid fa-magnifying-glass"></i>
</div>

</div>

<script>
  // Al cambiar de opción se redirige a la URL asignada en el value
  document.getElementById('categorySelect').addEventListener('change', function() {
    var url = this.value;
    if(url) {
      window.location.href = url;
    }
  });
</script>

      <div class="nav-signin border">
        <p><span>Hola, Bienvenido</span></p>
        <p class="nav-second">Conoce a las tiendas</p>
      </div>
      <div class="nav-return border">
        <p><span>También</span></p>
        <p class="nav-second">Puedes contactarnos</p>
      </div>
      <style>
    .social-icons {
      display: flex;
      gap: 10px; /* Espacio entre iconos */
      align-items: center;
    }
    .social-icons a img {
      width: 30px;      /* Tamaño del icono, ajústalo según necesites */
      height: 30px;
      border-radius: 10px; /* Bordes redondeados */
      object-fit: cover;   /* Se ajusta a su contenedor */
      transition: transform 0.2s ease;
    }
    .social-icons a img:hover {
      transform: scale(1.1); /* Efecto al pasar el ratón */
    }
  </style>

    <div class="social-icons">
      <a href="https://www.instagram.com/glamcity_corozal/" target="_blank">
        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram">
      </a>
      <a href="https://www.facebook.com/share/1B89L7NzCk/" target="_blank">
        <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/Facebook_f_logo_%282019%29.svg" alt="Facebook">
      </a>
      <a href="mailto:Glamcityredes2025@gmail.com">
        <img src="https://upload.wikimedia.org/wikipedia/commons/7/7e/Gmail_icon_%282020%29.svg" alt="Email" style="width: 30px; height: 30px;">
      </a>
      <a href="https://api.whatsapp.com/send/?phone=573001095796" target="_blank">
        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg" alt="WhatsApp">
      </a>
    </div>
    </div>
    <!-- Navbar Ends -->

    <!-- Navbar Panel Starts -->
    <div class="panel">
      <div class="panel-all">
      </div>
      <div class="panel-ops">
        <p><a href="login.php" style="color: inherit; text-decoration: none;"><i class="fas fa-user-tie"></i> Empleados</a></p>
        <p><a href="#shop-section" style="color: inherit; text-decoration: none;"><i class="fas fa-box-open"></i> Productos</a></p>
        <p><a href="#nosotros" style="color: inherit; text-decoration: none;"><i class="fas fa-users"></i> Nosotros</a></p>
        <p><a href="#portafolio" style="color: inherit; text-decoration: none;"><i class="fas fa-briefcase"></i> Portafolio</a></p>
        <p><a href="#newsletter" style="color: inherit; text-decoration: none;"><i class="fas fa-envelope-open-text"></i> Boletín</a></p>
        <p><a href="#nosotros" style="color: inherit; text-decoration: none;"><i class="fas fa-gem"></i> Valores</a></p>
        <p><a href="pqrs_info.html" style="color: inherit; text-decoration: none;"><i class="fas fa-question-circle"></i> PQRS</a></p>
      </div>
      <div class="panel-deals">
        Soluciones Creativas para Marcas Imparables
      </div>
    </div>
    <!-- Navbar Panel Ends -->
  </header>

    <main class="pqrs-container">
        <a href="pqrs_info.html" class="back-link">&larr; Volver a Información</a>

        <?php
        if (isset($_SESSION['pqrs_status'])) {
            if ($_SESSION['pqrs_status'] === 'success') {
                echo '<div class="pqrs-success-message" style="text-align: center; padding: 2rem; border: 1px solid #28a745; background-color: #d4edda; color: #155724; margin-bottom: 1rem; border-radius: 5px;">';
                echo '<h3>¡Tu solicitud ha sido radicada con éxito!</h3>';
                echo '<p>Tu número de radicado es: <strong>' . htmlspecialchars($_SESSION['pqrs_radicado']) . '</strong></p>';
                echo '<p>Guarda este número para consultar el estado de tu solicitud más adelante.</p>';
                echo '<a href="pqrs.php" class="btn-submit" style="display: inline-block; margin-top: 1rem;">Radicar otra PQRSF</a>';
                echo '</div>';
            } else {
                echo '<div class="pqrs-error-message" style="text-align: center; padding: 2rem; border: 1px solid #dc3545; background-color: #f8d7da; color: #721c24; margin-bottom: 1rem; border-radius: 5px;">';
                echo '<h3>Hubo un error al procesar tu solicitud.</h3>';
                echo '<p>' . (isset($_SESSION['pqrs_message']) ? htmlspecialchars($_SESSION['pqrs_message']) : 'Por favor, inténtalo de nuevo.') . '</p>';
                echo '</div>';
            }
            // Limpiar las variables de sesión
            unset($_SESSION['pqrs_status']);
            unset($_SESSION['pqrs_radicado']);
            unset($_SESSION['pqrs_message']);
        } else {
        ?>

        <div class="form-pqrs">
            <h2><i class="fas fa-edit"></i> Radicar una PQRSF</h2>
            <form action="php/submit_pqrs.php" method="POST" enctype="multipart/form-data">
                <div class="pqrs-type-group">
                    <label><strong>Tipo de Solicitud</strong></label>
                    <div class="pqrs-type-options">
                        <input type="radio" id="felicitacion" name="tipo_pqrs" value="Felicitacion" required><label for="felicitacion"><i class="fas fa-grin-stars"></i> Felicitación</label>
                        <input type="radio" id="peticion" name="tipo_pqrs" value="Peticion"><label for="peticion"><i class="fas fa-question-circle"></i> Petición</label>
                        <input type="radio" id="queja" name="tipo_pqrs" value="Queja"><label for="queja"><i class="fas fa-angry"></i> Queja</label>
                        <input type="radio" id="reclamo" name="tipo_pqrs" value="Reclamo"><label for="reclamo"><i class="fas fa-exclamation-triangle"></i> Reclamo</label>
                        <input type="radio" id="sugerencia" name="tipo_pqrs" value="Sugerencia"><label for="sugerencia"><i class="fas fa-lightbulb"></i> Sugerencia</label>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <i class="fas fa-user"></i>
                        <label for="nombre">Nombre Completo</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <i class="fas fa-envelope"></i>
                        <label for="email">Correo Electrónico</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <i class="fas fa-phone"></i>
                        <label for="telefono">Número de Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" required>
                    </div>
                    <div class="form-group">
                        <i class="fas fa-info-circle"></i>
                        <label for="asunto">Asunto</label>
                        <input type="text" id="asunto" name="asunto" required>
                    </div>
                    <div class="form-group full-width">
                        <i class="fas fa-comment-dots"></i>
                        <label for="mensaje">Mensaje</label>
                        <textarea id="mensaje" name="mensaje" rows="6" required></textarea>
                    </div>
                    <div class="form-group full-width">
                        <i class="fas fa-paperclip"></i>
                        <label for="archivo">Adjuntar Imagen (Opcional)</label>
                        <input type="file" id="archivo" name="archivo" style="padding-left: 45px;">
                    </div>
                </div>
                <button type="submit" class="btn-submit">Enviar PQRS</button>
            </form>
        </div>
        <?php
        }
        ?>

        <div class="consulta-pqrs" id="consulta">
            <h2><i class="fas fa-search"></i> Consultar Estado de PQRS</h2>
            <form id="consulta-form">
                <div class="form-group">
                    <i class="fas fa-hashtag"></i>
                    <label for="radicado">Número de Radicado</label>
                    <input type="text" id="radicado" name="radicado" required>
                </div>
                <button type="submit" class="btn-submit">Consultar</button>
            </form>
        </div>
    </main>

    <!-- Ventana Modal para el Estado de la PQRS -->
    <div id="pqrs-modal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <div id="modal-body"></div>
        </div>
    </div>

        <footer>
    <div class="foot-panel1">
      Portafolio de Servicios, Contenido de Valor y Soluciones Creativas
    </div>
    <div class="foot-panel2">
    <ul>
    <p>Nuestro Portafolio</p>
    <a href="#portafolio">Diseño Gráfico</a>
    <a href="#portafolio">Branding</a>
    <a href="#portafolio">Desarrollo Web</a>
    <a href="#portafolio">Marketing Digital</a>
  </ul>
  <ul>
    <p>Nuestros Servicios</p>
    <a href="#servicios">Identidad Corporativa</a>
    <a href="#servicios">Campañas 360°</a>
    <a href="#servicios">Redes Sociales</a>
    <a href="#servicios">Producción Audiovisual</a>
  </ul>
  <ul>
    <p>Contenido de Valor</p>
    <a href="#blog">Blog de Marketing</a>
    <a href="#blog">Consejos de Diseño</a>
    <a href="#blog">Tendencias</a>
    <a href="#">Casos de Éxito</a>
  </ul>
  <ul>
    <p>Contacto</p>
    <a href="#nosotros">Sobre Nosotros</a>
    <a href="mailto:Glamcityredes2025@gmail.com">Email</a>
    <a href="https://api.whatsapp.com/send/?phone=573001095796">WhatsApp</a>
    <a href="#newsletter">Newsletter</a>

   
  </ul>
</div>
<style>
.foot-panel2 a {
  transition: color 0.3s ease, padding-left 0.3s ease;
}
.foot-panel2 a:hover {
  color: #c81d25;
  padding-left: 5px;
}
</style>

    <div class="foot-panel3">
      <div class="logo"></div>

<!-- Scripts para animaciones -->
<!-- Cambiar las líneas 269, 347-348 de: -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

<!-- Por: -->
<script src="vendor/particles/particles.min.js"></script>
<script src="vendor/gsap/gsap.min.js"></script>
<script src="vendor/gsap/ScrollTrigger.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Animación de tarjetas al hacer scroll
    const cards = document.querySelectorAll('.info-card');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
        }
      });
    }, { threshold: 0.1 });
    cards.forEach(card => observer.observe(card));

    // Configuración de Particles.js
    if (document.getElementById('particles-js')) {
      particlesJS('particles-js', {
        "particles": {
          "number": {"value": 80, "density": {"enable": true, "value_area": 800}},
          "color": {"value": "#c81d25"},
          "shape": {"type": "circle"},
          "opacity": {"value": 0.5, "random": false},
          "size": {"value": 3, "random": true},
          "line_linked": {"enable": true, "distance": 150, "color": "#c81d25", "opacity": 0.4, "width": 1},
          "move": {"enable": true, "speed": 4, "direction": "none", "out_mode": "out"}
        },
        "interactivity": {
          "events": {
            "onhover": {"enable": true, "mode": "grab"},
            "onclick": {"enable": true, "mode": "push"}
          },
          "modes": {
            "grab": {"distance": 140, "line_linked": {"opacity": 1}},
            "push": {"particles_nb": 4}
          }
        }
      });
    }

    // Animación de cursor secuencial falsa
    const allCards = Array.from(document.querySelectorAll('.info-card'));
    let currentIndex = 0;

    function highlightCard() {
        allCards.forEach(card => card.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)');
        
        if(allCards.length > 0) {
            const card = allCards[currentIndex];
            card.style.boxShadow = '0 20px 50px rgba(200, 29, 37, 0.5)'; // Sombra roja
            currentIndex = (currentIndex + 1) % allCards.length;
        }
    }

    setInterval(highlightCard, 2000); // Cambia de tarjeta cada 2 segundos
  });
</script>
    </div>
    <div class="foot-panel4">
      <div class="pages">
        <a>Condiciones de uso</a>
        <a>Politicas de privacidad</a>
        
      </div>
      <div class="copyright">
        © 2025 Glamcity | Kr 29B #31-36 San Juan Corozal, Sucre
      </div>
    </div>
  </footer>

    <a href="https://wa.me/573001095796" class="whatsapp-float" target="_blank">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Elementos del Cursor Personalizado -->
    <div class="cursor"></div>
    <div class="cursor-follower"></div>

    <!-- Scripts de Animación -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="js/main.js"></script> <!-- Script principal para animaciones como el cursor -->
    <script src="js/animations.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('pqrs-modal');
        const modalBody = document.getElementById('modal-body');
        const closeButton = document.querySelector('.close-button');
        const consultaForm = document.getElementById('consulta-form');

        if (consultaForm) {
            consultaForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const radicado = document.getElementById('radicado').value;

                fetch(`php/get_pqrs_details.php?radicado=${radicado}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                                modalBody.innerHTML = `<p>${data.error}</p>`;
                            } else {
                                let responseHTML = `
                                    <div class="pqrs-details">
                                        <h3>Detalles de la PQRS</h3>
                                        <p><strong>Radicado:</strong> ${data.radicado}</p>
                                        <p><strong>Fecha:</strong> ${data.fecha}</p>
                                        <p><strong>Nombre:</strong> ${data.nombre}</p>
                                        <p><strong>Email:</strong> ${data.email}</p>
                                        <p><strong>Teléfono:</strong> ${data.telefono}</p>
                                        <p><strong>Tipo:</strong> ${data.tipo_pqrs}</p>
                                        <p><strong>Asunto:</strong> ${data.asunto}</p>
                                        <p><strong>Mensaje:</strong></p>
                                        <div class="pqrs-message">${data.mensaje}</div>
                                        <p><strong>Estado:</strong> <span class="status-${data.estado.toLowerCase()}">${data.estado}</span></p>
                                        <p><strong>Respuesta:</strong></p>
                                        <div class="agent-response">${data.respuesta || 'Aún no hay respuesta.'}</div>
                                    `;
                                if (data.archivo) {
                                    responseHTML += `<p><strong>Archivo Adjunto:</strong> <a href="uploads/pqrs/${data.archivo}" target="_blank">Ver Archivo</a></p>`;
                                }
                                responseHTML += `</div>`;
                                modalBody.innerHTML = responseHTML;
                            }
                        modal.style.display = 'block';
                    })
                    .catch(error => {
                        modalBody.innerHTML = `<p>Error al consultar el estado. Por favor, inténtalo de nuevo.</p>`;
                        modal.style.display = 'block';
                    });
            });
        }

        if (closeButton) {
            closeButton.onclick = function() {
                modal.style.display = "none";
            }
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
    </script>

</body>
</html>