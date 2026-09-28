<?php
/**
 * Contrôle d'accès : vérification d'un badge et enregistrement des entrées.
 */

/**
 * Extrait le code (et la signature) d'un contenu scanné : URL de vérification
 * ou saisie manuelle du numéro de badge.
 */
function analyser_scan(string $contenu): array
{
    $contenu = trim($contenu);
    if (preg_match('#^https?://#i', $contenu)) {
        parse_str((string) parse_url($contenu, PHP_URL_QUERY), $q);
        return ['code' => strtoupper((string) ($q['c'] ?? '')), 'sig' => (string) ($q['s'] ?? '')];
    }
    return ['code' => strtoupper(preg_replace('/\s+/', '', $contenu)), 'sig' => null];
}

/**
 * @param bool        $signatureExigee false uniquement pour un agent connecté qui saisit un code à la main
 * @param string|null $agent           login de l'agent si l'entrée doit être enregistrée
 */
function controler_badge(string $code, ?string $sig, bool $signatureExigee, ?string $agent = null, string $point = ''): array
{
    if ($code === '' || (($signatureExigee || $sig !== null) && !signature_ok($code, (string) $sig, 'qr'))) {
        return ['etat' => 'invalide', 'message' => 'Badge non reconnu ou falsifié.'];
    }
    $insc = trouver_inscription($code);
    if (!$insc) {
        return ['etat' => 'invalide', 'message' => 'Aucun participant ne correspond à ce badge.'];
    }
    $cat = categorie($insc['categorie']);
    $participant = [
        'code'         => $insc['code'],
        'nom'          => $insc['nom'],
        'prenom'       => $insc['prenom'],
        'categorie'    => $cat['label'],
        'couleur'      => $cat['couleur'],
        'organisation' => $insc['organisation'],
        'fonction'     => $insc['fonction'],
        'province'     => $insc['province'],
        'photo'        => photo_url($insc),
        'statut'       => $insc['statut'],
    ];
    $jour = date('Y-m-d');
    $st = db()->prepare('SELECT heure, point_acces FROM entrees WHERE inscription_id = ? AND jour = ? ORDER BY id');
    $st->execute([$insc['id'], $jour]);
    $entreesJour = $st->fetchAll();

    if ($insc['statut'] === 'revoque') {
        return ['etat' => 'revoque', 'message' => 'Badge annulé – accès refusé.', 'participant' => $participant];
    }
    if ($insc['statut'] === 'en_attente') {
        return ['etat' => 'attente', 'message' => 'Badge en attente de validation – accès refusé.', 'participant' => $participant];
    }

    if ($agent !== null) {
        db()->prepare('INSERT INTO entrees (inscription_id, jour, heure, agent, point_acces) VALUES (?,?,?,?,?)')
            ->execute([$insc['id'], $jour, date('H:i:s'), $agent, mb_substr($point, 0, 60)]);
    }
    if ($entreesJour) {
        $premiere = $entreesJour[0];
        return [
            'etat'        => 'deja',
            'message'     => 'Badge valide – déjà entré(e) aujourd\'hui à ' . substr($premiere['heure'], 0, 5)
                . ($premiere['point_acces'] ? ' (' . $premiere['point_acces'] . ')' : '') . '.',
            'participant' => $participant,
            'entrees'     => count($entreesJour) + ($agent !== null ? 1 : 0),
        ];
    }
    return [
        'etat'        => 'ok',
        'message'     => $agent !== null ? 'Accès autorisé – entrée enregistrée.' : 'Badge valide.',
        'participant' => $participant,
        'entrees'     => $agent !== null ? 1 : 0,
    ];
}
