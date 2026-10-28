<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'recepcion']);
require_once '../includes/header.php';
?>

<style>
    .action-btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; color: #6b7280; transition: all 0.2s; }
    .action-btn:hover { background: #f3f4f6; color: #111827; }
    .action-btn.view:hover { color: #0d6efd; border-color: #93c5fd; background: #eff6ff; }
    .action-btn.print:hover { color: #198754; border-color: #a3cfbb; background: #e8f5e9; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Facturación</h3>
        <p class="text-muted small mb-0">Gestiona las cuentas por cobrar y emite facturas a los pacientes.</p>
    </div>
    <div class="d-flex gap-2">
        <select id="filtro" class="form-select shadow-sm" style="width: auto;">
            <option value="todas">Todas</option>
            <option value="pendientes">Pendientes</option>
            <option value="pagadas">Pagadas</option>
            <option value="anuladas">Anuladas</option>
        </select>
        <button class="btn btn-primary shadow-sm px-3" onclick="emitirFactura()">
            <i class="bi bi-receipt"></i> Emitir Factura
        </button>
    </div>
</div>

<!-- Resumen KPI -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold mb-1">Total Facturado</div>
            <div class="h4 mb-0 fw-bold" id="totalFacturado">Q 0.00</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold mb-1">Total Cobrado</div>
            <div class="h4 mb-0 fw-bold text-success" id="totalCobrado">Q 0.00</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold mb-1">Por Cobrar</div>
            <div class="h4 mb-0 fw-bold text-danger" id="totalPorCobrar">Q 0.00</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold mb-1">Facturas Emitidas</div>
            <div class="h4 mb-0 fw-bold" id="totalFacturas">0</div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3 border-0">Factura #</th>
                    <th class="py-3 border-0">Paciente</th>
                    <th class="py-3 border-0">Fecha</th>
                    <th class="text-end py-3 border-0">Total</th>
                    <th class="text-end py-3 border-0">Saldo</th>
                    <th class="py-3 border-0">Estado</th>
                    <th class="text-end pe-4 py-3 border-0">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaFacturas">
                <tr><td colspan="7" class="text-center py-5 text-muted">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Emitir Factura -->
<div class="modal fade" id="modalEmitir" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Emitir Nueva Factura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Selecciona una cita completada que no tenga factura generada.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th>Cita #</th>
                                <th>Paciente</th>
                                <th class="text-center">Trat.</th>
                                <th class="text-end">Total est.</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tablaCitasSinFactura">
                            <tr><td colspan="5" class="text-center py-4 text-muted">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modalEmitir = new bootstrap.Modal(document.getElementById('modalEmitir'));

    async function cargarFacturas() {
        const filtro = document.getElementById('filtro').value;
        const tbody  = document.getElementById('tablaFacturas');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Cargando...</td></tr>';

        try {
            const r = await fetch(`../api/facturas_api.php?accion=listar&filtro=${filtro}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            // KPIs
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
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No hay facturas.</td></tr>';
                return;
            }

            tbody.innerHTML = j.datos.map(f => {
                const badge = {
                    pagada:    'bg-success bg-opacity-10 text-success border border-success-subtle',
                    pendiente: 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
                    anulada:   'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle'
                }[f.estado];

                return `
                    <tr>
                        <td class="ps-4 fw-bold text-primary">#${f.id_factura}</td>
                        <td>
                            <div class="fw-bold text-dark">${escapar(f.paciente)}</div>
                            <div class="text-muted small">Cita: ${formatearFecha(f.fecha_cita)}</div>
                        </td>
                        <td class="text-muted">${formatearFecha(f.fecha_emision)}</td>
                        <td class="text-end fw-medium">Q ${parseFloat(f.total).toFixed(2)}</td>
                        <td class="text-end fw-bold ${f.saldo > 0 ? 'text-danger' : 'text-success'}">
                            Q ${parseFloat(f.saldo).toFixed(2)}
                        </td>
                        <td><span class="badge ${badge} rounded-pill px-2 py-1">${f.estado}</span></td>
                        <td class="text-end pe-4">
                            <button class="action-btn print me-1" onclick="imprimirFactura(${f.id_factura})" title="Imprimir Factura">
                                <i class="bi bi-printer"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    async function cargarCitasSinFactura() {
        const tbody = document.getElementById('tablaCitasSinFactura');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Buscando...</td></tr>';

        const r = await fetch('../api/facturas_api.php?accion=citas_sin_factura');
        const j = await r.json();
        
        if (j.datos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay citas pendientes de facturar.</td></tr>';
            return;
        }

        tbody.innerHTML = j.datos.map(c => `
            <tr>
                <td>#${c.id_cita}</td>
                <td class="fw-medium">${escapar(c.paciente)}</td>
                <td class="text-center">${c.num_tratamientos}</td>
                <td class="text-end fw-bold">Q ${parseFloat(c.total_estimado).toFixed(2)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-primary" onclick="confirmarEmitir(${c.id_cita})">Emitir</button>
                </td>
            </tr>
        `).join('');
    }

    window.emitirFactura = function () {
        cargarCitasSinFactura();
        modalEmitir.show();
    };

    window.confirmarEmitir = async function (id_cita) {
        if (!confirm('¿Generar la factura para esta cita?')) return;
        const datos = new FormData();
        datos.append('accion', 'emitir');
        datos.append('id_cita', id_cita);
        const r = await fetch('../api/facturas_api.php', { method: 'POST', body: datos });
        const j = await r.json();
        if (j.ok) { modalEmitir.hide(); mostrarAlerta('Factura generada', 'success'); cargarFacturas(); } 
        else { mostrarAlerta(j.error, 'danger'); }
    };

    window.imprimirFactura = async function(id) {
        // Abrimos una pestaña nueva
        const ventana = window.open('', '_blank');
        ventana.document.write('<div style="text-align:center; padding:50px; font-family:sans-serif;">Cargando factura...</div>');

        const r = await fetch(`../api/facturas_api.php?accion=imprimir&id=${id}`);
        const j = await r.json();

        if (!j.ok) {
            ventana.close();
            return mostrarAlerta(j.error, 'danger');
        }

        const f = j.factura;
        
        // Construimos el HTML de la factura
        let html = `
            <html>
            <head>
                <title>Factura #${f.id_factura}</title>
                <style>
                    body { font-family: 'Arial', sans-serif; color: #333; margin: 0; padding: 40px; }
                    .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0d6efd; padding-bottom: 20px; margin-bottom: 30px; }
                    .logo { font-size: 24px; font-weight: bold; color: #0d6efd; }
                    .info { text-align: right; }
                    .details { display: flex; justify-content: space-between; margin-bottom: 40px; }
                    .box { width: 45%; }
                    .box h4 { margin-bottom: 5px; color: #666; font-size: 12px; text-transform: uppercase; }
                    .box p { margin: 0; font-size: 14px; line-height: 1.5; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                    th { text-align: left; border-bottom: 1px solid #ddd; padding: 10px; color: #666; font-size: 12px; text-transform: uppercase; }
                    td { padding: 10px; border-bottom: 1px solid #eee; font-size: 14px; }
                    .totales { width: 50%; float: right; }
                    .totales table td { border: none; padding: 5px 10px; }
                    .total-row { font-size: 18px; font-weight: bold; color: #0d6efd; }
                    .footer { text-align: center; margin-top: 80px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; clear: both;}
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="no-print" style="background: #eef2f5; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <span>Vista previa de impresión</span>
                    <button onclick="window.print()" style="background: #0d6efd; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Imprimir / Guardar PDF</button>
                </div>

                <div class="header">
                    <div>
                        <div class="logo">🦷 Clínica Dental</div>
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Ciudad de Guatemala, Guatemala<br>Tel: 2222-3333</p>
                    </div>
                    <div class="info">
                        <h2 style="margin: 0; color: #333;">FACTURA</h2>
                        <p style="margin: 5px 0 0 0; font-weight: bold;">#${f.id_factura}</p>
                        <p style="margin: 0; font-size: 12px; color: #666;">Fecha: ${formatearFecha(f.fecha_emision)}</p>
                    </div>
                </div>

                <div class="details">
                    <div class="box">
                        <h4>Facturado a:</h4>
                        <p><strong>${escapar(f.paciente)}</strong></p>
                        <p>DPI: ${escapar(f.dpi)}</p>
                        <p>Tel: ${escapar(f.telefono || '-')}</p>
                        <p>${escapar(f.direccion || '')}</p>
                    </div>
                    <div class="box" style="text-align: right;">
                        <h4>Atendido por:</h4>
                        <p>Dr(a). ${escapar(f.odontologo)}</p>
                        <p>Especialidad: ${escapar(f.especialidad)}</p>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Descripción del Tratamiento</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>`;
        
        j.tratamientos.forEach(t => {
            html += `<tr><td>${escapar(t.nombre)}</td><td style="text-align: right;">Q ${parseFloat(t.costo).toFixed(2)}</td></tr>`;
        });

        html += `
                    </tbody>
                </table>

                <div class="totales">
                    <table>
                        <tr>
                            <td style="text-align: right; color: #666;">Subtotal:</td>
                            <td style="text-align: right;">Q ${parseFloat(f.total).toFixed(2)}</td>
                        </tr>
                        <tr class="total-row">
                            <td style="text-align: right;">TOTAL:</td>
                            <td style="text-align: right;">Q ${parseFloat(f.total).toFixed(2)}</td>
                        </tr>
                    </table>
                </div>

                <div class="footer">
                    ¡Gracias por confiar en Clínica Dental!<br>
                    Este documento es un comprobante de los servicios prestados.
                </div>
            </body>
            </html>
        `;

        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
    };

    document.getElementById('filtro').addEventListener('change', cargarFacturas);

    function escapar(s) { if (s == null) return ''; return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function formatearFecha(f) { if(!f) return '-'; const d = new Date(f.replace(' ', 'T')); return d.toLocaleString('es-GT', { day: '2-digit', month: 'short', year: 'numeric' }); }
    function mostrarAlerta(msg, tipo) { const id = 'al-' + Date.now(); document.getElementById('alertas').insertAdjacentHTML('beforeend', `<div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`); setTimeout(() => document.getElementById(id)?.remove(), 3500); }

    cargarFacturas();
});
</script>

<?php require_once '../includes/footer.php'; ?>