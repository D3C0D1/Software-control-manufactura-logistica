<?php
require_once 'db_connection.php';

try {
    $pdo = getPDOConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS notification_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20),
        notify_daily TINYINT(1) DEFAULT 0,
        last_run DATETIME NULL
    )";
    
    $pdo->exec($sql);
    
    // Check if row exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM notification_config");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO notification_config (phone_number, notify_daily) VALUES ('', 0)");
    }
    
    echo "Table created/checked successfully.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
