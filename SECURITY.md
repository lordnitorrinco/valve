# Medidas de seguridad

Este documento detalla las 44 medidas de seguridad implementadas, organizadas por capa.

---

## Nginx (Gateway) — 8 medidas

### 1. Rate limiting
Limita peticiones por IP: **120 req/min** para `/api/*` (con burst) y **60 req/min** para el proxy de assets. El exceso devuelve **HTTP 429** (`limit_req_status 429`), no 503, para distinguir rate limit de errores reales del upstream.

**Archivo:** `nginx/default.conf`
```nginx
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=120r/m;
limit_req_status 429;
limit_req zone=api_limit burst=10 nodelay;
```

### 2. Headers de seguridad completos
Cada respuesta incluye headers que protegen contra ataques comunes:

| Header | Protege contra |
|--------|---------------|
| `Content-Security-Policy` | XSS, inyección de scripts externos |
| `Strict-Transport-Security` | Downgrade attacks (HTTP → HTTPS) |
| `X-Content-Type-Options: nosniff` | MIME type sniffing |
| `X-Frame-Options: DENY` | Clickjacking |
| `X-XSS-Protection: 1; mode=block` | Reflected XSS (legacy browsers) |
| `Referrer-Policy` | Filtración de URLs internas |
| `Permissions-Policy` | Acceso no autorizado a cámara/micrófono/GPS |

### 3. Ocultar versión de Nginx
`server_tokens off` en todos los bloques server. Un atacante no puede saber qué versión de Nginx se ejecuta para buscar exploits específicos.

### 4. Limitar métodos HTTP
Solo se permiten `GET`, `POST` y `OPTIONS`. Cualquier otro método (`PUT`, `DELETE`, `PATCH`, `TRACE`) devuelve 405. TRACE es especialmente peligroso porque puede revelar cookies y headers.

### 5. Timeouts agresivos
```nginx
client_body_timeout 10s;
client_header_timeout 10s;
send_timeout 10s;
keepalive_timeout 15s;
```
Previene ataques Slowloris (mantener conexiones abiertas indefinidamente para agotar recursos).

### 6. Limitar tamaño de headers
`large_client_header_buffers 4 8k` rechaza peticiones con headers excesivamente grandes, previniendo header injection y buffer overflow.

### 7. Bloquear user-agents maliciosos
Rechaza automáticamente herramientas de escaneo conocidas: sqlmap, nikto, nmap, masscan, havij, w3af, nessus, acunetix, python-requests, Go-http-client, libwww-perl.

### 8. Bloquear acceso a archivos sensibles
Cualquier petición a `.env`, `.git`, `.htaccess`, `.htpasswd`, `docker-compose`, `Dockerfile` o `Makefile` devuelve 404. Impide filtrar configuración, credenciales o estructura del proyecto.

---

## Backend PHP — 14 medidas

### 9. CSRF token (Cross-Site Request Forgery)
Cada envío requiere un token CSRF válido:

1. El frontend solicita `GET /api/csrf-token` → recibe un token HMAC-SHA256 firmado con timestamp
2. Lo envía en el header `X-CSRF-Token` con cada POST
3. El backend verifica firma + expiración (15 minutos)

No necesita base de datos (stateless). Impide que un sitio malicioso envíe formularios en nombre del usuario.

**Archivos:** `app/Http/Security.php`, `src/services/api.js`

### 10. Sanitización avanzada
Todos los campos pasan por `strip_tags()` que elimina cualquier etiqueta HTML/PHP. Combinado con prepared statements (PDO), protege contra XSS almacenado e inyección SQL.

**Archivo:** `app/Controllers/SubmissionController.php` → `sanitizeInput()`

### 11. Prepared statements (PDO)
Todas las consultas SQL usan parámetros vinculados (`:placeholder`). Nunca se concatena input del usuario en una query. Esto hace que la inyección SQL sea estructuralmente imposible.

### 12. Validación de tipos estricta
Cada campo se valida con reglas específicas:
- **Longitudes máximas** por campo (100 chars nombre, 255 email, 500 LinkedIn...)
- **Formato de teléfono**: solo dígitos, espacios, +, -, ()
- **Formato de fecha**: YYYY-MM-DD con regex
- **Formato de email**: `filter_var(FILTER_VALIDATE_EMAIL)`
- **Formato de URL**: `filter_var(FILTER_VALIDATE_URL)` para LinkedIn

**Archivo:** `app/Validation/SubmissionValidator.php`

### 13. Rate limiting por IP (PHP)
Tabla `rate_limits` en MySQL que cuenta intentos de POST por IP. Si supera el máximo configurado (`RATE_LIMIT_MAX`) en la ventana temporal (`RATE_LIMIT_WINDOW_MINUTES`), devuelve HTTP 429. Complementa el rate limiting de Nginx (defensa en profundidad).

**Archivo:** `app/Services/RateLimiter.php`

### 14. Limitar tamaño de payload
`client_max_body_size 15M` en Nginx + validación de tamaño máximo de archivo (10MB) en PHP.

### 15. Logging de seguridad
Cada evento de seguridad se registra con IP, user-agent y timestamp en formato JSON estructurado:
- Intentos de envío
- Tokens CSRF inválidos/expirados
- Orígenes bloqueados
- Archivos rechazados (tipo/tamaño/magic bytes)
- Rate limiting activado
- Errores de webhook
- Errores de DB

**Archivo:** `app/Services/SecurityLogger.php`

### 16. Honeypot field
Campo oculto `website` inyectado en el DOM con CSS (`position:absolute; left:-9999px`). Los bots lo rellenan automáticamente. Si llega relleno, el backend responde con éxito falso (confunde al bot sin revelar que fue detectado).

**Archivos:** `src/framework/router.js` (DOM), `app/Http/Security.php` (validación)

### 17. Validar Content-Type
Solo se acepta `multipart/form-data` en POST. Cualquier otro Content-Type devuelve 415. Previene ataques que usan `text/plain` o `application/json` para bypasear CORS.

### 18. Validar origen de la petición
Se comprueba el header `Origin` (y `Referer` como fallback) contra `ALLOWED_ORIGIN` configurado en `.env`. Peticiones de otros dominios se rechazan con 403.

### 19. Verificación de magic bytes
Los archivos subidos (CV) se verifican no solo por extensión sino por sus primeros bytes:
- PDF: `%PDF`
- DOC: `\xD0\xCF\x11\xE0`
- DOCX: `PK\x03\x04`

Un archivo `.pdf` que realmente es un ejecutable se rechaza.

**Archivo:** `app/Services/FileUploader.php` → `verifyMagicBytes()`

### 20. Nombres de archivo aleatorios
Los CVs se renombran con `bin2hex(random_bytes(16))` — 32 caracteres hexadecimales impredecibles. Impide path traversal y enumeración de archivos.

### 21. CORS restrictivo
`Access-Control-Allow-Origin` usa el valor de `ALLOWED_ORIGIN` (no `*`). Solo el frontend autorizado puede hacer peticiones al API. Se permite `X-CSRF-Token` en `Allow-Headers`.

### 22. Ocultar PHP
`expose_php = Off` en `php.ini`. Los headers de respuesta no revelan que el backend usa PHP ni su versión.

**Archivo:** `backend/Dockerfile` → `security.ini`

---

## MySQL — 5 medidas

### 23. Usuario con permisos mínimos
El usuario de la aplicación solo tiene:
- `SELECT, INSERT` en `submissions`
- `SELECT, INSERT, DELETE` en `rate_limits`

No tiene `DROP`, `ALTER`, `UPDATE`, `DELETE` en submissions, `CREATE`, `GRANT` ni ningún otro privilegio. Si la app es comprometida, el atacante no puede destruir ni modificar datos existentes.

**Archivo:** `mysql/init.sql`

### 24. MySQL solo en red interna
El puerto 3306 no se expone al host. MySQL solo es accesible desde la red interna de Docker. No aparece en `ports:` del `docker-compose.yml`.

### 25. Contraseñas fuertes
Al copiar `.env.example` a `.env`, sustituir los placeholders `CHANGE_ME_*` por valores largos y aleatorios (mayúsculas, minúsculas, números y símbolos).

### 26. Cifrado AES-256 de datos PII
Email y teléfono (los campos más sensibles bajo GDPR) se cifran con AES-256-CBC antes de guardar:
1. Se genera un IV aleatorio de 16 bytes por cada valor
2. Se cifra con la clave `ENCRYPTION_KEY` de `.env` (hasheada a SHA-256)
3. Se almacena como `base64(IV + ciphertext)`

Si la base de datos es comprometida, los datos PII son ilegibles sin la clave.

**Archivo:** `app/Services/Encryptor.php`

### 27. Charset y collation seguros
`utf8mb4` con `utf8mb4_unicode_ci` previene ataques de truncamiento de cadenas y asegura compatibilidad completa con Unicode (incluyendo emojis y caracteres especiales).

---

## Docker — 8 medidas

### 28. Filesystem de solo lectura
Todos los contenedores excepto MySQL usan `read_only: true`. El sistema de archivos raíz no es escribible. Solo directorios específicos (`/tmp`, `/var/run`, `/var/log`) se montan como tmpfs con tamaño limitado.

Un atacante que comprometa un proceso no puede escribir scripts, binarios ni archivos de configuración.

### 29. tmpfs con tamaño limitado
Los directorios escribibles usan tmpfs (en memoria) con límites:
- Backend: `/tmp` 64MB (uploads temporales), `/var/run` 1MB, `/var/log` 16MB
- Frontend/Nginx: `/var/cache/nginx` 16MB, `/var/run` 1MB, `/tmp` 16MB

Previene que un atacante llene el disco del host.

### 30. Capabilities eliminadas
`cap_drop: ALL` elimina todas las capabilities de Linux. Solo se re-añaden las mínimas necesarias:
- `CHOWN`, `SETGID`, `SETUID`: necesarias para inicialización de procesos
- `DAC_OVERRIDE`: solo backend (acceso a archivos de upload)
- `NET_BIND_SERVICE`: solo nginx (bind a puerto 80)

### 31. No new privileges
`security_opt: no-new-privileges:true` impide que un proceso escale privilegios a través de setuid/setgid binaries, incluso si los encuentra en el filesystem.

### 32. Health checks con timeout
MySQL tiene healthcheck con `timeout: 3s` y `retries: 20`. Si el contenedor deja de responder, Docker lo reinicia automáticamente.

### 33. Límites de recursos
Cada contenedor tiene límites de CPU y memoria:

| Contenedor | RAM | CPU |
|-----------|-----|-----|
| MySQL | 512MB | 1.0 |
| Backend | 256MB | 0.5 |
| Frontend | 128MB | 0.25 |
| Nginx | 128MB | 0.25 |

Previene que un proceso comprometido consuma todos los recursos del host.

### 34. Imagen base Alpine
El backend usa `php:8.3-fpm-alpine` en lugar de la imagen Debian completa. Alpine tiene ~5MB vs ~150MB, lo que significa menos paquetes instalados y menor superficie de ataque.

### 35. Escaneo de imágenes
Se recomienda ejecutar `docker scout cves` o `trivy image` periódicamente sobre las imágenes construidas para detectar vulnerabilidades conocidas en dependencias del sistema.

```bash
docker scout cves sandbox-backend:latest
docker scout cves sandbox-nginx:latest
```

---

## Frontend — 5 medidas

### 36. Content Security Policy (CSP)
Implementada tanto en meta tag HTML como en header Nginx:

```
default-src 'self';
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
font-src https://fonts.gstatic.com;
img-src 'self' data:;
script-src 'self'
```

- **Scripts**: solo desde el propio dominio (bloquea XSS inyectado)
- **Estilos**: propio dominio + Google Fonts
- **Fuentes**: solo Google Fonts CDN
- **Imágenes**: propio dominio + data URIs
- **Todo lo demás**: solo propio dominio

### 37. Validación doble (cliente + servidor)
Cada campo se valida en JavaScript antes de enviar Y en PHP al recibir. Un atacante que bypasee el frontend sigue enfrentando la validación del backend.

### 38. Autocomplete off en campos sensibles
Email, teléfono, nombre y apellidos tienen `autocomplete="off"`. Previene que el navegador almacene y sugiera datos personales sensibles.

### 39. Subresource Integrity (SRI)
Todos los scripts de la aplicación son locales (ES Modules servidos desde el mismo dominio), eliminando el riesgo de manipulación de CDN. Para Google Fonts, la CSP restringe la carga solo al dominio `fonts.googleapis.com`.

> **Nota**: SRI no es compatible con Google Fonts porque sirven CSS diferente según user-agent. Para máxima seguridad, se puede self-hostear la fuente Inter.

### 40. Content Security Policy (meta tag)
CSP duplicada como meta tag HTML como defensa en profundidad. Si el header Nginx falla o se omite (acceso directo al contenedor frontend), el meta tag sigue protegiendo.

---

## Infraestructura general — 4 medidas

### 41. HTTPS y HSTS
El gateway incluye `Strict-Transport-Security` en el bloque del frontend (puerto 8080): ante un sitio servido por **HTTPS**, el navegador recordará usar TLS durante un año. Con **HTTP** local (`localhost`) el header no tiene efecto práctico.

Para servir el dominio con TLS (ej. evaluación en servidor con certificado):
```bash
# Con Let's Encrypt
certbot --nginx -d admision.tudominio.es
```

### 42. .env fuera del build context
`.dockerignore` en frontend y backend excluye `.env`. Las variables de entorno se inyectan via `environment:` en docker-compose, nunca se copian en la imagen. Si alguien obtiene la imagen Docker, no tiene las credenciales.

### 43. Git hooks pre-commit
Se recomienda configurar un hook que detecte si alguien intenta commitear archivos sensibles:

```bash
# .git/hooks/pre-commit
#!/bin/sh
if git diff --cached --name-only | grep -qE '\.env$|\.pem$|\.key$|credentials'; then
    echo "ERROR: Intentando commitear archivos sensibles"
    exit 1
fi
```

### 44. Backups automáticos
Se recomienda configurar backups periódicos de MySQL:

```bash
# Crontab: cada día a las 3:00 AM
0 3 * * * docker exec sandbox-db-1 mysqldump -u root -p"$DB_ROOT_PASSWORD" evolve | gzip > /backups/evolve_$(date +\%Y\%m\%d).sql.gz
```

---

## Webhook forwarding

Además de guardar en MySQL, los datos se pueden reenviar al endpoint original:

```
POST https://n8n.cloud.evolveacademy.es/webhook/prueba-tecnica-fullstack
```

Controlado por variables de entorno:

| Variable | Default | Descripción |
|----------|---------|-------------|
| `FORWARD_WEBHOOK_ENABLED` | `true` (en `.env.example`) | Activa/desactiva el reenvío |
| `FORWARD_WEBHOOK_URL` | `https://n8n.cloud.evolve...` | URL del webhook |

El reenvío es **no bloqueante**: si el webhook falla, la solicitud se guarda igualmente en MySQL y el error se registra en el log de seguridad.

**Archivo:** `app/Services/WebhookForwarder.php`

---

## Observabilidad y trazabilidad (complementarias)

No sustituyen las medidas anteriores; refuerzan observabilidad y trazabilidad.

| Tema | Qué hace |
|------|----------|
| **`X-Request-ID`** | Nginx genera un ID por petición, lo reenvía a PHP (`fastcgi_param HTTP_X_REQUEST_ID $request_id`) y lo expone en la respuesta. `SecurityLogger` incluye `request_id` en cada evento JSON. |
| **`X-Response-Time`** | El backend mide tiempo de proceso y lo expone en el header (útil para SLOs y depuración). |
| **`GET /api/health`** | Comprueba BBDD (`SELECT 1`), versión de PHP y espacio libre en el volumen de uploads — sin PII. |
| **Compresión y caché** | Gzip para JSON en el gateway API; en el contenedor frontend, gzip + `ETag` + `Cache-Control` para CSS/JS/SVG. |

---

## Resumen por tipo de ataque

| Ataque | Medidas que lo previenen |
|--------|-------------------------|
| SQL Injection | #11 (prepared statements), #10 (sanitización) |
| XSS | #10 (strip_tags), #36/#40 (CSP), #2 (X-XSS-Protection) |
| CSRF | #9 (token HMAC), #18 (origin check) |
| DDoS / Brute Force | #1 (nginx rate limit), #13 (PHP rate limit), #33 (resource limits) |
| Clickjacking | #2 (X-Frame-Options: DENY) |
| File Upload Attack | #19 (magic bytes), #14 (size limit), #20 (random names) |
| Bot / Spam | #7 (user-agent block), #16 (honeypot) |
| Data Breach | #26 (AES-256 cifrado), #23 (permisos mínimos), #24 (red interna) |
| Container Escape | #28 (read-only), #30 (cap_drop ALL), #31 (no-new-privileges) |
| MITM | #41 (HTTPS/HSTS) |
| Info Disclosure | #3/#22 (ocultar versiones), #8 (bloquear archivos sensibles) |
