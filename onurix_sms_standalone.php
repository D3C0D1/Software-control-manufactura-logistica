<?php
// Tester autónomo de Onurix SMS (un solo archivo)
// Configura tus credenciales aquí:
$CLIENT_ID = ' 7389';
$API_KEY   = 'baf0076e7d995fc544c21cea4fdf898ce00612f268dc5f38c3565';

$httpCode = null;
$responseRaw = null;
$responseJson = null;
$errorMsg = null;
$sentOk = false;
$sentId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $sms   = isset($_POST['sms']) ? trim($_POST['sms']) : '';

    if ($phone === '' || $sms === '') {
        $errorMsg = 'Faltan campos: ingresa número y mensaje.';
    } else {
        // Normalizar número básico
        $normalizedPhone = preg_replace('/[\s\-()]/', '', $phone);
        if (!preg_match('/^\+?[0-9]{10,15}$/', $normalizedPhone)) {
            $errorMsg = 'Número inválido. Usa formato internacional (ej: +573001234567).';
        } else {
            // Enviar a Onurix
            $url = 'https://www.onurix.com/api/v1/sms/send';
            $postFields = http_build_query([
                'client' => $CLIENT_ID,
                'key'    => $API_KEY,
                'phone'  => $normalizedPhone,
                'sms'    => $sms,
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $responseRaw = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($responseRaw === false) {
                $errorMsg = 'Error de conexión cURL: ' . $curlErr;
            } else {
                $decoded = json_decode($responseRaw, true);
                if (is_array($decoded)) {
                    $responseJson = $decoded;
                    if ($httpCode === 200 && isset($decoded['data']['id'])) {
                        $sentOk = true;
                        $sentId = $decoded['data']['id'];
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Onurix SMS - Tester autónomo</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: #f6f7fb; margin: 0; padding: 24px; color: #222; }
        .card { max-width: 680px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 20px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p.sub { margin: 0 0 16px; color: #555; font-size: 14px; }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; }
        input, textarea { width: 100%; padding: 10px 12px; border: 1px solid #d7d9e0; border-radius: 8px; font-size: 14px; }
        textarea { min-height: 92px; resize: vertical; }
        .actions { display: flex; gap: 10px; margin-top: 10px; }
        button { appearance: none; border: none; border-radius: 8px; padding: 10px 14px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #eef2ff; color: #1e40af; }
        .result { margin-top: 18px; padding: 10px 12px; border-radius: 8px; font-size: 13px; background: #f9fafb; border: 1px solid #e5e7eb; }
        .ok { border-color: #10b981; background: #ecfdf5; }
        .err { border-color: #ef4444; background: #fef2f2; }
        pre { white-space: pre-wrap; word-break: break-word; }
        details { margin-top: 8px; }
        .hint { font-size: 12px; color: #666; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Onurix SMS - Tester autónomo</h1>
        <p class="sub">Archivo único en PHP con formulario y envío directo a Onurix (sin depender del sistema).</p>
        <form method="POST">
            <div class="form-group">
                <label for="phone">Número de teléfono (E.164)</label>
                <input id="phone" name="phone" type="text" placeholder="Ej: +573001234567" required />
            </div>
            <div class="form-group">
                <label for="sms">Texto del mensaje</label>
                <textarea id="sms" name="sms" placeholder="Escribe tu mensaje" required></textarea>
            </div>
            <div class="actions">
                <button class="btn-primary" type="submit">Enviar SMS</button>
                <button class="btn-secondary" type="button" onclick="document.getElementById('phone').value='+573001234567';document.getElementById('sms').value='Mensaje de prueba desde tester autónomo.';">Rellenar ejemplo</button>
            </div>
        </form>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST') : ?>
            <div class="result <?php echo $sentOk ? 'ok' : 'err'; ?>">
                <h3><?php echo $sentOk ? 'SMS enviado exitosamente' : 'Resultado del intento'; ?></h3>
                <p><strong>HTTP:</strong> <?php echo htmlspecialchars($httpCode ?? 'N/A'); ?></p>
                <?php if ($sentId) : ?>
                    <p><strong>ID de envío:</strong> <?php echo htmlspecialchars($sentId); ?></p>
                <?php endif; ?>
                <?php if ($errorMsg) : ?>
                    <p><strong>Error:</strong> <?php echo htmlspecialchars($errorMsg); ?></p>
                <?php endif; ?>
                <details open>
                    <summary>Respuesta RAW de Onurix</summary>
                    <pre><?php echo htmlspecialchars($responseRaw ?? ''); ?></pre>
                </details>
                <?php if ($responseJson) : ?>
                    <details open>
                        <summary>Respuesta JSON (formateada)</summary>
                        <pre><?php echo htmlspecialchars(json_encode($responseJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                    </details>
                <?php endif; ?>
                <p class="hint">Configura tus credenciales en la cabecera del archivo (<code>$CLIENT_ID</code> y <code>$API_KEY</code>).</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>