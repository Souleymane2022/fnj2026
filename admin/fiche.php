<?php
define('FNJ_ROOT', '../');
require __DIR__ . '/../inc/app.php';
require __DIR__ . '/../inc/champs.php';
exiger_connexion('admin');

$st = db()->prepare('SELECT * FROM inscriptions WHERE id = ?');
$st->execute([(int) ($_GET['id'] ?? 0)]);
$insc = $st->fetch();
if (!$insc) {
    header('Location: index.php');
    exit;
}
$cat = categorie($insc['categorie']);
$details = json_decode((string) $insc['details'], true) ?: [];
$profil = champs_profil($insc['categorie']);
$st = db()->prepare('SELECT * FROM entrees WHERE inscription_id = ? ORDER BY id DESC');
$st->execute([$insc['id']]);
$entrees = $st->fetchAll();

$titre = 'Fiche ' . $insc['code'];
$page = 'admin';
require __DIR__ . '/../inc/header.php';
?>
<section class="page-titre" style="background: <?= e($cat['couleur']) ?>">
  <div class="container">
    <div class="ariane"><a href="index.php">Administration</a> › Fiche</div>
    <h1><?= e($insc['prenom'] . ' ' . $insc['nom']) ?></h1>
    <p><?= e($cat['label']) ?> · <code style="color:#fff"><?= e($insc['code']) ?></code></p>
  </div>
</section>
<section class="section section--gris" style="padding-top:30px">
  <div class="container">
    <div class="badge-page" style="grid-template-columns: 220px 1fr">
      <div>
        <?php if ($insc['photo']): ?><img src="<?= e(photo_url($insc)) ?>" alt="" style="width:100%;border-radius:8px;border:4px solid <?= e($cat['couleur']) ?>"><?php endif; ?>
        <p><a class="btn btn-bleu" style="width:100%" href="<?= e(badge_url($insc)) ?>" target="_blank">Voir / imprimer le badge</a></p>
      </div>
      <div class="form-carte">
        <table class="infos-table">
          <tr><th>Statut</th><td><span class="pastille statut-<?= e($insc['statut']) ?>"><?= e($insc['statut']) ?></span></td></tr>
          <tr><th>Civilité / sexe</th><td><?= e($insc['civilite'] . ' · ' . $insc['sexe']) ?></td></tr>
          <tr><th>Date de naissance</th><td><?= $insc['date_naissance'] ? e(date('d/m/Y', strtotime($insc['date_naissance']))) : '—' ?></td></tr>
          <tr><th>Nationalité</th><td><?= e($insc['nationalite']) ?></td></tr>
          <tr><th>Province / ville</th><td><?= e(trim($insc['province'] . ' · ' . $insc['ville'], ' ·')) ?></td></tr>
          <tr><th>Téléphone</th><td><?= e($insc['telephone']) ?></td></tr>
          <tr><th>E-mail</th><td><?= e($insc['email']) ?></td></tr>
          <?php foreach ($profil as $nom => $c): ?>
            <?php $v = !empty($c['colonne']) ? $insc[$nom] : ($details[$nom] ?? ''); if ($v === '' || $v === []) continue; ?>
            <tr><th><?= e($c['label']) ?></th><td><?= nl2br(e(is_array($v) ? implode(', ', $v) : $v)) ?></td></tr>
          <?php endforeach; ?>
          <tr><th>Inscrit le</th><td><?= e(date('d/m/Y H:i', strtotime($insc['cree_le']))) ?></td></tr>
        </table>

        <h3>Historique des entrées (<?= count($entrees) ?>)</h3>
        <?php if (!$entrees): ?><p style="color:var(--gris)">Aucune entrée enregistrée.</p><?php else: ?>
        <ul class="historique">
          <?php foreach ($entrees as $en): ?>
            <li><span><?= e(date('d/m/Y', strtotime($en['jour']))) ?> à <?= e(substr($en['heure'], 0, 5)) ?></span><span><?= e($en['point_acces']) ?> · <?= e($en['agent']) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <form method="post" action="index.php" style="margin-top:24px" onsubmit="return confirm('Supprimer définitivement cette inscription ?')">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $insc['id'] ?>">
          <button class="btn btn-sm btn-rouge" name="action" value="supprimer">Supprimer l'inscription</button>
        </form>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../inc/footer.php'; ?>
