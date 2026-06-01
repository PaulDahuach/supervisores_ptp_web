<?php
/**
 * Configuración — Supervisores PTP (interfaz reducida de planta).
 * Mismo backend que Producción PTP, pero login sectorizado: el supervisor entra
 * como OPERARIO (clave CDAOPR) y elige uno de SUS sectores (Tbl Operarios Sectores).
 */
return [
    'base_url'    => '/supervisores_ptp',
    'name'        => 'Supervisores PTP',
    'short_name'  => 'Supervisores',
    'tagline'     => 'Producción — Planta',

    // Mismo .mdb de dev que Producción.
    'mdb_path'    => 'C:\\_Inforemp\\_dev_ptp\\Produccion PTP_w2.mdb',
    'mdb_provider'=> 'Microsoft.ACE.OLEDB.12.0',
    'mdb_pass'    => '',

    'mode'        => 'readwrite',   // los supervisores despachan lotes

    // Login contra OPERARIOS (no Tbl Usuarios): clave = CDAOPR.
    'auth' => [
        'table'    => 'Tbl Operarios',
        'col_id'   => 'CODOPR',
        'col_name' => 'DENOPR',
        'col_pass' => 'CDAOPR',
    ],

    // Login sectorizado: tras autenticar, elegir sector de los asignados al operario.
    'sector_login' => [
        'rel_tabla'  => 'Tbl Operarios Sectores',
        'rel_fk'     => 'CODOPR',
        'rel_sector' => 'CODETA',
        'sec_tabla'  => 'Tbl Etapas',
        'sec_pk'     => 'CODETA',
        'sec_den'    => 'DENETA',
    ],

    'logo'        => '/assets/img/logo.png',
    'primary'     => '#16a34a',
    'theme'       => 'dark',

    'menu' => [
        'Mi sector' => [
            ['label' => 'Despacho de Lotes', 'desc' => 'Mover lotes al próximo proceso', 'icon' => 'bi-truck', 'url' => '/modules/despacho/'],
        ],
        // Acceso al portal (selector de sistemas). URL absoluta → bu() la respeta.
        // En producción cambiar localhost por el host/raíz de la LAN.
        'Acceso' => [
            ['label' => 'Portal de Sistemas', 'desc' => 'Volver al selector / cambiar de sistema', 'icon' => 'bi-grid-3x3-gap-fill', 'url' => 'http://localhost/ptp_portal/'],
        ],
    ],

    // Tablero: indicadores de MI sector ({SECTOR} = sector activo) + acceso rápido.
    'dashboard' => [
        'kpis' => [
            ['label' => 'Lotes en mi sector',    'icon' => 'bi-truck',    'color' => '#10b981', 'url' => '/modules/despacho/',
             'sql' => "SELECT Count(*) AS N FROM [Tbl Ordenes De Proceso Lotes] WHERE CSDODP={SECTOR} AND DSPODP>0;"],
            ['label' => 'Prendas pendientes',     'icon' => 'bi-stack',    'color' => '#0ea5e9', 'url' => '/modules/despacho/',
             'sql' => "SELECT Int(Sum(DSPODP)) AS N FROM [Tbl Ordenes De Proceso Lotes] WHERE CSDODP={SECTOR} AND DSPODP>0;"],
        ],
        'quick' => [
            ['label' => 'Despacho de Lotes', 'icon' => 'bi-truck',            'url' => '/modules/despacho/'],
            ['label' => 'Portal',            'icon' => 'bi-grid-3x3-gap-fill', 'url' => 'http://localhost/ptp_portal/'],
        ],
    ],

    'afip' => ['enabled' => false],
    'deploy_key'  => 'sup_ptp_2026_cambiar',
];
