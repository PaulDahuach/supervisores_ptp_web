<?php
/** Despacho (Supervisores) — lotes en mi sector. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

module_head('Lotes en mi sector', 'bi-truck',
    '<button id="btnReload" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
      <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Lotes que llegaron a tu sector (pendientes)</span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100">
      <thead><tr>
        <th>ODP N°</th><th>Lote</th><th>Cliente</th><th>Marca</th><th>Prenda</th>
        <th>Proceso</th><th class="text-end">Pendiente</th>
      </tr></thead><tbody></tbody>
    </table>
  </div>
</div>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/despacho.js"></script>
');
