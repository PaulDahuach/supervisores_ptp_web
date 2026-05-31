# Supervisores PTP — Notas técnicas

Sistema hermano de `produccion_ptp`: misma `.mdb`, mismo kit, mismas convenciones técnicas
(ver `docs/NOTAS.md` de **produccion_ptp** para PHP 7.4, fechas serial Access, paréntesis de JOIN,
`next_number`/Rec Control, transacciones, etc.). Acá solo las particularidades de Supervisores.

## Login sectorizado (`sector_login`)

Feature opt-in del kit (`includes/auth.php`), activada por config. Flujo:
1. Login contra **`Tbl Operarios`** (`col_id=CODOPR`, `col_name=DENOPR`, clave `CDAOPR`) — NO `Tbl Usuarios`.
   - `auth_logged_in()` es robusto ante `uid=0` (existe el operario PAUL con CODOPR=0).
2. Tras autenticar, `app/sector.php` ofrece **elegir el sector** desde `Tbl Operarios Sectores`
   (FK `CODOPR`, sector `CODETA` → `Tbl Etapas`). Si hay uno solo, autoselecciona.
3. `auth_sector()` devuelve el sector activo; las pantallas lo usan para scopear server-side.
4. La topbar muestra el sector y permite cambiarlo.

Config relevante (`config/system.php` → `'sector_login'`): `rel_tabla='Tbl Operarios Sectores'`,
`rel_fk='CODOPR'`, `rel_sector='CODETA'`, `sec_tabla='Tbl Etapas'`, `sec_pk='CODETA'`, `sec_den='DENETA'`.

## Módulo `despacho`

Vista de lotes EN MI SECTOR: `CSDODP = auth_sector() AND DSPODP > 0` (scope server-side).

- **Avanzar** un lote (`despachar`, porta `Frm Etapa Personalizada` SetData "M", netea las 2 fases
  iniciar+cerrar del legacy): crea un lote hacia el sector del **próximo proceso** (CODETA del CODPRC
  en ORDODP+1; o 110 DESPACHO si es el último), descuenta del lote/proceso origen y acumula en el destino.
- **Despacho final** (cuando `auth_sector()==110`, porta `Frm Despacho`): manda el lote a
  **Administración (120)**; cabecera CIDODP/CFDODP/fechas de entrega, CODETA=120, lote CSOODP=110.
- SIN movimientos de stock (CODOPC=1=off en este install) ni captura de operarios (tablas vacías) —
  fiel a cómo opera ESTE install. Se evitó `Nz()` en SQL (leer-calcular-escribir).

## Circuito completo (2 sistemas)

`produccion_ptp`: Recepción → Definición → Programación → **`supervisores_ptp`**: avance por sector →
Despacho → Administración (120).

## Notas

- Fuente legacy: `_ProcesadoraTextilParque/VBA_Source_Supervisores/` (repo `ptp.git`).
- En localhost la cookie de sesión `IWKSESSID` se comparte entre instancias → al alternar
  produccion/supervisores hay que re-loguear. En producción cada uno vive en su host → sin conflicto.
- Al instalar: apuntar `mdb_path` a la MISMA `.mdb` de Producción; `mode=readwrite`.
