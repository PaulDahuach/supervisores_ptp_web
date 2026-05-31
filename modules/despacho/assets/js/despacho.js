/* Despacho (Supervisores) — lotes en mi sector. */
(function () {
    var table = null;
    function esc(s) { return (s === null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    async function load() {
        document.getElementById('resumen').textContent = 'Cargando...';
        var j = await (await fetch('api.php?action=list')).json();
        if (!j.ok) { document.getElementById('resumen').textContent = 'Error: ' + j.error; return; }
        var rows = j.data || [];
        var data = rows.map(function (r) {
            return [r.ODP, r.LOTE, esc(r.CLIENTE), esc(r.MARCA), esc(r.PRENDA), esc(r.PROCESO), r.PENDIENTE];
        });
        if (table) { table.clear().rows.add(data).draw(); }
        else {
            table = new DataTable('#tbl', {
                data: data, pageLength: 50, order: [[0, 'asc'], [1, 'asc']],
                columnDefs: [{ targets: [6], className: 'text-end' }],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-AR.json' }
            });
        }
        document.getElementById('resumen').textContent = rows.length + ' lote(s) en tu sector';
    }

    document.getElementById('btnReload').addEventListener('click', load);
    load();
})();
