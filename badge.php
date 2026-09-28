<?php
require __DIR__ . '/inc/app.php';

$code = (string) ($_GET['c'] ?? '');
$k = (string) ($_GET['k'] ?? '');
$admin = utilisateur() && utilisateur()['role'] === 'admin';
$insc = (signature_ok($code, $k, 'badge') || ($admin && $code !== '')) ? trouver_inscription($code) : null;

if (!$insc) {
    http_response_code(404);
    $titre = 'Badge introuvable';
    require __DIR__ . '/inc/header.php';
    echo '<section class="section"><div class="container"><div class="alerte alerte-erreur">Ce lien de badge est invalide ou a expiré. <a href="retrouver.php">Retrouver mon badge</a>.</div></div></section>';
    require __DIR__ . '/inc/footer.php';
    exit;
}

$cat = categorie($insc['categorie']);
$nouveau = ($_SESSION['nouveau_badge'] ?? null) === $insc['code'];
unset($_SESSION['nouveau_badge']);
$lienBadge = badge_url($insc, true);

$donnees = [
    'prenom'       => $insc['prenom'],
    'nom'          => $insc['nom'],
    'organisation' => $insc['organisation'],
    'fonction'     => $insc['fonction'],
    'categorie'    => $cat['badge'],
    'couleur'      => $cat['couleur'],
    'code'         => $insc['code'],
    'qr'           => verification_url($insc),
    'photo'        => photo_url($insc),
    'logo'         => config('event.logo'),
    'evenement'    => config('event.nom'),
    'annee'        => config('event.annee'),
    'edition'      => config('event.edition') . ' · ' . mb_strtoupper(config('event.ville')) . ' ' . config('event.annee'),
    'dates'        => config('event.dates'),
    'lieu'         => config('event.lieu'),
    'pays'         => config('ministere.pays'),
    'ministere'    => config('ministere.nom'),
    'statut'       => $insc['statut'],
];

$statuts = ['valide' => 'Badge actif', 'en_attente' => 'En attente de validation', 'revoque' => 'Badge annulé'];
$titre = 'Mon badge – ' . $insc['code'];
require __DIR__ . '/inc/header.php';
?>

<section class="page-titre" style="background: <?= e($cat['couleur']) ?>">
  <div class="container">
    <div class="ariane"><a href="index.php">FNJ 2026</a> › Badge électronique</div>
    <h1>Badge électronique d'entrée</h1>
    <p><?= e($insc['prenom'] . ' ' . $insc['nom']) ?> – <?= e($cat['label']) ?></p>
  </div>
</section>

<section class="section section--gris" style="padding-top:36px">
  <div class="container">
    <?php if ($nouveau && $insc['statut'] === 'valide'): ?>
      <div class="alerte alerte-succes"><strong>Félicitations, votre inscription est enregistrée !</strong> Votre badge est prêt : téléchargez-le ou imprimez-le et présentez-le à l'entrée de la FNJ.</div>
    <?php elseif ($nouveau): ?>
      <div class="alerte alerte-info"><strong>Votre inscription est enregistrée.</strong> Votre badge sera activé après vérification par le comité d'organisation. Conservez ce lien pour le consulter.</div>
    <?php endif; ?>
    <?php if ($insc['statut'] === 'en_attente' && !$nouveau): ?>
      <div class="alerte alerte-attention">Ce badge est en attente de validation par le comité d'organisation.</div>
    <?php elseif ($insc['statut'] === 'revoque'): ?>
      <div class="alerte alerte-erreur">Ce badge a été annulé et ne permet plus l'accès à la FNJ.</div>
    <?php endif; ?>

    <div class="badge-page">
      <div class="badge-canvas-wrap badge-print">
        <canvas id="badge" width="1016" height="1606" aria-label="Badge de <?= e($insc['prenom'] . ' ' . $insc['nom']) ?>"></canvas>
      </div>

      <div>
        <div class="form-carte">
          <h2 style="margin-top:0">Votre badge</h2>
          <p>Enregistrez votre badge sur votre téléphone ou imprimez-le (format carte 86 × 136 mm). Le QR code sera scanné à l'entrée par les agents d'accueil.</p>
          <div class="badge-actions">
            <button class="btn btn-bleu" id="btn-png" type="button">⬇ Télécharger (PNG)</button>
            <button class="btn btn-rouge" id="btn-print" type="button">🖨 Imprimer / PDF</button>
            <button class="btn btn-ligne-bleu" id="btn-partager" type="button">🔗 Copier le lien</button>
          </div>

          <table class="infos-table">
            <tr><th>N° de badge</th><td><strong style="font-family:monospace;font-size:16px"><?= e($insc['code']) ?></strong></td></tr>
            <tr><th>Statut</th><td><span class="pastille statut-<?= e($insc['statut']) ?>"><?= e($statuts[$insc['statut']] ?? $insc['statut']) ?></span></td></tr>
            <tr><th>Catégorie</th><td><span class="pastille" style="--c: <?= e($cat['couleur']) ?>"><?= e($cat['label']) ?></span></td></tr>
            <tr><th>Nom complet</th><td><?= e(trim($insc['civilite'] . ' ' . $insc['prenom'] . ' ' . $insc['nom'])) ?></td></tr>
            <?php if ($insc['organisation']): ?><tr><th>Organisation</th><td><?= e($insc['organisation']) ?></td></tr><?php endif; ?>
            <?php if ($insc['fonction']): ?><tr><th>Fonction</th><td><?= e($insc['fonction']) ?></td></tr><?php endif; ?>
            <?php if ($insc['province']): ?><tr><th>Province</th><td><?= e($insc['province']) ?></td></tr><?php endif; ?>
            <tr><th>Inscrit le</th><td><?= e(date('d/m/Y à H:i', strtotime($insc['cree_le']))) ?></td></tr>
            <tr><th>Événement</th><td><?= e(config('event.dates')) ?><br><?= e(config('event.lieu')) ?></td></tr>
          </table>
          <p class="aide" style="font-size:13px;color:var(--gris);margin-bottom:0">Ce lien est personnel : ne le partagez pas. En cas de perte, utilisez « <a href="retrouver.php">Retrouver mon badge</a> » avec votre e-mail et votre téléphone.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="assets/js/vendor/qrcode.js"></script>
<script src="assets/js/badge.js?v=4"></script>
<script>
(function () {
  var donnees = <?= json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  var lien = <?= json_encode($lienBadge, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;
  var canvas = document.getElementById('badge');
  var pret = FNJBadge.dessiner(canvas, donnees);
  var nomFichier = 'Badge-' + donnees.code + '.png';

  document.getElementById('btn-png').addEventListener('click', function () {
    pret.then(function () {
      canvas.toBlob(function (blob) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = nomFichier;
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1000);
      }, 'image/png');
    });
  });

  document.getElementById('btn-print').addEventListener('click', function () {
    pret.then(function () { window.print(); });
  });

  document.getElementById('btn-partager').addEventListener('click', function () {
    var b = this;
    function ok() { b.textContent = '✔ Lien copié'; setTimeout(function () { b.textContent = '🔗 Copier le lien'; }, 2500); }
    if (navigator.share && /Android|iPhone|iPad/i.test(navigator.userAgent)) {
      navigator.share({ title: 'Mon badge ' + donnees.evenement + ' ' + donnees.annee, url: lien }).catch(function () {});
    } else if (navigator.clipboard) {
      navigator.clipboard.writeText(lien).then(ok, function () { prompt('Copiez ce lien :', lien); });
    } else {
      prompt('Copiez ce lien :', lien);
    }
  });
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
