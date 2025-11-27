<?php
require_once 'db_connection.php';

function getExtraMetrics($conn) {
    // PQRS Abiertas
    $sql_open_pqrs = "SELECT COUNT(*) as open_pqrs FROM pqrs WHERE estado = 'abierto'";
    $result_open_pqrs = $conn->query($sql_open_pqrs);
    $open_pqrs = $result_open_pqrs->fetch_assoc()['open_pqrs'];

    // PQRS Cerradas
    $sql_closed_pqrs = "SELECT COUNT(*) as closed_pqrs FROM pqrs WHERE estado = 'cerrado'";
    $result_closed_pqrs = $conn->query($sql_closed_pqrs);
    $closed_pqrs = $result_closed_pqrs->fetch_assoc()['closed_pqrs'];

    // Empleados Activos
    $sql_active_employees = "SELECT COUNT(*) as active_employees FROM usuarios";
    $result_active_employees = $conn->query($sql_active_employees);
    $active_employees = $result_active_employees->fetch_assoc()['active_employees'];

    // Empleados Registrados
    $sql_registered_employees = "SELECT COUNT(*) as registered_employees FROM usuarios";
    $result_registered_employees = $conn->query($sql_registered_employees);
    $registered_employees = $result_registered_employees->fetch_assoc()['registered_employees'];

    

    return [
        'open_pqrs' => $open_pqrs,
        'closed_pqrs' => $closed_pqrs,
        'active_employees' => $active_employees,
        'registered_employees' => $registered_employees
    ];
}

header('Content-Type: application/json');
echo json_encode(getExtraMetrics($conn));
?>