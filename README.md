# Iyata API - Sistema de Gestión de Proyectos y Tareas

API RESTful construida con Laravel para la gestión de proyectos y tareas, implementando arquitectura hexagonal (ports & adapters) y Domain-Driven Design (DDD).

## 🚀 Características

- **Arquitectura Hexagonal** con separación clara de responsabilidades
- **Autenticación** con Laravel Sanctum
- **Gestión de Proyectos** con estados personalizados
- **Gestión de Tareas** con fechas de vencimiento
- **API RESTful** completamente documentada
- **Dockerizado** para fácil despliegue

## 🏗️ Estructura del Proyecto

```
app/
├── Core/
│   ├── Application/          # Casos de uso
│   ├── Domain/              # Entidades y lógica de negocio
│   └── Ports/               # Interfaces
├── Infrastructure/          # Implementaciones concretas
└── Http/                   # Controladores y Middlewares
```

## 📋 Requisitos

- Docker & Docker Compose
- PHP 8.2+
- Composer

## 🐳 Despliegue con Docker

```bash
# Construir y levantar contenedores
docker-compose up -d --build

# Ejecutar migraciones
docker-compose exec laravel_back php artisan migrate
```

La API estará disponible en: `http://localhost:8000`

## 🔑 Autenticación

### Registro de Usuario
```bash
POST /api/auth/register
{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### Login
```bash
POST /api/auth/login
{
    "email": "test@example.com",
    "password": "password123"
}
```

**Respuesta:**
```json
{
    "message": "Login successful",
    "user": {
        "id": 1,
        "name": "Test User",
        "email": "test@example.com"
    },
    "token": "1|lhuYMjoxrnQ4osSs0m8PgqabxSGmd0up23DtSGEdb41774a3"
}
```

## 📚 Endpoints de la API

### Proyectos
- `GET    /api/projects` - Listar proyectos
- `POST   /api/projects` - Crear proyecto
- `GET    /api/projects/{id}` - Obtener proyecto
- `PUT    /api/projects/{id}` - Actualizar proyecto
- `DELETE /api/projects/{id}` - Eliminar proyecto
- `PATCH  /api/projects/{id}/status` - Actualizar estado

### Tareas
- `GET    /api/projects/{project}/tasks` - Listar tareas
- `POST   /api/projects/{project}/tasks` - Crear tarea
- `GET    /api/projects/{project}/tasks/{task}` - Obtener tarea
- `PUT    /api/projects/{project}/tasks/{task}` - Actualizar tarea
- `DELETE /api/projects/{project}/tasks/{task}` - Eliminar tarea
- `PATCH  /api/projects/{project}/tasks/{task}/status` - Actualizar estado

## 🔄 Ejemplos de Uso

### Crear Proyecto
```bash
POST /api/projects
Headers: Authorization: Bearer {token}
{
    "name": "Mi Primer Proyecto",
    "description": "Descripción del proyecto"
}
```

### Crear Tarea
```bash
POST /api/projects/1/tasks
Headers: Authorization: Bearer {token}
{
    "title": "Tarea de Prueba",
    "description": "Descripción de la tarea",
    "due_date": "2025-12-31"
}
```

## 🗄️ Base de Datos

El proyecto utiliza MariaDB/MySQL con las siguientes tablas principales:
- `users` - Usuarios del sistema
- `projects` - Proyectos con estados
- `tasks` - Tareas con fechas de vencimiento
- `personal_access_tokens` - Tokens de autenticación

## 🛠️ Comandos Útiles

```bash
# Listar rutas disponibles
php artisan route:list

# Ejecutar migraciones
php artisan migrate

# Limpiar cache
php artisan config:clear
php artisan cache:clear

# Ejecutar tests
php artisan test
```

## 🔒 Seguridad

- Autenticación con tokens Bearer
- Validación de datos en todos los endpoints
- Protección CORS configurada
- Sanitización de inputs

## 📦 Dependencias Principales

- Laravel 10.x
- Laravel Sanctum (Autenticación)
- MariaDB (Base de datos)
- Redis (Cache y colas)

## 🎯 Dominio del Negocio

El sistema modela:
- **Usuarios** que pueden crear y gestionar proyectos
- **Proyectos** con estados personalizados
- **Tareas** asignadas a proyectos con fechas límite

## 🌟 Próximas Características

- [ ] Notificaciones por email
- [ ] Reportes y estadísticas
- [ ] Colaboración en tiempo real
- [ ] Subida de archivos
- [ ] API documentation con Swagger

---

**Desarrollado con Laravel y Arquitectura Hexagonal**