<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Habilitar el registro de errores en un archivo
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log'); // Asegúrate de que la carpeta 'logs' exista y tenga permisos de escritura

require_once 'db_connection.php';
require_once '../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

header('Content-Type: application/json');

// Comprobación defensiva de la conexión
if (!isset($conn) || !is_object($conn)) {
    error_log('Error: conexión a la base de datos no válida (conn es null o no es objeto).');
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    error_log('Intento de acceso no autorizado.');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Método no permitido: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$nombre_cliente = trim($_POST['nombre_completo'] ?? '');
$email_cliente = trim($_POST['email'] ?? '');
$movil_cliente = trim($_POST['movil'] ?? '');
$notas = trim($_POST['notas'] ?? '');
$prioridad = trim($_POST['prioridad'] ?? 'normal'); // Capturar prioridad
$enviar_sms = isset($_POST['enviar_sms']) && $_POST['enviar_sms'] === 'on'; // Capturar opción de SMS
$id_usuario_creador = $_SESSION['user_id'];

// Validar que el usuario creador exista para evitar FK errores
try {
    $stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
    if ($stmt_user === false) {
        error_log('Error al preparar validación de usuario: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Error al validar usuario creador.']);
        $conn->close();
        exit;
    }
    $stmt_user->bind_param('i', $id_usuario_creador);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();
    if (!$res_user || $res_user->num_rows === 0) {
        error_log('Usuario creador inválido (no existe en usuarios): ' . $id_usuario_creador);
        echo json_encode(['success' => false, 'message' => 'Usuario creador inválido. Inicie sesión nuevamente.']);
        $stmt_user->close();
        $conn->close();
        exit;
    }
    $stmt_user->close();
} catch (mysqli_sql_exception $e) {
    error_log('Error validando usuario creador: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error validando usuario creador.']);
    $conn->close();
    exit;
}

if (empty($nombre_cliente) || empty($email_cliente) || empty($movil_cliente)) {
    error_log('Campos obligatorios vacíos: ' . print_r($_POST, true));
    echo json_encode(['success' => false, 'message' => 'Por favor, complete todos los campos obligatorios.']);
    exit;
}

if (!filter_var($email_cliente, FILTER_VALIDATE_EMAIL)) {
    error_log('Formato de correo electrónico no válido: ' . $email_cliente);
    echo json_encode(['success' => false, 'message' => 'El formato del correo electrónico no es válido.']);
    exit;
}

$guia = 'GUIA' . strtoupper(uniqid());

$ruta_adjunto_db = null;
if (isset($_FILES['adjunto_zip']) && $_FILES['adjunto_zip']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['adjunto_zip'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file_extension == 'zip') {
        $upload_dir_fs = __DIR__ . '/../uploads/pedidos/';
        
        if (!is_dir($upload_dir_fs)) {
            if (!mkdir($upload_dir_fs, 0777, true)) {
                error_log('Error: No se pudo crear el directorio de subidas.');
                echo json_encode(['success' => false, 'message' => 'Error: No se pudo crear el directorio de subidas.']);
                exit;
            }
        }

        $file_name = $guia . '_' . basename($file['name']);
        $ruta_final_fs = $upload_dir_fs . $file_name;

        if (move_uploaded_file($file['tmp_name'], $ruta_final_fs)) {
            $ruta_adjunto_db = 'uploads/pedidos/' . $file_name;
        } else {
            error_log('Error al subir el archivo adjunto.');
            echo json_encode(['success' => false, 'message' => 'Error al subir el archivo adjunto.']);
            exit;
        }
    } else {
        error_log('Tipo de archivo no permitido: ' . $file_extension);
        echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se aceptan archivos ZIP.']);
        exit;
    }
}

$estado_inicial = 1; // Correcto: Estado "Iniciando"
$area_inicial = 20; // Correcto: Área "Guia Generada"

// Actualizar la consulta SQL SIN el campo sms_enviado (temporal)
$sql = "INSERT INTO pedidos (numero_guia, nombre_cliente, correo_cliente, numero_cliente, notas, prioridad, creado_por, estado_id, area_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    error_log('Error al preparar la consulta de pedido: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta de pedido: ' . $conn->error]);
    $conn->close();
    exit;
}

// Ajustar los parámetros del bind SIN sms_enviado
$stmt->bind_param('ssssssiii', $guia, $nombre_cliente, $email_cliente, $movil_cliente, $notas, $prioridad, $id_usuario_creador, $estado_inicial, $area_inicial);

try {
    if ($stmt->execute()) {
        $pedido_id = $stmt->insert_id;
    } else {
        throw new mysqli_sql_exception($stmt->error);
    }
} catch (mysqli_sql_exception $e) {
    if (strpos($e->getMessage(), "Field 'id' doesn't have a default value") !== false) {
        error_log("create_pedido.php - 'pedidos.id' no es AUTO_INCREMENT, aplicando fallback manual: " . $e->getMessage());
        // Calcular next_id manualmente
        $next_id = 1;
        try {
            $res_max = $conn->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM pedidos");
            if ($res_max) {
                $row = $res_max->fetch_assoc();
                if ($row && isset($row['next_id'])) {
                    $next_id = (int)$row['next_id'];
                }
                $res_max->close();
            }
        } catch (mysqli_sql_exception $e2) {
            error_log('create_pedido.php - Error calculando next_id de pedidos: ' . $e2->getMessage());
        }

        // Reintentar inserción incluyendo el id manual
        $sql2 = "INSERT INTO pedidos (id, numero_guia, nombre_cliente, correo_cliente, numero_cliente, notas, prioridad, creado_por, estado_id, area_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql2);
        if ($stmt2 && ($stmt2 instanceof mysqli_stmt)) {
            $stmt2->bind_param('issssssiii', $next_id, $guia, $nombre_cliente, $email_cliente, $movil_cliente, $notas, $prioridad, $id_usuario_creador, $estado_inicial, $area_inicial);
            try {
                $stmt2->execute();
                $pedido_id = $next_id;
            } catch (mysqli_sql_exception $e3) {
                error_log('create_pedido.php - Falló inserción manual de pedido con id: ' . $e3->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error al guardar el pedido: ' . $e3->getMessage()]);
                if ($stmt2 instanceof mysqli_stmt) { $stmt2->close(); }
                if ($stmt instanceof mysqli_stmt) { $stmt->close(); }
                if ($conn instanceof mysqli) { $conn->close(); }
                exit;
            }
            $stmt2->close();
        } else {
            error_log('create_pedido.php - No se pudo preparar inserción con id manual: ' . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Error al preparar inserción con id manual: ' . $conn->error]);
            if ($stmt instanceof mysqli_stmt) { $stmt->close(); }
            if ($conn instanceof mysqli) { $conn->close(); }
            exit;
        }
    } else {
        // Error distinto al de id por defecto
        error_log('create_pedido.php - Error ejecutando inserción de pedido: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al guardar el pedido: ' . $e->getMessage()]);
        if ($stmt instanceof mysqli_stmt) { $stmt->close(); }
        if ($conn instanceof mysqli) { $conn->close(); }
        exit;
    }
}

    // Procesar archivos adjuntos múltiples
    if (isset($_FILES['adjuntos'])) {
        $upload_dir_fs = __DIR__ . '/../uploads/pedidos/';
        if (!is_dir($upload_dir_fs)) {
            mkdir($upload_dir_fs, 0777, true);
        }

        $sql_adjunto = "INSERT INTO pedido_adjuntos (pedido_id, ruta_archivo) VALUES (?, ?)";
        $stmt_adjunto = $conn->prepare($sql_adjunto);
        if ($stmt_adjunto === false) {
            error_log('No se pudo preparar inserción de adjuntos: ' . $conn->error);
        }

        foreach ($_FILES['adjuntos']['name'] as $key => $name) {
            if ($_FILES['adjuntos']['error'][$key] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['adjuntos']['tmp_name'][$key];
                $file_name = $guia . '_' . basename($name);
                $ruta_final_fs = $upload_dir_fs . $file_name;

                if (move_uploaded_file($tmp_name, $ruta_final_fs)) {
                    $ruta_adjunto_db = 'uploads/pedidos/' . $file_name;
                    if ($stmt_adjunto && ($stmt_adjunto instanceof mysqli_stmt)) {
                        try {
                            $stmt_adjunto->bind_param('is', $pedido_id, $ruta_adjunto_db);
                            $stmt_adjunto->execute();
                        } catch (mysqli_sql_exception $e) {
                            // Fallback si el ID de pedido_adjuntos no es AUTO_INCREMENT
                            if (strpos($e->getMessage(), "Field 'id' doesn't have a default value") !== false) {
                                error_log('Adjunto requiere ID manual, aplicando fallback: ' . $e->getMessage());
                                try {
                                    $res_max = $conn->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM pedido_adjuntos");
                                    $next_id = 1;
                                    if ($res_max) {
                                        $row = $res_max->fetch_assoc();
                                        if ($row && isset($row['next_id'])) {
                                            $next_id = (int)$row['next_id'];
                                        }
                                        $res_max->close();
                                    }
                                    $stmt_adjunto2 = $conn->prepare("INSERT INTO pedido_adjuntos (id, pedido_id, ruta_archivo) VALUES (?, ?, ?)");
                                    if ($stmt_adjunto2 && ($stmt_adjunto2 instanceof mysqli_stmt)) {
                                        $stmt_adjunto2->bind_param('iis', $next_id, $pedido_id, $ruta_adjunto_db);
                                        $stmt_adjunto2->execute();
                                        $stmt_adjunto2->close();
                                    } else {
                                        error_log('No se pudo preparar inserción de adjunto con ID: ' . $conn->error);
                                    }
                                } catch (mysqli_sql_exception $e2) {
                                    error_log('Fallo en fallback de adjunto: ' . $e2->getMessage());
                                }
                            } else {
                                error_log('Error al insertar adjunto: ' . $e->getMessage());
                            }
                        }
                    } else {
                        error_log('Salto inserción de adjunto (stmt_adjunto inválido) para: ' . $ruta_adjunto_db);
                    }
                }
            }
        }
        if ($stmt_adjunto && ($stmt_adjunto instanceof mysqli_stmt)) {
            $stmt_adjunto->close();
        }
    }

    echo json_encode(['success' => true, 'guia' => $guia]);
    
    // Enviar SMS si está habilitado
    if ($enviar_sms) {
        enviarSMSConfirmacion($movil_cliente, $guia);
    }

if (isset($stmt) && ($stmt instanceof mysqli_stmt)) {
    $stmt->close();
}
if (isset($conn) && ($conn instanceof mysqli)) {
    $conn->close();
}

// Función para enviar SMS de confirmación usando Onurix API
function enviarSMSConfirmacion($telefono, $guia) {
    require_once 'get_sms_config.php';
    require_once '../vendor/autoload.php';
    
    // Obtener configuración SMS desde la base de datos
    $sms_config = getSMSConfig();
    
    // Verificar si el SMS está habilitado
    if ($sms_config['sms_enabled'] != '1') {
        error_log("SMS deshabilitado en configuración para pedido $guia");
        return false;
    }
    
    // Verificar credenciales según modo (Proxy vs directo)
    $useProxy = (($sms_config['sms_proxy_enabled'] ?? '0') === '1') && !empty($sms_config['sms_proxy_url']);
    $requireCreds = !$useProxy || (($sms_config['sms_proxy_send_credentials'] ?? '0') === '1');
    if ($requireCreds && (empty($sms_config['onurix_client_id']) || empty($sms_config['onurix_api_key']))) {
        error_log("Credenciales SMS no configuradas para pedido $guia");
        return false;
    }
    
    $client_id = $sms_config['onurix_client_id'];
    $api_key = $sms_config['onurix_api_key'];

    // Obtener nombre del cliente por número de guía
    $nombre_cliente = 'cliente';
    try {
        $pdo = getPDOConnection();
        $stmt = $pdo->prepare("SELECT nombre_cliente FROM pedidos WHERE numero_guia = ? LIMIT 1");
        $stmt->execute([$guia]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['nombre_cliente']) && $row['nombre_cliente'] !== '') {
            $nombre_cliente = $row['nombre_cliente'];
        }
    } catch (Exception $e) {
        error_log('No se pudo obtener nombre_cliente para guia ' . $guia . ': ' . $e->getMessage());
    }
    
    // Usar la plantilla configurada y reemplazar número de guía y nombre del cliente
    $plantilla = $sms_config['sms_template'] ?? '';
    $mensaje = str_replace(['{numero_guia}', '{nombre_cliente}'], [$guia, $nombre_cliente], $plantilla);
    if (trim($mensaje) === '') {
        $mensaje = "¡Hola $nombre_cliente! Su pedido #$guia ha sido creado exitosamente.";
    }
    
    try {
        $client = new \GuzzleHttp\Client();

        if ($useProxy) {
            // Envío vía Proxy/Webhook (Hosting B)
            $proxyPayload = [
                'phone' => $telefono,
                'sms' => $mensaje,
                'numero_guia' => $guia,
                'context' => 'create'
            ];
            if (($sms_config['sms_proxy_send_credentials'] ?? '0') === '1') {
                $proxyPayload['client'] = $client_id;
                $proxyPayload['key'] = $api_key;
            }

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];
            if (!empty($sms_config['sms_proxy_token'])) {
                $headers['Authorization'] = 'Bearer ' . $sms_config['sms_proxy_token'];
            }

            error_log("Enviando SMS de confirmación vía Proxy a {$sms_config['sms_proxy_url']} para guía $guia");
            $response = $client->post($sms_config['sms_proxy_url'], [
                'headers' => $headers,
                'json' => $proxyPayload,
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = $response->getBody()->getContents();
            $responseBody = json_decode($rawBody, true);
            error_log("Respuesta Proxy (HTTP $statusCode): " . substr($rawBody, 0, 300));

            $success = is_array($responseBody) ? ($responseBody['success'] ?? false) : false;
            if ($statusCode === 200 && $success) {
                error_log("SMS enviado exitosamente vía proxy para pedido $guia al número $telefono");
                return true;
            } else {
                $msg = is_array($responseBody) ? ($responseBody['message'] ?? 'Error desconocido (proxy)') : ('HTTP ' . $statusCode);
                error_log("Error al enviar SMS vía proxy para pedido $guia: " . $msg);
                return false;
            }
        } else {
            // Envío directo a Onurix
            $request_body = [
                'client' => $client_id,
                'key' => $api_key,
                'phone' => $telefono,
                'sms' => $mensaje
            ];
            error_log("Intentando enviar SMS para pedido $guia al número $telefono con datos: " . json_encode($request_body));
            $response = $client->post('https://www.onurix.com/api/v1/sms/send', [
                'form_params' => $request_body,
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);
            error_log("Respuesta de Onurix para pedido $guia: Status $statusCode, Body: " . json_encode($responseBody));

            if ($statusCode === 200 && isset($responseBody['data']['id'])) {
                error_log("SMS enviado exitosamente para pedido $guia al número $telefono");
                return true;
            } else {
                error_log("Error al enviar SMS para pedido $guia: " . json_encode($responseBody));
                return false;
            }
        }
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        error_log("Error de conexión al enviar SMS para pedido $guia: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Error general al enviar SMS para pedido $guia: " . $e->getMessage());
        return false;
    }
}
?>