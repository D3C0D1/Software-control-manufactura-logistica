<?php
require_once 'php/db_connection.php';

echo "<h2>Creando tabla de configuración SMS</h2>\n";

// Leer el archivo SQL
$sql_file = 'sql/sms_config.sql';
if (!file_exists($sql_file)) {
    die("❌ Archivo SQL no encontrado: $sql_file");
}

$sql_content = file_get_contents($sql_file);
if ($sql_content === false) {
    die("❌ No se pudo leer el archivo SQL");
}

try {
    // Dividir las consultas por punto y coma
    $queries = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            echo "Ejecutando: " . substr($query, 0, 50) . "...\n<br>";
            
            if ($conn instanceof mysqli) {
                $result = $conn->query($query);
                if ($result === true) {
                    echo "✅ Consulta ejecutada exitosamente\n<br>";
                } else {
                    echo "❌ Error: " . $conn->error . "\n<br>";
                }
            }
        }
    }
    
    echo "<h3>Verificando tabla creada:</h3>\n";
    if ($conn instanceof mysqli) {
        $describe_result = $conn->query("DESCRIBE sms_config");
        if ($describe_result instanceof mysqli_result && $describe_result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th><th>Extra</th></tr>";
            while (($row = $describe_result->fetch_assoc()) !== null) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value ?? '') . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ No se pudo obtener la estructura de la tabla\n<br>";
        }
    }
    
    echo "<h3>Configuraciones insertadas:</h3>\n";
    if ($conn instanceof mysqli) {
        $select_result = $conn->query("SELECT * FROM sms_config");
        if ($select_result instanceof mysqli_result && $select_result->num_rows > 0) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>ID</th><th>Nombre</th><th>Valor</th><th>Descripción</th><th>Creado</th></tr>";
            while (($row = $select_result->fetch_assoc()) !== null) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['config_name'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['config_value'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['description'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['created_at'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ No se encontraron configuraciones en la tabla\n<br>";
        }
    }

} catch (Exception $e) {
    echo "❌ Error durante la ejecución: " . $e->getMessage() . "\n<br>";
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}

echo "<p><a href='configuracion.php'>← Volver a Configuración</a></p>";
?>