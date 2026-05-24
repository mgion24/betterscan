<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Informe BetterScan — {{ $proyecto->nombre }}</title>
<style>
/* Estilo inspirado en el vault de Obsidian (tema Blue Topaz).
   Como dompdf no soporta flexbox/gradients/web-fonts uso Arial
   (mapea bien) y pt en lugar de px (compatible con dompdf). */

@page {
    margin: 20mm 18mm 22mm 18mm;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10.5pt;
    color: #1f2328;
    line-height: 1.5;
    text-align: justify;
}

/* Cabecera de página tipo "título del documento" del vault. */
.titulo {
    padding: 12pt 0;
    font-family: Arial, sans-serif;
    letter-spacing: 3pt;
    font-size: 22pt;
    text-align: center;
    color: #08377d;
    font-weight: bold;
    border-top: 3pt solid #0e63b9;
    border-bottom: 3pt solid #0e63b9;
    margin-bottom: 14pt;
}

.subtitulo {
    text-align: center;
    color: #57606a;
    font-size: 10pt;
    margin-bottom: 18pt;
}

/* Metadatos del informe (proyecto, empresa, auditor). */
.meta-box {
    background: #f6f8fa;
    border-left: 4pt solid #0e63b9;
    padding: 10pt 12pt;
    margin-bottom: 18pt;
    font-size: 10pt;
}

.meta-box .row {
    padding: 1.5pt 0;
}

.meta-box .label {
    display: inline-block;
    width: 110pt;
    color: #57606a;
    font-weight: bold;
}

/* Cabeceras de sección estilo Obsidian. */
h2 {
    color: #08377d;
    font-size: 15pt;
    font-weight: bold;
    margin-top: 22pt;
    margin-bottom: 8pt;
    padding-bottom: 4pt;
    border-bottom: 1.5pt solid #0e63b9;
    text-align: left;
}

h3 {
    color: #08377d;
    font-size: 12pt;
    font-weight: bold;
    margin-top: 14pt;
    margin-bottom: 6pt;
    text-align: left;
}

h4 {
    color: #1f2328;
    font-size: 11pt;
    font-weight: bold;
    margin-top: 8pt;
    margin-bottom: 4pt;
    text-align: left;
}

p {
    margin: 4pt 0 8pt;
}

.muted {
    color: #57606a;
}

.mono {
    font-family: 'Courier New', monospace;
    font-size: 9.5pt;
}

/* Grid de KPIs del resumen ejecutivo.
   En dompdf no hay flex, pero un table con 4 celdas da el mismo
   efecto visual. Sin bordes externos, fondo claro en cada celda. */
.kpi-row {
    width: 100%;
    border-collapse: separate;
    border-spacing: 6pt;
    margin: 12pt 0 16pt;
}

.kpi-row td {
    width: 25%;
    background: #f6f8fa;
    border-top: 3pt solid #d0d7de;
    padding: 10pt 8pt;
    text-align: center;
    vertical-align: top;
}

.kpi-row td.k-critica { border-top-color: #ff4f4f; }
.kpi-row td.k-alta    { border-top-color: #f28d3f; }
.kpi-row td.k-media   { border-top-color: #f0c040; }
.kpi-row td.k-baja    { border-top-color: #28a745; }

.kpi-row .label {
    font-size: 8pt;
    color: #57606a;
    text-transform: uppercase;
    letter-spacing: 1pt;
    font-weight: bold;
}

.kpi-row .value {
    font-size: 22pt;
    font-weight: bold;
    color: #08377d;
    margin-top: 4pt;
}

.kpi-row td.k-critica .value { color: #ff4f4f; }
.kpi-row td.k-alta .value    { color: #f28d3f; }
.kpi-row td.k-media .value   { color: #d29400; }
.kpi-row td.k-baja .value    { color: #28a745; }

/* Tablas tipo "informe técnico": cabecera azul, filas con stripe. */
table.data {
    width: 100%;
    border-collapse: collapse;
    margin-top: 6pt;
    font-size: 9.5pt;
    text-align: left;
}

table.data thead th {
    background: #08377d;
    color: #fff;
    font-weight: bold;
    padding: 5pt 7pt;
    text-align: left;
    border: 0;
    font-size: 9pt;
    text-transform: uppercase;
    letter-spacing: 0.5pt;
}

table.data tbody td {
    padding: 5pt 7pt;
    border-bottom: 0.7pt solid #d0d7de;
    vertical-align: top;
    text-align: left;
}

table.data tbody tr.alt td {
    background: #f6f8fa;
}

/* Badges de severidad: pildoritas pequeñas con los colores del
   vault de Obsidian (.riesgo-alto, .riesgo-medio, .riesgo-bajo). */
.badge {
    display: inline-block;
    padding: 1.5pt 7pt;
    border-radius: 8pt;
    font-size: 8pt;
    font-weight: bold;
    color: #fff;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.3pt;
}

.badge-critica { background: #ff4f4f; }
.badge-alta    { background: #f28d3f; }
.badge-media   { background: #f0c040; color: #1f2328; }
.badge-baja    { background: #28a745; }
.badge-nada    { background: #6e7681; }

/* Cabecera de escaneo (recuadro lateral). */
.escaneo-header {
    background: #eef4fa;
    border-left: 3pt solid #0e63b9;
    padding: 7pt 10pt;
    margin: 10pt 0 6pt;
    font-size: 9.5pt;
}

.escaneo-header .nombre {
    font-weight: bold;
    color: #08377d;
    font-size: 11pt;
    display: block;
    margin-bottom: 2pt;
}

/* Anexo de vulnerabilidades detalladas. */
.vuln-card {
    border: 1pt solid #d0d7de;
    border-left: 4pt solid #0e63b9;
    padding: 9pt 12pt;
    margin: 8pt 0;
    background: #fbfcfd;
    page-break-inside: avoid;
}

.vuln-card.sev-critica { border-left-color: #ff4f4f; }
.vuln-card.sev-alta    { border-left-color: #f28d3f; }
.vuln-card.sev-media   { border-left-color: #f0c040; }
.vuln-card.sev-baja    { border-left-color: #28a745; }

.vuln-card .titulo-vuln {
    font-weight: bold;
    color: #08377d;
    font-size: 11pt;
}

.vuln-card .ubic {
    color: #57606a;
    font-size: 9pt;
    margin-top: 1pt;
}

.vuln-card .desc {
    margin-top: 5pt;
    font-size: 10pt;
    text-align: justify;
}

.vuln-card .vector {
    font-family: 'Courier New', monospace;
    font-size: 8.5pt;
    color: #57606a;
    margin-top: 3pt;
    word-break: break-all;
}

.vuln-card .remed {
    margin-top: 5pt;
    padding: 5pt 8pt;
    background: #f0f6ec;
    border-left: 2pt solid #28a745;
    font-size: 9.5pt;
}

/* Pie del informe (incluyendo el número de página). */
.report-footer {
    margin-top: 24pt;
    border-top: 1pt solid #d0d7de;
    padding-top: 6pt;
    font-size: 8pt;
    color: #57606a;
    text-align: center;
}

/* dompdf no permite "running" elements para footer per page,
   pero el @page { @bottom-center } funciona para numeración. */
@page {
    @bottom-right {
        content: counter(page) " / " counter(pages);
        font-family: Arial, sans-serif;
        font-size: 8pt;
        color: #57606a;
    }
    @bottom-left {
        content: "BetterScan · Informe de auditoría";
        font-family: Arial, sans-serif;
        font-size: 8pt;
        color: #57606a;
    }
}

/* Evitar cortes feos a mitad de elementos. */
h2, h3, h4 { page-break-after: avoid; }
table { page-break-inside: avoid; }
</style>
</head>
<body>

@php
    $totalActivos = 0;
    $totalVulns = 0;
    $sevCounts = ['critica'=>0,'alta'=>0,'media'=>0,'baja'=>0,'nada'=>0];

    foreach ($proyecto->escaneos as $e) {
        $totalActivos += $e->activos->count();
        foreach ($e->activos as $a) {
            foreach ($a->puertos as $p) {
                foreach ($p->vulnerabilidades as $v) {
                    $totalVulns++;
                    $sev = $v->severidad ?? 'nada';
                    $sevCounts[$sev] = ($sevCounts[$sev] ?? 0) + 1;
                }
            }
        }
    }

    $empresaNombre = $proyecto->empresa->nombre_comercial ?? $proyecto->empresa->nombre;
@endphp


<div class="titulo">BETTERSCAN</div>
<div class="subtitulo">Informe de auditoría de seguridad · Tipo <strong>{{ ucfirst($tipo) }}</strong></div>


<div class="meta-box">
    <div class="row"><span class="label">Proyecto:</span> <strong>{{ $proyecto->nombre }}</strong></div>
    <div class="row"><span class="label">Empresa cliente:</span> {{ $empresaNombre }}</div>
    <div class="row"><span class="label">Tipo de auditoría:</span> {{ $proyecto->tipo_auditoria ?? '—' }}</div>
    <div class="row"><span class="label">Auditor responsable:</span> {{ $proyecto->auditor->nombreCompleto() }}</div>
    <div class="row"><span class="label">Emitido por:</span> {{ $emisor->nombreCompleto() }}</div>
    <div class="row"><span class="label">Fecha de emisión:</span> {{ now()->format('d/m/Y H:i') }}</div>
</div>


<h2>Resumen ejecutivo</h2>

<p>
    El proyecto <strong>{{ $proyecto->nombre }}</strong> de la empresa
    <strong>{{ $empresaNombre }}</strong> consta de
    <strong>{{ $proyecto->escaneos->count() }}</strong> escaneos,
    en los que se han descubierto un total de
    <strong>{{ $totalActivos }}</strong> activos
    y <strong>{{ $totalVulns }}</strong> vulnerabilidades.
    El siguiente cuadro detalla la distribución por severidad CVSS v3.1.
</p>

<table class="kpi-row">
    <tr>
        <td class="k-critica">
            <div class="label">Críticas</div>
            <div class="value">{{ $sevCounts['critica'] }}</div>
        </td>
        <td class="k-alta">
            <div class="label">Altas</div>
            <div class="value">{{ $sevCounts['alta'] }}</div>
        </td>
        <td class="k-media">
            <div class="label">Medias</div>
            <div class="value">{{ $sevCounts['media'] }}</div>
        </td>
        <td class="k-baja">
            <div class="label">Bajas</div>
            <div class="value">{{ $sevCounts['baja'] }}</div>
        </td>
    </tr>
</table>


@if($tipo !== 'ejecutivo')

    <h2>Detalle técnico por escaneo</h2>

    @foreach($proyecto->escaneos as $escaneo)

        <div class="escaneo-header">
            <span class="nombre">{{ $escaneo->nombre }}</span>
            <span class="muted">
                Objetivo: <span class="mono">{{ $escaneo->objetivo }}</span> ·
                Plantilla: {{ $escaneo->plantilla_escaneo ?? '—' }} ·
                Estado: {{ $escaneo->estado }}
                @if($escaneo->fecha_inicio)
                    · Inicio: {{ $escaneo->fecha_inicio->format('d/m/Y H:i') }}
                @endif
            </span>
        </div>

        @if($escaneo->activos->count() === 0)
            <p class="muted">El escaneo no descubrió ningún activo.</p>
        @else

            {{-- Una sección por activo: refleja la jerarquía
                 Escaneo -> Activo -> Puerto -> Vulnerabilidad. --}}
            @foreach($escaneo->activos as $iActivo => $activo)

                <h3>Activo #{{ $iActivo + 1 }} — {{ $activo->ip ?? 'sin IP' }}</h3>

                {{-- Ficha del activo con MAC explícita. --}}
                <table class="data">
                    <tbody>
                        <tr>
                            <td><strong>IP</strong></td>
                            <td class="mono">{{ $activo->ip ?? '—' }}</td>
                        </tr>
                        <tr class="alt">
                            <td><strong>MAC</strong></td>
                            <td class="mono">{{ $activo->mac ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Hostname</strong></td>
                            <td class="mono">{{ $activo->hostname ?? '—' }}</td>
                        </tr>
                        <tr class="alt">
                            <td><strong>Sistema operativo</strong></td>
                            <td>{{ $activo->sistema_operativo ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Red</strong></td>
                            <td class="mono">{{ $activo->direccion_red ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>

                @if($activo->puertos->count() > 0)
                    <h4>Puertos descubiertos en {{ $activo->ip }}</h4>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Puerto</th>
                                <th>Protocolo</th>
                                <th>Estado</th>
                                <th>Servicio</th>
                                <th>Versión</th>
                                <th>Vulns</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activo->puertos as $iPuerto => $puerto)
                                <tr class="{{ $iPuerto % 2 === 1 ? 'alt' : '' }}">
                                    <td class="mono">{{ $puerto->numero }}</td>
                                    <td class="mono">{{ $puerto->protocolo }}</td>
                                    <td>{{ $puerto->estado }}</td>
                                    <td>{{ $puerto->servicio ?? '—' }}</td>
                                    <td class="mono">{{ $puerto->version ?? '—' }}</td>
                                    <td>{{ $puerto->vulnerabilidades->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Una subsección por cada puerto que tenga vulns. Ahí
                         es donde se ve la relación Puerto -> Vulnerabilidad. --}}
                    @foreach($activo->puertos as $puerto)
                        @if($puerto->vulnerabilidades->count() > 0)
                            <h4>
                                Vulnerabilidades en {{ $activo->ip }}:{{ $puerto->numero }}/{{ $puerto->protocolo }}
                                @if($puerto->servicio) · servicio: <span class="mono">{{ $puerto->servicio }}</span>@endif
                            </h4>
                            <table class="data">
                                <thead>
                                    <tr>
                                        <th>CVE</th>
                                        <th>Severidad</th>
                                        <th>CVSS</th>
                                        <th>Descripción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($puerto->vulnerabilidades as $iVuln => $vuln)
                                        @php $sev = $vuln->severidad ?? 'nada'; @endphp
                                        <tr class="{{ $iVuln % 2 === 1 ? 'alt' : '' }}">
                                            <td class="mono">{{ $vuln->cve_asociado ?? 'Sin CVE' }}</td>
                                            <td>
                                                <span class="badge badge-{{ $sev }}">{{ $sev }}</span>
                                            </td>
                                            <td class="mono">{{ $vuln->cvss !== null ? number_format($vuln->cvss, 1) : '—' }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($vuln->descripcion ?? '', 110) ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endforeach
                @else
                    <p class="muted">Sin puertos detectados en este activo.</p>
                @endif

            @endforeach

        @endif

    @endforeach


    @if($tipo === 'completo')

        <h2>Anexo: detalle de vulnerabilidades</h2>
        <p class="muted">
            En esta sección se incluye la información extendida de cada
            vulnerabilidad encontrada (descripción completa, vector CVSS
            y recomendación de remediación cuando está disponible).
        </p>

        @foreach($proyecto->escaneos as $escaneo)
            @foreach($escaneo->activos as $a)
                @foreach($a->puertos as $p)
                    @foreach($p->vulnerabilidades as $v)

                        @php
                            $sev = $v->severidad ?? 'nada';
                        @endphp

                        <div class="vuln-card sev-{{ $sev }}">
                            <div class="titulo-vuln">
                                {{ $v->cve_asociado ?? 'Sin CVE asignado' }}
                            </div>
                            <div class="ubic">
                                {{ $a->ip }}:{{ $p->numero }}/{{ $p->protocolo }}
                                @if($p->servicio) · servicio: <span class="mono">{{ $p->servicio }}</span>@endif
                                @if($p->version) · versión: <span class="mono">{{ $p->version }}</span>@endif
                            </div>
                            <p style="margin: 4pt 0;">
                                <span class="badge badge-{{ $sev }}">{{ $sev }}</span>
                                @if($v->cvss !== null)
                                    <span class="mono" style="margin-left: 4pt;">CVSS {{ number_format($v->cvss, 1) }}</span>
                                @endif
                            </p>
                            @if($v->vector)
                                <div class="vector">{{ $v->vector }}</div>
                            @endif
                            <div class="desc">
                                {{ $v->descripcion ?? 'Sin descripción disponible.' }}
                            </div>
                            @if($v->remediacion)
                                <div class="remed">
                                    <strong>Remediación:</strong> {{ $v->remediacion }}
                                </div>
                            @endif
                        </div>

                    @endforeach
                @endforeach
            @endforeach
        @endforeach

    @endif

@endif


<div class="report-footer">
    Documento generado automáticamente por BetterScan ·
    {{ now()->format('d/m/Y H:i') }} ·
    Confidencial — solo para el destinatario indicado.
</div>

</body>
</html>
