<?php
/**
 * deploy-webhook.php - Webhook seguro para despliegue automático
 * 
 * Este archivo debe estar en la carpeta public/ para ser accesible vía HTTP
 * URL: https://padelbb.com/deploy-webhook.php (fallback; prefer /deploy-webhook route)
 */

// ============================================
// CONFIGURACIÓN
// ============================================

// Clave secreta - CAMBIA ESTO por una clave aleatoria única
// Genera una nueva con: openssl rand -hex 32
$secret = '5b6f0e897eba622ce3a357dc2fa59168199f0df6eb072050b10c389f1990c2ea';

// Ruta absoluta al proyecto
$project_path = '/home/u895805914/domains/padelbb.com/public_html';

// Archivo de log
$log_file = $project_path . '/storage/logs/webhook-deploy.log';

// ============================================
// SEGURIDAD
// ============================================

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'error' => 'Método no permitido. Solo se acepta POST.'
    ]));
}

// Verificar secreto (opcional pero MUY recomendado)
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? $_SERVER['HTTP_X_GITHUB_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if ($secret && $secret !== '0e6ce09117155a7105a38e7355fe5356f03c19b03a4baa3d155fedd77d678296') {
    if (!empty($signature)) {
        // GitHub usa sha1, otros servicios pueden usar sha256
        $hash = 'sha1=' . hash_hmac('sha1', $payload, $secret);
        if (!hash_equals($hash, $signature)) {
            http_response_code(403);
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'error' => 'Firma inválida. Acceso denegado.'
            ]));
        }
    } else {
        // Si no hay firma, verificar token en el body
        $data = json_decode($payload, true);
        $token = $data['token'] ?? $_POST['token'] ?? '';
        if ($token !== $secret) {
            http_response_code(403);
            header('Content-Type: application/json');
            die(json_encode([
                'success' => false,
                'error' => 'Token inválido. Acceso denegado.'
            ]));
        }
    }
}

// ============================================
// EJECUTAR DESPLIEGUE
// ============================================

// Crear directorio de logs si no existe
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Registrar que el webhook fue recibido
$log_entry = date('Y-m-d H:i:s') . " - ✅ Webhook recibido desde GitHub\n";
$log_entry .= date('Y-m-d H:i:s') . " - Ref: " . ($data['ref'] ?? 'unknown') . "\n";
$log_entry .= date('Y-m-d H:i:s') . " - Commit: " . ($data['head_commit']['id'] ?? 'unknown') . "\n";
$log_entry .= date('Y-m-d H:i:s') . " - Mensaje: " . ($data['head_commit']['message'] ?? 'unknown') . "\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

// Ejecutar deploy en background (sin bloquear la respuesta)
$command = "cd {$project_path} && ./deploy.sh >> {$log_file} 2>&1 &";
$log_entry = date('Y-m-d H:i:s') . " - 🚀 Ejecutando: {$command}\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

exec($command, $output, $return_var);

// Registrar resultado
$log_entry = date('Y-m-d H:i:s') . " - Webhook procesado. Return code: {$return_var}\n";
if (!empty($output)) {
    $log_entry .= date('Y-m-d H:i:s') . " - Output: " . implode("\n", $output) . "\n";
}
file_put_contents($log_file, $log_entry, FILE_APPEND);

// ============================================
// RESPUESTA
// ============================================

header('Content-Type: application/json');

$response = [
    'success' => $return_var === 0,
    'message' => $return_var === 0 
        ? 'Despliegue iniciado en segundo plano' 
        : 'Error al iniciar despliegue',
    'timestamp' => date('Y-m-d H:i:s'),
    'log_file' => 'storage/logs/webhook-deploy.log',
    'return_code' => $return_var
];

// Si hay output, agregarlo
if (!empty($output)) {
    $response['output'] = $output;
}

echo json_encode($response, JSON_PRETTY_PRINT);

