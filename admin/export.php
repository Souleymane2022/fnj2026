<?php
/** Export CSV (séparateur « ; », UTF-8 avec BOM : s'ouvre directement dans Excel). */
define('FNJ_ROOT', '../');
require __DIR__ . '/../inc/app.php';
require __DIR__ . '/../inc/champs.php';
exiger_connexion('admin');

$where = [];
$params = [];
if (($q = trim((string) ($_GET['q'] ?? ''))) !== '') {
    $conds = [];
    foreach (['nom', 'prenom', 'code', 'email', 'telephone', 'organisation'] as $i => $col) {
        $conds[] = "$col " . sql_like() . " :q$i";
        $params[":q$i"] = "%$q%";
    }
    $where[] = '(' . implode(' OR ', $conds) . ')';
}
foreach (['categorie', 'statut', 'province'] as $k) {
    if (($v = (string) ($_GET[$k] ?? '')) !== '') {
        $where[] = "$k = :$k";
        $params[":$k"] = $v;
    }
}
$st = db()->prepare('SELECT i.*, (SELECT COUNT(*) FROM entrees e WHERE e.inscription_id = i.id) AS nb_entrees FROM inscriptions i '
    . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY categorie, nom, prenom');
$st->execute($params);

// Colonnes « détails » de toutes les catégories concernées
$colsDetails = [];
foreach (array_keys(categories()) as $k) {
    if (!empty($_GET['categorie']) && $_GET['categorie'] !== $k) continue;
    foreach (champs_profil($k) as $nom => $c) {
        if (empty($c['colonne'])) $colsDetails[$nom] = $c['label'];
    }
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="inscriptions-fnj2026-' . date('Ymd-His') . '.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
$entete = ['Code', 'Catégorie', 'Statut', 'Civilité', 'Nom', 'Prénom', 'Sexe', 'Date de naissance', 'Nationalité', 'Province', 'Ville',
           'Téléphone', 'E-mail', 'Organisation', 'Fonction'];
fputcsv($out, array_merge($entete, array_values($colsDetails), ['Nb entrées', 'Inscrit le', 'Lien badge']), ';');

/** Neutralise les formules (=, +, -, @) à l'ouverture dans un tableur. */
function cellule($v): string
{
    $v = is_array($v) ? implode(', ', $v) : (string) $v;
    return preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
}

while ($r = $st->fetch()) {
    $d = json_decode((string) $r['details'], true) ?: [];
    $ligne = [$r['code'], categorie($r['categorie'])['label'] ?? $r['categorie'], $r['statut'], $r['civilite'], $r['nom'], $r['prenom'], $r['sexe'],
              $r['date_naissance'], $r['nationalite'], $r['province'], $r['ville'], $r['telephone'], $r['email'], $r['organisation'], $r['fonction']];
    foreach (array_keys($colsDetails) as $k) {
        $ligne[] = $d[$k] ?? '';
    }
    $ligne[] = $r['nb_entrees'];
    $ligne[] = $r['cree_le'];
    $ligne[] = badge_url($r, true);
    fputcsv($out, array_map('cellule', $ligne), ';');
}
fclose($out);
