<?php
session_start();
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo_pqrs = filter_input(INPUT_POST, 'tipo_pqrs', FILTER_SANITIZE_STRING);
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    $asunto = filter_input(INPUT_POST, 'asunto', FILTER_SANITIZE_STRING);
    $mensaje = filter_input(INPUT_POST, 'mensaje', FILTER_SANITIZE_STRING);

    $radicado = 'PQRSF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    $archivo_ruta = null;
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $archivo_nombre = basename($_FILES['archivo']['name']);
        $archivo_ruta_completa = $upload_dir . $radicado . '_' . $archivo_nombre;
        if (move_uploaded_file($_FILES['archivo']['tmp_name'], $archivo_ruta_completa)) {
            $archivo_ruta = 'uploads/' . $radicado . '_' . $archivo_nombre;
        }
    }

    $stmt = $conn->prepare("INSERT INTO pqrs (radicado, tipo_solicitud, nombre_completo, email, telefono, asunto, mensaje, archivo_adjunto, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Abierto')");
    $stmt->bind_param("ssssssss", $radicado, $tipo_pqrs, $nombre, $email, $telefono, $asunto, $mensaje, $archivo_ruta);

    if ($stmt->execute()) {
        $_SESSION['pqrs_radicado'] = $radicado;
        $_SESSION['pqrs_status'] = 'success';
    } else {
        $_SESSION['pqrs_status'] = 'error';
        $_SESSION['pqrs_message'] = 'Error al registrar la solicitud: ' . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: ../pqrs.php");
    exit();
}
?>