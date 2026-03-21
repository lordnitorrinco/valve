# Evolve Academy — Formulario de Admisión

Réplica del formulario multi-paso de admisión de [admision.evolve.es](https://admision.evolve.es), con backend PHP y base de datos MySQL, todo orquestado con Docker Compose.

## Documentación

| Documento | Descripción |
|-----------|-------------|
| [**SECURITY.md**](./SECURITY.md) | Medidas de seguridad por capa (Nginx, PHP, MySQL, frontend, infra) |
| [**TECHNICAL.md**](./TECHNICAL.md) | Estructura del repo, flujos de código, Makefile, CI, observabilidad |
| [**ARCHITECTURE.md**](./ARCHITECTURE.md) | Opciones de arquitectura evaluadas y criterios de elección |

## Inicio rápido

```bash
git clone https://github.com/lordnitorrinco/valve.git && cd valve
cp .env.example .env
make up          # o: docker compose up -d --build
open http://localhost:8080
```

Comandos útiles (`Makefile`):

| Comando | Descripción |
|--------|-------------|
| `make up` | Levanta y reconstruye el stack |
| `make down` | Para contenedores |
| `make logs` | Logs en tiempo real |
| `make test` | Suite completa (backend + frontend + integración) |
| `make test-backend` / `make test-frontend` / `make test-integration` | Solo una capa |
| `make seed` | Inserta 30 solicitudes de demo + PDFs mock (requiere stack arriba) |
| `make clean` | `docker compose down -v` (borra BBDD y uploads) |

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
| `FORWARD_WEBHOOK_ENABLED` | Reenviar al webhook externo | `true` (`.env.example`) |
| `FORWARD_WEBHOOK_URL` | URL del webhook | `https://n8n.cloud.evolve...` |

## Endpoints

| Método | URL | Descripción |
|--------|-----|-------------|
| `GET`  | `/api/csrf-token` | Genera un token CSRF |
| `GET`  | `/api/health` | Estado del servicio (BBDD, PHP, espacio en volumen de uploads) |
| `POST` | `/api/submit` | Envía una solicitud de admisión |
| `GET`  | `/api/submissions` | Lista todas las solicitudes (PII descifrada) |
| `GET`  | `/api/submissions/{id}/cv` | Descarga el CV de una solicitud |

### Observabilidad

- **`X-Request-ID`**: el gateway Nginx genera un ID por petición, lo reenvía a PHP (`HTTP_X_REQUEST_ID`) y lo incluye en respuestas y en logs JSON (`SecurityLogger`).
- **`X-Response-Time`**: tiempo de proceso en el backend (segundos, 3 decimales).
- **`Access-Control-Expose-Headers`**: permite leer esos headers desde JavaScript en el origen permitido.

## Panel de administración

[`http://localhost:8080/admin`](http://localhost:8080/admin) — listado de solicitudes, modal con detalle (focus trap, teclado, ARIA), descarga de CV.

## Accesibilidad (frontend)

- Enlace **Saltar al contenido** (WCAG 2.4.1).
- Landmark `<main id="main-content">` y foco programático al cambiar de paso.
- Modal del admin: `role="dialog"`, `aria-modal`, captura de foco (Tab), filas de tabla activables con Enter/Espacio.

## CI / GitHub Actions

El workflow `.github/workflows/ci.yml` ejecuta en cada push/PR:

1. Tests unitarios del backend (Docker + PHPUnit + cobertura).
2. Tests unitarios del frontend (Docker + Vitest).
3. Stack completo con Docker Compose + `tests/integration.sh`.

## Tests

```bash
make test        # o: bash run-tests.sh
```

| Capa | Framework | Tests | Cobertura |
|------|-----------|-------|-----------|
| Backend | PHPUnit 11 | 153 | 100% líneas (clases de `app/`) |
| Frontend | Vitest 3 | 200 | 100% statements |
| Integración | bash + curl | 38 comprobaciones | HTTP, seguridad, health, admin |

### Por separado

```bash
make test-backend
make test-frontend
make test-integration   # requiere docker compose up
```

## Comandos Docker

```bash
docker compose logs -f
docker compose up -d --build
docker compose down
docker compose down -v    # reset completo de volúmenes
```
