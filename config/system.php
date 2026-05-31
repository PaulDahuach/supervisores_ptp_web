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
    ],

    'afip' => ['enabled' => false],
    'deploy_key'  => 'sup_ptp_2026_cambiar',
];
