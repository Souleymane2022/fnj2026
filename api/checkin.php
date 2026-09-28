<?php
/** API JSON du scanner d'entrée (agents connectés uniquement). */
define('FNJ_ROOT', '../');
require __DIR__ . '/../inc/app.php';
require __DIR__ . '/../inc/controle.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$u = utilisateur();
$entree = json_decode((string) file_get_contents('php://input'), true) ?: [];
if (!$u) {
    http_response_code(401);
    echo json_encode(['etat' => 'erreur', 'message' => 'Session expirée : reconnectez-vous.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), (string) ($entree['csrf'] ?? ''))) {
    http_response_code(400);
    echo json_encode(['etat' => 'erreur', 'message' => 'Requête invalide.']);
    exit;
}

$scan = analyser_scan((string) ($entree['contenu'] ?? ''));
$enregistrer = !empty($entree['enregistrer']);
// Saisie manuelle (sans signature) autorisée pour un agent authentifié
$res = controler_badge($scan['code'], $scan['sig'], false, $enregistrer ? $u['login'] : null, (string) ($entree['point'] ?? ''));
echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
