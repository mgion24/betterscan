<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Al editar ignoramos el id de la propia empresa para que no se compare consigo misma.
        $empresaId = $this->route('cliente')?->id;

        $sectoresValidos = [
            'tecnologia', 'financiero', 'sanidad', 'industrial',
            'retail', 'educacion', 'administracion', 'otros',
        ];

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:150'],
            'cif' => [
                'required', 'string', 'min:9', 'max:20',
                // Formato CIF/NIF español: letra inicial + 8 caracteres.
                'regex:/^[A-HJ-NP-SUVW0-9][0-9]{7}[0-9A-J]$/i',
                Rule::unique('empresa', 'cif')->ignore($empresaId),
            ],
            'nombre_comercial' => ['nullable', 'string', 'max:150'],
            'razon_social' => ['nullable', 'string', 'max:200'],
            'sector' => ['nullable', Rule::in($sectoresValidos)],
            'direccion' => ['nullable', 'string', 'max:300'],
            'activo' => ['nullable', 'boolean'],
            'responsable_nombre' => [
                'nullable', 'string', 'max:150',
                'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\' \-]+$/u',
            ],
            'responsable_email' => ['nullable', 'email', 'max:180'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la empresa es obligatorio.',
            'cif.required' => 'El CIF/NIF es obligatorio.',
            'cif.regex' => 'El CIF no tiene un formato válido (letra inicial + 8 caracteres).',
            'cif.unique' => 'Ya existe otra empresa con ese CIF.',
            'responsable_nombre.regex' => 'El nombre del responsable solo puede contener letras y espacios.',
        ];
    }
}
