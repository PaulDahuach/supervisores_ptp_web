# Supervisores PTP — Front web de planta

Interfaz **reducida para los supervisores de planta** de Producción PTP, construida sobre el
inforemp-web-kit. Corre sobre la **misma `.mdb`** que el sistema de escritorio y que el front de
`produccion_ptp` (patrón B, COM/ADODB) — sin migrar datos. Los supervisores solo ven y operan lo
de **su sector**: avanzar los lotes al próximo proceso.

```
Navegador ──HTTPS──> Apache+PHP (Windows) ──COM/ADODB──> "Produccion PTP_w2.mdb" <── App escritorio / produccion_ptp
```

## Particularidad: login sectorizado

A diferencia de `produccion_ptp` (login contra `Tbl Usuarios`), acá el login es contra
**`Tbl Operarios`** (clave `CDAOPR`) y, tras autenticar, el operario **elige el sector** desde el
cual operar (de `Tbl Operarios Sectores`). Es la feature **`sector_login`** del kit (opt-in por
config). El resto de las pantallas quedan scopeadas a ese sector.

## Módulos

- **`despacho`** — vista de los lotes **en mi sector** (`CSDODP = sector AND DSPODP > 0`) y acción de
  **avanzar** un lote al próximo proceso (porta `Frm Etapa Personalizada` SetData "M"). Cuando el
  sector es DESPACHO (110), el avance manda el lote a **Administración (120)** (porta `Frm Despacho`).

## Documentación

- **[docs/NOTAS.md](docs/NOTAS.md)** — notas técnicas (sector_login, lógica de despacho, convenciones).
- Instalación/puesta en producción: ver **`docs/DEPLOY.md` de `produccion_ptp`** (mismos requisitos:
  Windows + WAMP + PHP 7.4 + COM + Access Database Engine; apuntar `mdb_path` a la MISMA `.mdb`).

## Configuración

`config/system.php`: `auth` → `Tbl Operarios`/`CDAOPR`, `sector_login` activado, `mode=readwrite`
(los supervisores despachan), `mdb_path` = la misma `.mdb` que usa Producción PTP.
