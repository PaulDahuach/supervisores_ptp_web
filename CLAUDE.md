# Supervisores PTP — contexto para Claude

Interfaz **reducida de planta** para supervisores de PTP. MISMO backend que Producción PTP
(la misma `.mdb`), pero con **login sectorizado** y vista acotada a "lo de mi sector".
Construido con `inforemp-web-kit` (patrón B, COM/ADODB sobre la `.mdb` legacy).

- **Repo:** github.com/PaulDahuach/supervisores_ptp_web.git (rama `main`)
- **base_url:** `/supervisores_ptp` · **mode:** `readwrite` (los supervisores despachan lotes)
- **Backend dev:** `C:\_Inforemp\_dev_ptp\Produccion PTP_w2.mdb` (misma copia que produccion_ptp).
- **Login = OPERARIO** (clave `CDAOPR` en `Tbl Operarios`, NO `Tbl Usuarios`) **+ elige sector**.
  Logins test: `MAURI123` (FERNANDEZ MAURICIO / sector LAVADO), `DNALUAP` (PAUL, 40 sectores).
- **Fuente VBA:** `..._ProcesadoraTextilParque\VBA_Source_Supervisores\` (Menu_Logon,
  Frm Etapa Personalizada=avance, Frm Despacho=a administración).

## Feature clave del kit: `sector_login` (opt-in)
Activado en `config/system.php`. El login pasa por `app/sector.php` (autoselecciona si el
operario tiene 1 solo sector). Helpers en `includes/auth.php`: `auth_sector()`,
`auth_set_sector()`, `auth_sectors()`. Config: `sector_login => [rel_tabla 'Tbl Operarios
Sectores', rel_fk CODOPR, rel_sector CODETA, sec_tabla 'Tbl Etapas', sec_pk CODETA, sec_den
DENETA]`. Es opt-in: NO afecta a las instancias estándar (produccion_ptp sigue sin paso de sector).

## Qué está hecho
- **`modules/despacho/`** — lotes EN MI SECTOR (`CSDODP = auth_sector() AND DSPODP>0`, scope
  server-side) + acción **despachar** (Frm Etapa Personalizada SetData "M", netea iniciar+cerrar):
  crea lote hacia el sector del próximo proceso (o 110 DESPACHO si es el último), descuenta del
  lote y proceso origen, acumula en destino. **Si `auth_sector()==110`** → `despacharAdmin`:
  envía a Administración (lote→120, cabecera de entrega). Orden NUEVA termina en **CODETA=+120**.
- Dashboard con KPIs de mi sector (KPI SQL soporta `{SECTOR}` → `auth_sector()`).
- SIN movimientos de stock ni captura de operarios (tablas vacías en este install — fiel a cómo
  opera). Evitar `Nz()` (leer-calcular-escribir).

## Circuito completo (2 sistemas)
Recepción→Definición→Programación (en **produccion_ptp**) → avance por sector / despacho al
próximo proceso → Administración (en **supervisores_ptp**).

## Reglas técnicas (ver CLAUDE.md del kit y de produccion_ptp)
- **PHP 5.5** target, **CRLF** EOL, ACE sin UNION-en-subquery/Count(DISTINCT)/Nz(), fechas serial.
- Escrituras en transacción, lógica fiel al VBA.
- `git -C C:\wamp64\www\supervisores_ptp`. `config/system.php` NO versionado.
- OJO cookie `IWKSESSID` compartida entre instancias en localhost → alternar produccion/
  supervisores localmente exige re-login (en prod son hosts distintos, sin conflicto).

## Historia completa
Memoria de **inforemp_inside**:
`C:\Users\pauld\.claude\projects\C--wamp64-www-inforemp-inside\memory\inforemp_web_kit.md`.
