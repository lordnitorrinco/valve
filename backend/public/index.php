<?php

/**
 * Front controller — single entry point for all API requests.
 *
 * All Response methods throw HaltException instead of exit().
 * This outer try/catch captures every halt (OPTIONS, security blocks,
 * route handlers, validation errors) and outputs the response body.
 */

// ── Dependencies ─────────────────────────────────────────────
require_once __DIR__ . '/../app/Http/HaltException.php';
require_once __DIR__ . '/../app/Http/Response.php';
require_once __DIR__ . '/../app/Http/Router.php';
require_once __DIR__ . '/../app/Http/Security.php';
require_once __DIR__ . '/../app/Services/Database.php';
require_once __DIR__ . '/../app/Services/Encryptor.php';
require_once __DIR__ . '/../app/Services/FileUploader.php';
require_once __DIR__ . '/../app/Services/RateLimiter.php';
require_once __DIR__ . '/../app/Services/SecurityLogger.php';
require_once __DIR__ . '/../app/Services/WebhookForwarder.php';
require_once __DIR__ . '/../app/Validation/Validator.php';
require_once __DIR__ . '/../app/Validation/SubmissionValidator.php';
require_once __DIR__ . '/../app/Controllers/SubmissionController.php';
require_once __DIR__ . '/../app/Controllers/HealthController.php';

// ── Configuration ────────────────────────────────────────────
$config = require __DIR__ . '/../config/app.php';

// ── Observability: request timing + correlation id (Nginx may set HTTP_X_REQUEST_ID) ──
$GLOBALS['request_start'] = microtime(true);
if (empty($_SERVER['HTTP_X_REQUEST_ID'] ?? '')) {
    $_SERVER['HTTP_X_REQUEST_ID'] = bin2hex(random_bytes(16));
}
// Client-visible X-Request-ID is set by the gateway Nginx (add_header); we only ensure $_SERVER has a value for logs.

// ── CORS (applied to every response, including errors) ──────
Response::cors($config['security']['allowed_origin']);

try {
    // ── OPTIONS preflight — respond immediately ─────────────
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        Response::noContent();
    }

    // ── POST-only security checks ───────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Security::checkContentType();
        Security::checkOrigin($config['security']['allowed_origin']);
        Security::checkHoneypot();
        Security::validateCsrf(
            $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '',
            $config['security']['csrf_secret']
        );
    }

    // ── Database connection ─────────────────────────────────
    $db = Database::connect($config['db']);

    // ── Application-level rate limiting (POST only) ─────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rateLimiter = new RateLimiter(
            $db,
            $config['security']['rate_limit_max'],
            $config['security']['rate_limit_window']
        );
        $rateLimiter->check($_SERVER['REMOTE_ADDR']);
    }

    // ── Request logging ─────────────────────────────────────
    SecurityLogger::log('request', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri'    => $_SERVER['REQUEST_URI'],
    ]);

    // ── Routes ──────────────────────────────────────────────
    $router = new Router();

    $router->get('/api/csrf-token', function () use ($config) {
        $token = Security::generateCsrf($config['security']['csrf_secret']);
        Response::success(['token' => $token]);
    });

    $router->get('/api/health', function () use ($db, $config) {
        HealthController::check($db, $config['uploads']['directory']);
    });

    $router->get('/api/submissions', function () use ($config, $db) {
        $encryptor  = new Encryptor($config['security']['encryption_key']);
        $uploader   = new FileUploader($config['uploads']);
        $controller = new SubmissionController($db, $uploader, $encryptor, $config['webhook']);
        $controller->list();
    });

    $router->get('/api/submissions/{id}/cv', function (array $params) use ($config, $db) {
        $encryptor  = new Encryptor($config['security']['encryption_key']);
        $uploader   = new FileUploader($config['uploads']);
        $controller = new SubmissionController($db, $uploader, $encryptor, $config['webhook']);
        $controller->downloadCv((int) $params['id']);
    });

    $router->post('/api/submit', function () use ($config, $db) {
        $uploader   = new FileUploader($config['uploads']);
        $encryptor  = new Encryptor($config['security']['encryption_key']);
        $controller = new SubmissionController($db, $uploader, $encryptor, $config['webhook']);
        $controller->store();
    });

    $router->dispatch();

} catch (HaltException $e) {
    echo $e->body;
} catch (PDOException $e) {
    SecurityLogger::log('db_error', ['message' => $e->getMessage()]);
    Response::timingHeader();
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
} catch (Throwable $e) {
    SecurityLogger::log('server_error', ['message' => $e->getMessage()]);
    Response::timingHeader();
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
