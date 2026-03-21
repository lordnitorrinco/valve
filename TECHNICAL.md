# Documentación Técnica

## Estructura del proyecto

```
sandbox/
├── docker-compose.yml         # Orquestación de los 4 contenedores
├── Makefile                   # Atajos: up, test, seed, logs, clean
├── .env                       # Variables de entorno (no en git)
├── .env.example               # Plantilla de variables
├── .gitignore
├── run-tests.sh               # Suite completa (backend + frontend + integración)
├── scripts/seed.php           # Datos de demo (vía `make seed`)
├── .github/workflows/ci.yml   # CI en GitHub Actions
├── ARCHITECTURE.md            # Opciones de arquitectura evaluadas
├── SECURITY.md                # 44 medidas de seguridad documentadas
│
├── frontend/                  # ── Aplicación web (vanilla JS) ──
│   ├── Dockerfile
│   ├── nginx.conf             # Config Nginx del contenedor frontend
│   ├── index.html
│   ├── styles.css
│   ├── src/
│   │   ├── app.js             # Entry point: registra pasos e inicializa
│   │   ├── framework/         # Infraestructura interna (no toca lógica de negocio)
│   │   │   ├── createElement.js   # el(), showErrors(), clearFieldError()
│   │   │   ├── router.js          # Navegación SPA, transiciones exit/enter
│   │   │   └── store.js           # Estado global (formData, errors, cvFile)
│   │   ├── ui/                # Componentes visuales reutilizables
│   │   │   ├── fields.js          # Input, select, phone, file, date
│   │   │   ├── icons.js           # Definiciones SVG
│   │   │   └── progress-bar.js    # Barra de progreso persistente
│   │   ├── steps/             # Cada paso del formulario (en orden)
│   │   │   ├── 0-intro.js
│   │   │   ├── 1-contact.js
│   │   │   ├── 2-location.js
│   │   │   ├── 3-education.js
│   │   │   ├── 4-experience.js
│   │   │   ├── 5-consent.js
│   │   │   ├── results.js        # success, rejected, killer
│   │   │   └── admin.js          # panel /admin (GET /api/submissions)
│   │   ├── services/          # Comunicación con el exterior
│   │   │   ├── api.js             # POST /api/submit + CSRF; el admin usa fetch directo
│   │   │   └── validation.js      # Reglas de validación por paso
│   │   └── data/              # Datos estáticos y configuración
│   │       ├── options.js         # Países, niveles, constantes
│   │       └── partners.js        # Logos de empresas asociadas
│   └── assets/
│       ├── logo-evolve.svg
│       └── partners/
│
├── backend/                   # ── API REST (PHP 8.3) ──
│   ├── Dockerfile
│   ├── public/
│   │   └── index.php              # Front controller
│   ├── config/
│   │   └── app.php                # Config desde variables de entorno
│   └── app/
│       ├── Controllers/
│       │   ├── SubmissionController.php
│       │   └── HealthController.php
│       ├── Services/
│       │   ├── Database.php           # Conexión PDO singleton con retry
│       │   ├── Encryptor.php          # AES-256-CBC para cifrado de PII
│       │   ├── FileUploader.php       # Subida de CV con magic bytes check
│       │   ├── RateLimiter.php        # Rate limiting por IP en MySQL
│       │   ├── SecurityLogger.php     # Logging estructurado de eventos
│       │   └── WebhookForwarder.php   # Reenvío al endpoint externo
│       ├── Validation/
│       │   ├── Validator.php          # Motor genérico (required, email, maxLength, pattern, url)
│       │   └── SubmissionValidator.php # Reglas específicas con validación estricta
│       └── Http/
│           ├── Response.php       # JSON responses + CORS restrictivo
│           ├── Router.php         # Registro y dispatch de rutas (GET + POST)
│           └── Security.php       # CSRF, Origin, Content-Type, Honeypot
│
├── nginx/                     # ── Reverse proxy (gateway) ──
│   ├── Dockerfile
│   └── default.conf
│
└── mysql/
    └── init.sql
```

## Frontend — Cómo funciona

### Arquitectura: Framework + Steps

El frontend usa **ES Modules nativos** sin bundler. La estructura se divide en 5 capas con responsabilidades claras:

```
data/         →  Datos puros (constantes, listas de opciones)
framework/    →  Infraestructura (DOM, routing, estado)
ui/           →  Componentes visuales reutilizables
services/     →  Lógica de negocio (validación, HTTP)
steps/        →  Pantallas del formulario (en orden numérico)
```

### Flujo de dependencias

```
data/options, data/partners    ← módulos hoja (sin dependencias)
        ↓
  framework/store              ← estado global
  framework/createElement      ← depende de store
  framework/router             ← depende de store, createElement, ui/progress-bar
        ↓
  ui/icons                     ← sin dependencias
  ui/fields                    ← depende de store, createElement, icons, router
  ui/progress-bar              ← depende de createElement, data/options
        ↓
  services/validation          ← depende de store, data/options
  services/api                 ← depende de store, data/options, router
        ↓
  steps/0-intro ... 5-consent  ← dependen de framework, ui, services, data
  steps/results                ← depende de framework, ui
        ↓
  app.js                       ← importa router + todos los steps
```

### Patrón Registry (sin dependencias circulares)

Cada step se auto-registra al importarse:

```js
// steps/1-contact.js
import { registerView, goTo } from '../framework/router.js';

registerView('personal', function renderContact() {
  // ... construye el DOM y retorna el elemento
});
```

El router no importa los steps → no hay ciclos.

### Transiciones SPA

El router implementa transiciones `exit → enter` con `opacity` + `translateX`, replicando el comportamiento de `framer-motion` con `AnimatePresence mode="wait"`. La barra de progreso es un elemento persistente que actualiza clases CSS sin re-renderizarse.

## Backend — Cómo funciona

### Capas por responsabilidad

```
Http/         →  Entrada: recibir peticiones, enviar respuestas, seguridad pre-dispatch
Validation/   →  Verificar que los datos son correctos
Controllers/  →  Orquestar el flujo de negocio
Services/     →  Interactuar con recursos externos (DB, filesystem, cifrado, webhook)
```

### Rutas HTTP (resumen)

| Método | Ruta | Handler (resumen) |
|--------|------|-------------------|
| `GET` | `/api/csrf-token` | `Security::generateCsrf` → JSON |
| `GET` | `/api/health` | `HealthController::check` → BBDD + disco |
| `GET` | `/api/submissions` | `SubmissionController::list` → PII descifrada |
| `GET` | `/api/submissions/{id}/cv` | `SubmissionController::downloadCv` → stream de fichero |
| `POST` | `/api/submit` | `SubmissionController::store` → validación + INSERT + webhook opcional |

Todas pasan por `public/index.php` (CORS, seguridad en POST, rate limit en POST, `Router::dispatch()`).

### Flujo del envío del formulario (el más complejo)

```
POST /api/submit
    │
    ▼
public/index.php
    │
    ├── Response::cors()                    ← headers CORS
    ├── Router::dispatch()                  ← busca handler registrado
    │       │
    │       └── handler()
    │            ├── Database::connect()    ← PDO con retry
    │            ├── new FileUploader()
    │            └── SubmissionController::store()
    │                 │
    │                 ├── sanitizeInput()                    ← trim
    │                 ├── SubmissionValidator::validate()    ← reglas
    │                 ├── FileUploader::upload()             ← CV
    │                 └── save()                             ← INSERT
    │                      │
    │                      └── Response::success({ id })
    │
    └── catch (PDOException | RuntimeException | Throwable)
              └── Response::error()
```

### Separación de responsabilidades

| Clase | Capa | Responsabilidad |
|-------|------|----------------|
| `Router` | Http | Registrar rutas y despachar al handler correcto |
| `Response` | Http | Enviar JSON con códigos HTTP y CORS restrictivo |
| `Security` | Http | CSRF (HMAC), verificación de Origin, Content-Type, Honeypot |
| `Validator` | Validation | Motor genérico (required, email, maxLength, pattern, url) |
| `SubmissionValidator` | Validation | Reglas específicas con longitudes máximas y formatos |
| `SubmissionController` | Controllers | `store` (POST submit), `list` y `downloadCv` (admin); cifrado PII + webhook opcional |
| `HealthController` | Controllers | `GET /api/health`: comprobación de BBDD, PHP y espacio en uploads |
| `Database` | Services | Conexión PDO singleton con reintentos |
| `Encryptor` | Services | AES-256-CBC para cifrado de email y teléfono (GDPR) |
| `FileUploader` | Services | Subir CV con validación de tipo, tamaño y magic bytes |
| `RateLimiter` | Services | Limita envíos por IP usando tabla en MySQL |
| `SecurityLogger` | Services | Logging JSON estructurado de eventos de seguridad |
| `WebhookForwarder` | Services | Reenvío HTTP al endpoint externo (configurable) |

## Nginx — Gateway

El contenedor `nginx` actúa como **reverse proxy**:

- **Puerto 80**: Expone la API. Todas las peticiones `/api/*` se reenvían por FastCGI al contenedor `backend`.
- **Puerto 8080**: Expone el frontend. Las peticiones `/api/*` van al backend, el resto se proxean al contenedor `frontend`.

Incluye:
- Compresión gzip para JSON (API) y para assets del frontend
- Headers de seguridad (`X-Content-Type-Options`, `X-Frame-Options`)
- `X-Request-ID` en respuestas y reenvío a PHP vía FastCGI
- Límite de upload a 15MB

## Seguridad

Ver [SECURITY.md](SECURITY.md) para la documentación completa de las 44 medidas implementadas.

Resumen: CSRF tokens, rate limiting (Nginx + PHP), cifrado AES-256, CSP, honeypot, magic bytes verification, CORS restrictivo, Docker read-only con cap_drop, permisos MySQL mínimos, logging de seguridad.

## Base de datos

MySQL 8.0 con charset `utf8mb4`. Dos tablas:
- `submissions`: campos del formulario (email/teléfono cifrados AES-256), índices en `email` y `created_at`
- `rate_limits`: control de frecuencia por IP, índice compuesto `(ip_address, attempted_at)`

El usuario de la app tiene permisos mínimos: solo `INSERT`/`SELECT` en submissions y `INSERT`/`SELECT`/`DELETE` en rate_limits.

## Makefile, CI y scripts operativos

| Ruta | Uso |
|------|-----|
| `Makefile` | `make up`, `make test`, `make seed`, `make logs`, `make clean` |
| `.github/workflows/ci.yml` | CI en GitHub: tests backend, frontend e integración con Docker Compose |
| `scripts/seed.php` | Datos de demostración; invocado con `make seed` (montaje de solo lectura en el contenedor backend) |

## Observabilidad

| Pieza | Ubicación |
|-------|-----------|
| `GET /api/health` | `app/Controllers/HealthController.php` — sin PII |
| Tiempo de petición | `Response::timingHeader()` + `$GLOBALS['request_start']` en `public/index.php` |
| ID de correlación | Nginx `$request_id` → PHP `HTTP_X_REQUEST_ID` → campo `request_id` en `SecurityLogger` |
| CORS expone headers | `Access-Control-Expose-Headers: X-Request-ID, X-Response-Time` en `Response::cors()` |

## Accesibilidad (frontend)

- `index.html`: enlace *Saltar al contenido*, `<main id="main-content" tabindex="-1">`.
- `router.js`: foco en `#main-content` tras cada cambio de vista.
- `steps/admin.js`: modal con `role="dialog"`, `aria-labelledby`, foco inicial en cerrar, captura de Tab; filas de tabla con `role="button"` y activación con Enter/Espacio.
