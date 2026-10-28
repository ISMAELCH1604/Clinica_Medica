<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'auditor']);
require_once '../includes/header.php';
?>

<style>
    /* Estilos del modal de diferencias */
    .diff-container { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
    @media (max-width: 768px) { .diff-container { grid-template-columns: 1fr; } }
    .diff-panel { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background: #fff; }
    .diff-panel-header { background: #f8f9fa; padding: 10px 15px; font-weight: 600; border-bottom: 1px solid #e5e7eb; font-size: 0.85rem; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px; }
    .diff-panel-body { padding: 15px; font-family: 'Consolas', monospace; font-size: 0.85rem; }
    .diff-line { padding: 6px 10px; border-radius: 4px; margin-bottom: 4px; border: 1px solid transparent; }
    .diff-line.nuevo { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .diff-line.eliminado { background: #fef2f2; border-color: #fecaca; color: #991b1b; text-decoration: line-through; opacity: 0.8; }
    .diff-campo { font-weight: 700; margin-right: 5px; color: #374151; }
</style>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Auditoría del Sistema</h3>
        <p class="text-muted small mb-0">Registro histórico e inmutable de todas las operaciones realizadas.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-success shadow-sm px-3" onclick="window.location.href='../api/bitacora_api.php?accion=exportar_excel'">
            <i class="bi bi-file-earmark-excel"></i> Exportar a Excel
        </button>
        <button class="btn btn-light border shadow-sm" onclick="recargar()">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100">
            <div class="text-muted small text-uppercase fw-bold mb-1">Total Registros</div>
            <div class="h4 mb-0 fw-bold" id="statTotal">0</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm p-3 h-100">
            <div class="text-muted small text-uppercase fw-bold mb-1">Actividad Hoy</div>
            <div class="h4 mb-0 fw-bold text-primary" id="statHoy">0</div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm p-3 h-100">
            <div class="text-muted small text-uppercase fw-bold mb-2">Acciones Registradas</div>
            <div id="statOperaciones" class="d-flex gap-2 flex-wrap"></div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Usuario</label>
                <select id="filtroUsuario" class="form-select form-select-sm"></select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Tabla Afectada</label>
                <select id="filtroTabla" class="form-select form-select-sm"></select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Acción</label>
                <select id="filtroOperacion" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <option value="INSERT">Creación</option>
                    <option value="UPDATE">Actualización</option>
                    <option value="DELETE">Eliminación</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Desde</label>
                <input type="date" id="filtroDesde" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">Hasta</label>
                <input type="date" id="filtroHasta" class="form-control form-control-sm">
            </div>
            <div class="col-12 mt-3 text-end">
                <button class="btn btn-primary btn-sm px-4" onclick="aplicarFiltros()">Buscar</button>
                <button class="btn btn-light btn-sm px-3" onclick="limpiarFiltros()">Limpiar</button>
            </div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3 border-0">ID</th>
                    <th class="py-3 border-0">Fecha</th>
                    <th class="py-3 border-0">Usuario</th>
                    <th class="py-3 border-0">Módulo (Tabla)</th>
                    <th class="py-3 border-0">Acción</th>
                    <th class="text-end pe-4 py-3 border-0">Detalle</th>
                </tr>
            </thead>
            <tbody id="tablaBitacora">
                <tr><td colspan="6" class="text-center py-5 text-muted">Cargando historial...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Detalle de la Operación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="bg-light p-3 rounded mb-3 border">
                    <div class="row g-2">
                        <div class="col-sm-6"><strong>Usuario:</strong> <span id="detUsuario"></span></div>
                        <div class="col-sm-6"><strong>Fecha:</strong> <span id="detFecha"></span></div>
                        <div class="col-sm-6"><strong>Tabla afectada:</strong> <span id="detTabla" class="badge bg-secondary"></span></div>
                        <div class="col-sm-6"><strong>Acción:</strong> <span id="detOperacion"></span></div>
                    </div>
                </div>

                <div id="zonaDiff"></div>
            </div>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));
    let datosActuales = [];

    // Diccionarios de traducción
    const traducirAccion = { 'INSERT': 'CREACIÓN', 'UPDATE': 'ACTUALIZACIÓN', 'DELETE': 'ELIMINACIÓN' };
    const colorAccion = { 'INSERT': 'success', 'UPDATE': 'warning text-dark', 'DELETE': 'danger' };
    const iconoAccion = { 'INSERT': 'bi-plus-circle', 'UPDATE': 'bi-pencil-square', 'DELETE': 'bi-trash' };

    async function cargarOpciones() {
        const r = await fetch('../api/bitacora_api.php?accion=opciones');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('filtroUsuario').innerHTML = '<option value="">Todos</option>' + j.usuarios.map(u => `<option value="${u.id_usuario}">${escapar(u.nombre_usuario)} (${u.rol})</option>`).join('');
        document.getElementById('filtroTabla').innerHTML = '<option value="">Todas</option>' + j.tablas.map(t => `<option value="${escapar(t)}">${escapar(t)}</option>`).join('');
    }

    async function cargarEstadisticas() {
        const r = await fetch('../api/bitacora_api.php?accion=estadisticas');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('statTotal').textContent = j.total;
        document.getElementById('statHoy').textContent = j.hoy;

        document.getElementById('statOperaciones').innerHTML = j.por_operacion.map(o => `
            <span class="badge bg-${colorAccion[o.operacion]} rounded-pill px-3 py-2">
                <i class="bi ${iconoAccion[o.operacion]} me-1"></i> ${traducirAccion[o.operacion]}: ${o.total}
            </span>
        `).join('');
    }

    async function cargarBitacora() {
        const tbody = document.getElementById('tablaBitacora');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">Buscando...</td></tr>';

        const params = new URLSearchParams({
            accion: 'listar',
            id_usuario: document.getElementById('filtroUsuario').value,
            tabla: document.getElementById('filtroTabla').value,
            operacion: document.getElementById('filtroOperacion').value,
            desde: document.getElementById('filtroDesde').value,
            hasta: document.getElementById('filtroHasta').value,
        });

        try {
            const r = await fetch('../api/bitacora_api.php?' + params);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            datosActuales = j.datos;

            if (j.datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-5 text-muted">No hay registros que coincidan con la búsqueda.</td></tr>';
                return;
            }

            tbody.innerHTML = j.datos.map(b => `
                <tr>
                    <td class="ps-4 text-muted fw-bold">#${b.id_bitacora}</td>
                    <td class="text-muted">${formatearFecha(b.fecha_hora)}</td>
                    <td>
                        <div class="fw-bold text-dark">${escapar(b.usuario)}</div>
                        <div class="text-muted small">${escapar(b.rol || '')}</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">${escapar(b.tabla_afectada)}</span></td>
                    <td>
                        <span class="badge bg-${colorAccion[b.operacion]} rounded-pill px-2">
                            <i class="bi ${iconoAccion[b.operacion]} me-1"></i>${traducirAccion[b.operacion]}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-outline-secondary border-0" onclick="verDetalle(${b.id_bitacora})" title="Ver cambio">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    window.verDetalle = function (id) {
        const b = datosActuales.find(x => x.id_bitacora == id);
        if (!b) return;

        document.getElementById('detUsuario').textContent = b.usuario + (b.rol ? ' (' + b.rol + ')' : '');
        document.getElementById('detTabla').textContent = b.tabla_afectada;
        document.getElementById('detFecha').textContent = formatearFecha(b.fecha_hora);
        document.getElementById('detOperacion').innerHTML = `<span class="badge bg-${colorAccion[b.operacion]}">${traducirAccion[b.operacion]}</span>`;

        const anterior = parseJSON(b.valor_anterior);
        const nuevo    = parseJSON(b.valor_nuevo);

        let html = '';

        if (b.operacion === 'INSERT') {
            html = `<div class="diff-panel"><div class="diff-panel-header">Datos Creados</div><div class="diff-panel-body">${renderObjeto(nuevo, 'nuevo')}</div></div>`;
        } else if (b.operacion === 'DELETE') {
            html = `<div class="diff-panel"><div class="diff-panel-header">Datos Eliminados</div><div class="diff-panel-body">${renderObjeto(anterior, 'eliminado')}</div></div>`;
        } else {
            // UPDATE
            html = `
                <div class="diff-container">
                    <div class="diff-panel">
                        <div class="diff-panel-header">Antes del cambio</div>
                        <div class="diff-panel-body">${renderObjeto(anterior, 'anterior', nuevo)}</div>
                    </div>
                    <div class="diff-panel">
                        <div class="diff-panel-header">Después del cambio</div>
                        <div class="diff-panel-body">${renderObjeto(nuevo, 'nuevo', anterior)}</div>
                    </div>
                </div>
            `;
        }

        document.getElementById('zonaDiff').innerHTML = html;
        modalDetalle.show();
    };

    function renderObjeto(obj, tipo, comparar = null) {
        if (!obj || Object.keys(obj).length === 0) return '<em class="text-muted">Sin datos registrados</em>';
        return Object.entries(obj).map(([k, v]) => {
            let clase = '';
            if (comparar) {
                if (tipo === 'nuevo' && JSON.stringify(v) !== JSON.stringify(comparar[k])) clase = 'nuevo';
                else if (tipo === 'anterior' && JSON.stringify(v) !== JSON.stringify(comparar[k])) clase = 'eliminado';
            }
            const valor = (v === null || v === '') ? '<span class="text-muted fst-italic">vacío</span>' : escapar(String(v));
            return `<div class="diff-line ${clase}"><span class="diff-campo">${escapar(k)}:</span> ${valor}</div>`;
        }).join('');
    }

    function parseJSON(str) { if (!str) return {}; try { return typeof str === 'string' ? JSON.parse(str) : str; } catch { return {}; } }
    window.aplicarFiltros = () => cargarBitacora();
    window.limpiarFiltros = () => { ['filtroUsuario', 'filtroTabla', 'filtroOperacion', 'filtroDesde', 'filtroHasta'].forEach(id => document.getElementById(id).value = ''); cargarBitacora(); };
    window.recargar = () => { cargarBitacora(); cargarEstadisticas(); };

    function escapar(s) { if (s == null) return ''; return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function formatearFecha(f) { const d = new Date(f.replace(' ', 'T')); return d.toLocaleString('es-GT', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }); }

    cargarOpciones();
    cargarEstadisticas();
    cargarBitacora();
});
</script>

<?php require_once '../includes/footer.php'; ?>