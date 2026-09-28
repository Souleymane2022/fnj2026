<?php
/** Sert la photo d'un badge (stockée en base, accessible uniquement par lien signé). */
require __DIR__ . '/inc/app.php';

$code = (string) ($_GET['c'] ?? '');
$k = (string) ($_GET['k'] ?? '');
$jpeg = null;
if (signature_ok($code, $k, 'photo')) {
    $st = db()->prepare('SELECT data FROM photos WHERE code = ?');
    $st->execute([$code]);
    $data = $st->fetchColumn();
    $jpeg = $data ? base64_decode((string) $data) : null;
}
if (!$jpeg) {
    http_response_code(404);
    exit;
}
$info = @getimagesizefromstring($jpeg);
header('Content-Type: ' . ($info['mime'] ?? 'image/jpeg'));
header('Content-Length: ' . strlen($jpeg));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
echo $jpeg;
