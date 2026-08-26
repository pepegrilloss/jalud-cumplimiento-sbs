<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditRequest
{
    private const IGNORED_PREFIXES = [
        '_debugbar',
        'build',
        'css',
        'favicon',
        'filament',
        'js',
        'livewire',
        'storage',
        'vendor',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($this->shouldAudit($request)) {
            AuditLogger::event(
                event: $this->eventName($request),
                action: 'ACCESO',
                summary: $this->summary($request),
                metadata: [
                    'route' => $request->route()?->getName(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                ],
            );
        }

        return $response;
    }

    private function shouldAudit(Request $request): bool
    {
        if (!auth()->check()) {
            return false;
        }

        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return false;
        }

        foreach (self::IGNORED_PREFIXES as $prefix) {
            if ($request->is($prefix) || $request->is($prefix . '/*')) {
                return false;
            }
        }

        return true;
    }

    private function eventName(Request $request): string
    {
        return $request->routeIs('registros-clientes.dni')
            ? 'documento_consultado'
            : 'pagina_consultada';
    }

    private function summary(Request $request): string
    {
        return $request->routeIs('registros-clientes.dni')
            ? 'Consultó documento adjunto'
            : 'Consultó pantalla del sistema';
    }
}
