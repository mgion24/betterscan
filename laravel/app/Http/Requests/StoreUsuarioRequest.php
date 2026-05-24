<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validación de creación y edición de usuario.
// Reglas importantes:
//  - email tiene que ser único (ignorando al propio usuario al editar)
//  - la contraseña obliga mayúscula + número, mínimo 8 caracteres
//  - empresa_id solo es obligatoria si el rol es "cliente"
class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización ya la hace el middleware role:admin en la ruta.
        return true;
    }

    public function rules(): array
    {
        // Al editar, ignoramos el id del propio usuario en las reglas únicas.
        $usuarioId = $this->route('usuario')?->id;

        $rolCliente = Rol::where('nombre', Rol::CLIENTE)->value('id');
        $rolIdElegido = (int) $this->input('rol_id');
        $esCliente = $rolIdElegido === (int) $rolCliente;

        $reglaPassword = $usuarioId ? 'nullable' : 'required';

        return [
            'nombre' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\' \-]+$/u'],
            'apellido' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\' \-]+$/u'],

            'email' => [
                'required', 'email', 'max:180',
                Rule::unique('usuario', 'email')->ignore($usuarioId),
            ],

            // Teléfono español: prefijo +34 opcional, móvil/fijo (6-9).
            'telefono' => [
                'nullable', 'string', 'max:20',
                'regex:/^(\+34[ \-]?)?[6-9]\d{2}[ \-]?\d{3}[ \-]?\d{3}$/',
            ],

            'rol_id' => ['required', 'integer', 'exists:rol,id'],

            // empresa_id solo es obligatoria si el rol elegido es cliente.
            'empresa_id' => [
                $esCliente ? 'required' : 'nullable',
                'nullable', 'integer', 'exists:empresa,id',
            ],

            // Contraseña: obligatoria en creación, opcional al editar.
            'password' => [
                $reglaPassword,
                'string', 'min:8', 'max:72',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ese correo ya está registrado en el sistema.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'password.regex' => 'La contraseña debe contener al menos una mayúscula y un número.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'empresa_id.required' => 'Los usuarios con rol cliente deben tener una empresa asignada.',
            'nombre.regex' => 'El nombre solo puede contener letras, espacios, apóstrofes y guiones.',
            'apellido.regex' => 'El apellido solo puede contener letras, espacios, apóstrofes y guiones.',
            'telefono.regex' => 'El teléfono debe tener formato español: 9 dígitos empezando por 6, 7, 8 o 9. Admite prefijo +34 (ej.: +34 612 345 678).',
        ];
    }
}
