<?php
/** Minimal .env loader, with real server environment variables taking priority. */
function paicafe_load_env($path) {
    if (!is_readable($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || getenv($key) !== false) continue;
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function env_value($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function env_bool($key, $default = false) {
    return filter_var(env_value($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
}

paicafe_load_env(__DIR__ . '/.env');

// Database configuration
define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int)env_value('DB_PORT', 3306));
define('DB_USER', env_value('DB_USERNAME', ''));
define('DB_PASS', env_value('DB_PASSWORD', ''));
define('DB_NAME', env_value('DB_NAME', ''));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));

// Application Details
define('APP_NAME', 'PaiCafe Lounge & Cafe');
define('APP_URL', env_value('APP_URL', 'https://paicafes.com'));
define('APP_ENV', env_value('APP_ENV', 'production'));
define('APP_DEBUG', env_bool('APP_DEBUG', false));

// Optional Redis cache. Enable through environment variables in production.
define('REDIS_ENABLED', env_bool('REDIS_ENABLED', false));
define('REDIS_HOST', env_value('REDIS_HOST', '127.0.0.1'));
define('REDIS_PORT', (int)env_value('REDIS_PORT', 6379));
define('REDIS_PASSWORD', env_value('REDIS_PASSWORD', ''));
define('REDIS_DATABASE', (int)env_value('REDIS_DATABASE', 0));
define('CACHE_PREFIX', 'paicafe:');

// Security
define('PASSWORD_PEPPER', 'A-Secret-Pepper-String-For-PaiCafe');
?>
