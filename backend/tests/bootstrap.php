<?php

/**
 * PHPUnit bootstrap: loads application classes via explicit require_once so unit tests
 * can run without Composer autoloading. Include this file from phpunit.xml before tests.
 */
require_once __DIR__ . '/../app/Http/HaltException.php';
require_once __DIR__ . '/../app/Http/Response.php';
require_once __DIR__ . '/../app/Controllers/HealthController.php';
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
