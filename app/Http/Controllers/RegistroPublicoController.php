<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarRegistroClienteRequest;
use App\Models\EnlaceCliente;
use App\Models\RegistroCliente;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegistroPublicoController extends Controller
{
    public function show(string $token): View
    {
        $enlace = $this->enlaceValido($token);
        $registros = $enlace->registros()->latest('CREATED_AT')->get();
        $submissionToken = $enlace->CERRADO_AT ? null : Str::uuid()->toString();

        return view('registro-publico.formulario', compact('enlace', 'token', 'registros', 'submissionToken'));
    }

    public function store(GuardarRegistroClienteRequest $request, string $token): RedirectResponse
    {
        $enlace = $this->enlaceValido($token);
        $submissionToken = (string) $request->validated('submission_token');

        if ($enlace->CERRADO_AT) {
            return redirect()->route('registro-publico.show', ['token' => $token]);
        }

        if (RegistroCliente::query()->where('SUBMISSION_KEY', $submissionToken)->exists()) {
            return redirect()->route('registro-publico.show', ['token' => $token]);
        }

        $archivo = $request->file('dni_foto');
        $nombre = Str::uuid()->toString().'.'.$archivo->extension();
        $ruta = $archivo->storeAs(
            'clientes/dni/'.$enlace->ID_MP_VST_CLIENTE,
            $nombre,
            'local',
        );

        $registroCreado = false;

        try {
            DB::transaction(function () use ($request, $enlace, $ruta, $submissionToken, &$registroCreado): void {
                $enlaceBloqueado = EnlaceCliente::query()
                    ->whereKey($enlace->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($enlaceBloqueado->CERRADO_AT) {
                    return;
                }

                RegistroCliente::create([
                    'ID_MP_VST_ENLACE_CLIENTE' => $enlaceBloqueado->getKey(),
                    'SUBMISSION_KEY' => $submissionToken,
                    'NOMBRES' => $request->validated('nombres'),
                    'DNI' => $request->validated('dni'),
                    'DNI_PATH' => $ruta,
                    'IP' => $request->ip(),
                    'USER_AGENT' => Str::limit((string) $request->userAgent(), 255, ''),
                ]);

                $enlaceBloqueado->forceFill(['ULTIMO_ACCESO_AT' => now()])->save();
                $registroCreado = true;
            });
        } catch (UniqueConstraintViolationException) {
            Storage::disk('local')->delete($ruta);

            return redirect()->route('registro-publico.show', ['token' => $token]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($ruta);
            throw $exception;
        }

        if (! $registroCreado) {
            Storage::disk('local')->delete($ruta);
        }

        return redirect()->route('registro-publico.show', ['token' => $token]);
    }

    public function cerrar(string $token): RedirectResponse
    {
        $enlace = $this->enlaceValido($token);

        DB::transaction(function () use ($enlace): void {
            $enlaceBloqueado = EnlaceCliente::query()
                ->whereKey($enlace->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $enlaceBloqueado->CERRADO_AT) {
                $enlaceBloqueado->forceFill([
                    'CERRADO_AT' => now(),
                    'ULTIMO_ACCESO_AT' => now(),
                ])->save();
            }
        });

        return redirect()->route('registro-publico.show', ['token' => $token]);
    }

    public function gracias(string $token): RedirectResponse
    {
        $enlace = $this->enlaceValido($token);

        return redirect()->route('registro-publico.show', ['token' => $token]);
    }

    public function dni(string $token, RegistroCliente $registro): BinaryFileResponse
    {
        $enlace = $this->enlaceValido($token);
        abort_unless($registro->ID_MP_VST_ENLACE_CLIENTE === $enlace->getKey(), 404);

        abort_unless(Storage::disk('local')->exists($registro->DNI_PATH), 404);

        $response = response()->file(Storage::disk('local')->path($registro->DNI_PATH));
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function enlaceValido(string $token): EnlaceCliente
    {
        abort_unless(strlen($token) === 64, 404);

        return EnlaceCliente::buscarPorToken($token) ?? abort(404);
    }
}
