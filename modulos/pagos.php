<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'recepcion']);
require_once '../includes/header.php';
?>

<style>
    .metodo-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        color: #495057;
    }
    .metodo-badge i { font-size: 1rem; }
    .metodo-efectivo i { color: #198754; }
    .metodo-tarjeta i { color: #0d6efd; }
    .metodo-transferencia i { color: #6f42c1; }
    
    .action-btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; color: #6b7280; transition: all 0.2s; }
    .action-btn:hover { background: #f3f4f6; color: #111827; }
    .action-btn.print:hover { color: #198754; border-color: #a3cfbb; background: #e8f5e9; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Registro de Pagos</h3>
        <p class="text-muted small mb-0">Administra los abonos y pagos totales de las facturas.</p>
    </div>
    <div class="d-flex gap-2">
        <select id="filtro" class="form-select shadow-sm" style="width: auto;">
            <option value="todos">Historial Completo</option>
            <option value="hoy">Cobros de Hoy</option>
            <option value="mes">Este Mes</option>
        </select>
        <button class="btn btn-primary shadow-sm px-3" onclick="nuevoPago()">
            <i class="bi bi-cash-coin"></i> Registrar Pago
        </button>
    </div>
</div>

<!-- Resumen Financiero -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold mb-1">Cobrado Hoy</div>
            <div class="h4 mb-0 fw-bold text-success" id="cobradoHoy">Q 0.00</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold mb-1">Cobrado Este Mes</div>
            <div class="h4 mb-0 fw-bold text-dark" id="cobradoMes">Q 0.00</div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm p-3">
            <div class="text-muted small text-uppercase fw-bold mb-1">Total Histórico</div>
            <div class="h4 mb-0 fw-bold text-primary" id="cobradoTotal">Q 0.00</div>
        </div>
    </div>
</div>

<!-- Tabla de Pagos -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3 border-0">Recibo #</th>
                    <th class="py-3 border-0">Paciente</th>
                    <th class="py-3 border-0">Fecha de Pago</th>
                    <th class="py-3 border-0">Método</th>
                    <th class="text-end py-3 border-0">Monto</th>
                    <th class="text-end pe-4 py-3 border-0">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaPagos">
                <tr><td colspan="6" class="text-center py-5 text-muted">Cargando recibos...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="modalPago" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formPago">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Registrar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Factura Pendiente</label>
                        <select name="id_factura" id="selFactura" class="form-select" required>
                            <option value="">— Seleccionar Factura —</option>
                        </select>
                        <div id="infoFactura" class="mt-2 text-muted small"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Monto a Cobrar</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">Q</span>
                            <input type="number" name="monto" id="monto" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Método de Pago</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success px-4">Registrar Pago</button>
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

    async function cargarPagos() {
        const filtro = document.getElementById('filtro').value;
        const tbody  = document.getElementById('tablaPagos');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">Cargando...</td></tr>';

        try {
            const r = await fetch(`../api/pagos_api.php?accion=listar&filtro=${filtro}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            if (j.datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No hay pagos registrados.</td></tr>';
                return;
            }

            tbody.innerHTML = j.datos.map(p => {
                let iconoMetodo = '';
                let claseColor = '';
                let textoMetodo = p.metodo_pago;

                if (p.metodo_pago === 'efectivo') { iconoMetodo = '<i class="bi bi-cash-stack"></i>'; claseColor = 'metodo-efectivo'; }
                else if (p.metodo_pago === 'tarjeta') { iconoMetodo = '<i class="bi bi-credit-card"></i>'; claseColor = 'metodo-tarjeta'; textoMetodo = 'Tarjeta'; }
                else if (p.metodo_pago === 'transferencia') { iconoMetodo = '<i class="bi bi-bank"></i>'; claseColor = 'metodo-transferencia'; textoMetodo = 'Transferencia'; }

                return `
                <tr>
                    <td class="ps-4 text-muted fw-bold">#${p.id_pago}</td>
                    <td class="fw-bold text-dark">${escapar(p.paciente)}</td>
                    <td class="text-muted">${formatearFecha(p.fecha_pago)}</td>
                    <td><span class="metodo-badge ${claseColor}">${iconoMetodo} ${textoMetodo.charAt(0).toUpperCase() + textoMetodo.slice(1)}</span></td>
                    <td class="text-end fw-bold text-success">+ Q ${parseFloat(p.monto).toFixed(2)}</td>
                    <td class="text-end pe-4">
                        <button class="action-btn print" onclick="imprimirRecibo(${p.id_pago})" title="Imprimir Recibo">
                            <i class="bi bi-printer"></i>
                        </button>
                    </td>
                </tr>
            `}).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    async function cargarResumen() {
        const r = await fetch('../api/pagos_api.php?accion=resumen');
        const j = await r.json();
        if (!j.ok) return;
        document.getElementById('cobradoHoy').textContent   = 'Q ' + parseFloat(j.hoy).toFixed(2);
        document.getElementById('cobradoMes').textContent   = 'Q ' + parseFloat(j.mes).toFixed(2);
        document.getElementById('cobradoTotal').textContent = 'Q ' + parseFloat(j.total).toFixed(2);
    }

    async function cargarFacturasPendientes() {
        const r = await fetch('../api/pagos_api.php?accion=facturas_pendientes');
        const j = await r.json();
        if (!j.ok) return;

        facturasPendientes = j.datos;
        const sel = document.getElementById('selFactura');
        sel.innerHTML = '<option value="">— Seleccionar Factura —</option>' +
            j.datos.map(f => `<option value="${f.id_factura}" data-saldo="${f.saldo}">#${f.id_factura} - ${escapar(f.paciente)} (Saldo: Q${parseFloat(f.saldo).toFixed(2)})</option>`).join('');
    }

    window.nuevoPago = async function () {
        form.reset();
        document.getElementById('infoFactura').innerHTML = '';
        await cargarFacturasPendientes();
        modal.show();
    };

    document.getElementById('selFactura').addEventListener('change', e => {
        const id = e.target.value;
        if (!id) { document.getElementById('infoFactura').innerHTML = ''; return; }
        const f = facturasPendientes.find(x => x.id_factura == id);
        if (f) {
            document.getElementById('infoFactura').innerHTML = `
                <div class="bg-light p-2 rounded border">
                    <div class="d-flex justify-content-between"><span>Total:</span> <strong>Q ${parseFloat(f.total).toFixed(2)}</strong></div>
                    <div class="d-flex justify-content-between text-danger"><span>Saldo pendiente:</span> <strong>Q ${parseFloat(f.saldo).toFixed(2)}</strong></div>
                </div>`;
            document.getElementById('monto').max = f.saldo;
            document.getElementById('monto').value = f.saldo;
        }
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const datos = new FormData(form);
        datos.append('accion', 'registrar');
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const r = await fetch('../api/pagos_api.php', { method: 'POST', body: datos });
        const j = await r.json();
        
        btn.disabled = false;

        if (j.ok) {
            modal.hide();
            const msg = parseFloat(j.saldo_restante) === 0 ? '¡Pago completo! La factura ha sido liquidada.' : `Pago registrado. Saldo restante: Q ${parseFloat(j.saldo_restante).toFixed(2)}`;
            mostrarAlerta(msg, 'success');
            cargarPagos();
            cargarResumen();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    });

    window.imprimirRecibo = async function(id) {
        const ventana = window.open('', '_blank');
        ventana.document.write('<div style="text-align:center; padding:50px; font-family:sans-serif;">Generando recibo...</div>');

        const r = await fetch(`../api/pagos_api.php?accion=imprimir_recibo&id=${id}`);
        const j = await r.json();

        if (!j.ok) { ventana.close(); return mostrarAlerta(j.error, 'danger'); }

        const p = j.recibo;
        const saldoRestante = parseFloat(p.total_factura) - parseFloat(p.total_pagado);

        let html = `
            <html>
            <head>
                <title>Recibo #${p.id_pago}</title>
                <style>
                    body { font-family: 'Courier New', monospace; color: #000; margin: 0; padding: 20px; width: 300px; margin: 0 auto; }
                    .text-center { text-align: center; }
                    .header { border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 15px; }
                    .logo { font-size: 20px; font-weight: bold; font-family: Arial, sans-serif;}
                    .details { margin-bottom: 15px; font-size: 12px;}
                    .row { display: flex; justify-content: space-between; margin-bottom: 5px;}
                    .total-row { font-size: 16px; font-weight: bold; border-top: 2px dashed #000; border-bottom: 2px dashed #000; padding: 10px 0; margin: 10px 0; text-align: center;}
                    .footer { text-align: center; font-size: 10px; margin-top: 30px; }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="no-print" style="text-align:center; margin-bottom: 20px;">
                    <button onclick="window.print()" style="background: #0d6efd; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-family: sans-serif; font-weight: bold; width: 100%;">Imprimir Recibo</button>
                </div>

                <div class="header text-center">
                    <div class="logo">Clínica Dental</div>
                    <div style="font-size: 11px; margin-top: 5px;">Hospital FAG</div>
                    <div style="font-size: 11px;">Tel: 2222-3333</div>
                </div>

                <div class="text-center" style="margin-bottom: 15px;">
                    <strong>RECIBO DE PAGO</strong><br>
                    <span style="font-size: 12px;">No. ${p.id_pago}</span>
                </div>

                <div class="details">
                    <div class="row"><span>Fecha:</span> <span>${formatearFecha(p.fecha_pago)}</span></div>
                    <div class="row"><span>Paciente:</span> <span>${escapar(p.paciente)}</span></div>
                    <div class="row"><span>DPI:</span> <span>${escapar(p.dpi)}</span></div>
                    <div class="row"><span>Factura Ref:</span> <span>#${p.id_factura}</span></div>
                </div>

                <div class="total-row">
                    PAGO RECIBIDO<br>
                    Q ${parseFloat(p.monto).toFixed(2)}
                </div>

                <div class="details" style="text-align:right;">
                    <div class="row"><span>Método:</span> <span style="text-transform: uppercase;">${p.metodo_pago}</span></div>
                    <div class="row"><span>Total Factura:</span> <span>Q ${parseFloat(p.total_factura).toFixed(2)}</span></div>
                    <div class="row"><span>Total Pagado:</span> <span>Q ${parseFloat(p.total_pagado).toFixed(2)}</span></div>
                    <div class="row" style="font-weight: bold; color: ${saldoRestante > 0 ? 'red' : 'green'};">
                        <span>Saldo Pendiente:</span> <span>Q ${saldoRestante.toFixed(2)}</span>
                    </div>
                </div>

                <div class="footer">
                    ¡Gracias por su pago!<br>
                    Este documento es un comprobante oficial de su abono.
                </div>
            </body>
            </html>
        `;

        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
    };

    document.getElementById('filtro').addEventListener('change', cargarPagos);

    //funcion imprimir
        window.imprimirRecibo = async function(id) {
        const ventana = window.open('', '_blank');
        ventana.document.write('<div style="text-align:center; padding:50px; font-family:sans-serif;">Generando recibo...</div>');

        const r = await fetch(`../api/pagos_api.php?accion=imprimir_recibo&id=${id}`);
        const j = await r.json();

        if (!j.ok) { ventana.close(); return mostrarAlerta(j.error, 'danger'); }

        const p = j.recibo;
        const saldoRestante = parseFloat(p.total_factura) - parseFloat(p.total_pagado);

        let html = `
            <html>
            <head>
                <title>Recibo #${p.id_pago}</title>
                <style>
                    body { font-family: 'Courier New', monospace; color: #000; margin: 0; padding: 20px; width: 300px; margin: 0 auto; }
                    .text-center { text-align: center; }
                    .header { border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 15px; }
                    .logo { font-size: 20px; font-weight: bold; font-family: Arial, sans-serif;}
                    .details { margin-bottom: 15px; font-size: 12px;}
                    .row { display: flex; justify-content: space-between; margin-bottom: 5px;}
                    .total-row { font-size: 16px; font-weight: bold; border-top: 2px dashed #000; border-bottom: 2px dashed #000; padding: 10px 0; margin: 10px 0; text-align: center;}
                    .footer { text-align: center; font-size: 10px; margin-top: 30px; }
                    @media print {
                        body { padding: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="no-print" style="text-align:center; margin-bottom: 20px;">
                    <button onclick="window.print()" style="background: #0d6efd; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; font-family: sans-serif; font-weight: bold; width: 100%;">Imprimir Recibo</button>
                </div>

                <div class="header text-center">
                    <div class="logo">Clínica Dental</div>
                    <div style="font-size: 11px; margin-top: 5px;">Hospital FAG</div>
                    <div style="font-size: 11px;">Tel: 2222-3333</div>
                </div>

                <div class="text-center" style="margin-bottom: 15px;">
                    <strong>RECIBO DE PAGO</strong><br>
                    <span style="font-size: 12px;">No. ${p.id_pago}</span>
                </div>

                <div class="details">
                    <div class="row"><span>Fecha:</span> <span>${formatearFecha(p.fecha_pago)}</span></div>
                    <div class="row"><span>Paciente:</span> <span>${escapar(p.paciente)}</span></div>
                    <div class="row"><span>DPI:</span> <span>${escapar(p.dpi)}</span></div>
                    <div class="row"><span>Factura Ref:</span> <span>#${p.id_factura}</span></div>
                </div>

                <div class="total-row">
                    PAGO RECIBIDO<br>
                    Q ${parseFloat(p.monto).toFixed(2)}
                </div>

                <div class="details" style="text-align:right;">
                    <div class="row"><span>Método:</span> <span style="text-transform: uppercase;">${p.metodo_pago}</span></div>
                    <div class="row"><span>Total Factura:</span> <span>Q ${parseFloat(p.total_factura).toFixed(2)}</span></div>
                    <div class="row"><span>Total Pagado:</span> <span>Q ${parseFloat(p.total_pagado).toFixed(2)}</span></div>
                    <div class="row" style="font-weight: bold; color: ${saldoRestante > 0 ? 'red' : 'green'};">
                        <span>Saldo Pendiente:</span> <span>Q ${saldoRestante.toFixed(2)}</span>
                    </div>
                </div>

                <div class="footer">
                    ¡Gracias por su pago!<br>
                    Este documento es un comprobante oficial de su abono.
                </div>
            </body>
            </html>
        `;

        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
    };
    //funcion imprimir

    function escapar(s) { if (s == null) return ''; return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function formatearFecha(f) { const d = new Date(f.replace(' ', 'T')); return d.toLocaleString('es-GT', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }
    function mostrarAlerta(msg, tipo) { const id = 'al-' + Date.now(); document.getElementById('alertas').insertAdjacentHTML('beforeend', `<div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`); setTimeout(() => document.getElementById(id)?.remove(), 3500); }

    cargarPagos();
    cargarResumen();
});
</script>

<?php require_once '../includes/footer.php'; ?>