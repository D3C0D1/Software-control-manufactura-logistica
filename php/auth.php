<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM usuarios WHERE usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Verificar la contraseña
        if (password_verify($password, $user['password'])) {
            // Contraseña correcta, iniciar sesión
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['rol_id'] = $user['rol_id'];
            $_SESSION['area_id'] = $user['area_id']; // Guardar area_id en la sesión

            // Redirección basada en el rol
            if ($user['rol_id'] == 3) { // Rol de Empleado
                $area_id = $user['area_id'];
                $area_redirect_map = [
                    1 => 'recepcion.php',
                    2 => 'mensajeria.php',
                    3 => 'diseno.php',
                    4 => 'impresion.php',
                    5 => 'sublimado.php',
                    6 => 'confeccion.php',
                    7 => 'control_calidad.php',
                    // Añadir más mapeos si es necesario
                ];
                if (array_key_exists($area_id, $area_redirect_map)) {
                    header("Location: ../" . $area_redirect_map[$area_id]);
                } else {
                    header("Location: ../dashboard.php"); // Redirección por defecto si el área no está mapeada
                }
            } else { // Para otros roles (admin, operador)
                header("Location: ../dashboard.php");
            }
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: ../login.php?error=invalid_credentials");
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: ../login.php?error=invalid_credentials");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>