<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador']);
require_once '../includes/header.php';
?>

<!-- Cabecera -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-0 text-dark">Gestión de Usuarios</h3>
        <p class="text-muted small mb-0">Administra los accesos y roles del personal de la clínica.</p>
    </div>
    <button class="btn btn-primary shadow-sm px-3" onclick="nuevoUsuario()">
        <i class="bi bi-person-plus"></i> Nuevo Usuario
    </button>
</div>

<!-- Tabla Limpia -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th class="ps-4 py-3 border-0">Usuario</th>
                    <th class="py-3 border-0">Rol del Sistema</th>
                    <th class="py-3 border-0 d-none d-md-table-cell">Vinculado a</th>
                    <th class="py-3 border-0">Estado</th>
                    <th class="text-end pe-4 py-3 border-0">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaUsuarios">
                <tr><td colspan="5" class="text-center py-5 text-muted">Cargando usuarios...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="formUsuario">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nombre de Usuario *</label>
                        <input type="text" name="nombre_usuario" class="form-control" required placeholder="Ej. dr.gomez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Contraseña Temporal *</label>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Rol del Sistema *</label>
                        <select name="rol" id="selRol" class="form-select" required>
                            <option value="">— Seleccionar —</option>
                            <option value="administrador">Administrador</option>
                            <option value="recepcion">Recepción</option>
                            <option value="odontologo">Odontólogo</option>
                            <option value="auditor">Auditor</option>
                        </select>
                    </div>

                    <!-- Se muestra solo si elige Odontólogo -->
                    <div class="mb-3 d-none" id="divOdontologo">
                        <label class="form-label small fw-bold text-muted">Vincular a Odontólogo *</label>
                        <select name="id_odontologo" id="selOdontologo" class="form-select">
                            <option value="">— Seleccionar Doctor —</option>
                        </select>
                    </div>

                    <!-- Se muestra solo si elige Paciente -->
                    <div class="mb-3 d-none" id="divPaciente">
                        <label class="form-label small fw-bold text-muted">Vincular a Paciente *</label>
                        <select name="id_paciente" id="selPaciente" class="form-select">
                            <option value="">— Seleccionar Paciente —</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="alertas" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
window.addEventListener('load', function () {
    if (typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    const form  = document.getElementById('formUsuario');

    // Colores para los roles
    const coloresRoles = {
        'administrador': 'bg-danger text-white',
        'recepcion': 'bg-info text-dark',
        'odontologo': 'bg-primary text-white',
        'auditor': 'bg-secondary text-white'
    };

    async function cargarUsuarios() {
        const tbody = document.getElementById('tablaUsuarios');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">Cargando...</td></tr>';

        try {
            const r = await fetch('../api/usuarios_api.php?accion=listar');
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            tbody.innerHTML = j.datos.map(u => {
                const badgeEstado = u.activo == 1 
                    ? '<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2">Activo</span>' 
                    : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle rounded-pill px-2">Inactivo</span>';
                
                let vinculado = '<span class="text-muted">-</span>';
                if (u.odontologo) vinculado = '<i class="bi bi-person-badge text-muted me-1"></i> Dr(a). ' + escapar(u.odontologo);
                if (u.paciente) vinculado = '<i class="bi bi-person text-muted me-1"></i> ' + escapar(u.paciente);

                // Validar si es el mismo usuario logueado para no permitir desactivarse
                const esMiUsuario = (u.id_usuario == <?= $_SESSION['id_usuario'] ?>);

                const btnEstado = u.activo == 1 
                    ? `<button class="btn btn-sm btn-outline-secondary border-0" onclick="cambiarEstado(${u.id_usuario}, 0)" title="Desactivar" ${esMiUsuario ? 'disabled' : ''}><i class="bi bi-lock"></i></button>`
                    : `<button class="btn btn-sm btn-outline-success border-0" onclick="cambiarEstado(${u.id_usuario}, 1)" title="Activar"><i class="bi bi-unlock"></i></button>`;

                const claseRol = coloresRoles[u.rol] || 'bg-dark';

                return `
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark">${escapar(u.nombre_usuario)}</div>
                            <div class="text-muted small">Creado: ${new Date(u.fecha_creacion).toLocaleDateString()}</div>
                        </td>
                        <td><span class="badge ${claseRol} text-uppercase" style="font-size: 0.7rem;">${u.rol}</span></td>
                        <td class="d-none d-md-table-cell">${vinculado}</td>
                        <td>${badgeEstado}</td>
                        <td class="text-end pe-4">
                            ${btnEstado}
                        </td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Error: ${e.message}</td></tr>`;
        }
    }

    async function cargarOpciones() {
        const r = await fetch('../api/usuarios_api.php?accion=opciones');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('selOdontologo').innerHTML = '<option value="">— Seleccionar Doctor —</option>' + j.odontologos.map(o => `<option value="${o.id}">${escapar(o.nombre)}</option>`).join('');
        document.getElementById('selPaciente').innerHTML = '<option value="">— Seleccionar Paciente —</option>' + j.pacientes.map(p => `<option value="${p.id}">${escapar(p.nombre)}</option>`).join('');
    }

    // Mostrar/Ocultar selects según el rol
    document.getElementById('selRol').addEventListener('change', e => {
        const rol = e.target.value;
        document.getElementById('divOdontologo').classList.toggle('d-none', rol !== 'odontologo');
        document.getElementById('divPaciente').classList.toggle('d-none', rol !== 'paciente');
    });

    window.nuevoUsuario = function() {
        form.reset();
        document.getElementById('divOdontologo').classList.add('d-none');
        document.getElementById('divPaciente').classList.add('d-none');
        cargarOpciones();
        modal.show();
    };

    window.cambiarEstado = async function(id, nuevoEstado) {
        if (!confirm('¿Seguro que deseas cambiar el estado de este usuario?')) return;
        
        const datos = new FormData();
        datos.append('accion', 'cambiar_estado');
        datos.append('id_usuario', id);
        datos.append('activo', nuevoEstado);

        const r = await fetch('../api/usuarios_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        if (j.ok) {
            mostrarAlerta(j.mensaje, 'success');
            cargarUsuarios();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    };

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const datos = new FormData(form);
        datos.append('accion', 'crear');
        
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const r = await fetch('../api/usuarios_api.php', { method: 'POST', body: datos });
        const j = await r.json();

        btn.disabled = false;

        if (j.ok) {
            modal.hide();
            mostrarAlerta(j.mensaje, 'success');
            cargarUsuarios();
        } else {
            mostrarAlerta(j.error, 'danger');
        }
    });

    function escapar(s) {
        if (s == null) return '';
        return String(s).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function mostrarAlerta(msg, tipo) {
        const id = 'al-' + Date.now();
        document.getElementById('alertas').insertAdjacentHTML('beforeend', `
            <div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`);
        setTimeout(() => document.getElementById(id)?.remove(), 3500);
    }

    cargarUsuarios();
});
</script>

<?php require_once '../includes/footer.php'; ?>