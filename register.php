<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Glamcity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- CAMBIO AQUÍ -->
</head>
<body class="login-page"> <!-- CAMBIO AQUÍ -->
    <div class="login-wrapper">
        <div class="login-form-container">
            <div class="login-header">
                <h1>Crea tu Cuenta</h1>
                <p>Únete a nuestra comunidad</p>
            </div>
            <form action="php/register_process.php" method="POST">
                <div class="input-field">
                    <input type="text" name="nombre_completo" id="nombre_completo" required>
                    <label for="nombre_completo">Nombre Completo</label>
                </div>
                <div class="input-field">
                    <input type="text" name="usuario" id="usuario" required>
                    <label for="usuario">Usuario</label>
                </div>
                <div class="input-field">
                    <input type="password" name="password" id="password" required>
                    <label for="password">Contraseña</label>
                </div>
                <div class="input-field">
                    <select name="rol" id="rol" required>
                        <option value="" disabled selected>Selecciona un rol</option>
                        <option value="2">Operador</option>
                        <option value="1">Admin</option>
                        <option value="3">Invitado</option>
                    </select>
                    <label for="rol">Rol</label>
                </div>
                <button type="submit" class="btn-submit">Registrarse</button>
            </form>
            <div class="login-links">
                <p>¿Ya tienes una cuenta? <a href="login.php">Inicia Sesión</a></p>
            </div>
        </div>
        <div class="login-art-container">
        </div>
    </div>
</body>
</html>