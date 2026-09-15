<?php

namespace App\Http\Middleware;

use App\Filament\Cumplimiento\Pages\SelectSede;
use App\Services\SedeContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSedeIsSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()
            || $request->routeIs('filament.cumplimiento.auth.*')
            || $request->routeIs('filament.cumplimiento.pages.seleccionar-sede')) {
            return $next($request);
        }

        if (! app(SedeContext::class)->sedeActivaId(auth()->user())) {
            return redirect(SelectSede::getUrl());
        }

        return $next($request);
    }
}
