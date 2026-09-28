<?php
/**
 * Point d'entrée unique pour Vercel (runtime vercel-php).
 *
 * Vercel n'exécute que les fonctions du dossier api/ : toutes les requêtes
 * (hors assets/) sont redirigées ici par vercel.json, puis transmises à la
 * page PHP correspondante. Seules les pages listées sont accessibles, ce qui
 * protège config.php, inc/ et data/.
 */

$pages = [
    ''              => 'index.php',
    'index.php'     => 'index.php',
    'inscription.php' => 'inscription.php',
    'badge.php'     => 'badge.php',
    'retrouver.php' => 'retrouver.php',
    'verifier.php'  => 'verifier.php',
    'photo.php'     => 'photo.php',
    'admin'         => 'admin/index.php',
    'admin/'        => 'admin/index.php',
    'admin/index.php'   => 'admin/index.php',
    'admin/login.php'   => 'admin/login.php',
    'admin/logout.php'  => 'admin/logout.php',
    'admin/fiche.php'   => 'admin/fiche.php',
    'admin/export.php'  => 'admin/export.php',
    'admin/scanner.php' => 'admin/scanner.php',
    'admin/checkin.php' => 'admin/checkin.php',
];

$chemin = ltrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$chemin = rawurldecode($chemin);

// Test local : php -S localhost:8000 api/index.php (sert aussi les assets)
if (PHP_SAPI === 'cli-server' && strpos($chemin, 'assets/') === 0 && strpos($chemin, '..') === false
    && is_file(dirname(__DIR__) . '/' . $chemin)) {
    return false;
}

// /admin sans « / » final : les liens relatifs de l'admin exigent /admin/
if ($chemin === 'admin') {
    header('Location: /admin/');
    exit;
}

$racine = dirname(__DIR__);
if (!isset($pages[$chemin])) {
    http_response_code(404);
    define('FNJ_ROOT', str_repeat('../', substr_count($chemin, '/')));
    require $racine . '/inc/app.php';
    $titre = 'Page introuvable';
    require $racine . '/inc/header.php';
    echo '<section class="section"><div class="container"><div class="alerte alerte-erreur">La page demandée n\'existe pas. <a href="/">Retour à l\'accueil</a></div></div></section>';
    require $racine . '/inc/footer.php';
    exit;
}

$fichier = $racine . '/' . $pages[$chemin];
$_SERVER['SCRIPT_NAME'] = '/' . $pages[$chemin];
$_SERVER['SCRIPT_FILENAME'] = $fichier;
$_SERVER['PHP_SELF'] = '/' . $pages[$chemin];
chdir(dirname($fichier));
require $fichier;
