/* Despacho (Supervisores) — lotes en mi sector + despachar al próximo proceso. */
const Desp = {
    table: null, RO: window.SUP_RO, modal: null, rows: [],

    el(id) { return document.getElementById(id); },
    esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); },

    async load() {
        this.el('resumen').textContent = 'Cargando...';
        var j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) { this.el('resumen').textContent = 'Error: ' + j.error; return; }
        this.rows = j.data || [];
        var self = this;
        var data = this.rows.map(function (r, i) {
            var btn = self.RO ? '' : '<button class="btn btn-sm btn-success btn-desp" data-i="' + i + '"><i class="bi bi-arrow-right-circle me-1"></i>Despachar</button>';
            return [r.ODP, r.LOTE, self.esc(r.CLIENTE), self.esc(r.MARCA), self.esc(r.PRENDA), self.esc(r.PROCESO), r.PENDIENTE, btn];
        });
        if (this.table) { this.table.clear().rows.add(data).draw(); }
        else {
            this.table = new DataTable('#tbl', {
                data: data, pageLength: 50, order: [[0, 'asc'], [1, 'asc']],
                columnDefs: [{ targets: [6], className: 'text-end' }, { targets: [7], orderable: false, searchable: false }],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
            if (!this.RO) {
                this.el('tbl').querySelector('tbody').addEventListener('click', function (e) {
                    var b = e.target.closest('.btn-desp'); if (b) self.abrir(self.rows[b.dataset.i]);
                });
            }
        }
        this.el('resumen').textContent = this.rows.length + ' lote(s) en tu sector';
    },

    abrir(r) {
        this.el('dErr').textContent = '';
        this.el('dInfo').textContent = 'ODP ' + r.ODP + ' · Lote ' + r.LOTE + ' · ' + (r.PROCESO || '') + ' · ' + (r.CLIENTE || '');
        this.el('d_num').value = r.ODP; this.el('d_ord').value = r.ORDEN; this.el('d_lot').value = r.LOTE;
        this.el('d_cant').value = r.PENDIENTE; this.el('d_rez').value = 0; this.el('d_obs').value = '';
        this.el('dDestino').textContent = 'Pendiente: ' + r.PENDIENTE;
        if (!this.modal) this.modal = new bootstrap.Modal(this.el('modalDesp'));
        this.modal.show();
        setTimeout(() => this.el('d_cant').focus(), 200);
    },

    async despachar(e) {
        e.preventDefault();
        this.el('dErr').textContent = '';
        var fd = new FormData();
        fd.append('numodp', this.el('d_num').value);
        fd.append('ordodp', this.el('d_ord').value);
        fd.append('lotodp', this.el('d_lot').value);
        fd.append('cant', this.el('d_cant').value);
        fd.append('rez', this.el('d_rez').value);
        fd.append('obs', this.el('d_obs').value);
        var j = await (await fetch('api.php?action=despachar', { method: 'POST', body: fd })).json();
        if (!j.ok) { this.el('dErr').textContent = j.error || 'Error'; return; }
        this.modal.hide();
        var msg = j.data.admin ? 'Lote enviado a Administración' : ('Lote despachado' + (j.data.despacho ? ' (a Despacho)' : ''));
        this.toast(msg, 'success');
        this.load();
    },

    toast(msg, type) {
        var t = this.el('toastMsg'); this.el('toastBody').textContent = msg;
        t.className = 'toast align-items-center border-0 text-bg-' + (type || 'info');
        bootstrap.Toast.getOrCreateInstance(t, { delay: 4000 }).show();
    },

    init() {
        this.el('btnReload').addEventListener('click', () => this.load());
        if (!this.RO) this.el('frmDesp').addEventListener('submit', (e) => this.despachar(e));
        this.load();
    },
};
document.addEventListener('DOMContentLoaded', () => Desp.init());
