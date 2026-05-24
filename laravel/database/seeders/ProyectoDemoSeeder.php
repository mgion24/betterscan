<?php

namespace Database\Seeders;

use App\Models\Activo;
use App\Models\Escaneo;
use App\Models\Proyecto;
use App\Models\Puerto;
use App\Models\Vulnerabilidad;
use Illuminate\Database\Seeder;

/**
 * Crea proyectos y escaneos de demostración con datos coherentes.
 *
 * Esto es lo que permite enseñar la aplicación durante la defensa
 * sin necesidad de lanzar un escaneo en vivo: hay vulnerabilidades
 * ya enriquecidas, informes, etc.
 *
 * Adicionalmente se crea un escaneo en estado "pendiente" para que
 * se pueda mostrar el flujo completo (asistente → polling → resultados)
 * con datos generados al vuelo por el motor.
 */
class ProyectoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $proyectos = [
            [
                'id' => 1, 'nombre' => 'Auditoria Red Interna ACME',
                'descripcion' => 'Auditoría de seguridad de la infraestructura de red interna corporativa.',
                'tipo_auditoria' => 'Caja blanca', 'alcance_red' => '192.168.10.0/24, 10.0.0.0/24',
                'excepciones_red' => '192.168.10.1', 'visibilidad' => 'cliente',
                'fecha_limite_estimada' => now()->addMonth()->toDateString(),
                'empresa_id' => 1, 'auditor_id' => 2,
                'etiquetas' => 'red-interna, q2-2026',
            ],
            [
                'id' => 2, 'nombre' => 'Pentest web ACME',
                'descripcion' => 'Auditoría del portal corporativo público.',
                'tipo_auditoria' => 'Caja negra', 'alcance_red' => 'web.acmecorp.com',
                'visibilidad' => 'privado', 'fecha_limite_estimada' => now()->addWeeks(3)->toDateString(),
                'empresa_id' => 1, 'auditor_id' => 2, 'etiquetas' => 'web, owasp',
            ],
            [
                'id' => 3, 'nombre' => 'Compliance RetailMax',
                'descripcion' => 'Revisión PCI-DSS de la red de TPVs.',
                'tipo_auditoria' => 'Compliance', 'alcance_red' => '172.16.0.0/16',
                'visibilidad' => 'cliente', 'fecha_limite_estimada' => now()->addMonths(2)->toDateString(),
                'empresa_id' => 3, 'auditor_id' => 3, 'etiquetas' => 'pci, retail',
            ],
        ];
        foreach ($proyectos as $p) {
            Proyecto::updateOrCreate(['id' => $p['id']], $p);
        }

        // ESCANEO COMPLETADO de demostración (proyecto 1).
        $escaneo = Escaneo::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Escaneo inicial de descubrimiento',
                'descripcion' => 'Primera pasada para inventariar activos.',
                'tipo_escaneo' => 'Análisis de vulnerabilidades',
                'plantilla_escaneo' => 'vuln_scan',
                'objetivo' => '192.168.10.0/24',
                'velocidad' => 'normal', 'intensidad' => 'normal',
                'estado' => Escaneo::ESTADO_COMPLETADO,
                'progreso_pct' => 100,
                'fase_actual' => 'Completado',
                'fecha_inicio' => now()->subDay(),
                'fecha_fin'    => now()->subDay()->addMinutes(8),
                'parametros_nmap' => [
                    'plantilla' => 'vuln_scan', 'velocidad' => 'T3',
                    'intensidad' => 'normal', 'puertos' => 'top-1000',
                    'scripts_nse' => ['default', 'vuln'],
                    'detectar_servicios' => true,
                ],
                'proyecto_id' => 1,
            ]
        );

        $this->sembrarActivosDemo($escaneo);

        // ESCANEO PENDIENTE para enseñar el flujo en vivo.
        Escaneo::updateOrCreate(
            ['id' => 2],
            [
                'nombre' => 'Escaneo agresivo (pendiente)',
                'tipo_escaneo' => 'Escaneo agresivo',
                'plantilla_escaneo' => 'aggressive',
                'objetivo' => '10.0.0.0/24',
                'velocidad' => 'rapido', 'intensidad' => 'agresivo',
                'estado' => Escaneo::ESTADO_PENDIENTE,
                'progreso_pct' => 0,
                'fase_actual' => 'Pendiente de lanzamiento',
                'parametros_nmap' => [
                    'plantilla' => 'aggressive', 'velocidad' => 'T4',
                    'intensidad' => 'agresivo', 'puertos' => 'top-1000',
                    'scripts_nse' => ['default', 'safe', 'vuln'],
                    'detectar_os' => true, 'detectar_servicios' => true,
                ],
                'proyecto_id' => 1,
            ]
        );
    }

    /**
     * Genera 3 hosts con servicios y vulnerabilidades realistas.
     * Los CVEs son reales y existen en NVD por si en algún momento
     * se les pasa el enricher.
     */
    private function sembrarActivosDemo(Escaneo $escaneo): void
    {
        // Limpiar resultados previos para idempotencia.
        $escaneo->activos()->delete();

        // OUIs reales para que el dato parezca salida natural de nmap:
        // 00:50:56 = VMware (servidor virtualizado),
        // 00:0c:29 = VMware (otra serie),
        // 00:1b:21 = Intel (NIC de hardware físico, coherente con un FW edge).
        $hosts = [
            [
                'ip' => '192.168.10.10', 'hostname' => 'srv-web-01',
                'mac' => '00:50:56:a1:b2:c3',
                'sistema_operativo' => 'Ubuntu 22.04 LTS',
                'puertos' => [
                    [
                        'numero' => 22, 'servicio' => 'ssh', 'version' => 'OpenSSH 8.9p1',
                        'vulns' => [],
                    ],
                    [
                        'numero' => 80, 'servicio' => 'http', 'version' => 'nginx 1.24.0',
                        'vulns' => [],
                    ],
                    [
                        'numero' => 443, 'servicio' => 'https', 'version' => 'nginx 1.24.0',
                        'vulns' => [['cve' => 'CVE-2014-0160',
                                      'desc' => 'Vulnerabilidad Heartbleed en OpenSSL: permite leer memoria del proceso del servidor remoto.',
                                      'cvss' => 7.5, 'sev' => 'alta',
                                      'vec' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N',
                                      'rem' => 'Actualizar OpenSSL a una versión >= 1.0.1g.']],
                    ],
                ],
            ],
            [
                'ip' => '192.168.10.20', 'hostname' => 'srv-db-01',
                'mac' => '00:0c:29:d4:e5:f6',
                'sistema_operativo' => 'Debian 12',
                'puertos' => [
                    [
                        'numero' => 22, 'servicio' => 'ssh', 'version' => 'OpenSSH 9.2p1',
                        'vulns' => [],
                    ],
                    [
                        'numero' => 3306, 'servicio' => 'mysql', 'version' => 'MariaDB 10.11',
                        'vulns' => [['cve' => 'CVE-2022-21417',
                                      'desc' => 'Vulnerabilidad en MySQL Server: permite a un atacante autenticado provocar denegación de servicio.',
                                      'cvss' => 4.9, 'sev' => 'media',
                                      'vec' => 'CVSS:3.1/AV:N/AC:L/PR:H/UI:N/S:U/C:N/I:N/A:H',
                                      'rem' => 'Actualizar a MySQL 5.7.38 o MySQL 8.0.29.']],
                    ],
                ],
            ],
            [
                'ip' => '192.168.10.50', 'hostname' => 'fw-edge',
                'mac' => '00:1b:21:7a:8b:9c',
                'sistema_operativo' => 'FreeBSD 14',
                'puertos' => [
                    [
                        'numero' => 21, 'servicio' => 'ftp', 'version' => 'vsftpd 2.3.4',
                        'vulns' => [['cve' => 'CVE-2011-2523',
                                      'desc' => 'Puerta trasera en vsftpd 2.3.4: abre un shell en el puerto 6200/tcp cuando se envía ":)" como usuario.',
                                      'cvss' => 9.8, 'sev' => 'critica',
                                      'vec' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
                                      'rem' => 'Reemplazar vsftpd por una versión limpia descargada del repositorio oficial.']],
                    ],
                    [
                        'numero' => 445, 'servicio' => 'microsoft-ds', 'version' => 'Samba 4.17',
                        'vulns' => [],
                    ],
                ],
            ],
        ];

        foreach ($hosts as $h) {
            $activo = Activo::create([
                'ip' => $h['ip'], 'mac' => $h['mac'], 'hostname' => $h['hostname'],
                'sistema_operativo' => $h['sistema_operativo'],
                'direccion_red' => '192.168.10.0/24',
                'escaneo_id' => $escaneo->id,
            ]);

            foreach ($h['puertos'] as $p) {
                $puerto = Puerto::create([
                    'numero' => $p['numero'], 'protocolo' => 'tcp', 'estado' => 'open',
                    'servicio' => $p['servicio'], 'version' => $p['version'],
                    'activo_id' => $activo->id,
                ]);

                foreach ($p['vulns'] as $v) {
                    Vulnerabilidad::create([
                        'cve_asociado' => $v['cve'],
                        'descripcion' => $v['desc'],
                        'cvss' => $v['cvss'],
                        'severidad' => $v['sev'],
                        'vector' => $v['vec'],
                        'remediacion' => $v['rem'],
                        'referencias' => "https://nvd.nist.gov/vuln/detail/{$v['cve']}",
                        'enriquecido_en' => now(),
                        'puerto_id' => $puerto->id,
                    ]);
                }
            }
        }
    }
}
