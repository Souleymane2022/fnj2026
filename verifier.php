<?php
/**
 * Page ouverte en scannant le QR code d'un badge avec n'importe quel téléphone.
 * Un agent connecté peut en plus enregistrer l'entrée.
 */
require __DIR__ . '/inc/app.php';
require __DIR__ . '/inc/controle.php';

$code = strtoupper(trim((string) ($_GET['c'] ?? '')));
$sig = (string) ($_GET['s'] ?? '');
$u = utilisateur();
$resultat = null;

if ($code !== '') {
    $enregistrer = $u && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check();
    $resultat = controler_badge($code, $sig, true, $enregistrer ? $u['login'] : null, $enregistrer ? 'Scan mobile' : '');
}

$titre = 'Vérification de badge';
$page = 'verifier';
require __DIR__ . '/inc/header.php';

$classes = ['ok' => 'verif--ok', 'deja' => 'verif--ok', 'attente' => 'verif--attente', 'revoque' => 'verif--ko', 'invalide' => 'verif--ko'];
$titres = ['ok' => '✔ BADGE VALIDE', 'deja' => '✔ BADGE VALIDE', 'attente' => '⏳ EN ATTENTE', 'revoque' => '✖ BADGE ANNULÉ', 'invalide' => '✖ BADGE INVALIDE'];
?>

<section class="page-titre">
  <div class="container">
    <div class="ariane"><a href="index.php">Forum 2026</a> › Vérification</div>
    <h1>Vérification d'un badge</h1>
    <p>Contrôle d'authenticité des badges du <?= e(config('event.nom') . ' ' . config('event.annee')) ?>.</p>
  </div>
</section>

<section class="section section--gris" style="padding-top:36px">
  <div class="container">
    <?php if (!$resultat): ?>
      <div class="form-carte" style="max-width:640px;margin:0 auto;text-align:center">
        <p style="font-size:44px;margin:0">📱</p>
        <h2>Scannez le QR code du badge</h2>
        <p>Utilisez l'appareil photo de votre téléphone pour scanner le QR code imprimé sur le badge : cette page affichera automatiquement son authenticité.</p>
        <p>Agents de contrôle : utilisez le <a href="admin/scanner.php">scanner d'entrée</a> pour enregistrer les passages.</p>
      </div>
    <?php else: $p = $resultat['participant'] ?? null; ?>
      <div class="verif <?= $classes[$resultat['etat']] ?>" style="--c: <?= e($p['couleur'] ?? '#002664') ?>">
        <div class="verif__entete"><?= $titres[$resultat['etat']] ?><small><?= e($resultat['message']) ?></small></div>
        <?php if ($p): ?>
        <div class="verif__corps">
          <?php if ($p['photo']): ?><img class="verif__photo" src="<?= e($p['photo']) ?>" alt="Photo"><?php endif; ?>
          <div class="verif__nom"><?= e($p['prenom'] . ' ' . $p['nom']) ?></div>
          <p><span class="pastille" style="--c: <?= e($p['couleur']) ?>"><?= e($p['categorie']) ?></span></p>
          <?php if ($p['organisation'] || $p['fonction']): ?><p style="color:var(--gris)"><?= e(implode(' – ', array_filter([$p['fonction'], $p['organisation']]))) ?></p><?php endif; ?>
          <p><code><?= e($p['code']) ?></code></p>
          <?php if ($u && in_array($resultat['etat'], ['ok', 'deja'], true) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <form method="post">
              <?= csrf_field() ?>
              <button class="btn btn-vert" type="submit">Enregistrer l'entrée</button>
            </form>
          <?php elseif (!$u): ?>
            <p style="font-size:13px;color:var(--gris)">Vérifiez que la photo correspond à la personne qui présente le badge.</p>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
