<?php
require __DIR__ . '/inc/app.php';

$ev = config('event');
$titre = $ev['nom'] . ' ' . $ev['annee'];
$page = 'forum';

$total = (int) db()->query("SELECT COUNT(*) FROM inscriptions WHERE statut != 'revoque'")->fetchColumn();
$provinces = (int) db()->query("SELECT COUNT(DISTINCT province) FROM inscriptions WHERE statut != 'revoque' AND province != ''")->fetchColumn();

require __DIR__ . '/inc/header.php';
?>

<section class="hero">
  <div class="container">
    <span class="hero__kicker">Inscriptions officielles <?= inscriptions_ouvertes() ? 'ouvertes' : 'clôturées' ?></span>
    <h1><?= e($ev['nom']) ?> <span><?= e($ev['annee']) ?></span></h1>
    <p class="hero__theme">« <?= e($ev['theme']) ?> »</p>
    <div class="hero__infos">
      <div>📅 <b>Dates :</b> <?= e($ev['dates']) ?></div>
      <div>📍 <b>Lieu :</b> <?= e($ev['lieu']) ?></div>
      <?php if ($ev['date_limite_inscription']): ?>
      <div>⏳ <b>Clôture :</b> <?= e(date('d/m/Y', strtotime($ev['date_limite_inscription']))) ?></div>
      <?php endif; ?>
    </div>
    <a class="btn btn-or" href="inscription.php?type=jeune">Je m'inscris comme jeune</a>
    <a class="btn btn-ligne" href="#inscriptions">Partenaires, sponsors, presse…</a>
  </div>
</section>

<section class="section section--gris" id="inscriptions">
  <div class="container">
    <div class="section-titre">
      <h2>Formulaires d'inscription</h2>
      <p>Choisissez votre catégorie de participation. Après validation du formulaire, votre <strong>badge électronique d'entrée</strong> avec QR code est généré immédiatement.</p>
    </div>
    <?php if (!inscriptions_ouvertes()): ?>
      <div class="alerte alerte-attention">Les inscriptions en ligne sont clôturées. Vous pouvez toujours <a href="retrouver.php">retrouver votre badge</a>.</div>
    <?php endif; ?>
    <div class="cartes">
      <?php foreach (categories() as $key => $cat): ?>
      <div class="carte" style="--c: <?= e($cat['couleur']) ?>">
        <div class="carte__icone" aria-hidden="true"><?= $cat['icone'] ?></div>
        <h3><?= e($cat['label']) ?></h3>
        <p><?= e($cat['resume']) ?></p>
        <a class="btn btn-sm" href="inscription.php?type=<?= e($key) ?>">S'inscrire</a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-titre">
      <h2>Comment ça marche ?</h2>
      <p>Une procédure simple, entièrement en ligne, pour un accès rapide et sécurisé au Forum.</p>
    </div>
    <div class="etapes">
      <div class="etape"><h3>Remplissez le formulaire</h3><p>Sélectionnez votre catégorie et renseignez vos informations avec une photo d'identité récente.</p></div>
      <div class="etape"><h3>Recevez votre badge</h3><p>Votre badge électronique nominatif et son QR code sécurisé sont générés automatiquement.</p></div>
      <div class="etape"><h3>Téléchargez ou imprimez</h3><p>Enregistrez le badge sur votre téléphone (PNG) ou imprimez-le au format carte.</p></div>
      <div class="etape"><h3>Présentez-le à l'entrée</h3><p>Les agents scannent votre QR code aux points d'accès du Forum pour valider votre entrée.</p></div>
    </div>
  </div>
</section>

<section class="section section--gris">
  <div class="container">
    <div class="section-titre">
      <h2>Thématiques du Forum</h2>
      <p>Panels, ateliers, village de l'entrepreneuriat et espaces d'échanges entre la jeunesse, le Gouvernement et les partenaires.</p>
    </div>
    <div class="chiffres" style="margin-bottom:30px">
      <div class="chiffre"><b><?= number_format($total, 0, ',', ' ') ?></b><span>Inscrits</span></div>
      <div class="chiffre"><b><?= $provinces ?: 23 ?></b><span>Provinces <?= $provinces ? 'représentées' : 'attendues' ?></span></div>
      <div class="chiffre"><b>3</b><span>Jours d'échanges</span></div>
      <div class="chiffre"><b><?= count(thematiques()) ?></b><span>Thématiques</span></div>
    </div>
    <div class="cartes">
      <?php foreach (thematiques() as $i => $t): ?>
      <div class="carte" style="--c: <?= ['#002664', '#C60C30', '#B8860B'][$i % 3] ?>; padding:18px 20px">
        <h3 style="margin:0;font-size:16px"><?= e($t) ?></h3>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/inc/footer.php'; ?>
