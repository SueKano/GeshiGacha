<?php

// Bridge OS environment variables to $_SERVER/$_ENV (Railway + PHP built-in server)
foreach (['APP_ENV', 'APP_DEBUG', 'APP_SECRET', 'DATABASE_URL', 'CORS_ALLOW_ORIGIN', 'JWT_PASSPHRASE'] as $var) {
    $val = getenv($var);
    if ($val !== false) {
        $_SERVER[$var] = $_ENV[$var] = $val;
    }
}

// Generate JWT keys if they don't exist
$jwtDir = dirname(__DIR__) . '/config/jwt';
$privatePath = $jwtDir . '/private.pem';
$publicPath = $jwtDir . '/public.pem';
if (!file_exists($privatePath) || !file_exists($publicPath)) {
    if (!is_dir($jwtDir)) {
        mkdir($jwtDir, 0755, true);
    }
    $passphrase = $_ENV['JWT_PASSPHRASE'] ?? 'test';
    $key = openssl_pkey_new(['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    if ($key) {
        openssl_pkey_export($key, $priv, $passphrase);
        file_put_contents($privatePath, $priv);
        file_put_contents($publicPath, openssl_pkey_get_details($key)['key']);
    }
}