<?php
require_once '../includes/seguridad.php';
requerirRol(['administrador']); // Solo el admin entra aquí
require_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">Gestión de Usuarios</h2>
    <button class="btn btn-success text-nowrap" onclick="nuevoUsuario()">
        <i class="bi bi-person-plus"></i> Nuevo Usuario
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th class="d-none d-md-table-cell">Vinculado a</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaUsuarios">
                <tr><td colspan="6" class="text-center py-4">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formUsuario">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario *</label>
                        <input type="text" name="nombre_usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol *</label>
                        <select name="rol" id="selRol" class="form-select" required>
                            <option value="">— Seleccionar —</option>
                            <option value="administrador">Administrador</option>
                            <option value="recepcion">Recepción</option>
                            <option value="odontologo">Odontólogo</option>
                            <option value="auditor">Auditor</option>
                            <option value="paciente">Paciente</option>
                        </select>
                    </div>

                    <!-- Se muestra solo si elige Odontólogo -->
                    <div class="mb-3 d-none" id="divOdontologo">
                        <label class="form-label">Vincular a Odontólogo</label>
                        <select name="id_odontologo" id="selOdontologo" class="form-select">
                            <option value="">— Ninguno —</option>
                        </select>
                    </div>

                    <!-- Se muestra solo si elige Paciente -->
                    <div class="mb-3 d-none" id="divPaciente">
                        <label class="form-label">Vincular a Paciente</label>
                        <select name="id_paciente" id="selPaciente" class="form-select">
                            <option value="">— Ninguno —</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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

    // Cargar lista de usuarios
    async function cargarUsuarios() {
        const tbody = document.getElementById('tablaUsuarios');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Cargando...</td></tr>';

        try {
            const r = await fetch('../api/usuarios_api.php?accion=listar');
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);

            tbody.innerHTML = j.datos.map(u => {
                const badgeEstado = u.activo == 1 
                    ? '<span class="badge bg-success">Activo</span>' 
                    : '<span class="badge bg-secondary">Inactivo</span>';
                
                let vinculado = '-';
                if (u.odontologo) vinculado = 'Dr(a). ' + u.odontologo;
                if (u.paciente) vinculado = u.paciente;

                const btnEstado = u.activo == 1 
                    ? `<button class="btn btn-sm btn-outline-danger" onclick="cambiarEstado(${u.id_usuario}, 0)" title="Desactivar"><i class="bi bi-lock"></i></button>`
                    : `<button class="btn btn-sm btn-outline-success" onclick="cambiarEstado(${u.id_usuario}, 1)" title="Activar"><i class="bi bi-unlock"></i></button>`;

                return `
                    <tr class="${u.activo == 0 ? 'table-light text-muted' : ''}">
                        <td>${u.id_usuario}</td>
                        <td><strong>${escapar(u.nombre_usuario)}</strong></td>
                        <td><span class="badge bg-primary">${u.rol}</span></td>
                        <td class="d-none d-md-table-cell">${escapar(vinculado)}</td>
                        <td>${badgeEstado}</td>
                        <td class="text-end">${btnEstado}</td>
                    </tr>
                `;
            }).join('');
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error: ${e.message}</td></tr>`;
        }
    }

    // Cargar selects de vinculación
    async function cargarOpciones() {
        const r = await fetch('../api/usuarios_api.php?accion=opciones');
        const j = await r.json();
        if (!j.ok) return;

        document.getElementById('selOdontologo').innerHTML = '<option value="">— Ninguno —</option>' + 
            j.odontologos.map(o => `<option value="${o.id}">${escapar(o.nombre)}</option>`).join('');
            
        document.getElementById('selPaciente').innerHTML = '<option value="">— Ninguno —</option>' + 
            j.pacientes.map(p => `<option value="${p.id}">${escapar(p.nombre)}</option>`).join('');
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
        if (!confirm('¿Cambiar el estado de este usuario?')) return;
        
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

        const r = await fetch('../api/usuarios_api.php', { method: 'POST', body: datos });
        const j = await r.json();

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
            <div id="${id}" class="alert alert-${tipo} alert-dismissible fade show shadow">
                ${msg}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`);
        setTimeout(() => document.getElementById(id)?.remove(), 4000);
    }

    cargarUsuarios();
});
</script>

<?php require_once '../includes/footer.php'; ?>