<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'recepcion', 'odontologo']);
require_once '../includes/header.php';
?>

<style>
    /* Estilos para los botones de acción (igual que en pacientes) */
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
    .action-btn:hover { background: #f3f4f6; color: #111827; }
    .action-btn.edit:hover { color: #d97706; border-color: #fcd34d; background: #fffbeb; }
    .action-btn.delete:hover { color: #dc2626; border-color: #fca5a5; background: #fef2f2; }
    .action-btn.view:hover { color: #0d6efd; border-color: #93c5fd; background: #eff6ff; }
</style>

<!-- Cabecera con Buscador -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Agenda de Citas</h3>
        <p class="text-muted small mb-0">Gestiona las consultas y registra los expedientes clínicos.</p>
    </div>
    <div class="d-flex gap-2 w-100 w-md-auto">
        <div class="input-group" style="max-width: 250px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="search" id="buscador" class="form-control border-start-0 ps-0 shadow-none" placeholder="Buscar paciente...">
        </div>
        <select id="filtro" class="form-select shadow-sm" style="width: auto;">
            <option value="todas">Todas</option>
            <option value="hoy">Hoy</option>
            <option value="programadas">Programadas</option>
            <option value="completadas">Completadas</option>
        </select>
        <button class="btn btn-primary shadow-sm text-nowrap px-3" onclick="nuevaCita()">
            <i class="bi bi-calendar-plus"></i> Agendar
        </button>
    </div>
</div>

<!-- Tabla Limpia -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3 border-0">Paciente</th>
                    <th class="py-3 border-0">Fecha / Hora</th>
                    <th class="py-3 border-0 d-none d-md-table-cell">Odontólogo</th>
                    <th class="py-3 border-0">Estado</th>
                    <th class="text-end pe-4 py-3 border-0">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaCitas">
                <tr><td colspan="5" class="text-center py-5 text-muted">Cargando agenda...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Agendar Cita -->
<div class="modal fade" id="modalCita" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formCita">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Agendar Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Paciente</label>
                        <select name="id_paciente" id="selPaciente" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Odontólogo</label>
                        <select name="id_odontologo" id="selOdontologo" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Consultorio</label>
                        <select name="id_consultorio" id="selConsultorio" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Fecha y Hora</label>
                        <input type="datetime-local" name="fecha_hora" id="fecha_hora" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agendar Cita</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Expediente Clínico (Para llenar) -->
<div class="modal fade" id="modalExpediente" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formExpediente">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-journal-medical me-2"></i> Ficha Clínica</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cita" id="exp_id_cita">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Diagnóstico *</label>
                        <textarea name="diagnostico" id="exp_diagnostico" class="form-control" rows="3" required placeholder="Ej. Caries en pieza 16, Gingivitis..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Notas / Historial</label>
                        <textarea name="historial" id="exp_historial" class="form-control" rows="3" placeholder="Tratamiento aplicado, medicamentos, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" class="btn btn-primary w-100">Guardar Expediente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modalCita = new bootstrap.Modal(document.getElementById('modalCita'));
    const modalExpediente = new bootstrap.Modal(document.getElementById('modalExpediente'));
    const formCita  = document.getElementById('formCita');
    const formExpediente  = document.getElementById('formExpediente');
    let datosCitasGlobal = []; // Para guardar las citas y poder filtrar sin ir a la BD cada vez

    async function cargarCitas() {
        const filtro = document.getElementById('filtro').value;
        const tbody  = document.getElementById('tablaCitas');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Cargando...</td></tr>';

        try {
            const r = await fetch(`../api/citas_api.php?accion=listar&filtro=${filtro}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            datosCitasGlobal = j.datos; // Guardamos los datos
            renderizarTabla(datosCitasGlobal);
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    // renderizar tabla de citas
        function renderizarTabla(citas) {
        const tbody = document.getElementById('tablaCitas');
        if (citas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron citas.</td></tr>';
            return;
        }

        const ahora = new Date();

        tbody.innerHTML = citas.map(c => {
            // Validar si la cita ya pasó
            const fechaCita = new Date(c.fecha_hora.replace(' ', 'T'));
            const yaPaso = fechaCita < ahora;
            const esProgramada = c.estado === 'programada';
            
            // Si ya pasó y sigue programada, resaltamos la fila
            const alertaFila = (yaPaso && esProgramada) ? 'table-warning' : '';

            const estadoBadge = {
                programada: 'bg-primary bg-opacity-10 text-primary border border-primary-subtle',
                completada: 'bg-success bg-opacity-10 text-success border border-success-subtle',
                cancelada:  'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
                no_asistio: 'bg-danger bg-opacity-10 text-danger border border-danger-subtle'
            }[c.estado];

            let acciones = '';
            if (c.estado === 'programada') {
                if (yaPaso) {
                    // Si ya pasó la hora, le damos prioridad al botón de No Asistió
                    acciones = `
                        <button class="btn btn-sm btn-outline-danger fw-bold me-1" onclick="cambiarEstado(${c.id_cita}, 'no_asistio')" title="Marcar como No Asistió">
                            <i class="bi bi-person-x"></i> No asistió
                        </button>
                        <button class="action-btn edit me-1" onclick="cambiarEstado(${c.id_cita}, 'completada')" title="Completar"><i class="bi bi-check-lg"></i></button>
                    `;
                } else {
                    // Si aún no es la hora
                    acciones = `
                        <button class="action-btn edit me-1" onclick="cambiarEstado(${c.id_cita}, 'completada')" title="Completar"><i class="bi bi-check-lg"></i></button>
                        <button class="action-btn delete" onclick="cambiarEstado(${c.id_cita}, 'cancelada')" title="Cancelar"><i class="bi bi-x-lg"></i></button>
                    `;
                }
            } else if (c.estado === 'completada') {
                acciones = `
                    <button class="btn btn-sm btn-outline-primary fw-bold" onclick="abrirExpediente(${c.id_cita})">
                        <i class="bi bi-journal-medical"></i> Ficha Clínica
                    </button>
                `;
            }

            return `
                <tr class="${alertaFila}">
                    <td class="ps-4">
                        <div class="fw-bold text-dark">${escapar(c.paciente)}</div>
                    </td>
                    <td>
                        <div class="fw-medium text-dark">${formatearFecha(c.fecha_hora)}</div>
                        <div class="text-muted small"><i class="bi bi-door-closed me-1"></i>${escapar(c.consultorio)}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <div class="text-dark">${escapar(c.odontologo)}</div>
                        <div class="text-muted small">${escapar(c.especialidad)}</div>
                    </td>
                    <td><span class="badge ${estadoBadge} rounded-pill px-2 py-1">${c.estado}</span></td>
                    <td class="text-end pe-4">${acciones}</td>
                </tr>
            `;
        }).join('');
    }

    // renderizar tabla de citas

    async function cargarOpciones() {
        const r = await fetch('../api/citas_api.php?accion=opciones');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('selPaciente').innerHTML = '<option value="">— Seleccionar —</option>' + j.pacientes.map(p => `<option value="${p.id}">${escapar(p.nombre)}</option>`).join('');
        document.getElementById('selOdontologo').innerHTML = '<option value="">— Seleccionar —</option>' + j.odontologos.map(o => `<option value="${o.id}">${escapar(o.nombre)}</option>`).join('');
        document.getElementById('selConsultorio').innerHTML = '<option value="">— Seleccionar —</option>' + j.consultorios.map(c => `<option value="${c.id}">${escapar(c.nombre)}</option>`).join('');
    }

    window.nuevaCita = function () {
        formCita.reset();
        cargarOpciones();
        modalCita.show();
    };

    window.cambiarEstado = async function (id, nuevo) {
        if (!confirm(`¿Cambiar estado a ${nuevo}?`)) return;
        const datos = new FormData();
        datos.append('accion', 'cambiar_estado');
        datos.append('id_cita', id);
        datos.append('nuevo_estado', nuevo);

        const r = await fetch('../api/citas_api.php', { method: 'POST', body: datos });
        const j = await r.json();
        if (j.ok) { mostrarAlerta(j.mensaje, 'success'); cargarCitas(); } 
        else { mostrarAlerta(j.error, 'danger'); }
    };

    window.abrirExpediente = async function(id_cita) {
        formExpediente.reset();
        document.getElementById('exp_id_cita').value = id_cita;
        
        const r = await fetch(`../api/citas_api.php?accion=obtener_expediente_cita&id_cita=${id_cita}`);
        const j = await r.json();
        
        if (j.ok && j.expediente) {
            document.getElementById('exp_diagnostico').value = j.expediente.diagnostico;
            document.getElementById('exp_historial').value = j.expediente.historial || '';
        }
        
        modalExpediente.show();
    };

    formExpediente.addEventListener('submit', async e => {
        e.preventDefault();
        const datos = new FormData(formExpediente);
        datos.append('accion', 'guardar_expediente');

        const r = await fetch('../api/citas_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            modalExpediente.hide();
            mostrarAlerta(j.mensaje, 'success');
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    });

    formCita.addEventListener('submit', async e => {
        e.preventDefault();
        const datos = new FormData(formCita);
        datos.append('accion', 'agendar');
        const r = await fetch('../api/citas_api.php', { method: 'POST', body: datos });
        const j = await r.json();
        if (j.ok) { modalCita.hide(); mostrarAlerta(j.mensaje, 'success'); cargarCitas(); } 
        else { mostrarAlerta(j.error, 'danger'); }
    });

    // --- Lógica de Búsqueda en vivo ---
    document.getElementById('buscador').addEventListener('input', e => {
        const texto = e.target.value.toLowerCase();
        const filtradas = datosCitasGlobal.filter(c => 
            c.paciente.toLowerCase().includes(texto) || 
            c.odontologo.toLowerCase().includes(texto)
        );
        renderizarTabla(filtradas);
    });

    document.getElementById('filtro').addEventListener('change', cargarCitas);

    function escapar(s) { if (s == null) return ''; return String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); }
    function formatearFecha(f) { const d = new Date(f.replace(' ', 'T')); return d.toLocaleString('es-GT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }); }
    function mostrarAlerta(msg, tipo) { const id = 'al-' + Date.now(); document.getElementById('alertas').insertAdjacentHTML('beforeend', `<div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`); setTimeout(() => document.getElementById(id)?.remove(), 3500); }

    cargarCitas();
});
</script>

<?php require_once '../includes/footer.php'; ?>