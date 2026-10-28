# Sistema de Gestión Clínica Dental

Sistema web para la gestión operativa de una clínica dental. Agenda de citas, expedientes clínicos, facturación y pagos, garantizando la integridad de los datos mediante el uso de procedimientos almacenados y la seguridad por medio de roles y auditoría.

## Características Principales

- **Gestión de Pacientes y Expedientes:** 
- **Agenda de Citas:** Validación de disponibilidad en tiempo real
- **Facturación :** Cálculo de totales basado en los tratamientos aplicados,
- **Control de Pagos:** Registro de abonos parciales, validación de saldos y generación de recibos tipo ticket.
- **Ciberseguridad (Mínimo Privilegio):**
  - Autenticación con contraseñas encriptadas
  - 4 roles definidos (Administrador, Recepción, Odontólogo, Auditor).
  - Bloqueo de rutas y APIs según el rol del usuario.
- **Auditoría Completa (Bitácora):** Registro automático de cada operación (INSERT, UPDATE, DELETE) 
- **Reportes Dinámicos:** Generación de reportes de productividad, facturación y agenda, con exportación a Excel y PDF.

## Tecnologías Utilizadas

- **Backend:** PHP 8.0.
- **Base de Datos:** MySQL 8.0 (InnoDB).
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.
- **Servidor Local:** XAMPP (Apache + MySQL).

## Estructura del Proyecto

```text
clinica_dental/
│
├── api/                  #  frontend
├── css/                  # estilos.css
├── img/                  # Logos e íconos (Favicon)
├── includes/             # Archivos compartidos (header, footer, seguridad, conexión)
├── modulos/              # Vistas del sistema (Dashboard, Pacientes, Citas, etc.)
├── index.php             # Punto de entrada (Redirige al login)
├── login.php             # Formulario de acceso.