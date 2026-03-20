# Evolve Academy — Formulario de Admisión

Réplica del formulario multi-paso de admisión de [admision.evolve.es](https://admision.evolve.es), con backend PHP y base de datos MySQL, todo orquestado con Docker Compose.

## Arquitectura

```
┌───────────┐      ┌───────────────────┐      ┌──────────────┐
│  Browser  │─────▶│  Nginx (gateway)  │─────▶│   Frontend   │
│           │      │  :80   API        │      │  (estático)  │
│           │      │  :8080 Frontend   │      └──────────────┘
└───────────┘      │                   │
     │             │  /api/* ──────────┼─────▶┌──────────────┐      ┌───────────┐
     │             │                   │      │   Backend    │─────▶│   MySQL   │
     │             └───────────────────┘      │  (PHP-FPM)  │      │    8.0    │
     │                                        │             │      └─────┬─────┘
     │                                        │  ┌───────┐  │            │
     │                                        │  │forward│  │            │
     │                                        │  └───┬───┘  │            │
     │                                        └──────┼──────┘            │
     │                                               │                   │
     │                                               ▼                   │
     │                                        ┌──────────────┐           │
     │                                        │   Webhook    │           │
     │                                        │  (n8n.cloud) │           │
     │                                        └──────────────┘           │
     │                                                                   │
     │  :8081                                 ┌──────────────┐           │
     └───────────────────────────────────────▶│  phpMyAdmin  │───────────┘
                                              └──────────────┘
```

| Contenedor | Rol | Puerto |
|------------|-----|--------|
| `nginx`    | Reverse proxy / gateway | `:80` (API), `:8080` (frontend) |
| `frontend` | Sirve HTML/CSS/JS estáticos | interno |
| `backend`  | API REST (PHP-FPM) | interno (FastCGI :9000) |
| `phpmyadmin` | Panel de administración de MySQL | `:8081` |
| `db`       | MySQL 8.0 | interno (no expuesto) |

## Inicio rápido

```bash
# 1. Clonar el proyecto
git clone <repo-url> && cd evolve-form

# 2. Configurar variables de entorno
cp .env.example .env

# 3. Levantar todo
docker compose up -d

# 4. Abrir en el navegador
open http://localhost:8080
```

## Variables de entorno

| Variable | Descripción | Default |
|----------|-------------|---------|
| `DB_ROOT_PASSWORD` | Contraseña root de MySQL | — |
| `DB_NAME` | Nombre de la base de datos | `evolve` |
| `DB_USER` | Usuario de la base de datos | `evolve` |
| `DB_PASSWORD` | Contraseña del usuario | — |
| `API_PORT` | Puerto público de la API | `80` |
| `FRONTEND_PORT` | Puerto público del frontend | `8080` |
| `PMA_PORT` | Puerto de phpMyAdmin | `8081` |
| `ENCRYPTION_KEY` | Clave AES-256 para cifrar PII | — |
| `CSRF_SECRET` | Secreto para tokens CSRF (HMAC) | — |
| `ALLOWED_ORIGIN` | Origen permitido (CORS) | `http://localhost:8080` |
| `RATE_LIMIT_MAX` | Máx envíos por IP en ventana | `10` |
| `RATE_LIMIT_WINDOW_MINUTES` | Ventana de rate limiting | `5` |
| `FORWARD_WEBHOOK_ENABLED` | Reenviar al webhook externo | `false` |
| `FORWARD_WEBHOOK_URL` | URL del webhook | `https://n8n.cloud.evolve...` |

## Endpoints

| Método | URL | Descripción |
|--------|-----|-------------|
| `GET`  | `/api/csrf-token` | Genera un token CSRF |
| `POST` | `/api/submit` | Envía una solicitud de admisión |
| `GET`  | `/api/submissions` | Lista todas las solicitudes (PII descifrada) |
| `GET`  | `/api/submissions/{id}/cv` | Descarga el CV de una solicitud |

## Panel de administración

Accesible en [`http://localhost:8080/admin`](http://localhost:8080/admin).

Muestra un listado de todas las solicitudes. Al hacer click en una fila se abre un popup con todos los datos del candidato y un botón para descargar su CV.

## Tests

La suite de tests se ejecuta completamente en Docker (no necesitas PHP ni Node.js instalados):

```bash
# Ejecutar todos los tests (backend + frontend + integración)
bash run-tests.sh
```

### Estructura de tests

| Capa | Framework | Tests | Qué cubre |
|------|-----------|-------|-----------|
| Backend | PHPUnit 11 | 71 | Validator, SubmissionValidator, Encryptor, Security (CSRF), Router, FileUploader, SecurityLogger, WebhookForwarder, Database |
| Frontend | Vitest 3 | 59 | Store, validaciones (4 funciones), createElement, showErrors, options/data |
| Integración | Shell/curl | 32 | Frontend, security headers, CSRF, API validation, honeypot, HTTP methods, route protection, CORS, admin API, phpMyAdmin |

### Ejecutar por separado

```bash
# Solo backend
docker build -t evolve-backend-test -f backend/Dockerfile.test backend/
docker run --rm evolve-backend-test

# Solo frontend
docker build -t evolve-frontend-test -f frontend/Dockerfile.test frontend/
docker run --rm evolve-frontend-test

# Solo integración (requiere docker compose up)
bash tests/integration.sh
```

## Desarrollo

```bash
# Ver logs en tiempo real
docker compose logs -f

# Reconstruir tras cambios
docker compose up -d --build

# Parar todo
docker compose down

# Parar y eliminar volúmenes (reset completo)
docker compose down -v
```
