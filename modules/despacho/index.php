<?php
/** Despacho (Supervisores) — lotes en mi sector + mover al próximo proceso. */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
auth_require_login();

$ro = db_readonly();
module_head('Lotes en mi sector', 'bi-truck',
    '<button id="btnReload" class="btn btn-outline-light btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>');
?>
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="../abm/assets/css/abm.css" rel="stylesheet">
<script>window.SUP_RO = <?= $ro ? 'true' : 'false' ?>;</script>

<div class="card">
  <div class="card-body">
    <div class="d-flex justify-content-between mb-2">
      <span class="text-muted small" id="resumen">—</span>
      <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Despachá un lote para enviarlo al próximo proceso</span>
    </div>
    <table id="tbl" class="table table-sm table-striped table-hover w-100">
      <thead><tr>
        <th>ODP N°</th><th>Lote</th><th>Cliente</th><th>Marca</th><th>Prenda</th>
        <th>Proceso</th><th class="text-end">Pendiente</th><th class="w-1"></th>
      </tr></thead><tbody></tbody>
    </table>
  </div>
</div>

<!-- MODAL DESPACHO -->
<div class="modal fade" id="modalDesp" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="frmDesp">
        <div class="modal-header py-2">
          <h6 class="modal-title"><i class="bi bi-truck me-2"></i>Despachar lote</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2" id="dInfo">—</p>
          <input type="hidden" id="d_num"><input type="hidden" id="d_ord"><input type="hidden" id="d_lot">
          <div class="row g-2">
            <div class="col-6"><label class="form-label small">Cantidad procesada <span class="text-danger">*</span></label>
              <input type="number" id="d_cant" class="form-control text-end"></div>
            <div class="col-6"><label class="form-label small">Rezagos</label>
              <input type="number" id="d_rez" class="form-control text-end" value="0"></div>
            <div class="col-12"><label class="form-label small">Observaciones</label>
              <input type="text" id="d_obs" class="form-control"></div>
          </div>
          <div class="small text-muted mt-2" id="dDestino"></div>
          <div class="text-danger small mt-1" id="dErr"></div>
        </div>
        <div class="modal-footer py-1">
          <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-arrow-right-circle me-1"></i>Despachar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="fc-toast-container">
  <div id="toastMsg" class="toast align-items-center border-0" role="alert">
    <div class="d-flex"><div class="toast-body" id="toastBody"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
  </div>
</div>

<?php
module_foot('
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/despacho.js"></script>
');
