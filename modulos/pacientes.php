<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'recepcion', 'odontologo']);
require_once '../includes/header.php';
?>

<style>
    /* Estilos premium para la tabla de pacientes */
    .patient-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        font-weight: 600;
        border-bottom: 2px solid #e5e7eb;
        padding: 1rem;
        background-color: transparent;
    }
    .patient-table td {
        vertical-align: middle;
        padding: 1rem;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
    }
    .patient-table tbody tr {
        transition: all 0.2s;
    }
    .patient-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .avatar-initials {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e0e7ff;
        color: #4338ca;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
        margin-right: 12px;
    }
    .action-btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #6b7280;
        transition: all 0.2s;
    }
    .action-btn:hover {
        background: #f3f4f6;
        color: #111827;
    }
    .action-btn.edit:hover { color: #d97706; border-color: #fcd34d; background: #fffbeb; }
    .action-btn.delete:hover { color: #dc2626; border-color: #fca5a5; background: #fef2f2; }
    .action-btn.view:hover { color: #0d6efd; border-color: #93c5fd; background: #eff6ff; }
</style>

<!-- Encabezado de Página -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #111827;">Directorio de Pacientes</h4>
        <p class="text-muted small mb-0">Gestiona la información demográfica y el contacto de los pacientes.</p>
    </div>
    
    <div class="d-flex gap-2 w-100 w-md-auto">
        <div class="input-group" style="max-width: 300px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="search" id="buscador" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre, DPI...">
        </div>
        <button class="btn btn-primary text-nowrap px-4 shadow-sm" onclick="nuevoPaciente()">
            <i class="bi bi-plus-lg me-2"></i> Nuevo Paciente
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border: 1px solid #eef2f5 !important;">
    <div class="table-responsive">
        <table class="table patient-table mb-0">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Documento / DPI</th>
                    <th class="d-none d-md-table-cell">Contacto</th>
                    <th class="d-none d-lg-table-cell">Fecha Nac.</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaPacientes">
                <tr><td colspan="5" class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando directorio...
                </td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Crear / Editar Paciente -->
<div class="modal fade" id="modalPaciente" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formPaciente">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="tituloModal">Nuevo Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_paciente" id="id_paciente">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">Nombre *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">Apellido *</label>
                            <input type="text" name="apellido" id="apellido" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">DPI / Documento *</label>
                            <input type="text" name="dpi" id="dpi" class="form-control" required maxlength="20">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">Fecha de nacimiento *</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-bold text-muted">Correo Electrónico</label>
                            <input type="email" name="correo" id="correo" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">Dirección</label>
                            <input type="text" name="direccion" id="direccion" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">Guardar Paciente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Expediente Clínico (del paso anterior) -->
<div class="modal fade" id="modalExpediente" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-folder2-open me-2"></i> Expediente Clínico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="cuerpoExpediente">
                <!-- Se llena con JS -->
            </div>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(document.getElementById('modalPaciente'));
    const modalExpediente = new bootstrap.Modal(document.getElementById('modalExpediente'));
    const form  = document.getElementById('formPaciente');

    // ---- Cargar lista ----
    async function cargarPacientes(buscar = '') {
        const tbody = document.getElementById('tablaPacientes');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Buscando...</td></tr>';
        try {
            const r = await fetch(`../api/pacientes_api.php?accion=listar&buscar=${encodeURIComponent(buscar)}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            if (j.datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-people fs-2 d-block mb-2 text-secondary opacity-50"></i>No se encontraron pacientes.</td></tr>';
                return;
            }
            tbody.innerHTML = j.datos.map(p => {
                const inicial = p.nombre.charAt(0).toUpperCase();
                const inicial2 = p.apellido.charAt(0).toUpperCase();
                return `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-initials">${inicial}${inicial2}</div>
                            <div>
                                <div class="fw-bold text-dark">${escapar(p.nombre)} ${escapar(p.apellido)}</div>
                                <div class="text-muted small">Registrado: ${new Date(p.fecha_registro).toLocaleDateString()}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark border">${escapar(p.dpi)}</span></td>
                    <td class="d-none d-md-table-cell">
                        <div class="small"><i class="bi bi-telephone text-muted me-1"></i> ${escapar(p.telefono || '-')}</div>
                        <div class="small text-muted"><i class="bi bi-envelope text-muted me-1"></i> ${escapar(p.correo || '-')}</div>
                    </td>
                    <td class="d-none d-lg-table-cell text-muted small">${p.fecha_nacimiento}</td>
                    <td class="text-end">
                        <button class="action-btn view me-1" onclick="verExpediente(${p.id_paciente})" title="Ver Expediente">
                            <i class="bi bi-folder2-open"></i>
                        </button>
                        <button class="action-btn edit me-1" onclick="editar(${p.id_paciente})" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="action-btn delete" onclick="eliminar(${p.id_paciente})" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `}).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    // ---- Exponer funciones globales ----
    window.nuevoPaciente = function () {
        form.reset();
        document.getElementById('id_paciente').value = '';
        document.getElementById('tituloModal').textContent = 'Nuevo Paciente';
        modal.show();
    };

    window.editar = async function (id) {
        const r = await fetch(`../api/pacientes_api.php?accion=obtener&id=${id}`);
        const j = await r.json();
        if (!j.ok) return alert(j.error);

        const p = j.dato;
        document.getElementById('id_paciente').value      = p.id_paciente;
        document.getElementById('nombre').value           = p.nombre;
        document.getElementById('apellido').value         = p.apellido;
        document.getElementById('dpi').value              = p.dpi;
        document.getElementById('fecha_nacimiento').value = p.fecha_nacimiento;
        document.getElementById('telefono').value         = p.telefono || '';
        document.getElementById('correo').value           = p.correo || '';
        document.getElementById('direccion').value        = p.direccion || '';
        document.getElementById('tituloModal').textContent = 'Editar Paciente';
        modal.show();
    };

    window.eliminar = async function (id) {
        if (!confirm('¿Estás seguro de eliminar este paciente? Esta acción no se puede deshacer.')) return;
        const datos = new FormData();
        datos.append('accion', 'eliminar');
        datos.append('id_paciente', id);

        const r = await fetch('../api/pacientes_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            mostrarAlerta(j.mensaje, 'success');
            cargarPacientes(document.getElementById('buscador').value);
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    };

    window.verExpediente = async function(id) {
        document.getElementById('cuerpoExpediente').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
        modalExpediente.show();

        const r = await fetch(`../api/pacientes_api.php?accion=expediente&id=${id}`);
        const j = await r.json();

        if (!j.ok) {
            document.getElementById('cuerpoExpediente').innerHTML = `<div class="alert alert-danger">${j.error}</div>`;
            return;
        }

        let html = `<div class="d-flex align-items-center mb-4 pb-3 border-bottom">
            <div class="avatar-initials" style="width: 50px; height: 50px; font-size: 1.2rem;">
                ${j.paciente.nombre.charAt(0)}${j.paciente.apellido.charAt(0)}
            </div>
            <div>
                <h5 class="mb-0 fw-bold">${escapar(j.paciente.nombre)} ${escapar(j.paciente.apellido)}</h5>
                <p class="text-muted small mb-0">DPI: ${escapar(j.paciente.dpi)} | Tel: ${escapar(j.paciente.telefono || '-')}</p>
            </div>
        </div>`;

        if (j.expedientes.length === 0) {
            html += '<div class="text-center py-4 text-muted"><i class="bi bi-journal-x fs-2 d-block mb-2"></i>El paciente no tiene consultas registradas aún.</div>';
        } else {
            html += '<div class="list-group list-group-flush">';
            j.expedientes.forEach(e => {
                html += `
                    <div class="list-group-item px-0 py-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-primary"><i class="bi bi-calendar3 me-1"></i> ${formatearFecha(e.fecha_hora)}</span>
                            <span class="badge bg-secondary">Dr(a). ${escapar(e.odontologo)}</span>
                        </div>
                        <div class="bg-light p-3 rounded-3">
                            <p class="mb-2"><strong>Diagnóstico:</strong> ${escapar(e.diagnostico)}</p>
                            <p class="mb-0 text-muted small"><strong>Notas / Historial:</strong> ${escapar(e.historial || 'Sin notas adicionales')}</p>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
        }

        document.getElementById('cuerpoExpediente').innerHTML = html;
    };

    // ---- Guardar ----
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const id = document.getElementById('id_paciente').value;
        const datos = new FormData(form);
        datos.append('accion', id ? 'actualizar' : 'crear');

        const r = await fetch('../api/pacientes_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            modal.hide();
            mostrarAlerta(j.mensaje, 'success');
            cargarPacientes(document.getElementById('buscador').value);
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    });

    // ---- Utilidades ----
    function escapar(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function formatearFecha(f) {
        const d = new Date(f.replace(' ', 'T'));
        return d.toLocaleString('es-GT', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function mostrarAlerta(msg, tipo) {
        const id = 'al-' + Date.now();
        document.getElementById('alertas').insertAdjacentHTML('beforeend', `
            <div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow" role="alert" style="border-radius: 10px;">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`);
        setTimeout(() => document.getElementById(id)?.remove(), 3500);
    }

    // Búsqueda en vivo 
    let timeoutBusqueda;
    document.getElementById('buscador').addEventListener('input', e => {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => cargarPacientes(e.target.value), 300);
    });

    cargarPacientes();
});
</script>

<?php require_once '../includes/footer.php'; ?>