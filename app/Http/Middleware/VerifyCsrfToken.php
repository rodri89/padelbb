<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'deploy-webhook', // Webhook de GitHub para despliegue automático
        'buscar-jugadores-publico', // Búsqueda pública de jugadores sin autenticación
        'subir-foto-jugador-publico', // Subida de foto pública sin autenticación
    ];
}
