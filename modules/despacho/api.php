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

$action = $_GET['action'] ?? '';
try {
    switch ($action) {
        case 'list': listar(); break;
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
