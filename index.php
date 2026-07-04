<?php

// Charge les variables d'environnement depuis un fichier .env (s'il existe).
// Doit être fait AVANT le boot de Kirby pour que site/config/config.php
// puisse lire $_ENV (SMTP, etc.). Le fichier .env n'est jamais commité
// et est protégé de tout accès direct par le .htaccess.
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim(trim($value), "\"'");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("$key=$value");
    }
}

require 'kirby/bootstrap.php';

echo (new Kirby)->render();
