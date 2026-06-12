<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class DeployWebhookController extends Controller
{
    /**
     * Clave secreta para verificar el webhook
     * Debe coincidir con la configurada en GitHub
     */
    private $secret = '5b6f0e897eba622ce3a357dc2fa59168199f0df6eb072050b10c389f1990c2ea';

    /**
     * Ruta absoluta al proyecto en el servidor
     */
    private $projectPath = '/home/u895805914/domains/padelbb.com/public_html';

    /**
     * Manejar el webhook de GitHub para despliegue automático
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Registrar TODAS las peticiones al webhook
        $logDir = $this->projectPath . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/webhook-deploy.log';
        
        $logEntry = date('Y-m-d H:i:s') . " - Webhook llamado - Método: " . $request->method() . " - IP: " . $request->ip() . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        // Solo permitir POST
        if (!$request->isMethod('post')) {
            $logEntry = date('Y-m-d H:i:s') . " - ERROR: Método no permitido. Solo se acepta POST.\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            return response()->json([
                'success' => false,
                'error' => 'Método no permitido. Solo se acepta POST.'
            ], 405);
        }

        // Verificar secreto
        $signature = $request->header('X-Hub-Signature') ?? $request->header('X-GitHub-Signature') ?? '';
        $payload = $request->getContent();
        
        $logEntry = date('Y-m-d H:i:s') . " - Verificando firma... Signature header: " . ($signature ? 'presente' : 'ausente') . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);

        if (!$this->verifySignature($signature, $payload)) {
            $logEntry = date('Y-m-d H:i:s') . " - ERROR: Firma inválida. Acceso denegado. IP: " . $request->ip() . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            Log::warning('Intento de acceso no autorizado al webhook de despliegue', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Firma inválida. Acceso denegado.'
            ], 403);
        }

        // Verificar que es un push a main
        $data = json_decode($payload, true);
        $ref = $data['ref'] ?? '';
        
        $logEntry = date('Y-m-d H:i:s') . " - Ref recibido: " . $ref . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        if ($ref !== 'refs/heads/main') {
            $logEntry = date('Y-m-d H:i:s') . " - INFO: Webhook recibido pero no es push a main. Ref: " . $ref . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            Log::info('Webhook recibido pero no es push a main', ['ref' => $ref]);
            return response()->json([
                'success' => true,
                'message' => 'Webhook recibido pero no es push a main. No se ejecuta despliegue.',
                'ref' => $ref
            ]);
        }

        // Ejecutar despliegue en segundo plano
        try {
            $commitId = $data['head_commit']['id'] ?? 'unknown';
            $commitMessage = $data['head_commit']['message'] ?? 'unknown';
            
            $logEntry = date('Y-m-d H:i:s') . " - ✅ Iniciando despliegue - Commit: " . $commitId . " - Mensaje: " . $commitMessage . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            $this->executeDeploy();
            
            Log::info('Despliegue iniciado desde webhook', [
                'commit' => $commitId,
                'message' => $commitMessage
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Despliegue iniciado en segundo plano',
                'timestamp' => now()->toDateTimeString(),
                'log_file' => 'storage/logs/webhook-deploy.log'
            ]);
        } catch (\Exception $e) {
            $logEntry = date('Y-m-d H:i:s') . " - ❌ ERROR al ejecutar despliegue: " . $e->getMessage() . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            
            Log::error('Error al ejecutar despliegue desde webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al iniciar despliegue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar la firma del webhook
     *
     * @param string $signature
     * @param string $payload
     * @return bool
     */
    private function verifySignature($signature, $payload)
    {
        if (empty($signature) || empty($this->secret)) {
            return false;
        }

        // GitHub usa sha1
        $hash = 'sha1=' . hash_hmac('sha1', $payload, $this->secret);
        
        return hash_equals($hash, $signature);
    }

    /**
     * Ejecutar el script de despliegue en segundo plano
     *
     * @return void
     */
    private function executeDeploy()
    {
        $logFile = $this->projectPath . '/storage/logs/webhook-deploy.log';
        $deployScript = $this->projectPath . '/deploy.sh';

        // Crear directorio de logs si no existe
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Ejecutar deploy en background
        $command = "cd {$this->projectPath} && ./deploy.sh >> {$logFile} 2>&1 &";
        exec($command);

        // Registrar en log de Laravel también
        Log::info('Comando de despliegue ejecutado', [
            'command' => $command,
            'log_file' => $logFile
        ]);
    }
}
