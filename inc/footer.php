<?php
$m = config('ministere');
$ev = config('event');
$r = root();
?>
</main>

<footer class="site-footer">
  <div class="container site-footer__grid">
    <div>
      <div class="site-footer__brand">
        <img src="<?= e($r . $m['logo']) ?>" alt="" width="54" height="54">
        <div>
          <strong><?= e($m['nom']) ?></strong>
          <span><?= e($m['pays']) ?></span>
        </div>
      </div>
      <p>Le Ministère de la Jeunesse et des Sports est chargé de la conception, de la coordination et de la mise en œuvre de la politique du Gouvernement en matière de jeunesse, de sports et de promotion de l'entrepreneuriat.</p>
    </div>
    <div>
      <h3><?= e($ev['nom'] . ' ' . $ev['annee']) ?></h3>
      <ul>
        <li><?= e($ev['dates']) ?></li>
        <li><?= e($ev['lieu']) ?></li>
        <li><a href="<?= e($r) ?>index.php#inscriptions">S'inscrire au Forum</a></li>
        <li><a href="<?= e($r) ?>retrouver.php">Retrouver mon badge</a></li>
        <li><a href="<?= e($r) ?>admin/index.php">Espace organisateurs</a></li>
      </ul>
    </div>
    <div>
      <h3>Contact</h3>
      <ul>
        <li>☎ <a href="tel:<?= e(preg_replace('/\s+/', '', $m['telephone'])) ?>"><?= e($m['telephone']) ?></a></li>
        <li>✉ <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></li>
        <li>⌖ <?= e($m['adresse']) ?></li>
        <li><a href="<?= e($m['site']) ?>" target="_blank" rel="noopener">jeunesse.gouv.td</a></li>
      </ul>
    </div>
  </div>
  <div class="site-footer__bottom">
    <div class="container">© <?= date('Y') ?> <?= e($m['nom']) ?> – All rights reserved.</div>
  </div>
</footer>
</body>
</html>
