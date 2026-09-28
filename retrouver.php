<?php
require __DIR__ . '/inc/app.php';

$erreur = null;
$resultats = [];
$email = '';
$tel = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $tel = trim((string) ($_POST['telephone'] ?? ''));
    $_SESSION['essais_retrouver'] = ($_SESSION['essais_retrouver'] ?? 0) + 1;

    if (!csrf_check()) {
        $erreur = 'Session expirée, veuillez réessayer.';
    } elseif ($_SESSION['essais_retrouver'] > 15) {
        $erreur = 'Trop de tentatives. Veuillez réessayer plus tard ou contacter l\'organisation.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen(preg_replace('/\D/', '', $tel)) < 8) {
        $erreur = 'Veuillez saisir une adresse e-mail et un numéro de téléphone valides.';
    } else {
        // On compare les 8 derniers chiffres du téléphone (avec ou sans indicatif +235)
        $fin = substr(preg_replace('/\D/', '', $tel), -8);
        $st = db()->prepare('SELECT * FROM inscriptions WHERE email = ? ORDER BY cree_le DESC');
        $st->execute([$email]);
        foreach ($st->fetchAll() as $row) {
            if (substr(preg_replace('/\D/', '', $row['telephone']), -8) === $fin) {
                $resultats[] = $row;
            }
        }
        if (!$resultats) {
            $erreur = 'Aucune inscription ne correspond à ces informations.';
        }
    }
}

$titre = 'Retrouver mon badge';
$page = 'retrouver';
require __DIR__ . '/inc/header.php';
?>

<section class="page-titre">
  <div class="container">
    <div class="ariane"><a href="index.php">FNJ 2026</a> › Retrouver mon badge</div>
    <h1>Retrouver mon badge</h1>
    <p>Saisissez l'adresse e-mail et le téléphone utilisés lors de votre inscription.</p>
  </div>
</section>

<section class="section section--gris" style="padding-top:36px">
  <div class="container" style="max-width:720px">
    <form class="form-carte" method="post">
      <?= csrf_field() ?>
      <?php if ($erreur): ?><div class="alerte alerte-erreur"><?= e($erreur) ?></div><?php endif; ?>
      <div class="grille">
        <div class="champ"><label for="email">Adresse e-mail <span class="req">*</span></label><input type="email" id="email" name="email" required value="<?= e($email) ?>"></div>
        <div class="champ"><label for="tel">Téléphone <span class="req">*</span></label><input type="tel" id="tel" name="telephone" required value="<?= e($tel) ?>"></div>
      </div>
      <p><button class="btn btn-bleu" type="submit">Rechercher</button></p>

      <?php if ($resultats): ?>
        <h3>Inscription(s) trouvée(s)</h3>
        <table class="infos-table">
          <?php foreach ($resultats as $r): $c = categorie($r['categorie']); ?>
          <tr>
            <td><strong><?= e($r['prenom'] . ' ' . $r['nom']) ?></strong><br><span class="pastille" style="--c: <?= e($c['couleur']) ?>"><?= e($c['label']) ?></span> <code><?= e($r['code']) ?></code></td>
            <td style="text-align:right;vertical-align:middle"><a class="btn btn-sm btn-bleu" href="<?= e(badge_url($r)) ?>">Voir mon badge</a></td>
          </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </form>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
