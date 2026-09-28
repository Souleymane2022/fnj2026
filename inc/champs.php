<?php
/**
 * Définition des champs des formulaires. Chaque catégorie possède un bloc
 * "profil" propre ; les champs d'identité et de contact sont communs.
 *
 * Clés d'un champ : label, type, requis, options, aide, plein, min, max, colonne
 *  - "colonne" : le champ est enregistré dans la colonne SQL du même nom
 *    (organisation, fonction) au lieu du JSON "details".
 */

function champs_identite(string $cat): array
{
    $ageRequis = in_array($cat, ['jeune', 'benevole'], true);
    return [
        'civilite'       => ['label' => 'Civilité', 'type' => 'radio', 'requis' => true, 'options' => ['M.', 'Mme', 'Mlle'], 'plein' => true],
        'nom'            => ['label' => 'Nom', 'type' => 'text', 'requis' => true, 'max' => 60],
        'prenom'         => ['label' => 'Prénom(s)', 'type' => 'text', 'requis' => true, 'max' => 80],
        'sexe'           => ['label' => 'Sexe', 'type' => 'select', 'requis' => true, 'options' => ['Masculin', 'Féminin']],
        'date_naissance' => ['label' => 'Date de naissance', 'type' => 'date', 'requis' => $ageRequis,
                             'aide' => $cat === 'jeune' ? 'La FNJ est ouverte aux jeunes de 15 à 35 ans.' : null],
        'nationalite'    => ['label' => 'Nationalité', 'type' => 'text', 'requis' => true, 'max' => 60, 'defaut' => 'Tchadienne'],
        'province'       => ['label' => 'Province de résidence', 'type' => 'select', 'requis' => $cat !== 'officiel' && $cat !== 'partenaire',
                             'options' => array_merge(provinces(), ['Hors du Tchad'])],
        'ville'          => ['label' => 'Ville / Commune', 'type' => 'text', 'requis' => false, 'max' => 80],
    ];
}

function champs_contact(): array
{
    return [
        'telephone' => ['label' => 'Téléphone (WhatsApp de préférence)', 'type' => 'tel', 'requis' => true, 'aide' => 'Ex. : +235 66 00 00 00'],
        'email'     => ['label' => 'Adresse e-mail', 'type' => 'email', 'requis' => true, 'aide' => 'Elle servira à retrouver votre badge.'],
    ];
}

function champs_profil(string $cat): array
{
    $oui_non = ['Oui', 'Non'];
    switch ($cat) {
        case 'jeune':
            return [
                'statut'        => ['label' => 'Situation actuelle', 'type' => 'select', 'requis' => true,
                                    'options' => ['Élève', 'Étudiant(e)', 'Jeune diplômé(e)', 'Salarié(e)', 'Entrepreneur(e)', 'Agriculteur(trice) / Éleveur(se)', 'Artisan(e)', 'Artiste', 'Sportif(ve)', 'Innovateur(trice)', 'Acteur(trice) associatif(ve)', 'Sans emploi', 'Autre']],
                'niveau_etude'  => ['label' => 'Niveau d\'études', 'type' => 'select', 'requis' => true,
                                    'options' => ['Aucun', 'Primaire', 'Secondaire', 'Baccalauréat', 'Licence', 'Master', 'Doctorat', 'Formation professionnelle', 'École coranique / franco-arabe']],
                'organisation'  => ['label' => 'Association / mouvement de jeunesse', 'type' => 'text', 'requis' => false, 'colonne' => true, 'aide' => 'Facultatif'],
                'fonction'      => ['label' => 'Rôle dans l\'organisation', 'type' => 'text', 'requis' => false, 'colonne' => true],
                'thematiques'   => ['label' => 'Thématiques qui vous intéressent', 'type' => 'checkbox', 'requis' => true, 'options' => thematiques(), 'plein' => true],
                'talent'        => ['label' => 'Souhaitez-vous présenter un talent ?', 'type' => 'select', 'requis' => false,
                                    'options' => ['Musique / chant', 'Danse', 'Slam / poésie / théâtre', 'Arts plastiques / artisanat', 'Sport', 'Innovation / projet entrepreneurial', 'Produits agricoles / transformation', 'Autre']],
                'talent_detail' => ['label' => 'Décrivez votre talent ou votre projet', 'type' => 'text', 'requis' => false, 'max' => 200],
                'hebergement'   => ['label' => 'Besoin d\'hébergement à Pala ?', 'type' => 'radio', 'requis' => true, 'options' => ['Oui', 'Non']],
                'attentes'      => ['label' => 'Vos attentes vis-à-vis de la FNJ', 'type' => 'textarea', 'requis' => false, 'plein' => true, 'max' => 1000],
                'besoins'       => ['label' => 'Besoins spécifiques (handicap, accessibilité…)', 'type' => 'text', 'requis' => false, 'plein' => true],
            ];
        case 'partenaire':
            return [
                'organisation'      => ['label' => 'Nom de l\'organisation / institution', 'type' => 'text', 'requis' => true, 'colonne' => true, 'plein' => true],
                'type_organisation' => ['label' => 'Type d\'organisation', 'type' => 'select', 'requis' => true,
                                        'options' => ['Agence du Système des Nations Unies', 'Ambassade / Coopération bilatérale', 'Institution financière internationale', 'ONG internationale', 'ONG / Association nationale', 'Institution publique', 'Secteur privé', 'Autre']],
                'fonction'          => ['label' => 'Fonction', 'type' => 'text', 'requis' => true, 'colonne' => true],
                'type_partenariat'  => ['label' => 'Nature du partenariat', 'type' => 'checkbox', 'requis' => true, 'plein' => true,
                                        'options' => ['Appui technique', 'Appui financier', 'Appui logistique', 'Communication / visibilité', 'Intervenants / experts', 'Animation d\'un atelier']],
                'nombre_delegues'   => ['label' => 'Nombre de délégués prévus', 'type' => 'number', 'requis' => false, 'min' => 1, 'max' => 50],
                'site_web'          => ['label' => 'Site web', 'type' => 'url', 'requis' => false],
                'message'           => ['label' => 'Message / proposition', 'type' => 'textarea', 'requis' => false, 'plein' => true, 'max' => 1500],
            ];
        case 'sponsor':
            return [
                'organisation'       => ['label' => 'Raison sociale de l\'entreprise', 'type' => 'text', 'requis' => true, 'colonne' => true, 'plein' => true],
                'secteur'            => ['label' => 'Secteur d\'activité', 'type' => 'text', 'requis' => true],
                'fonction'           => ['label' => 'Fonction du représentant', 'type' => 'text', 'requis' => true, 'colonne' => true],
                'niveau_sponsoring'  => ['label' => 'Niveau de sponsoring envisagé', 'type' => 'select', 'requis' => true,
                                         'options' => ['Platine', 'Or', 'Argent', 'Bronze', 'Contribution en nature', 'À discuter']],
                'stand'              => ['label' => 'Souhaitez-vous un stand ?', 'type' => 'radio', 'requis' => true, 'options' => $oui_non],
                'contribution'       => ['label' => 'Description de la contribution', 'type' => 'textarea', 'requis' => false, 'plein' => true, 'max' => 1500],
                'site_web'           => ['label' => 'Site web', 'type' => 'url', 'requis' => false],
                'nif'                => ['label' => 'NIF / RCCM', 'type' => 'text', 'requis' => false],
            ];
        case 'exposant':
            return [
                'organisation'   => ['label' => 'Nom de la structure', 'type' => 'text', 'requis' => true, 'colonne' => true, 'plein' => true],
                'type_structure' => ['label' => 'Type de structure', 'type' => 'select', 'requis' => true,
                                     'options' => ['Startup', 'Coopérative / GIE', 'Association', 'PME / TPE', 'Grande entreprise', 'Institution / Programme', 'Artisan']],
                'fonction'       => ['label' => 'Fonction', 'type' => 'text', 'requis' => true, 'colonne' => true],
                'secteur'        => ['label' => 'Secteur d\'activité', 'type' => 'text', 'requis' => true],
                'produits'       => ['label' => 'Produits / services exposés', 'type' => 'textarea', 'requis' => true, 'plein' => true, 'max' => 1500],
                'taille_stand'   => ['label' => 'Stand souhaité', 'type' => 'select', 'requis' => true, 'options' => ['Table simple', '9 m²', '12 m²', '18 m²', 'Espace extérieur']],
                'electricite'    => ['label' => 'Besoin en électricité ?', 'type' => 'radio', 'requis' => true, 'options' => $oui_non],
            ];
        case 'presse':
            return [
                'organisation' => ['label' => 'Organe de presse', 'type' => 'text', 'requis' => true, 'colonne' => true, 'plein' => true],
                'type_media'   => ['label' => 'Type de média', 'type' => 'select', 'requis' => true,
                                   'options' => ['Télévision', 'Radio', 'Presse écrite', 'Presse en ligne', 'Agence de presse', 'Créateur de contenu / influenceur']],
                'fonction'     => ['label' => 'Fonction', 'type' => 'select', 'requis' => true, 'colonne' => true,
                                   'options' => ['Journaliste', 'Reporter', 'Rédacteur en chef', 'Photographe', 'Caméraman / JRI', 'Technicien', 'Community manager']],
                'carte_presse' => ['label' => 'N° de carte de presse', 'type' => 'text', 'requis' => false],
                'equipement'   => ['label' => 'Matériel apporté (caméra, drone…)', 'type' => 'textarea', 'requis' => false, 'plein' => true, 'max' => 800],
            ];
        case 'officiel':
            return [
                'qualite'         => ['label' => 'Qualité', 'type' => 'select', 'requis' => true,
                                      'options' => ['Membre du Gouvernement', 'Parlementaire', 'Autorité administrative', 'Autorité traditionnelle / religieuse', 'Corps diplomatique', 'Conférencier / Panéliste', 'Invité spécial']],
                'organisation'    => ['label' => 'Institution', 'type' => 'text', 'requis' => true, 'colonne' => true],
                'fonction'        => ['label' => 'Fonction / titre', 'type' => 'text', 'requis' => true, 'colonne' => true, 'plein' => true],
                'accompagnants'   => ['label' => 'Nombre d\'accompagnants', 'type' => 'number', 'requis' => false, 'min' => 0, 'max' => 20],
                'code_invitation' => ['label' => 'Référence de l\'invitation', 'type' => 'text', 'requis' => false],
                'intervention'    => ['label' => 'Sujet d\'intervention (panélistes)', 'type' => 'textarea', 'requis' => false, 'plein' => true, 'max' => 800],
            ];
        case 'benevole':
            return [
                'commission'    => ['label' => 'Commission souhaitée', 'type' => 'select', 'requis' => true,
                                    'options' => ['Accueil & orientation', 'Protocole', 'Logistique', 'Communication & médias', 'Sécurité & contrôle d\'accès', 'Santé / secourisme', 'Commission technique', 'Comité d\'organisation']],
                'organisation'  => ['label' => 'Structure d\'origine', 'type' => 'text', 'requis' => false, 'colonne' => true],
                'fonction'      => ['label' => 'Profession / filière', 'type' => 'text', 'requis' => false, 'colonne' => true],
                'disponibilite' => ['label' => 'Disponibilités', 'type' => 'checkbox', 'requis' => true, 'options' => ['Préparation (avant la FNJ)', 'Jour 1', 'Jour 2', 'Jour 3']],
                'taille_tshirt' => ['label' => 'Taille de t-shirt', 'type' => 'select', 'requis' => true, 'options' => ['S', 'M', 'L', 'XL', 'XXL']],
                'competences'   => ['label' => 'Compétences / expériences', 'type' => 'textarea', 'requis' => false, 'plein' => true, 'max' => 1000],
            ];
    }
    return [];
}

function photo_obligatoire(string $cat): bool
{
    return $cat !== 'officiel';
}

/** Rendu HTML d'un champ. */
function rendre_champ(string $nom, array $c, array $valeurs, array $erreurs): string
{
    $val = $valeurs[$nom] ?? ($c['defaut'] ?? '');
    $id = 'f_' . $nom;
    $req = !empty($c['requis']);
    $cls = 'champ' . (!empty($c['plein']) ? ' plein' : '') . (isset($erreurs[$nom]) ? ' erreur' : '');
    $etoile = $req ? ' <span class="req">*</span>' : '';
    $h = '<div class="' . $cls . '">';

    switch ($c['type']) {
        case 'radio':
        case 'checkbox':
            $h .= '<span class="label">' . e($c['label']) . $etoile . '</span>';
            $h .= '<div class="' . ($c['type'] === 'radio' ? 'radio-ligne' : 'choix') . '">';
            $sel = (array) $val;
            foreach ($c['options'] as $opt) {
                $checked = in_array($opt, $sel, true) ? ' checked' : '';
                $name = $c['type'] === 'checkbox' ? $nom . '[]' : $nom;
                $r = ($c['type'] === 'radio' && $req) ? ' required' : '';
                $h .= '<label><input type="' . $c['type'] . '" name="' . e($name) . '" value="' . e($opt) . '"' . $checked . $r . '> ' . e($opt) . '</label>';
            }
            $h .= '</div>';
            break;
        case 'select':
            $h .= '<label for="' . $id . '">' . e($c['label']) . $etoile . '</label>';
            $h .= '<select id="' . $id . '" name="' . e($nom) . '"' . ($req ? ' required' : '') . '><option value="">— Sélectionner —</option>';
            foreach ($c['options'] as $opt) {
                $h .= '<option' . ($val === $opt ? ' selected' : '') . '>' . e($opt) . '</option>';
            }
            $h .= '</select>';
            break;
        case 'textarea':
            $h .= '<label for="' . $id . '">' . e($c['label']) . $etoile . '</label>';
            $h .= '<textarea id="' . $id . '" name="' . e($nom) . '"' . ($req ? ' required' : '') . (isset($c['max']) ? ' maxlength="' . (int) $c['max'] . '"' : '') . '>' . e($val) . '</textarea>';
            break;
        default:
            $attrs = '';
            if (isset($c['max']) && $c['type'] !== 'number') $attrs .= ' maxlength="' . (int) $c['max'] . '"';
            if ($c['type'] === 'number') {
                if (isset($c['min'])) $attrs .= ' min="' . (int) $c['min'] . '"';
                if (isset($c['max'])) $attrs .= ' max="' . (int) $c['max'] . '"';
            }
            if ($c['type'] === 'date') $attrs .= ' max="' . date('Y-m-d') . '"';
            if ($c['type'] === 'email') $attrs .= ' autocomplete="email"';
            if ($c['type'] === 'tel') $attrs .= ' autocomplete="tel"';
            $h .= '<label for="' . $id . '">' . e($c['label']) . $etoile . '</label>';
            $h .= '<input type="' . e($c['type']) . '" id="' . $id . '" name="' . e($nom) . '" value="' . e($val) . '"' . ($req ? ' required' : '') . $attrs . '>';
    }
    if (!empty($c['aide'])) {
        $h .= '<div class="aide">' . e($c['aide']) . '</div>';
    }
    if (isset($erreurs[$nom])) {
        $h .= '<div class="msg-erreur">' . e($erreurs[$nom]) . '</div>';
    }
    return $h . '</div>';
}

/** Valide et normalise les valeurs postées pour un ensemble de champs. */
function valider_champs(array $champs, array $post, array &$erreurs): array
{
    $out = [];
    foreach ($champs as $nom => $c) {
        $raw = $post[$nom] ?? null;
        if ($c['type'] === 'checkbox') {
            $vals = array_values(array_filter((array) $raw, fn($v) => in_array($v, $c['options'], true)));
            if (!empty($c['requis']) && !$vals) {
                $erreurs[$nom] = 'Veuillez cocher au moins une option.';
            }
            $out[$nom] = $vals;
            continue;
        }
        $v = is_string($raw) ? trim(preg_replace('/\s+/u', ' ', $raw)) : '';
        if ($c['type'] === 'textarea' && is_string($raw)) {
            $v = trim($raw);
        }
        if ($v === '') {
            if (!empty($c['requis'])) {
                $erreurs[$nom] = 'Ce champ est obligatoire.';
            }
            $out[$nom] = '';
            continue;
        }
        if (isset($c['max']) && $c['type'] !== 'number' && mb_strlen($v) > $c['max']) {
            $erreurs[$nom] = 'Maximum ' . $c['max'] . ' caractères.';
        }
        switch ($c['type']) {
            case 'select':
            case 'radio':
                if (!in_array($v, $c['options'], true)) {
                    $erreurs[$nom] = 'Valeur invalide.';
                }
                break;
            case 'email':
                if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $erreurs[$nom] = 'Adresse e-mail invalide.';
                }
                $v = mb_strtolower($v);
                break;
            case 'tel':
                $chiffres = preg_replace('/\D/', '', $v);
                if (strlen($chiffres) < 8 || strlen($chiffres) > 15 || !preg_match('/^\+?[\d\s().-]+$/', $v)) {
                    $erreurs[$nom] = 'Numéro de téléphone invalide.';
                }
                break;
            case 'url':
                if (!preg_match('#^https?://#i', $v)) {
                    $v = 'https://' . $v;
                }
                if (!filter_var($v, FILTER_VALIDATE_URL)) {
                    $erreurs[$nom] = 'Adresse web invalide.';
                }
                break;
            case 'number':
                if (!preg_match('/^\d+$/', $v) || (isset($c['min']) && (int) $v < $c['min']) || (isset($c['max']) && (int) $v > $c['max'])) {
                    $erreurs[$nom] = 'Nombre invalide.';
                }
                break;
            case 'date':
                $d = DateTime::createFromFormat('Y-m-d', $v);
                if (!$d || $d->format('Y-m-d') !== $v || $v > date('Y-m-d') || $v < '1900-01-01') {
                    $erreurs[$nom] = 'Date invalide.';
                }
                break;
        }
        $out[$nom] = $v;
    }
    return $out;
}

/** Enregistre la photo recadrée (4:5, 480×600, JPEG) en base. Retourne un nom ou null + erreur. */
function enregistrer_photo(array $fichier, string $code, ?string &$erreur): ?string
{
    if (($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $maxMo = (int) config('photo_max_mo');
    if ($fichier['error'] !== UPLOAD_ERR_OK || $fichier['size'] > $maxMo * 1024 * 1024) {
        $erreur = "La photo n'a pas pu être envoyée (taille maximale : {$maxMo} Mo).";
        return null;
    }
    $info = @getimagesize($fichier['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
    if (!$info || !isset($types[$info[2]])) {
        $erreur = 'Format de photo non accepté (JPEG, PNG ou WEBP).';
        return null;
    }
    if (function_exists($types[$info[2]]) && function_exists('imagecreatetruecolor')) {
        $jpeg = recadrer_photo_gd($fichier['tmp_name'], $info, $types[$info[2]]);
        if ($jpeg === null) {
            $erreur = 'La photo est illisible.';
            return null;
        }
    } else {
        // Sans GD (ex. Vercel) : la photo a déjà été recadrée et compressée
        // par le navigateur (voir inscription.php) ; on la stocke telle quelle.
        if ($fichier['size'] > 1.5 * 1024 * 1024) {
            $erreur = 'La photo est trop lourde (1,5 Mo maximum).';
            return null;
        }
        $jpeg = (string) file_get_contents($fichier['tmp_name']);
    }
    // Stockée en base : le disque des fonctions Vercel n'est pas persistant.
    db()->prepare('INSERT INTO photos (code, data) VALUES (?, ?) ON CONFLICT (code) DO UPDATE SET data = excluded.data')
        ->execute([$code, base64_encode($jpeg)]);
    return $code . '.jpg';
}

/** Recadrage 4:5 et redimensionnement 480×600 avec GD. */
function recadrer_photo_gd(string $chemin, array $info, string $fonction): ?string
{
    $src = @$fonction($chemin);
    if (!$src) {
        return null;
    }
    // Orientation EXIF des photos prises au téléphone
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($chemin);
        $rot = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 0] ?? 0;
        if ($rot) {
            $src = imagerotate($src, $rot, 0);
        }
    }
    $w = imagesx($src);
    $h = imagesy($src);
    $ratio = 4 / 5;
    if ($w / $h > $ratio) {
        $cw = (int) round($h * $ratio); $ch = $h; $cx = (int) (($w - $cw) / 2); $cy = 0;
    } else {
        $cw = $w; $ch = (int) round($w / $ratio); $cx = 0; $cy = (int) max(0, ($h - $ch) / 3); // légèrement vers le haut (visage)
    }
    $dst = imagecreatetruecolor(480, 600);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, 0, 0, $cx, $cy, 480, 600, $cw, $ch);
    ob_start();
    imagejpeg($dst, null, 85);
    $jpeg = (string) ob_get_clean();
    imagedestroy($src);
    imagedestroy($dst);
    return $jpeg;
}
