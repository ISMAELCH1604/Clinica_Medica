<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'recepcion']);
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Facturas</h2>
    <div class="d-flex gap-2">
        <select id="filtro" class="form-select" style="min-width: 160px;">
            <option value="todas">Todas</option>
            <option value="pendientes">Pendientes</option>
            <option value="pagadas">Pagadas</option>
            <option value="anuladas">Anuladas</option>
        </select>
        <button class="btn btn-success text-nowrap" onclick="emitirFactura()">
            <i class="bi bi-receipt"></i> Emitir
        </button>
    </div>
</div>

<!-- Resumen -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-primary">
            <div class="card-body py-3">
                <div class="text-muted small">Total facturado</div>
                <div class="h4 mb-0" id="totalFacturado">Q 0.00</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-success">
            <div class="card-body py-3">
                <div class="text-muted small">Total cobrado</div>
                <div class="h4 mb-0 text-success" id="totalCobrado">Q 0.00</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-warning">
            <div class="card-body py-3">
                <div class="text-muted small">Por cobrar</div>
                <div class="h4 mb-0 text-warning" id="totalPorCobrar">Q 0.00</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-secondary">
            <div class="card-body py-3">
                <div class="text-muted small">Facturas</div>
                <div class="h4 mb-0" id="totalFacturas">0</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th class="d-none d-md-table-cell">Fecha</th>
                    <th>Paciente</th>
                    <th class="text-end">Total</th>
                    <th class="text-end d-none d-lg-table-cell">Pagado</th>
                    <th class="text-end">Saldo</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaFacturas">
                <tr><td colspan="8" class="text-center py-4">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Emitir Factura -->
<div class="modal fade" id="modalEmitir" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Emitir factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Se muestran las citas con tratamientos asignados que aún no tienen factura.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Paciente</th>
                                <th class="text-center">Trat.</th>
                                <th class="text-end">Total est.</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tablaCitasSinFactura">
                            <tr><td colspan="6" class="text-center py-3">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Factura -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de factura <span id="detId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleContenido">
                <!-- contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modalEmitir  = new bootstrap.Modal(document.getElementById('modalEmitir'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));

    // ---- Cargar facturas ----
    async function cargarFacturas() {
        const filtro = document.getElementById('filtro').value;
        const tbody  = document.getElementById('tablaFacturas');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4">Cargando...</td></tr>';

        try {
            const r = await fetch(`../api/facturas_api.php?accion=listar&filtro=${filtro}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            // Calcular resumen
            let totFact = 0, totCob = 0, totSaldo = 0;
            j.datos.forEach(f => {
                if (f.estado !== 'anulada') {
                    totFact  += parseFloat(f.total);
                    totCob   += parseFloat(f.pagado);
                    totSaldo += parseFloat(f.saldo);
                }
            });
            document.getElementById('totalFacturado').textContent = 'Q ' + totFact.toFixed(2);
            document.getElementById('totalCobrado').textContent   = 'Q ' + totCob.toFixed(2);
            document.getElementById('totalPorCobrar').textContent = 'Q ' + totSaldo.toFixed(2);
            document.getElementById('totalFacturas').textContent  = j.datos.length;

            if (j.datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">Sin facturas</td></tr>';
                return;
            }

            tbody.innerHTML = j.datos.map(f => {
                const badge = {
                    pagada:    'bg-success',
                    pendiente: 'bg-warning text-dark',
                    anulada:   'bg-secondary'
                }[f.estado] || 'bg-secondary';

                const acciones = f.estado !== 'anulada' ? `
                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(${f.id_factura})" title="Ver detalle">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${parseFloat(f.pagado) === 0 ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="anular(${f.id_factura})" title="Anular">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    ` : ''}
                ` : `
                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(${f.id_factura})">
                        <i class="bi bi-eye"></i>
                    </button>
                `;

                return `
                    <tr>
                        <td>${f.id_factura}</td>
                        <td class="d-none d-md-table-cell">${formatearFecha(f.fecha_emision)}</td>
                        <td>${escapar(f.paciente)}</td>
                        <td class="text-end">Q ${parseFloat(f.total).toFixed(2)}</td>
                        <td class="text-end d-none d-lg-table-cell">Q ${parseFloat(f.pagado).toFixed(2)}</td>
                        <td class="text-end ${f.saldo > 0 ? 'text-danger fw-bold' : 'text-success'}">
                            Q ${parseFloat(f.saldo).toFixed(2)}
                        </td>
                        <td><span class="badge ${badge}">${f.estado}</span></td>
                        <td class="text-end">${acciones}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    // ---- Citas sin factura ----
    async function cargarCitasSinFactura() {
        const tbody = document.getElementById('tablaCitasSinFactura');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3">Cargando...</td></tr>';

        const r = await fetch('../api/facturas_api.php?accion=citas_sin_factura');
        const j = await r.json();
        if (!j.ok) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-danger text-center">${j.error}</td></tr>`;
            return;
        }

        if (j.datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">No hay citas pendientes de facturar</td></tr>';
            return;
        }

        tbody.innerHTML = j.datos.map(c => `
            <tr>
                <td>${c.id_cita}</td>
                <td>${formatearFecha(c.fecha_hora)}</td>
                <td>${escapar(c.paciente)}</td>
                <td class="text-center">${c.num_tratamientos}</td>
                <td class="text-end">Q ${parseFloat(c.total_estimado).toFixed(2)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-success" onclick="confirmarEmitir(${c.id_cita})">
                        Emitir
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // ---- Exponer globales ----
    window.emitirFactura = function () {
        cargarCitasSinFactura();
        modalEmitir.show();
    };

    window.confirmarEmitir = async function (id_cita) {
        if (!confirm('¿Emitir la factura para esta cita?')) return;

        const datos = new FormData();
        datos.append('accion', 'emitir');
        datos.append('id_cita', id_cita);

        const r = await fetch('../api/facturas_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            modalEmitir.hide();
            mostrarAlerta(`Factura #${j.id_factura} emitida por Q ${parseFloat(j.total).toFixed(2)}`, 'success');
            cargarFacturas();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    };

    window.anular = async function (id) {
        if (!confirm('¿Anular esta factura? Esta acción no se puede deshacer.')) return;

        const datos = new FormData();
        datos.append('accion', 'anular');
        datos.append('id_factura', id);

        const r = await fetch('../api/facturas_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            mostrarAlerta(j.mensaje, 'success');
            cargarFacturas();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    };

    window.verDetalle = async function (id) {
        const r = await fetch(`../api/facturas_api.php?accion=detalle&id=${id}`);
        const j = await r.json();
        if (!j.ok) return mostrarAlerta(j.error, 'danger');

        const f = j.factura;
        document.getElementById('detId').textContent = '#' + f.id_factura;

        const tratamientos = j.tratamientos.map(t => `
            <tr>
                <td>${escapar(t.nombre)}</td>
                <td class="text-end">Q ${parseFloat(t.costo).toFixed(2)}</td>
            </tr>
        `).join('') || '<tr><td colspan="2" class="text-muted text-center">Sin tratamientos</td></tr>';

        const pagos = j.pagos.map(p => `
            <tr>
                <td>${formatearFecha(p.fecha_pago)}</td>
                <td>${p.metodo_pago}</td>
                <td class="text-end">Q ${parseFloat(p.monto).toFixed(2)}</td>
            </tr>
        `).join('') || '<tr><td colspan="3" class="text-muted text-center">Sin pagos registrados</td></tr>';

        document.getElementById('detalleContenido').innerHTML = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Paciente:</strong> ${escapar(f.paciente)}</p>
                    <p class="mb-1"><small class="text-muted">DPI: ${escapar(f.dpi)}</small></p>
                    <p class="mb-0"><small class="text-muted">Tel: ${escapar(f.telefono || '-')}</small></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1"><strong>Emitida:</strong> ${formatearFecha(f.fecha_emision)}</p>
                    <p class="mb-0"><strong>Cita:</strong> ${formatearFecha(f.fecha_cita)}</p>
                </div>
            </div>

            <h6 class="mt-3">Tratamientos</h6>
            <table class="table table-sm">
                <thead class="table-light">
                    <tr><th>Tratamiento</th><th class="text-end">Costo</th></tr>
                </thead>
                <tbody>${tratamientos}</tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td class="text-end">Total:</td>
                        <td class="text-end">Q ${parseFloat(f.total).toFixed(2)}</td>
                    </tr>
                </tfoot>
            </table>

            <h6 class="mt-3">Pagos</h6>
            <table class="table table-sm">
                <thead class="table-light">
                    <tr><th>Fecha</th><th>Método</th><th class="text-end">Monto</th></tr>
                </thead>
                <tbody>${pagos}</tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">Pagado:</td>
                        <td class="text-end text-success">Q ${j.pagado.toFixed(2)}</td>
                    </tr>
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">Saldo:</td>
                        <td class="text-end ${j.saldo > 0 ? 'text-danger' : 'text-success'}">
                            Q ${j.saldo.toFixed(2)}
                        </td>
                    </tr>
                </tfoot>
            </table>
        `;

        modalDetalle.show();
    };

    // ---- Filtro ----
    document.getElementById('filtro').addEventListener('change', cargarFacturas);

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
        setTimeout(() => document.getElementById(id)?.remove(), 4000);
    }

    // ---- Carga inicial ----
    cargarFacturas();
});
</script>

<?php require_once '../includes/footer.php'; ?>