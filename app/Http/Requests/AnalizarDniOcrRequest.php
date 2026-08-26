<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnalizarDniOcrRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dni_foto' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
                'dimensions:max_width=7000,max_height=7000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'dni_foto.required' => 'No se recibió una imagen para analizar.',
            'dni_foto.image' => 'No se pudo preparar el documento para la lectura automática.',
            'dni_foto.mimes' => 'La imagen preparada para OCR no tiene un formato compatible.',
            'dni_foto.max' => 'La imagen preparada para OCR es demasiado pesada.',
            'dni_foto.dimensions' => 'La imagen preparada para OCR tiene una resolución demasiado grande.',
        ];
    }
}
