<?php

use App\Kernel;

// Bridge OS environment variables to $_SERVER/$_ENV for Symfony DotEnv compatibility
foreach (['APP_ENV', 'APP_DEBUG', 'APP_SECRET', 'DATABASE_URL', 'CORS_ALLOW_ORIGIN', 'JWT_PASSPHRASE'] as $var) {
    $val = getenv($var);
    if ($val !== false) {
        $_SERVER[$var] = $_ENV[$var] = $val;
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
