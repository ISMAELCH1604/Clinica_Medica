<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'auditor']);
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Centro de Reportes</h3>
        <p class="text-muted small mb-0">Genera, visualiza e imprime informes de gestión de la clínica.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-success shadow-sm" onclick="exportarExcel()">
            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </button>
        <button class="btn btn-outline-primary shadow-sm" onclick="imprimirReporte()">
            <i class="bi bi-printer"></i> Imprimir / PDF
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4 bg-light">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-muted">Tipo de Reporte</label>
                <select id="tipoReporte" class="form-select" onchange="cargarReporte()">
                    <option value="facturacion">Facturación y Cobros</option>
                    <option value="citas">Agenda de Citas</option>
                    <option value="pacientes">Nuevos Pacientes</option>
                    <option value="productividad">Productividad Médica</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-bold text-muted">Desde</label>
                <input type="date" id="fechaDesde" class="form-control" value="<?= date('Y-m-01') ?>" onchange="cargarReporte()">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-bold text-muted">Hasta</label>
                <input type="date" id="fechaHasta" class="form-control" value="<?= date('Y-m-d') ?>" onchange="cargarReporte()">
            </div>
            <div class="col-12 col-md-2">
                <button class="btn btn-dark w-100" onclick="cargarReporte()">Generar</button>
            </div>
        </div>
    </div>
</div>

<!-- Área del Reporte (Para imprimir) -->
<div id="areaImpresion" class="card border-0 shadow-sm p-4">
    <!-- Encabezado solo visible al imprimir -->
    <div class="d-none d-print-block text-center mb-4 border-bottom pb-3">
        <h2 class="fw-bold">Clínica Dental</h2>
        <h5 id="tituloImpresion" class="text-muted"></h5>
        <p class="small mb-0">Generado el: <?= date('d/m/Y H:i A') ?> por <?= htmlspecialchars($_SESSION['nombre_usuario']) ?></p>
    </div>

    <h5 class="fw-bold mb-3" id="tituloReporte">Cargando reporte...</h5>
    
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle" id="tablaReporte">
            <thead class="table-light text-muted small text-uppercase">
                <!-- Se llena dinámicamente -->
            </thead>
            <tbody>
                <!-- Se llena dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        #areaImpresion, #areaImpresion * { visibility: visible; }
        #areaImpresion { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
        .table-responsive { overflow: visible !important; }
    }
</style>

<script>
let datosActuales = [];
let columnasActuales = [];

async function cargarReporte() {
    const tipo = document.getElementById('tipoReporte').value;
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    const tbody = document.querySelector('#tablaReporte tbody');
    const thead = document.querySelector('#tablaReporte thead');

    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4">Generando informe...</td></tr>';

    try {
        const r = await fetch(`../api/reportes_api.php?tipo=${tipo}&desde=${desde}&hasta=${hasta}`);
        const j = await r.json();
        if (!j.ok) throw new Error(j.error);

        datosActuales = j.datos;
        document.getElementById('tituloReporte').textContent = j.titulo;
        document.getElementById('tituloImpresion').textContent = j.titulo + ` (${desde} al ${hasta})`;

        if (j.datos.length === 0) {
            thead.innerHTML = '';
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-5 text-muted">No hay datos en este rango de fechas.</td></tr>';
            return;
        }

        // Configurar columnas según el tipo
        if (tipo === 'facturacion') {
            columnasActuales = ['id_factura', 'fecha_emision', 'paciente', 'total', 'pagado', 'saldo', 'estado'];
            thead.innerHTML = `<tr><th>#</th><th>Fecha</th><th>Paciente</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th><th>Estado</th></tr>`;
        } else if (tipo === 'citas') {
            columnasActuales = ['id_cita', 'fecha_hora', 'paciente', 'odontologo', 'estado'];
            thead.innerHTML = `<tr><th>#</th><th>Fecha/Hora</th><th>Paciente</th><th>Odontólogo</th><th>Estado</th></tr>`;
        } else if (tipo === 'pacientes') {
            columnasActuales = ['id_paciente', 'paciente', 'dpi', 'telefono', 'fecha_registro'];
            thead.innerHTML = `<tr><th>#</th><th>Paciente</th><th>DPI</th><th>Teléfono</th><th>Fecha Reg.</th></tr>`;
        } else if (tipo === 'productividad') {
            columnasActuales = ['odontologo', 'especialidad', 'total_citas', 'completadas', 'no_asistio'];
            thead.innerHTML = `<tr><th>Odontólogo</th><th>Especialidad</th><th class="text-center">Total Citas</th><th class="text-center">Completadas</th><th class="text-center">No Asistió</th></tr>`;
        }

        tbody.innerHTML = j.datos.map(row => {
            return `<tr>
                ${columnasActuales.map(col => {
                    let val = row[col];
                    
                    // Formatear dineros
                    if (col === 'total' || col === 'pagado' || col === 'saldo') {
                        val = 'Q ' + parseFloat(val).toFixed(2);
                    }
                    
                    // Formatear fechas
                    if (col === 'fecha_hora' || col === 'fecha_emision') {
                        val = new Date(val.replace(' ','T')).toLocaleString();
                    }

                    // FORMATEAR EL ESTADO PARA EL PDF/IMPRESIÓN
                    if (col === 'estado') {
                        let textoEstado = val.charAt(0).toUpperCase() + val.slice(1);
                        let colorTexto = '';
                        if (val === 'completada') colorTexto = 'color: #198754; font-weight: bold;'; // Verde
                        if (val === 'cancelada')  colorTexto = 'color: #dc3545; font-weight: bold;'; // Rojo
                        if (val === 'programada') colorTexto = 'color: #0d6efd; font-weight: bold;'; // Azul
                        if (val === 'no_asistio') colorTexto = 'color: #fd7e14; font-weight: bold;'; // Naranja
                        
                        return `<td><span style="${colorTexto}">${escapar(textoEstado)}</span></td>`;
                    }

                    // Formatear montos (centrados)
                    if (col === 'total_citas' || col === 'completadas' || col === 'no_asistio') {
                        return `<td class="text-center">${escapar(String(val))}</td>`;
                    }

                    return `<td>${escapar(String(val))}</td>`;
                }).join('')}
            </tr>`;
        }).join('');

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-danger text-center">Error: ${e.message}</td></tr>`;
    }
}

function exportarExcel() {
    if (datosActuales.length === 0) return alert('No hay datos para exportar');
    
    const tipo = document.getElementById('tipoReporte').value;
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    const titulo = document.getElementById('tituloReporte').textContent;
    
    const tabla = document.getElementById('tablaReporte');
    
    let html = `
        <html xmlns:x="urn:schemas-microsoft-com:office:excel">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <style>
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #f2f2f2; border: 1px solid #000; padding: 8px; font-weight: bold; }
                td { border: 1px solid #000; padding: 8px; }
                .title { font-size: 18px; font-weight: bold; text-align: center; }
                .subtitle { font-size: 14px; text-align: center; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="title">Clínica Dental - Reporte</div>
            <div class="subtitle">${titulo} (${desde} al ${hasta})</div>
            <table>
                ${tabla.innerHTML}
            </table>
        </body>
        </html>
    `;

    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    
    const a = document.createElement('a');
    a.href = url;
    a.download = `Reporte_${tipo}_${desde}_al_${hasta}.xls`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function imprimirReporte() {
    window.print();
}

// ESTA ES LA FUNCIÓN QUE FALTABA Y CAUSABA EL ERROR
function escapar(s) {
    if (s == null || s === 'null') return '-';
    return String(s).replace(/[&<>"']/g, c => ({ 
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' 
    }[c]));
}

window.addEventListener('load', () => {
    cargarReporte();
});
</script>

<?php require_once '../includes/footer.php'; ?>