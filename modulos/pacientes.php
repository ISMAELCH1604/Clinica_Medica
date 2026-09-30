<?php
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Pacientes</h2>
    <div class="d-flex gap-2">
        <input type="search" id="buscador" class="form-control"
               placeholder="Buscar por nombre, DPI o teléfono..." style="min-width: 250px;">
        <button class="btn btn-success text-nowrap" onclick="nuevoPaciente()">
            <i class="bi bi-plus-lg"></i> Nuevo
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>DPI</th>
                    <th class="d-none d-md-table-cell">Nacimiento</th>
                    <th class="d-none d-md-table-cell">Teléfono</th>
                    <th class="d-none d-lg-table-cell">Correo</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaPacientes">
                <tr><td colspan="7" class="text-center py-4">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Crear / Editar -->
<div class="modal fade" id="modalPaciente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formPaciente">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModal">Nuevo Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_paciente" id="id_paciente">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" id="apellido" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">DPI *</label>
                            <input type="text" name="dpi" id="dpi" class="form-control" required maxlength="20">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Fecha de nacimiento *</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="form-control">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Correo</label>
                            <input type="email" name="correo" id="correo" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="direccion" id="direccion" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Contenedor de alertas -->
<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
// ============================================================
// Esperar a que el DOM y Bootstrap estén listos antes de usar
// ============================================================
window.addEventListener('load', function () {
    // Verificar que Bootstrap está disponible
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap NO se cargó. Revisa el <script> en header.php');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalPaciente'));
    const form  = document.getElementById('formPaciente');

    // Exponer funciones globalmente para los onclick del HTML
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
        if (!confirm('¿Eliminar este paciente?')) return;
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

    // ---- Cargar lista ----
    async function cargarPacientes(buscar = '') {
        const tbody = document.getElementById('tablaPacientes');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Cargando...</td></tr>';
        try {
            const r = await fetch(`../api/pacientes_api.php?accion=listar&buscar=${encodeURIComponent(buscar)}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            if (j.datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Sin pacientes</td></tr>';
                return;
            }
            tbody.innerHTML = j.datos.map(p => `
                <tr>
                    <td>${p.id_paciente}</td>
                    <td><strong>${escapar(p.nombre)} ${escapar(p.apellido)}</strong></td>
                    <td>${escapar(p.dpi)}</td>
                    <td class="d-none d-md-table-cell">${p.fecha_nacimiento}</td>
                    <td class="d-none d-md-table-cell">${escapar(p.telefono || '-')}</td>
                    <td class="d-none d-lg-table-cell">${escapar(p.correo || '-')}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-warning" onclick="editar(${p.id_paciente})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="eliminar(${p.id_paciente})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    // ---- Guardar (crear o actualizar) ----
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
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function mostrarAlerta(msg, tipo) {
        const id = 'al-' + Date.now();
        const html = `
            <div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow" role="alert">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        document.getElementById('alertas').insertAdjacentHTML('beforeend', html);
        setTimeout(() => document.getElementById(id)?.remove(), 3500);
    }

    // ---- Búsqueda en vivo ----
    let timeoutBusqueda;
    document.getElementById('buscador').addEventListener('input', e => {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => cargarPacientes(e.target.value), 350);
    });

    // ---- Carga inicial ----
    cargarPacientes();
});
</script>

<?php require_once '../includes/footer.php'; ?>