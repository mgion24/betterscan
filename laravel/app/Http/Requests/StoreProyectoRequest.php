<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validación de creación y edición de proyecto.
class StoreProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La ruta ya filtra por rol con el middleware role:admin,empleado.
        return true;
    }

    public function rules(): array
    {
        $tiposPermitidos = [
            'caja_blanca', 'caja_negra', 'caja_gris',
            'red_team', 'pentest_web', 'pentest_interno', 'compliance',
        ];

        // El auditor tiene que ser un usuario con rol admin o empleado.
        $reglaAuditor = Rule::exists('usuario', 'id')->where(function ($q) {
            $q->whereIn('rol_id', function ($sub) {
                $sub->select('id')->from('rol')
                    ->whereIn('nombre', [Rol::ADMIN, Rol::EMPLEADO]);
            });
        });

        return [
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'etiquetas' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9\- _,]+$/'],
            'tipo_auditoria' => ['nullable', Rule::in($tiposPermitidos)],
            'alcance_red' => ['nullable', 'string', 'max:1000'],
            'excepciones_red' => ['nullable', 'string', 'max:500'],
            'visibilidad' => ['required', Rule::in(['privado', 'cliente'])],
            'fecha_limite_estimada' => ['nullable', 'date', 'after_or_equal:today'],
            'empresa_id' => ['required', 'integer', 'exists:empresa,id'],
            'auditor_id' => ['required', 'integer', $reglaAuditor],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del proyecto es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'empresa_id.exists' => 'La empresa seleccionada no existe.',
            'auditor_id.exists' => 'El auditor seleccionado no es válido (debe ser empleado o administrador).',
            'fecha_limite_estimada.after_or_equal' => 'La fecha límite debe ser hoy o una fecha futura.',
            'etiquetas.regex' => 'Las etiquetas solo pueden contener letras, números, espacios, guiones y comas.',
            'visibilidad.in' => 'La visibilidad debe ser «privado» o «cliente».',
        ];
    }
}
