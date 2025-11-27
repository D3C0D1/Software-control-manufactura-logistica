<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-page">
    <div id="particles-js"></div>
    <audio id="login-sound" src="sounds/login.mp3" preload="auto"></audio>
    <audio id="error-sound" src="sounds/dont.mp3" preload="auto"></audio>

    <div id="audio-permission-modal" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 1000; text-align: center;">
        <p>Haz clic aquí para habilitar el sonido</p>
        <button id="enable-audio-btn" class="btn-login">Habilitar Sonido</button>
    </div>
    
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo-g">G</div>
            <h2>Bienvenido de Nuevo</h2>
            <p>Accede a tu cuenta para continuar</p>
            <form action="php/auth.php" method="POST" class="login-form">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="usuario" placeholder="Nombre de Usuario" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Contraseña" required>
                    <i class="fas fa-eye-slash" id="togglePassword"></i>
                </div>
                <button type="submit" class="btn-login">Ingresar</button>
            </form>
        </div>
    </div>

    <script src="js/particles.js"></script>

    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            const audioModal = document.getElementById('audio-permission-modal');
            const enableAudioBtn = document.getElementById('enable-audio-btn');
            const loginSound = document.getElementById("login-sound");
            const errorSound = document.getElementById("error-sound");

            // Función para intentar reproducir sonidos
            function playSounds() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('error')) {
                    errorSound.play().catch(e => console.log("Aún no se puede reproducir el sonido de error."));
                } else {
                    loginSound.play().catch(e => console.log("Aún no se puede reproducir el sonido de login."));
                }
            }

            // Al hacer clic en el botón, oculta el modal y reproduce un sonido para "desbloquear" el audio
            enableAudioBtn.addEventListener('click', () => {
                audioModal.style.display = 'none';
                // Intenta reproducir un sonido corto y silencioso para activar el permiso
                loginSound.muted = true;
                loginSound.play().then(() => {
                    loginSound.muted = false;
                    console.log("Audio habilitado por el usuario.");
                    playSounds(); // Ahora intenta reproducir los sonidos de la página
                }).catch(error => {
                    console.log("La interacción del usuario no fue suficiente para habilitar el audio.");
                });
            });

            // Comprobar si podemos reproducir audio sin interacción
            loginSound.play().then(() => {
                // Si se reproduce, el permiso ya está concedido, ocultar modal
                audioModal.style.display = 'none';
                playSounds();
            }).catch(error => {
                // Si falla, el modal permanecerá visible para que el usuario interactúe
                console.log("La reproducción automática fue bloqueada. Se necesita interacción del usuario.");
            });
        });

        document.getElementById('togglePassword').addEventListener('click', function (e) {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>