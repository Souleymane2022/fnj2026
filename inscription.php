<?php
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/champs.php';

$type = (string) ($_GET['type'] ?? 'jeune');
$cat = categorie($type);
if (!$cat) {
    header('Location: index.php#inscriptions');
    exit;
}

$identite = champs_identite($type);
$contact = champs_contact();
$profil = champs_profil($type);
$valeurs = [];
$erreurs = [];
$erreurGlobale = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!inscriptions_ouvertes()) {
        $erreurGlobale = 'Les inscriptions sont clôturées.';
    } elseif (!csrf_check()) {
        $erreurGlobale = 'Votre session a expiré. Veuillez renvoyer le formulaire.';
    } elseif (!empty($_POST['site_internet'])) {
        // Champ piège anti-robots
        $erreurGlobale = 'Envoi refusé.';
    }

    $valeurs = array_merge(
        valider_champs($identite, $_POST, $erreurs),
        valider_champs($contact, $_POST, $erreurs),
        valider_champs($profil, $_POST, $erreurs)
    );

    // Tranche d'âge des jeunes (15 – 35 ans)
    if ($type === 'jeune' && !isset($erreurs['date_naissance']) && $valeurs['date_naissance']) {
        $age = (new DateTime($valeurs['date_naissance']))->diff(new DateTime())->y;
        if ($age < 15 || $age > 35) {
            $erreurs['date_naissance'] = "La catégorie « Jeune participant » est réservée aux 15 – 35 ans (âge calculé : {$age} ans).";
        }
    }
    if (empty($_POST['consentement'])) {
        $erreurs['consentement'] = 'Vous devez accepter les conditions pour continuer.';
    }
    $photoOk = ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (photo_obligatoire($type) && !$photoOk) {
        $erreurs['photo'] = 'La photo d\'identité est obligatoire pour le badge.';
    }

    // Doublon : même e-mail dans la même catégorie
    if (!$erreurs && !$erreurGlobale) {
        $st = db()->prepare("SELECT code FROM inscriptions WHERE email = ? AND categorie = ? AND statut != 'revoque'");
        $st->execute([$valeurs['email'], $type]);
        if ($st->fetchColumn()) {
            $erreurGlobale = 'Une inscription existe déjà avec cette adresse e-mail dans cette catégorie. <a href="retrouver.php">Retrouver mon badge</a>.';
        }
    }

    if (!$erreurs && !$erreurGlobale) {
        $code = generer_code($type);
        $photo = null;
        if ($photoOk) {
            $errPhoto = null;
            $photo = enregistrer_photo($_FILES['photo'], $code, $errPhoto);
            if ($errPhoto) {
                $erreurs['photo'] = $errPhoto;
            }
        }
        if (!$erreurs) {
            $details = [];
            foreach ($profil as $nom => $c) {
                if (empty($c['colonne'])) {
                    $details[$nom] = $valeurs[$nom];
                }
            }
            $statut = in_array($type, config('validation_requise') ?: [], true) ? 'en_attente' : 'valide';
            $st = db()->prepare('INSERT INTO inscriptions
                (code, categorie, civilite, nom, prenom, sexe, date_naissance, nationalite, province, ville,
                 telephone, email, organisation, fonction, details, photo, statut, cree_le, ip)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $code, $type, $valeurs['civilite'], mb_strtoupper($valeurs['nom']), $valeurs['prenom'], $valeurs['sexe'],
                $valeurs['date_naissance'], $valeurs['nationalite'], $valeurs['province'], $valeurs['ville'],
                $valeurs['telephone'], $valeurs['email'], $valeurs['organisation'] ?? '', $valeurs['fonction'] ?? '',
                json_encode($details, JSON_UNESCAPED_UNICODE), $photo, $statut, date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            $insc = trouver_inscription($code);
            envoyer_confirmation($insc);
            $_SESSION['nouveau_badge'] = $code;
            header('Location: ' . badge_url($insc));
            exit;
        }
    }
}

function envoyer_confirmation(array $insc): void
{
    if (!config('email.actif')) {
        return;
    }
    $ev = config('event');
    $sujet = '=?UTF-8?B?' . base64_encode('Votre badge – ' . $ev['nom'] . ' ' . $ev['annee']) . '?=';
    $corps = "Bonjour {$insc['prenom']} {$insc['nom']},\n\n"
        . "Votre inscription au {$ev['nom']} {$ev['annee']} est enregistrée.\n"
        . "Numéro de badge : {$insc['code']}\n\n"
        . "Téléchargez votre badge électronique ici :\n" . badge_url($insc, true) . "\n\n"
        . "{$ev['dates']} – {$ev['lieu']}\n\n"
        . config('ministere.nom') . "\n" . config('ministere.site');
    $entetes = 'From: ' . config('email.expediteur') . "\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($insc['email'], $sujet, $corps, $entetes);
}

$titre = 'Inscription – ' . $cat['label'];
$page = 'inscription';
require __DIR__ . '/inc/header.php';
?>

<section class="page-titre" style="background: <?= e($cat['couleur']) ?>">
  <div class="container">
    <div class="ariane"><a href="index.php">Forum 2026</a> › Inscription</div>
    <h1><?= $cat['icone'] ?> Formulaire d'inscription – <?= e($cat['label']) ?></h1>
    <p><?= e($cat['resume']) ?></p>
  </div>
</section>

<section class="section section--gris" style="padding-top:36px">
  <div class="container">
    <nav class="onglets-cat" aria-label="Catégories">
      <?php foreach (categories() as $k => $c): ?>
        <a href="?type=<?= e($k) ?>" class="<?= $k === $type ? 'actif' : '' ?>" style="--c: <?= e($c['couleur']) ?>"><?= $c['icone'] ?> <?= e($c['label']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="form-layout">
      <form class="form-carte" method="post" enctype="multipart/form-data" novalidate id="form-inscription">
        <?= csrf_field() ?>
        <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="site_internet" tabindex="-1" autocomplete="off"></div>

        <?php if (!inscriptions_ouvertes()): ?>
          <div class="alerte alerte-attention">Les inscriptions en ligne sont clôturées.</div>
        <?php endif; ?>
        <?php if ($erreurGlobale): ?>
          <div class="alerte alerte-erreur"><?= $erreurGlobale ?></div>
        <?php elseif ($erreurs): ?>
          <div class="alerte alerte-erreur">Le formulaire contient <?= count($erreurs) ?> erreur(s). Veuillez corriger les champs signalés en rouge.</div>
        <?php endif; ?>

        <fieldset class="fieldset">
          <legend>1. Identité</legend>
          <div class="grille">
            <?php foreach ($identite as $nom => $c) echo rendre_champ($nom, $c, $valeurs, $erreurs); ?>
          </div>
        </fieldset>

        <fieldset class="fieldset">
          <legend>2. Coordonnées</legend>
          <div class="grille">
            <?php foreach ($contact as $nom => $c) echo rendre_champ($nom, $c, $valeurs, $erreurs); ?>
          </div>
        </fieldset>

        <fieldset class="fieldset">
          <legend>3. Profil <?= e(mb_strtolower($cat['label'])) ?></legend>
          <div class="grille">
            <?php foreach ($profil as $nom => $c) echo rendre_champ($nom, $c, $valeurs, $erreurs); ?>
          </div>
        </fieldset>

        <fieldset class="fieldset">
          <legend>4. Photo pour le badge</legend>
          <div class="champ<?= isset($erreurs['photo']) ? ' erreur' : '' ?>">
            <div class="photo-zone">
              <div class="photo-apercu" id="photo-apercu">Aperçu<br>photo</div>
              <div>
                <label for="f_photo">Photo d'identité<?= photo_obligatoire($type) ? ' <span class="req">*</span>' : ' (facultative)' ?></label>
                <input type="file" id="f_photo" name="photo" accept="image/jpeg,image/png,image/webp" <?= photo_obligatoire($type) ? 'required' : '' ?>>
                <div class="aide">Visage de face, fond clair. JPEG, PNG ou WEBP – <?= (int) config('photo_max_mo') ?> Mo max. La photo est recadrée automatiquement.</div>
                <?php if (isset($erreurs['photo'])): ?><div class="msg-erreur"><?= e($erreurs['photo']) ?></div><?php endif; ?>
              </div>
            </div>
          </div>
        </fieldset>

        <div class="champ<?= isset($erreurs['consentement']) ? ' erreur' : '' ?>" style="margin-bottom:22px">
          <label class="consentement">
            <input type="checkbox" name="consentement" value="1" required <?= !empty($_POST['consentement']) ? 'checked' : '' ?>>
            <span>Je certifie l'exactitude des informations fournies et j'accepte qu'elles soient utilisées par le <?= e(config('ministere.nom')) ?> pour l'organisation du <?= e(config('event.nom') . ' ' . config('event.annee')) ?>. Mon badge est strictement personnel.</span>
          </label>
          <?php if (isset($erreurs['consentement'])): ?><div class="msg-erreur"><?= e($erreurs['consentement']) ?></div><?php endif; ?>
        </div>

        <button type="submit" class="btn btn-bleu" <?= inscriptions_ouvertes() ? '' : 'disabled' ?> id="btn-envoyer">Valider et générer mon badge</button>
      </form>

      <aside class="aside-carte" style="--c: <?= e($cat['couleur']) ?>">
        <h3>À savoir</h3>
        <ul>
          <li>Les champs marqués <span class="req" style="color:var(--rouge)">*</span> sont obligatoires.</li>
          <li>Votre badge électronique est généré dès la validation du formulaire<?= in_array($type, config('validation_requise') ?: [], true) ? ' et activé après vérification par le comité d\'organisation' : '' ?>.</li>
          <li>Conservez le lien de votre badge : il est aussi retrouvable avec votre e-mail et votre téléphone.</li>
          <li>Le badge (QR code) est exigé à chaque entrée sur le site du Forum.</li>
          <li>Une seule inscription par personne et par catégorie.</li>
        </ul>
        <hr style="border:0;border-top:1px solid var(--bordure);margin:18px 0">
        <p style="margin:0;font-size:14px"><strong><?= e(config('event.dates')) ?></strong><br><?= e(config('event.lieu')) ?></p>
        <p style="margin:12px 0 0;font-size:13px;color:var(--gris)">Assistance : <?= e(config('ministere.telephone')) ?><br><?= e(config('ministere.email')) ?></p>
      </aside>
    </div>
  </div>
</section>

<script>
(function () {
  var input = document.getElementById('f_photo');
  var apercu = document.getElementById('photo-apercu');
  input.addEventListener('change', function () {
    var f = input.files && input.files[0];
    if (!f) return;
    var url = URL.createObjectURL(f);
    apercu.style.backgroundImage = 'url(' + url + ')';
    apercu.textContent = '';
  });
  document.getElementById('form-inscription').addEventListener('submit', function (ev) {
    var form = ev.target;
    // Validation navigateur, puis contrôle des groupes de cases à cocher obligatoires
    if (!form.checkValidity()) { form.reportValidity(); ev.preventDefault(); return; }
    var groupes = form.querySelectorAll('.champ .choix');
    for (var i = 0; i < groupes.length; i++) {
      var champ = groupes[i].closest('.champ');
      if (champ.querySelector('.req') && !groupes[i].querySelector('input:checked')) {
        ev.preventDefault();
        champ.classList.add('erreur');
        champ.scrollIntoView({ behavior: 'smooth', block: 'center' });
        alert('Veuillez cocher au moins une option : ' + champ.querySelector('.label').textContent.replace('*', '').trim());
        return;
      }
    }
    var b = document.getElementById('btn-envoyer');
    b.disabled = true; b.textContent = 'Génération du badge…';
  });
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
