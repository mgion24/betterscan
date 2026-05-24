<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validación del formulario del asistente de escaneo.
// Casi todos los campos del paso 3 son opcionales: para las plantillas
// predefinidas el motor usa su propio comando y los ignora; sólo cuando
// la plantilla es "custom" tiene sentido pedir cada parámetro.
class StoreEscaneoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $esCustom = $this->input('plantilla') === 'custom';
        $gobActivo = $esCustom && $this->boolean('gobuster_habilitar');
        $listaTargets = $this->reglaListaTargets();

        return [
            // Paso 1.
            'nombre'      => ['required', 'string', 'min:3', 'max:150', 'regex:/^[\pL\pN\s\-_.]+$/u'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'objetivo'    => ['required', 'string', 'max:500', $listaTargets],
            'excluir'     => ['nullable', 'string', 'max:500', $listaTargets],

            // Paso 2.
            'plantilla' => ['required', Rule::in([
                'host_discovery', 'quick_scan', 'full_port_scan',
                'service_detection', 'vuln_scan', 'aggressive',
                'web_audit', 'custom',
            ])],

            // Paso 3.
            'velocidad'         => ['nullable', Rule::in(['none','T0','T1','T2','T3','T4','T5'])],
            'intensidad'        => ['nullable', Rule::in(['sigiloso','normal','agresivo'])],
            'tipo_escaneo_nmap' => ['nullable', 'string', Rule::in(['','sS','sT','sU','sN','sF','sX','sA'])],
            'descubrimiento'    => ['nullable', 'string', Rule::in(['auto','Pn','sn','PE','PS','PA'])],

            'puertos'        => ['nullable', 'string', Rule::in(['top-100','top-1000','top-5000','all','1-1024','custom'])],
            'puertos_custom' => [
                // Obligatorio si en el selector de puertos ponen "personalizado".
                $this->input('puertos') === 'custom' ? 'required' : 'nullable',
                'string', 'max:500',
                'regex:/^[0-9,\-T:U]+$/',
            ],

            'min_rate'    => ['nullable', 'integer', 'min:0', 'max:100000'],
            'max_retries' => ['nullable', 'integer', 'min:0', 'max:10'],

            'scripts_nse'   => ['nullable', 'array'],
            'scripts_nse.*' => [Rule::in(['default','safe','vuln','discovery','intrusive','auth','brute','exploit','malware','version'])],

            'detectar_os'        => ['nullable', 'boolean'],
            'detectar_servicios' => ['nullable', 'boolean'],
            'resolver_dns'       => ['nullable', 'boolean'],
            'traceroute'         => ['nullable', 'boolean'],
            'open_only'          => ['nullable', 'boolean'],
            'razon_estado'       => ['nullable', 'boolean'],
            'verbosidad'         => ['nullable', Rule::in(['','v','vv'])],

            // Evasión.
            'fragmentar'  => ['nullable', 'boolean'],
            'mtu'         => ['nullable', 'integer', 'min:8', 'max:1500'],
            'decoy'       => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9.,:\-_ ]+$/'],
            'spoof_ip'    => ['nullable', 'string', 'max:64', 'regex:/^(\d{1,3}\.){3}\d{1,3}$/'],
            'source_port' => ['nullable', 'integer', 'between:1,65535'],
            'spoof_mac'   => ['nullable', 'string', 'max:32', 'regex:/^([0]|[A-Za-z]+|([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2})$/'],
            'data_length' => ['nullable', 'integer', 'between:1,1400'],
            'badsum'      => ['nullable', 'boolean'],

            // Exportar.
            'exportar_xml'  => ['nullable', 'boolean'],
            'exportar_nmap' => ['nullable', 'boolean'],
            'exportar_grep' => ['nullable', 'boolean'],

            // Gobuster.
            'gobuster_habilitar'       => ['nullable', 'boolean'],
            'gobuster_modo'            => [$gobActivo ? 'required' : 'nullable', Rule::in(['dir','dns','vhost'])],
            'gobuster_wordlist'        => [$gobActivo ? 'required' : 'nullable', Rule::in(['common','medium','big','raft'])],
            'gobuster_extensiones'     => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9,]*$/'],
            'gobuster_status_codes'    => ['nullable', 'string', 'max:50', 'regex:/^[0-9,]*$/'],
            'gobuster_threads'         => ['nullable', 'integer', 'min:1', 'max:50'],
            'gobuster_follow_redirect' => ['nullable', 'boolean'],
            'gobuster_no_tls'          => ['nullable', 'boolean'],
            'gobuster_exportar'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'         => 'Tienes que dar un nombre al escaneo.',
            'nombre.min'              => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.regex'            => 'El nombre solo puede contener letras, números, espacios, guiones y puntos.',
            'objetivo.required'       => 'Tienes que indicar al menos un objetivo.',
            'plantilla.required'      => 'Selecciona una plantilla de escaneo.',
            'plantilla.in'            => 'La plantilla seleccionada no es válida.',
            'puertos_custom.required' => 'Has elegido «Personalizado» en puertos: indica la lista.',
            'puertos_custom.regex'    => 'Lista de puertos: solo números, comas, guiones y prefijos T:/U:.',
            'decoy.regex'             => 'Decoys: IPs separadas por comas, RND:n o ME.',
            'spoof_ip.regex'          => 'Spoof IP no es una IP válida.',
            'source_port.between'     => 'El puerto origen debe estar entre 1 y 65535.',
            'spoof_mac.regex'         => 'Spoof MAC inválida.',
            'data_length.between'     => 'Data length debe estar entre 1 y 1400.',
        ];
    }

    // Valida una lista de IPs/CIDR/hostnames separados por comas.
    private function reglaListaTargets(): \Closure
    {
        return function (string $atributo, ?string $valor, \Closure $fail) {
            if ($valor === null || trim($valor) === '') return;

            $reIp   = '/^(\d{1,3}\.){3}\d{1,3}$/';
            $reCidr = '/^(\d{1,3}\.){3}\d{1,3}\/(3[0-2]|[12]?\d)$/';
            $reHost = '/^(?=.{1,253}$)([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/';

            foreach (explode(',', $valor) as $item) {
                $t = trim($item);
                if ($t === '') continue;

                if (preg_match($reCidr, $t) || preg_match($reIp, $t)) {
                    foreach (explode('.', explode('/', $t)[0]) as $oct) {
                        if ((int) $oct < 0 || (int) $oct > 255) {
                            $fail("«{$t}» no es una IP válida.");
                            return;
                        }
                    }
                    continue;
                }
                if (preg_match($reHost, $t)) continue;

                $fail("«{$t}» no es una IP, rango CIDR ni hostname válido.");
                return;
            }
        };
    }
}
