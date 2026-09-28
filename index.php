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
  <div class="container hero__grille">
    <div>
      <span class="hero__kicker"><?= e($ev['edition']) ?> · Inscriptions <?= inscriptions_ouvertes() ? 'ouvertes' : 'clôturées' ?></span>
      <h1><?= e($ev['nom']) ?> <span><?= e($ev['annee']) ?></span></h1>
      <p class="hero__theme"><b>Thème :</b> « <?= e($ev['theme']) ?> »</p>
      <div class="hero__infos">
        <div>📅 <b>Dates :</b> <?= e($ev['dates']) ?></div>
        <div>📍 <b>Lieu :</b> <?= e($ev['lieu']) ?></div>
        <?php if ($ev['date_limite_inscription']): ?>
        <div>⏳ <b>Clôture des inscriptions :</b> <?= e(date('d/m/Y', strtotime($ev['date_limite_inscription']))) ?></div>
        <?php endif; ?>
      </div>
      <a class="btn btn-or" href="inscription.php?type=jeune">Je m'inscris comme jeune</a>
      <a class="btn btn-ligne" href="#inscriptions">Partenaires, sponsors, presse…</a>
      <p class="hero__patronage"><?= e($ev['patronage']) ?></p>
    </div>
    <div class="hero__logo">
      <img src="<?= e($ev['logo']) ?>" alt="Logo de la <?= e($ev['edition'] . ' ' . $ev['nom']) ?>" width="360" height="360">
    </div>
  </div>
</section>

<section class="section appel">
  <div class="container appel__grille">
    <div>
      <h2>Tous à <?= e($ev['ville']) ?> !</h2>
      <p class="appel__intro">Du 12 au 14 octobre 2026, tous les regards seront tournés vers <strong>Pala</strong>, chef-lieu de la province du <strong>Mayo-Kebbi Ouest</strong>, à l'occasion de la <strong>27ᵉ Fête Nationale de la Jeunesse</strong>.</p>
      <p>Trois jours pour célébrer une jeunesse qui ose, qui crée, qui entreprend, qui s'exprime et qui agit. Trois jours pour <strong>révéler les talents, faire entendre les voix et renforcer les liens</strong> qui unissent notre jeunesse, autour de la paix, de la cohésion sociale, du vivre-ensemble et du développement de notre pays.</p>
      <p>De N'Djaména aux provinces, filles et garçons, élèves, étudiants, entrepreneurs, agriculteurs, artistes, sportifs, innovateurs, artisans et acteurs associatifs : <strong>Pala vous appelle !</strong></p>
      <p class="appel__slogan"><?= e($ev['slogan']) ?></p>
    </div>
    <div class="compte" id="compte" data-date="2026-10-12T08:00:00+01:00" aria-live="polite">
      <div class="compte__titre">Jour J dans</div>
      <div class="compte__cases">
        <div><b data-u="j">–</b><span>jours</span></div>
        <div><b data-u="h">–</b><span>heures</span></div>
        <div><b data-u="m">–</b><span>minutes</span></div>
      </div>
      <div class="compte__lieu">📍 <?= e($ev['lieu']) ?></div>
    </div>
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
      <p>Une procédure simple, entièrement en ligne, pour un accès rapide et sécurisé à la FNJ.</p>
    </div>
    <div class="etapes">
      <div class="etape"><h3>Remplissez le formulaire</h3><p>Sélectionnez votre catégorie et renseignez vos informations avec une photo d'identité récente.</p></div>
      <div class="etape"><h3>Recevez votre badge</h3><p>Votre badge électronique nominatif et son QR code sécurisé sont générés automatiquement.</p></div>
      <div class="etape"><h3>Téléchargez ou imprimez</h3><p>Enregistrez le badge sur votre téléphone (PNG) ou imprimez-le au format carte.</p></div>
      <div class="etape"><h3>Présentez-le à l'entrée</h3><p>Les agents scannent votre QR code aux points d'accès de la FNJ pour valider votre entrée.</p></div>
    </div>
  </div>
</section>

<section class="section section--gris">
  <div class="container">
    <div class="section-titre">
      <h2>Thématiques de la FNJ</h2>
      <p>Talents, voix et avenir : trois jours de célébration, d'expression et d'échanges entre la jeunesse, le Gouvernement et les partenaires.</p>
    </div>
    <div class="chiffres" style="margin-bottom:30px">
      <div class="chiffre"><b><?= number_format($total, 0, ',', ' ') ?></b><span>Inscrits</span></div>
      <div class="chiffre"><b><?= $provinces ?: 23 ?></b><span>Provinces <?= $provinces ? 'représentées' : 'attendues' ?></span></div>
      <div class="chiffre"><b>3</b><span>Jours de fête</span></div>
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

<script>
(function () {
  var el = document.getElementById('compte'), cible = new Date(el.dataset.date).getTime();
  function maj() {
    var d = Math.max(0, cible - Date.now());
    el.querySelector('[data-u=j]').textContent = Math.floor(d / 864e5);
    el.querySelector('[data-u=h]').textContent = Math.floor(d / 36e5) % 24;
    el.querySelector('[data-u=m]').textContent = Math.floor(d / 6e4) % 60;
    if (d === 0) el.querySelector('.compte__titre').textContent = 'C\'est parti !';
  }
  maj(); setInterval(maj, 30000);
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
