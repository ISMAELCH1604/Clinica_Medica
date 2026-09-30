<?php
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">🔍 Bitácora de auditoría</h2>
    <div class="d-flex gap-2">
        <a href="../api/bitacora_api.php?accion=exportar_csv" class="btn btn-outline-success text-nowrap">
            <i class="bi bi-download"></i> Exportar CSV
        </a>
        <button class="btn btn-outline-secondary text-nowrap" onclick="recargar()">
            <i class="bi bi-arrow-clockwise"></i> Refrescar
        </button>
    </div>
</div>

<!-- Estadísticas -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Total registros</div>
                <div class="h4 mb-0" id="statTotal">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-info shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Actividad hoy</div>
                <div class="h4 mb-0" id="statHoy">0</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small mb-1">Por operación</div>
                <div id="statOperaciones" class="d-flex gap-3 flex-wrap"></div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1">Usuario</label>
                <select id="filtroUsuario" class="form-select form-select-sm">
                    <option value="">Todos</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1">Tabla</label>
                <select id="filtroTabla" class="form-select form-select-sm">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Operación</label>
                <select id="filtroOperacion" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <option value="INSERT">INSERT</option>
                    <option value="UPDATE">UPDATE</option>
                    <option value="DELETE">DELETE</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Desde</label>
                <input type="date" id="filtroDesde" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Hasta</label>
                <input type="date" id="filtroHasta" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-primary btn-sm w-100" onclick="aplicarFiltros()">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" onclick="limpiarFiltros()">
                    Limpiar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Tabla</th>
                    <th>Operación</th>
                    <th class="text-end">Detalle</th>
                </tr>
            </thead>
            <tbody id="tablaBitacora">
                <tr><td colspan="6" class="text-center py-4">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del cambio <span id="detId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Usuario:</strong> <span id="detUsuario"></span></p>
                        <p class="mb-1"><strong>Tabla:</strong> <span id="detTabla"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Fecha:</strong> <span id="detFecha"></span></p>
                        <p class="mb-1"><strong>Operación:</strong> <span id="detOperacion"></span></p>
                    </div>
                </div>

                <div id="zonaDiff">
                    <!-- se llena dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<style>
.diff-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
@media (max-width: 768px) {
    .diff-container { grid-template-columns: 1fr; }
}
.diff-panel {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    overflow: hidden;
}
.diff-panel-header {
    background: #f8f9fa;
    padding: 8px 12px;
    font-weight: 600;
    border-bottom: 1px solid #dee2e6;
    font-size: 0.9rem;
}
.diff-panel-body {
    padding: 12px;
    font-family: monospace;
    font-size: 0.85rem;
}
.diff-line {
    padding: 3px 6px;
    border-radius: 3px;
    margin-bottom: 2px;
}
.diff-line.cambiado {
    background: #fff3cd;
    border-left: 3px solid #ffc107;
}
.diff-line.nuevo {
    background: #d4edda;
    border-left: 3px solid #28a745;
}
.diff-line.eliminado {
    background: #f8d7da;
    border-left: 3px solid #dc3545;
    text-decoration: line-through;
    opacity: 0.7;
}
.diff-campo {
    color: #0d6efd;
    font-weight: 600;
}
</style>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));
    let datosActuales = [];

    // ---- Cargar opciones de filtros ----
    async function cargarOpciones() {
        const r = await fetch('../api/bitacora_api.php?accion=opciones');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('filtroUsuario').innerHTML =
            '<option value="">Todos</option>' +
            j.usuarios.map(u => `<option value="${u.id_usuario}">${escapar(u.nombre_usuario)} (${u.rol})</option>`).join('');

        document.getElementById('filtroTabla').innerHTML =
            '<option value="">Todas</option>' +
            j.tablas.map(t => `<option value="${escapar(t)}">${escapar(t)}</option>`).join('');
    }

    // ---- Cargar estadísticas ----
    async function cargarEstadisticas() {
        const r = await fetch('../api/bitacora_api.php?accion=estadisticas');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('statTotal').textContent = j.total;
        document.getElementById('statHoy').textContent = j.hoy;

        const colores = { INSERT: 'success', UPDATE: 'warning', DELETE: 'danger' };
        const iconos  = { INSERT: '➕', UPDATE: '✏️', DELETE: '🗑️' };

        document.getElementById('statOperaciones').innerHTML = j.por_operacion.map(o => `
            <span class="badge bg-${colores[o.operacion] || 'secondary'}">
                ${iconos[o.operacion] || ''} ${o.operacion}: ${o.total}
            </span>
        `).join('');
    }

    // ---- Cargar bitácora ----
    async function cargarBitacora() {
        const tbody = document.getElementById('tablaBitacora');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Cargando...</td></tr>';

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
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Sin registros con esos filtros</td></tr>';
                return;
            }

            const colores = { INSERT: 'success', UPDATE: 'warning text-dark', DELETE: 'danger' };
            const iconos  = { INSERT: '➕', UPDATE: '✏️', DELETE: '🗑️' };

            tbody.innerHTML = j.datos.map(b => `
                <tr>
                    <td>${b.id_bitacora}</td>
                    <td>${formatearFecha(b.fecha_hora)}</td>
                    <td>
                        <strong>${escapar(b.usuario)}</strong><br>
                        <small class="text-muted">${escapar(b.rol || '')}</small>
                    </td>
                    <td><span class="badge bg-secondary">${escapar(b.tabla_afectada)}</span></td>
                    <td>
                        <span class="badge bg-${colores[b.operacion] || 'secondary'}">
                            ${iconos[b.operacion] || ''} ${b.operacion}
                        </span>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(${b.id_bitacora})">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    // ---- Ver detalle con diff ----
    window.verDetalle = function (id) {
        const b = datosActuales.find(x => x.id_bitacora == id);
        if (!b) return;

        document.getElementById('detId').textContent = '#' + b.id_bitacora;
        document.getElementById('detUsuario').textContent = b.usuario + (b.rol ? ' (' + b.rol + ')' : '');
        document.getElementById('detTabla').textContent = b.tabla_afectada;
        document.getElementById('detFecha').textContent = formatearFecha(b.fecha_hora);

        const colores = { INSERT: 'success', UPDATE: 'warning', DELETE: 'danger' };
        document.getElementById('detOperacion').innerHTML =
            `<span class="badge bg-${colores[b.operacion]}">${b.operacion}</span>`;

        const anterior = parseJSON(b.valor_anterior);
        const nuevo    = parseJSON(b.valor_nuevo);

        let html = '';

        if (b.operacion === 'INSERT') {
            html = `
                <div class="diff-panel">
                    <div class="diff-panel-header">🆕 Valores insertados</div>
                    <div class="diff-panel-body">
                        ${renderObjeto(nuevo, 'nuevo')}
                    </div>
                </div>
            `;
        } else if (b.operacion === 'DELETE') {
            html = `
                <div class="diff-panel">
                    <div class="diff-panel-header">🗑️ Valores eliminados</div>
                    <div class="diff-panel-body">
                        ${renderObjeto(anterior, 'eliminado')}
                    </div>
                </div>
            `;
        } else {
            // UPDATE: mostrar diff
            html = `
                <div class="diff-container">
                    <div class="diff-panel">
                        <div class="diff-panel-header">Antes</div>
                        <div class="diff-panel-body">
                            ${renderObjeto(anterior, 'anterior', nuevo)}
                        </div>
                    </div>
                    <div class="diff-panel">
                        <div class="diff-panel-header">Después</div>
                        <div class="diff-panel-body">
                            ${renderObjeto(nuevo, 'nuevo', anterior)}
                        </div>
                    </div>
                </div>
            `;
        }

        document.getElementById('zonaDiff').innerHTML = html;
        modalDetalle.show();
    };

    function renderObjeto(obj, tipo, comparar = null) {
        if (!obj || Object.keys(obj).length === 0) {
            return '<em class="text-muted">Sin datos</em>';
        }
        return Object.entries(obj).map(([k, v]) => {
            let clase = '';
            if (comparar) {
                if (tipo === 'nuevo' && JSON.stringify(v) !== JSON.stringify(comparar[k])) {
                    clase = 'nuevo';
                } else if (tipo === 'anterior' && JSON.stringify(v) !== JSON.stringify(comparar[k])) {
                    clase = 'eliminado';
                }
            }
            const valor = typeof v === 'object' ? JSON.stringify(v) : (v ?? '<em>null</em>');
            return `<div class="diff-line ${clase}">
                <span class="diff-campo">${escapar(k)}:</span> ${escapar(String(valor))}
            </div>`;
        }).join('');
    }

    function parseJSON(str) {
        if (!str) return {};
        try { return typeof str === 'string' ? JSON.parse(str) : str; }
        catch { return {}; }
    }

    // ---- Filtros ----
    window.aplicarFiltros = () => cargarBitacora();

    window.limpiarFiltros = () => {
        ['filtroUsuario', 'filtroTabla', 'filtroOperacion', 'filtroDesde', 'filtroHasta']
            .forEach(id => document.getElementById(id).value = '');
        cargarBitacora();
    };

    window.recargar = () => {
        cargarBitacora();
        cargarEstadisticas();
    };

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
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }

    // ---- Carga inicial ----
    cargarOpciones();
    cargarEstadisticas();
    cargarBitacora();

    // Auto-refresco cada 30 segundos
    setInterval(() => {
        cargarBitacora();
        cargarEstadisticas();
    }, 30000);
});
</script>

<?php require_once '../includes/footer.php'; ?>