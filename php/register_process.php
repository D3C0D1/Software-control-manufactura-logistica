<?php
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_completo = $_POST['nombre_completo'];
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];
    $rol_id = $_POST['rol'];

    // Hashear la contraseña para seguridad
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Usar la columna correcta 'rol_id'
    $sql = "INSERT INTO usuarios (nombre_completo, usuario, password, rol_id) VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error al preparar la consulta: " . $conn->error);
    }

    // Usar 'i' para el tipo de dato de rol_id (entero)
    $stmt->bind_param("sssi", $nombre_completo, $usuario, $hashed_password, $rol_id);

    if ($stmt->execute()) {
        // Redirigir al login si el registro es exitoso
        header("Location: ../login.php?status=success");
        exit();
    } else {
        // Manejar error
        echo "Error al ejecutar la consulta: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>