<?php
/**
 * Router pour le serveur PHP intégré
 * Permet de router les requêtes vers Kirby pour la génération des thumbs
 */

$path = $_SERVER['REQUEST_URI'];
$path = parse_url($path, PHP_URL_PATH);

// Fichiers statiques existants : les servir directement
$staticFile = __DIR__ . $path;
if (is_file($staticFile)) {
    return false; // Laisse PHP servir le fichier statique
}

// Tout le reste passe par index.php (Kirby)
// Kirby génère les thumbs de manière synchrone (async => false dans config)
require __DIR__ . '/index.php';
