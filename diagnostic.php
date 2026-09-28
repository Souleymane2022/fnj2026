<?php
/**
 * Diagnostic de l'hébergement (Vercel) : environnement PHP et connexion à la
 * base de données. N'affiche aucun mot de passe ni donnée personnelle.
 */
require __DIR__ . '/inc/app.php';

$tests = [];
$tests[] = ['Version de PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=')];
foreach (['pdo_pgsql' => 'PostgreSQL (pdo_pgsql)', 'pdo_sqlite' => 'SQLite (pdo_sqlite)', 'mbstring' => 'mbstring', 'gd' => 'GD (facultatif)'] as $ext => $nom) {
    $tests[] = [$nom, extension_loaded($ext) ? 'disponible' : 'absent', extension_loaded($ext) || $ext === 'gd' || $ext === 'pdo_sqlite'];
}
$tests[] = ['Hébergement', getenv('VERCEL') ? 'Vercel (' . (getenv('VERCEL_ENV') ?: '?') . ')' : 'serveur classique', true];

$url = db_url();
if ($url) {
    $u = parse_url($url);
    $tests[] = ['Variable DATABASE_URL', 'définie – hôte ' . ($u['host'] ?? '?') . ', base ' . ltrim($u['path'] ?? '', '/'), true];
} else {
    $tests[] = ['Variable DATABASE_URL', getenv('VERCEL') ? 'ABSENTE : créez une base Neon (Storage) et reliez-la au projet' : 'absente (SQLite local)', !getenv('VERCEL')];
}
try {
    $debut = microtime(true);
    $n = (int) db()->query('SELECT COUNT(*) FROM inscriptions')->fetchColumn();
    $tests[] = ['Connexion à la base', 'OK (' . round((microtime(true) - $debut) * 1000) . ' ms) – ' . $n . ' inscription(s)' . (db_temporaire() ? ' – TEMPORAIRE (/tmp)' : ''), !db_temporaire()];
} catch (Throwable $ex) {
    $msg = preg_replace('#(postgres(ql)?://)[^@\s]+@#i', '$1***@', $ex->getMessage());
    $tests[] = ['Connexion à la base', 'ÉCHEC : ' . $msg, false];
}
$tests[] = ['Variable FNJ_BASE_URL', config('base_url') ?: 'absente (détection automatique : ' . base_url() . ')', (bool) config('base_url')];
$tests[] = ['Clé secrète FNJ_SECRET', getenv('FNJ_SECRET') ? 'définie' : 'valeur par défaut – à définir', (bool) getenv('FNJ_SECRET')];
$tests[] = ['Mots de passe admin / agent', mot_de_passe_par_defaut() ? 'valeurs par défaut – à changer' : 'personnalisés', !mot_de_passe_par_defaut()];

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
?><!DOCTYPE html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Diagnostic FNJ 2026</title></head>
<body style="font-family:Arial,sans-serif;background:#f4f6fa;margin:0;padding:30px 16px;color:#1f2937">
<div style="max-width:760px;margin:auto;background:#fff;border-radius:8px;border-top:6px solid #002664;padding:24px;box-shadow:0 6px 24px rgba(0,38,100,.1)">
<h1 style="color:#002664;margin-top:0;font-size:22px">Diagnostic de la plateforme FNJ 2026</h1>
<table style="width:100%;border-collapse:collapse;font-size:14px">
<?php foreach ($tests as [$nom, $val, $ok]): ?>
<tr><td style="padding:9px;border-bottom:1px solid #dde3ec;width:34%;font-weight:600"><?= e($nom) ?></td>
<td style="padding:9px;border-bottom:1px solid #dde3ec;word-break:break-word"><span style="color:<?= $ok ? '#1B7F3B' : '#C60C30' ?>;font-weight:700"><?= $ok ? '✔' : '✖' ?></span> <?= e($val) ?></td></tr>
<?php endforeach; ?>
</table>
<p><a href="/">← Retour au site</a></p>
</div></body></html>
