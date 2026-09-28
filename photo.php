<?php
/** Sert la photo d'un badge (fichiers stockés hors accès direct, dans data/). */
require __DIR__ . '/inc/app.php';

$code = (string) ($_GET['c'] ?? '');
$k = (string) ($_GET['k'] ?? '');
$insc = signature_ok($code, $k, 'photo') ? trouver_inscription($code) : null;
$fichier = $insc && $insc['photo'] ? config('photos_dir') . '/' . basename($insc['photo']) : null;

if (!$fichier || !is_file($fichier)) {
    http_response_code(404);
    exit;
}
header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($fichier));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($fichier);
