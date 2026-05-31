<?php
/**
 * Despacho (Supervisores) — API. Vista de los lotes que están EN MI SECTOR.
 * El sector se toma de la sesión (auth_sector), NO del cliente → cada supervisor
 * sólo ve/opera lo suyo. (El "mover al próximo proceso" se agrega como acción aparte,
 * portando Frm Etapa Personalizada SetData "M".)
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/auth.php';
auth_require_login();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
try {
    switch ($action) {
        case 'list':     listar(); break;
        case 'despachar': despachar(); break;
        default: fail('Acción inválida: ' . $action);
    }
} catch (Exception $e) {
    fail($e->getMessage(), 500);
}

/** Lotes pendientes en el sector del supervisor (CSDODP = sector, DSPODP > 0). */
function listar() {
    $sector = intval(auth_sector());
    if ($sector <= 0) { fail('Sin sector activo'); return; }
    $sql = "SELECT L.NUMODP AS ODP, L.LOTODP AS LOTE, L.ORDODP AS ORDEN, L.DSPODP AS PENDIENTE,
              C.DENCLI AS CLIENTE, M.DENMAR AS MARCA, Pre.DENPRE AS PRENDA,
              Prc.DENPRC AS PROCESO, O.CANODP AS CANTIDAD
            FROM ((((([Tbl Ordenes De Proceso Lotes] AS L
              INNER JOIN [Tbl Ordenes De Proceso] AS O ON L.NUMODP = O.NUMODP)
              LEFT JOIN [Tbl Clientes] AS C ON O.CODCLI = C.CODCLI)
              LEFT JOIN [Tbl Marcas] AS M ON O.CODMAR = M.CODMAR)
              LEFT JOIN [Tbl Prendas] AS Pre ON O.CODPR1 = Pre.CODPRE)
              LEFT JOIN [Tbl Ordenes De Proceso Procesos] AS OPP ON (OPP.NUMODP = L.NUMODP) AND (OPP.ORDODP = L.ORDODP))
              LEFT JOIN [Tbl Procesos] AS Prc ON OPP.CODPRC = Prc.CODPRC
            WHERE L.CSDODP = $sector AND L.DSPODP > 0
            ORDER BY L.NUMODP, L.LOTODP;";
    ok(db_query($sql));
}

/**
 * Despacha un lote al próximo proceso (Frm Etapa Personalizada SetData "M", neteado
 * iniciar+cerrar; sin movimientos de stock ni operarios porque están desactivados).
 * El lote DEBE estar en el sector del supervisor (validado server-side).
 */
function despachar() {
    if (db_readonly()) { fail('Sistema en modo solo-lectura', 403); return; }
    $sector = intval(auth_sector());
    if ($sector <= 0) { fail('Sin sector activo'); return; }

    $num = intval($_POST['numodp'] ?? 0);
    $ord = intval($_POST['ordodp'] ?? 0);   // orden del proceso actual (en mi sector)
    $lot = intval($_POST['lotodp'] ?? 0);
    $cf  = (int) round((float) str_replace(',', '.', $_POST['cant'] ?? '0'));   // cantidad procesada (buena)
    $rez = (int) round((float) str_replace(',', '.', $_POST['rez'] ?? '0'));    // rezagos
    $obs = trim($_POST['obs'] ?? '');

    // Validar el lote de entrada y que esté en MI sector
    $L = db_row("SELECT DSPODP, CSDODP FROM [Tbl Ordenes De Proceso Lotes]
                 WHERE NUMODP=$num AND ORDODP=$ord AND LOTODP=$lot;");
    if (!$L) { fail('Lote no encontrado'); return; }
    if (intval($L['CSDODP']) !== $sector) { fail('Ese lote no pertenece a tu sector'); return; }
    $disp = (int) $L['DSPODP'];
    if ($cf <= 0) { fail('Ingresá la cantidad procesada'); return; }
    if ($cf + $rez > $disp) { fail("La cantidad + rezagos ($cf + $rez) supera lo pendiente ($disp)"); return; }

    // Sector DESPACHO (110): el lote terminó la ruta → enviar a Administración (120).
    if ($sector === 110) { despacharAdmin($num, $ord, $lot, $cf, $rez, $obs, $disp); return; }

    // Próximo proceso y su sector destino
    $maxPrc = (int) (db_row("SELECT MAX(ORDODP) AS m FROM [Tbl Ordenes De Proceso Procesos] WHERE NUMODP=$num;")['m'] ?? 0);
    $nextOrd = $ord + 1;
    if ($nextOrd <= $maxPrc) {
        $cp = db_row("SELECT CODPRC FROM [Tbl Ordenes De Proceso Procesos] WHERE NUMODP=$num AND ORDODP=$nextOrd;");
        $sec = db_row("SELECT CODETA FROM [Tbl Procesos] WHERE CODPRC=" . intval($cp['CODPRC'] ?? 0) . ";");
        $nextSector = intval($sec['CODETA'] ?? 110);
    } else {
        $nextSector = 110;   // DESPACHO (último proceso → administración)
    }
    $maxLot = (int) (db_row("SELECT MAX(LOTODP) AS m FROM [Tbl Ordenes De Proceso Lotes] WHERE NUMODP=$num AND ORDODP=$nextOrd;")['m'] ?? 0);
    $newLot = $maxLot + 1;

    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    $iso = to_iso_date($rc['FECAPE']); $p = explode('-', $iso);
    $f = "#{$p[1]}/{$p[2]}/{$p[0]}#";
    $obsSql = $obs === '' ? 'Null' : "'" . db_esc($obs) . "'";

    // valores actuales del proceso origen/destino (evito Nz en SQL: no siempre está en ACE)
    $opOri = db_row("SELECT DSPODP FROM [Tbl Ordenes De Proceso Procesos] WHERE NUMODP=$num AND ORDODP=$ord;");
    $oriDsp = (int) ($opOri['DSPODP'] ?? 0);
    $opDst = ($nextOrd <= $maxPrc)
        ? db_row("SELECT CANODP, DSPODP, REZODP FROM [Tbl Ordenes De Proceso Procesos] WHERE NUMODP=$num AND ORDODP=$nextOrd;")
        : null;

    db_begin();
    try {
        // 1) Lote destino (cerrado, disponible para el próximo sector)
        $lc = ['NUMODP', 'OPOODP', 'LPOODP', 'CSOODP', 'LOTODP', 'FEXODP', 'FIPODP', 'HIPODP', 'FFPODP', 'HFPODP',
               'CIPODP', 'CANODP', 'REZODP', 'DSPODP', 'OBSODP', 'ORDODP', 'CSDODP'];
        $lv = [$num, $ord, $lot, $sector, $newLot, $f, $f, 'Now()', $f, 'Now()',
               $cf, $cf, $rez, $cf, $obsSql, $nextOrd, $nextSector];
        db_exec("INSERT INTO [Tbl Ordenes De Proceso Lotes] ([" . implode('],[', $lc) . "]) VALUES (" . implode(',', $lv) . ");");

        // 2) Descontar del lote de entrada
        db_exec("UPDATE [Tbl Ordenes De Proceso Lotes] SET DSPODP = " . ($disp - $cf - $rez) .
                " WHERE NUMODP=$num AND ORDODP=$ord AND LOTODP=$lot;");

        // 3) Ajustar el proceso origen
        db_exec("UPDATE [Tbl Ordenes De Proceso Procesos] SET DSPODP = " . ($oriDsp - $cf - $rez) .
                " WHERE NUMODP=$num AND ORDODP=$ord;");

        // 4) Acumular en el proceso destino (si existe)
        if ($opDst !== null) {
            $nCan = (int) ($opDst['CANODP'] ?? 0) + ($cf - $rez);
            $nDsp = (int) ($opDst['DSPODP'] ?? 0) + $cf;
            $nRez = (int) ($opDst['REZODP'] ?? 0) + $rez;
            db_exec("UPDATE [Tbl Ordenes De Proceso Procesos]
                     SET CANODP=$nCan, DSPODP=$nDsp, REZODP=$nRez
                     WHERE NUMODP=$num AND ORDODP=$nextOrd;");
        }

        db_commit();
        ok(['numodp' => $num, 'nextSector' => $nextSector, 'despacho' => ($nextOrd > $maxPrc)]);
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo despachar: ' . $e->getMessage(), 500);
    }
}

/**
 * Envío a Administración (Frm Despacho SetData "M", camino normal): el lote terminó
 * la ruta y está en DESPACHO (110). Crea el lote final hacia Administración (120),
 * descompromete el lote de entrada, completa fechas/cantidades de entrega en la cabecera
 * y, si no quedan lotes pendientes en ese tramo, pasa la orden a CODETA=120.
 */
function despacharAdmin($num, $ord, $lot, $cf, $rez, $obs, $disp) {
    $nextOrd = $ord + 1;
    $maxLot = (int) (db_row("SELECT MAX(LOTODP) AS m FROM [Tbl Ordenes De Proceso Lotes] WHERE NUMODP=$num AND ORDODP=$nextOrd;")['m'] ?? 0);
    $newLot = $maxLot + 1;
    $rc = db_row("SELECT FECAPE FROM [Rec Control];");
    $iso = to_iso_date($rc['FECAPE']); $p = explode('-', $iso);
    $f = "#{$p[1]}/{$p[2]}/{$p[0]}#";
    $obsSql = $obs === '' ? 'Null' : "'" . db_esc($obs) . "'";

    $h = db_row("SELECT CIDODP, FIDODP FROM [Tbl Ordenes De Proceso] WHERE NUMODP=$num;");
    $cid = (int) ($h['CIDODP'] ?? 0) + $cf;
    $primero = empty($h['FIDODP']);

    db_begin();
    try {
        // 1) Lote final → Administración (120)
        $lc = ['NUMODP', 'OPOODP', 'LPOODP', 'CSOODP', 'LOTODP', 'FEXODP', 'FIPODP', 'HIPODP', 'FFPODP', 'HFPODP',
               'CANODP', 'REZODP', 'DSPODP', 'OBSODP', 'ORDODP', 'CSDODP'];
        // CSOODP = Null y CODETA = -120: estado en que Administración deja el lote/orden
        // (uniforme en 7613 órdenes reales; lo crea la app de Administración al recibir).
        $lv = [$num, $ord, $lot, 'Null', $newLot, $f, $f, 'Now()', $f, 'Now()',
               $cf, $rez, $cf, $obsSql, $nextOrd, '120'];
        db_exec("INSERT INTO [Tbl Ordenes De Proceso Lotes] ([" . implode('],[', $lc) . "]) VALUES (" . implode(',', $lv) . ");");

        // 2) Descomprometer el lote de entrada (en DESPACHO)
        db_exec("UPDATE [Tbl Ordenes De Proceso Lotes] SET DSPODP = " . ($disp - $cf - $rez) .
                " WHERE NUMODP=$num AND ORDODP=$ord AND LOTODP=$lot;");

        // 3) Cabecera: fechas/cantidades de despacho a entrega
        $sets = "CIDODP=$cid, CFDODP=$cid, FDXODP=$f, FFDODP=$f, HFDODP=Now()";
        if ($primero) $sets .= ", FIDODP=$f, HIDODP=Now(), OIDODP=$obsSql";
        // ¿quedan lotes pendientes en este tramo? si no, la orden pasa a Administración
        $pend = db_row("SELECT COUNT(*) AS n FROM [Tbl Ordenes De Proceso Lotes] WHERE NUMODP=$num AND ORDODP=$ord AND DSPODP>0;");
        if (!$pend || (int) $pend['n'] === 0) $sets .= ", CODETA=-120";
        db_exec("UPDATE [Tbl Ordenes De Proceso] SET $sets WHERE NUMODP=$num;");

        db_commit();
        ok(['numodp' => $num, 'admin' => true]);
    } catch (Exception $e) {
        db_rollback();
        fail('No se pudo enviar a Administración: ' . $e->getMessage(), 500);
    }
}
