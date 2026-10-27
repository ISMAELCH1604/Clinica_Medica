<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador', 'recepcion', 'odontologo']);
require_once '../includes/header.php';
?>

<style>
    .action-btn { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid #e5e7eb; background: #fff; color: #6b7280; transition: all 0.2s; }
    .action-btn:hover { background: #f3f4f6; color: #111827; }
    .action-btn.edit:hover { color: #d97706; border-color: #fcd34d; background: #fffbeb; }
    .action-btn.delete:hover { color: #dc2626; border-color: #fca5a5; background: #fef2f2; }
    .action-btn.view:hover { color: #0d6efd; border-color: #93c5fd; background: #eff6ff; }
    .action-btn.tools:hover { color: #6f42c1; border-color: #d3d0e5; background: #f5f3ff; }
    
    .btn-horario { transition: all 0.2s; font-weight: 600; border-radius: 8px;}
    .btn-horario:hover:not(:disabled) { transform: scale(1.05); }
</style>

<!-- Cabecera con Buscador y Botones -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Agenda de Citas</h3>
        <p class="text-muted small mb-0">Gestiona las consultas y registra los expedientes clínicos.</p>
    </div>
    <div class="d-flex gap-2 w-100 w-md-auto flex-wrap">
        <div class="input-group" style="max-width: 220px;">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="search" id="buscador" class="form-control border-start-0 ps-0 shadow-none" placeholder="Buscar paciente...">
        </div>
        <select id="filtro" class="form-select shadow-sm" style="width: auto;">
            <option value="todas">Todas</option>
            <option value="hoy">Hoy</option>
            <option value="programadas">Programadas</option>
            <option value="completadas">Completadas</option>
        </select>
        <button class="btn btn-outline-primary shadow-sm text-nowrap px-3" onclick="abrirDisponibilidad()">
            <i class="bi bi-calendar3"></i> Disponibilidad
        </button>
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

<!-- Modal Disponibilidad -->
<div class="modal fade" id="modalDisponibilidad" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-calendar3 text-primary me-2"></i> Panel de Disponibilidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Seleccionar Fecha</label>
                        <input type="date" id="dispFecha" class="form-control" value="<?= date('Y-m-d') ?>" onchange="cargarDisponibilidad()">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Seleccionar Consultorio</label>
                        <select id="dispConsultorio" class="form-select" onchange="cargarDisponibilidad()"></select>
                    </div>
                </div>

                <h6 class="text-muted small text-uppercase fw-bold mb-3 border-bottom pb-2">Horarios del Día</h6>
                <div id="gridHorarios" class="row g-2">
                    <!-- Se llena con JS -->
                </div>
                
                <div class="mt-4 text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Haz clic en un horario <span class="badge bg-success bg-opacity-25 text-success border border-success-subtle">Verde</span> para agendar una cita en ese espacio.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tratamientos -->
<div class="modal fade" id="modalTratamientos" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formTratamientos">
                <div class="modal-header bg-dark text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tools me-2"></i> Tratamientos de la Cita</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id_cita" id="trat_id_cita">
                    <p class="text-muted small mb-3">Selecciona los tratamientos que se realizarán en esta cita.</p>
                    <div id="listaTratamientos" class="d-flex flex-column gap-2">
                        <!-- Se llena con JS -->
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" class="btn btn-primary w-100">Guardar Tratamientos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Expediente Clínico -->
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
    const modalTratamientos = new bootstrap.Modal(document.getElementById('modalTratamientos'));
    const modalDisponibilidad = new bootstrap.Modal(document.getElementById('modalDisponibilidad'));
    
    const formCita  = document.getElementById('formCita');
    const formExpediente  = document.getElementById('formExpediente');
    const formTratamientos = document.getElementById('formTratamientos');
    
    let datosCitasGlobal = []; 

    async function cargarCitas() {
        const filtro = document.getElementById('filtro').value;
        const tbody  = document.getElementById('tablaCitas');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Cargando...</td></tr>';

        try {
            const r = await fetch(`../api/citas_api.php?accion=listar&filtro=${filtro}`);
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            datosCitasGlobal = j.datos; 
            renderizarTabla(datosCitasGlobal);
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Error: ${e.message}</td></tr>`;
        }
    }

    function renderizarTabla(citas) {
        const tbody = document.getElementById('tablaCitas');
        if (citas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron citas.</td></tr>';
            return;
        }

        const ahora = new Date();

        tbody.innerHTML = citas.map(c => {
            const fechaCita = new Date(c.fecha_hora.replace(' ', 'T'));
            const yaPaso = fechaCita < ahora;
            const esProgramada = c.estado === 'programada';
            const alertaFila = (yaPaso && esProgramada) ? 'table-warning' : '';

            const estadoBadge = {
                programada: 'bg-primary bg-opacity-10 text-primary border border-primary-subtle',
                completada: 'bg-success bg-opacity-10 text-success border border-success-subtle',
                cancelada:  'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
                no_asistio: 'bg-danger bg-opacity-10 text-danger border border-danger-subtle'
            }[c.estado];

            let acciones = '';
            const btnTratamientos = `<button class="action-btn tools me-1" onclick="abrirTratamientos(${c.id_cita})" title="Asignar Tratamientos"><i class="bi bi-tools"></i></button>`;

            if (c.estado === 'programada') {
                if (yaPaso) {
                    acciones = `
                        ${btnTratamientos}
                        <button class="btn btn-sm btn-outline-danger fw-bold me-1" onclick="cambiarEstado(${c.id_cita}, 'no_asistio')" title="Marcar como No Asistió"><i class="bi bi-person-x"></i></button>
                        <button class="action-btn edit me-1" onclick="cambiarEstado(${c.id_cita}, 'completada')" title="Completar"><i class="bi bi-check-lg"></i></button>
                    `;
                } else {
                    acciones = `
                        ${btnTratamientos}
                        <button class="action-btn edit me-1" onclick="cambiarEstado(${c.id_cita}, 'completada')" title="Completar"><i class="bi bi-check-lg"></i></button>
                        <button class="action-btn delete" onclick="cambiarEstado(${c.id_cita}, 'cancelada')" title="Cancelar"><i class="bi bi-x-lg"></i></button>
                    `;
                }
            } else if (c.estado === 'completada') {
                acciones = `
                    ${btnTratamientos}
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

    async function cargarOpciones() {
        const r = await fetch('../api/citas_api.php?accion=opciones');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('selPaciente').innerHTML = '<option value="">— Seleccionar —</option>' + j.pacientes.map(p => `<option value="${p.id}">${escapar(p.nombre)}</option>`).join('');
        document.getElementById('selOdontologo').innerHTML = '<option value="">— Seleccionar —</option>' + j.odontologos.map(o => `<option value="${o.id}">${escapar(o.nombre)}</option>`).join('');
        document.getElementById('selConsultorio').innerHTML = '<option value="">— Seleccionar —</option>' + j.consultorios.map(c => `<option value="${c.id}">${escapar(c.nombre)}</option>`).join('');
        
        return j;
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

    window.abrirTratamientos = async function(id_cita) {
        document.getElementById('trat_id_cita').value = id_cita;
        document.getElementById('listaTratamientos').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div> Cargando...</div>';
        modalTratamientos.show();

        const r = await fetch(`../api/citas_api.php?accion=obtener_tratamientos&id_cita=${id_cita}`);
        const j = await r.json();
        
        if (!j.ok) return;

        if (j.tratamientos.length === 0) {
            document.getElementById('listaTratamientos').innerHTML = '<div class="alert alert-warning">No hay tratamientos registrados en la base de datos.</div>';
            return;
        }

        let html = '';
        j.tratamientos.forEach(t => {
            const checked = j.asignados.includes(t.id_tratamiento.toString()) ? 'checked' : '';
            html += `
                <label class="list-group-item d-flex justify-content-between align-items-center border rounded p-2" style="cursor:pointer;">
                    <div>
                        <input class="form-check-input me-2" type="checkbox" name="tratamientos[]" value="${t.id_tratamiento}" ${checked}>
                        <span class="fw-bold text-dark">${t.nombre}</span>
                    </div>
                    <span class="badge bg-success bg-opacity-75">Q ${parseFloat(t.costo).toFixed(2)}</span>
                </label>
            `;
        });
        document.getElementById('listaTratamientos').innerHTML = html;
    };

    // --- LÓGICA DE DISPONIBILIDAD ---
    window.abrirDisponibilidad = async function() {
        const j = await cargarOpciones(); // Aprovechamos para llenar el select de consultorios
        if(j && j.ok) {
            document.getElementById('dispConsultorio').innerHTML = j.consultorios.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
        }
        modalDisponibilidad.show();
        cargarDisponibilidad();
    };

    window.cargarDisponibilidad = async function() {
        const fecha = document.getElementById('dispFecha').value;
        const consultorio = document.getElementById('dispConsultorio').value;
        const grid = document.getElementById('gridHorarios');
        
        if(!fecha || !consultorio) return;
        
        grid.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div> Buscando horarios...</div>';

        const r = await fetch(`../api/citas_api.php?accion=disponibilidad&fecha=${fecha}&id_consultorio=${consultorio}`);
        const j = await r.json();
        
        if (!j.ok) return;

        grid.innerHTML = j.disponibilidad.map(h => {
            let [hour, minute] = h.hora.split(':');
            let ampm = hour >= 12 ? 'PM' : 'AM';
            hour = hour % 12 || 12;
            let horaBonita = `${hour}:${minute} ${ampm}`;

            if (h.disponible) {
                return `
                    <div class="col-6 col-md-3">
                        <button class="btn btn-outline-success btn-horario w-100 py-3" onclick="agendarDesdeGrid('${h.hora}')">
                            <i class="bi bi-clock d-block mb-1 fs-5"></i>
                            ${horaBonita}
                        </button>
                    </div>`;
            } else {
                return `
                    <div class="col-6 col-md-3">
                        <button class="btn btn-light border w-100 py-3 text-muted" disabled>
                            <i class="bi bi-x-circle d-block mb-1 fs-5 text-danger"></i>
                            ${horaBonita}
                        </button>
                    </div>`;
            }
        }).join('');
    };

    window.agendarDesdeGrid = async function(hora) {
        const fecha = document.getElementById('dispFecha').value;
        const consultorio = document.getElementById('dispConsultorio').value;
        
        modalDisponibilidad.hide();
        formCita.reset();
        
        await cargarOpciones(); // Asegurar que los selects estén llenos
        
        // Autocompletar los campos
        document.getElementById('selConsultorio').value = consultorio;
        document.getElementById('fecha_hora').value = `${fecha}T${hora}`;
        
        modalCita.show();
    };

    formTratamientos.addEventListener('submit', async e => {
        e.preventDefault();
        const datos = new FormData(formTratamientos);
        datos.append('accion', 'guardar_tratamientos');
        
        const r = await fetch('../api/citas_api.php', { method: 'POST', body: datos });
        const j = await r.json();
        
        if (j.ok) {
            modalTratamientos.hide();
            mostrarAlerta(j.mensaje, 'success');
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    });

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