<?php
// Simple configuration loader
// Loads environment variables from a .env file if present.

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        [$name, $value] = array_pad(explode('=', $line, 2), 2, null);
        if ($name !== null && $value !== null && !isset($_ENV[$name])) {
            $_ENV[$name] = trim($value);
        }
    }
}
