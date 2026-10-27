<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'recepcion']);
require_once '../includes/header.php';
?>


<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Pagos</h2>
    <div class="d-flex gap-2">
        <select id="filtro" class="form-select" style="min-width: 150px;">
            <option value="todos">Todos</option>
            <option value="hoy">Hoy</option>
            <option value="mes">Este mes</option>
        </select>
        <button class="btn btn-success text-nowrap" onclick="nuevoPago()">
            <i class="bi bi-cash-coin"></i> Registrar pago
        </button>
    </div>
</div>

<!-- Resumen -->
<div class="row g-3 mb-3">
    <div class="col-12 col-md-4">
        <div class="card border-success">
            <div class="card-body py-3">
                <div class="text-muted small">Cobrado hoy</div>
                <div class="h4 mb-0 text-success" id="cobradoHoy">Q 0.00</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-info">
            <div class="card-body py-3">
                <div class="text-muted small">Cobrado este mes</div>
                <div class="h4 mb-0" id="cobradoMes">Q 0.00</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-primary">
            <div class="card-body py-3">
                <div class="text-muted small">Total histórico</div>
                <div class="h4 mb-0" id="cobradoTotal">Q 0.00</div>
            </div>
        </div>
    </div>
</div>

<!-- Métodos de pago -->
<div class="row g-3 mb-3" id="tarjetasMetodos">
    <!-- se llena dinámicamente -->
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Paciente</th>
                    <th class="d-none d-md-table-cell">Factura</th>
                    <th>Método</th>
                    <th class="text-end">Monto</th>
                </tr>
            </thead>
            <tbody id="tablaPagos">
                <tr><td colspan="6" class="text-center py-4">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formPago">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Factura *</label>
                        <select name="id_factura" id="selFactura" class="form-select" required>
                            <option value="">— Seleccionar —</option>
                        </select>
                        <div id="infoFactura" class="form-text"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto *</label>
                        <div class="input-group">
                            <span class="input-group-text">Q</span>
                            <input type="number" name="monto" id="monto" class="form-control"
                                   step="0.01" min="0.01" required>
                        </div>
                        <div class="form-text" id="ayudaMonto"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de pago *</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(document.getElementById('modalPago'));
    const form  = document.getElementById('formPago');
    let facturasPendientes = [];

    // ---- Cargar pagos ----
    async function cargarPagos() {
        const filtro = document.getElementById('filtro').value;
        const tbody  = document.getElementById('tablaPagos');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Cargando...</td></tr>';

        try {
            const r = await fetch(`../api/pagos_api.php?accion=listar&filtro=${filtro}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            if (j.datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Sin pagos</td></tr>';
                return;
            }

            const iconos = { efectivo: '💵', tarjeta: '💳', transferencia: '🏦' };

            tbody.innerHTML = j.datos.map(p => `
                <tr>
                    <td>${p.id_pago}</td>
                    <td>${formatearFecha(p.fecha_pago)}</td>
                    <td>${escapar(p.paciente)}</td>
                    <td class="d-none d-md-table-cell">#${p.id_factura}</td>
                    <td>${iconos[p.metodo_pago] || ''} ${p.metodo_pago}</td>
                    <td class="text-end text-success fw-bold">Q ${parseFloat(p.monto).toFixed(2)}</td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    // ---- Cargar resumen ----
    async function cargarResumen() {
        const r = await fetch('../api/pagos_api.php?accion=resumen');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('cobradoHoy').textContent   = 'Q ' + parseFloat(j.hoy).toFixed(2);
        document.getElementById('cobradoMes').textContent   = 'Q ' + parseFloat(j.mes).toFixed(2);
        document.getElementById('cobradoTotal').textContent = 'Q ' + parseFloat(j.total).toFixed(2);

        const iconos = { efectivo: '💵', tarjeta: '💳', transferencia: '🏦' };
        document.getElementById('tarjetasMetodos').innerHTML = j.por_metodo.map(m => `
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">${iconos[m.metodo_pago] || ''} ${m.metodo_pago}</div>
                            <div class="h5 mb-0">Q ${parseFloat(m.total).toFixed(2)}</div>
                        </div>
                        <span class="badge bg-secondary">${m.cantidad} pago(s)</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // ---- Cargar facturas pendientes ----
    async function cargarFacturasPendientes() {
        const r = await fetch('../api/pagos_api.php?accion=facturas_pendientes');
        const j = await r.json();
        if (!j.ok) return;

        facturasPendientes = j.datos;
        const sel = document.getElementById('selFactura');
        sel.innerHTML = '<option value="">— Seleccionar —</option>' +
            j.datos.map(f => `
                <option value="${f.id_factura}" data-saldo="${f.saldo}">
                    #${f.id_factura} — ${escapar(f.paciente)} — Saldo: Q ${parseFloat(f.saldo).toFixed(2)}
                </option>
            `).join('');
    }

    // ---- Exponer globales ----
    window.nuevoPago = async function () {
        form.reset();
        document.getElementById('infoFactura').textContent = '';
        document.getElementById('ayudaMonto').textContent = '';
        await cargarFacturasPendientes();
        modal.show();
    };

    // ---- Mostrar info de la factura al seleccionarla ----
    document.getElementById('selFactura').addEventListener('change', e => {
        const id = e.target.value;
        if (!id) {
            document.getElementById('infoFactura').textContent = '';
            document.getElementById('ayudaMonto').textContent = '';
            return;
        }
        const f = facturasPendientes.find(x => x.id_factura == id);
        if (f) {
            document.getElementById('infoFactura').innerHTML =
                `Total: <strong>Q ${parseFloat(f.total).toFixed(2)}</strong> · 
                 Pagado: Q ${parseFloat(f.pagado).toFixed(2)} · 
                 <span class="text-danger fw-bold">Saldo: Q ${parseFloat(f.saldo).toFixed(2)}</span>`;
            document.getElementById('monto').max = f.saldo;
            document.getElementById('ayudaMonto').innerHTML =
                `Máximo permitido: <strong>Q ${parseFloat(f.saldo).toFixed(2)}</strong>`;
        }
    });

    // ---- Guardar ----
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const datos = new FormData(form);
        datos.append('accion', 'registrar');

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        const r = await fetch('../api/pagos_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        btn.disabled = false;
        btn.textContent = 'Registrar pago';

        if (j.ok) {
            modal.hide();
            const msg = parseFloat(j.saldo_restante) === 0
                ? '✅ Pago completo. La factura quedó como PAGADA.'
                : `Pago registrado. Saldo restante: Q ${parseFloat(j.saldo_restante).toFixed(2)}`;
            mostrarAlerta(msg, 'success');
            cargarPagos();
            cargarResumen();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    });

    // ---- Filtro ----
    document.getElementById('filtro').addEventListener('change', cargarPagos);

    // ---- Utilidades ----
    function escapar(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function formatearFecha(f) {
        const d = new Date(f.replace(' ', 'T'));
        return d.toLocaleString('es-GT', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }

    function mostrarAlerta(msg, tipo) {
        const id = 'al-' + Date.now();
        document.getElementById('alertas').insertAdjacentHTML('beforeend', `
            <div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`);
        setTimeout(() => document.getElementById(id)?.remove(), 5000);
    }

    // ---- Carga inicial ----
    cargarPagos();
    cargarResumen();
});
</script>

<?php require_once '../includes/footer.php'; ?>