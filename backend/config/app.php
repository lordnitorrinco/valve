<?php

/**
 * Application configuration.
 *
 * All sensitive values are loaded from environment variables,
 * with fallbacks for local development only.
 * In production, every value MUST be set via .env / Docker env.
 */
return [
    // Database connection settings
    'db' => [
        'host'     => getenv('DB_HOST') ?: 'db',
        'name'     => getenv('DB_NAME') ?: 'evolve',
        'user'     => getenv('DB_USER') ?: 'evolve',
        'password' => getenv('DB_PASS') ?: 'evolve_pass',
        'charset'  => 'utf8mb4',
    ],

    // File upload constraints
    'uploads' => [
        'directory'          => '/var/www/uploads',
        'max_size'           => 10 * 1024 * 1024, // 10 MB
        'allowed_extensions' => ['pdf', 'doc', 'docx'],
    ],

    // Security settings
    'security' => [
        'encryption_key'    => getenv('ENCRYPTION_KEY') ?: 'change-me-in-production-32chars!',
        'csrf_secret'       => getenv('CSRF_SECRET') ?: 'change-me-csrf-secret',
        'allowed_origin'    => getenv('ALLOWED_ORIGIN') ?: 'http://localhost:8080',
        'rate_limit_max'    => (int) (getenv('RATE_LIMIT_MAX') ?: 10),
        'rate_limit_window' => (int) (getenv('RATE_LIMIT_WINDOW_MINUTES') ?: 5),
    ],

    // External webhook forwarding (disabled by default)
    'webhook' => [
        'enabled' => filter_var(getenv('FORWARD_WEBHOOK_ENABLED') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'url'     => getenv('FORWARD_WEBHOOK_URL') ?: '',
    ],
];
