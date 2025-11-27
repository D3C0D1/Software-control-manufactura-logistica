<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'php/db_connection.php';

echo "<h2>Verificación de Estructura de Base de Datos</h2>";

// Verificar estructura de tabla pedidos
echo "<h3>1. Estructura de tabla 'pedidos':</h3>";
try {
    $result = $conn->query("DESCRIBE pedidos");
    echo "<table border='1'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Verificar áreas
echo "<h3>2. Áreas disponibles:</h3>";
try {
    $result = $conn->query("SELECT id, nombre FROM areas ORDER BY id");
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nombre</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['nombre']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Verificar pedidos existentes
echo "<h3>3. Pedidos existentes (últimos 10):</h3>";
try {
    $result = $conn->query("SELECT * FROM pedidos ORDER BY id DESC LIMIT 10");
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        $first = true;
        while ($row = $result->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $key) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No hay pedidos en la base de datos.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>