<?php

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

$config = require __DIR__ . '/../config/app.php';

Response::cors($config['security']['allowed_origin']);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::noContent();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::checkContentType();
    Security::checkOrigin($config['security']['allowed_origin']);
    Security::checkHoneypot();
    Security::validateCsrf(
        $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '',
        $config['security']['csrf_secret']
    );
}

$db = Database::connect($config['db']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rateLimiter = new RateLimiter(
        $db,
        $config['security']['rate_limit_max'],
        $config['security']['rate_limit_window']
    );
    $rateLimiter->check($_SERVER['REMOTE_ADDR']);
}

SecurityLogger::log('request', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri'    => $_SERVER['REQUEST_URI'],
]);

$router = new Router();

$router->get('/api/csrf-token', function () use ($config) {
    $token = Security::generateCsrf($config['security']['csrf_secret']);
    Response::success(['token' => $token]);
});

$router->post('/api/submit', function () use ($config, $db) {
    $uploader   = new FileUploader($config['uploads']);
    $encryptor  = new Encryptor($config['security']['encryption_key']);
    $controller = new SubmissionController($db, $uploader, $encryptor, $config['webhook']);
    $controller->store();
});

try {
    $router->dispatch();
} catch (PDOException $e) {
    SecurityLogger::log('db_error', ['message' => $e->getMessage()]);
    Response::error('Database error', 500);
} catch (RuntimeException $e) {
    SecurityLogger::log('runtime_error', ['message' => $e->getMessage()]);
    Response::error($e->getMessage(), 400);
} catch (Throwable $e) {
    SecurityLogger::log('server_error', ['message' => $e->getMessage()]);
    Response::error('Internal server error', 500);
}
