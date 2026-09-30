<?php
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Citas</h2>
    <div class="d-flex gap-2">
        <select id="filtro" class="form-select" style="min-width: 180px;">
            <option value="todas">Todas</option>
            <option value="hoy">Hoy</option>
            <option value="programadas">Programadas</option>
            <option value="completadas">Completadas</option>
        </select>
        <button class="btn btn-success text-nowrap" onclick="nuevaCita()">
            <i class="bi bi-plus-lg"></i> Agendar
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Fecha y hora</th>
                    <th>Paciente</th>
                    <th class="d-none d-md-table-cell">Odontólogo</th>
                    <th class="d-none d-lg-table-cell">Consultorio</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaCitas">
                <tr><td colspan="7" class="text-center py-4">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Agendar Cita -->
<div class="modal fade" id="modalCita" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formCita">
                <div class="modal-header">
                    <h5 class="modal-title">Agendar nueva cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Paciente *</label>
                        <select name="id_paciente" id="selPaciente" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Odontólogo *</label>
                        <select name="id_odontologo" id="selOdontologo" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Consultorio *</label>
                        <select name="id_consultorio" id="selConsultorio" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha y hora *</label>
                        <input type="datetime-local" name="fecha_hora" id="fecha_hora" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agendar cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(document.getElementById('modalCita'));
    const form  = document.getElementById('formCita');

    // ---- Cargar lista de citas ----
    async function cargarCitas() {
        const filtro = document.getElementById('filtro').value;
        const tbody  = document.getElementById('tablaCitas');
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Cargando...</td></tr>';

        try {
            const r = await fetch(`../api/citas_api.php?accion=listar&filtro=${filtro}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            if (j.datos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Sin citas</td></tr>';
                return;
            }

            tbody.innerHTML = j.datos.map(c => {
                const estadoBadge = {
                    programada: 'bg-primary',
                    completada: 'bg-success',
                    cancelada:  'bg-secondary',
                    no_asistio: 'bg-danger'
                }[c.estado] || 'bg-secondary';

                const acciones = c.estado === 'programada' ? `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-success" onclick="cambiarEstado(${c.id_cita}, 'completada')" title="Completar">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button class="btn btn-outline-warning" onclick="cambiarEstado(${c.id_cita}, 'cancelada')" title="Cancelar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="cambiarEstado(${c.id_cita}, 'no_asistio')" title="No asistió">
                            <i class="bi bi-person-x"></i>
                        </button>
                    </div>
                ` : '<span class="text-muted small">—</span>';

                return `
                    <tr>
                        <td>${c.id_cita}</td>
                        <td>${formatearFecha(c.fecha_hora)}</td>
                        <td>${escapar(c.paciente)}</td>
                        <td class="d-none d-md-table-cell">
                            ${escapar(c.odontologo)}<br>
                            <small class="text-muted">${escapar(c.especialidad)}</small>
                        </td>
                        <td class="d-none d-lg-table-cell">${escapar(c.consultorio)}</td>
                        <td><span class="badge ${estadoBadge}">${c.estado}</span></td>
                        <td class="text-end">${acciones}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    // ---- Cargar selects ----
    async function cargarOpciones() {
        const r = await fetch('../api/citas_api.php?accion=opciones');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('selPaciente').innerHTML =
            '<option value="">— Seleccionar —</option>' +
            j.pacientes.map(p => `<option value="${p.id}">${escapar(p.nombre)}</option>`).join('');

        document.getElementById('selOdontologo').innerHTML =
            '<option value="">— Seleccionar —</option>' +
            j.odontologos.map(o => `<option value="${o.id}">${escapar(o.nombre)}</option>`).join('');

        document.getElementById('selConsultorio').innerHTML =
            '<option value="">— Seleccionar —</option>' +
            j.consultorios.map(c => `<option value="${c.id}">${escapar(c.nombre)}</option>`).join('');
    }

    // ---- Exponer funciones globales ----
    window.nuevaCita = function () {
        form.reset();
        cargarOpciones();
        modal.show();
    };

    window.cambiarEstado = async function (id, nuevo) {
        const etiquetas = { completada: 'completar', cancelada: 'cancelar', no_asistio: 'marcar como no asistió' };
        if (!confirm(`¿Seguro que deseas ${etiquetas[nuevo]} esta cita?`)) return;

        const datos = new FormData();
        datos.append('accion', 'cambiar_estado');
        datos.append('id_cita', id);
        datos.append('nuevo_estado', nuevo);

        const r = await fetch('../api/citas_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            mostrarAlerta(j.mensaje, 'success');
            cargarCitas();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    };

    // ---- Guardar nueva cita ----
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const datos = new FormData(form);
        datos.append('accion', 'agendar');

        const r = await fetch('../api/citas_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            modal.hide();
            mostrarAlerta(j.mensaje, 'success');
            cargarCitas();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    });

    // ---- Filtro ----
    document.getElementById('filtro').addEventListener('change', cargarCitas);

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
    cargarCitas();
});
</script>

<?php require_once '../includes/footer.php'; ?>