<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarRegistroClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $archivo = $this->file('dni_foto');
        $esPdf = $archivo?->getMimeType() === 'application/pdf';

        $reglasArchivo = [
            'required',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf',
            'max:10240',
        ];

        if (! $esPdf) {
            $reglasArchivo[] = 'dimensions:max_width=7000,max_height=7000';
        }

        return [
            'submission_token' => ['required', 'uuid'],
            'nombres' => ['required', 'string', 'min:3', 'max:150'],
            'dni' => ['required', 'regex:/^\d{8}$/'],
            'dni_foto' => $reglasArchivo,
        ];
    }

    public function messages(): array
    {
        return [
            'submission_token.required' => 'Actualiza la página e intenta nuevamente.',
            'submission_token.uuid' => 'Actualiza la página e intenta nuevamente.',
            'nombres.required' => 'Ingresa los nombres completos de la persona.',
            'nombres.min' => 'Ingresa los nombres completos de la persona.',
            'dni.required' => 'Ingresa el número de DNI.',
            'dni.regex' => 'El DNI debe tener 8 dígitos.',
            'dni_foto.required' => 'Toma una foto o selecciona un archivo de tu DNI.',
            'dni_foto.file' => 'El documento seleccionado no es válido.',
            'dni_foto.mimes' => 'Usa una imagen JPG, PNG, WEBP o un PDF.',
            'dni_foto.max' => 'El archivo no debe superar los 10 MB.',
            'dni_foto.dimensions' => 'La resolución de la imagen es demasiado grande.',
        ];
    }
}
