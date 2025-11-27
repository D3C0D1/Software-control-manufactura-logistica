<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

// Total PQRS
$total_result = $conn->query("SELECT COUNT(*) as total FROM pqrs");
$total_pqrs = $total_result->fetch_assoc()['total'];

// PQRS Abiertas
$open_result = $conn->query("SELECT COUNT(*) as abiertas FROM pqrs WHERE estado = 'Abierto'");
$open_pqrs = $open_result->fetch_assoc()['abiertas'];

// PQRS Cerradas
$closed_result = $conn->query("SELECT COUNT(*) as cerradas FROM pqrs WHERE estado = 'Cerrado'");
$closed_pqrs = $closed_result->fetch_assoc()['cerradas'];

// Usuarios Activos (total de usuarios registrados)
$users_result = $conn->query("SELECT COUNT(*) as activos FROM usuarios");
$active_users = $users_result->fetch_assoc()['activos'];

$stats = [
    'total' => $total_pqrs,
    'abiertas' => $open_pqrs,
    'cerradas' => $closed_pqrs,
    'usuarios' => $active_users
];

echo json_encode($stats);

$conn->close();
?>