<?php
/** @var string $titre  Titre de la page */
/** @var string $page   Identifiant de la page active pour le menu */
$m = config('ministere');
$ev = config('event');
$titre = $titre ?? $ev['nom'] . ' ' . $ev['annee'];
$page = $page ?? '';
$r = root();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre) ?> – <?= e($m['nom']) ?></title>
<meta name="description" content="Inscriptions et badges officiels de la <?= e($ev['nom'] . ' ' . $ev['annee']) ?> – <?= e($m['nom']) ?>, <?= e($m['pays']) ?>.">
<link rel="icon" href="<?= e($r . $ev['logo']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e($r) ?>assets/css/style.css?v=4">
</head>
<body>

<?php if (db_temporaire()): ?>
<div style="background:#C60C30;color:#fff;text-align:center;padding:10px 16px;font-size:14px;font-weight:600">
  ⚠ Mode test : aucune base de données n'est configurée (variable DATABASE_URL). Les inscriptions seront perdues au prochain redémarrage du serveur.
</div>
<?php endif; ?>
<div class="topbar">
  <div class="container topbar__inner">
    <div class="topbar__contact">
      <a href="tel:<?= e(preg_replace('/\s+/', '', $m['telephone'])) ?>"><span aria-hidden="true">☎</span> <?= e($m['telephone']) ?></a>
      <a href="mailto:<?= e($m['email']) ?>"><span aria-hidden="true">✉</span> <?= e($m['email']) ?></a>
      <span class="topbar__adresse"><span aria-hidden="true">⌖</span> <?= e($m['adresse']) ?></span>
    </div>
    <div class="topbar__social">
      <a href="<?= e($m['facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M13.5 21v-7.5H16l.4-3h-2.9V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.3H8v3h2.5V21h3z"/></svg>
      </a>
      <a href="<?= e($m['site']) ?>" target="_blank" rel="noopener">jeunesse.gouv.td</a>
    </div>
  </div>
</div>
<div class="flagline" aria-hidden="true"><span></span><span></span><span></span></div>

<header class="site-header">
  <div class="container site-header__inner">
    <a class="brand" href="<?= e($r) ?>index.php">
      <img class="brand__logo" src="<?= e($r . $ev['logo']) ?>" alt="Logo de la <?= e($ev['edition'] . ' ' . $ev['nom']) ?>">
      <span class="brand__text">
        <span class="brand__pays"><?= e($m['pays']) ?></span>
        <span class="brand__devise"><?= e($m['devise']) ?></span>
        <span class="brand__nom"><?= e($m['nom']) ?></span>
      </span>
    </a>
    <div class="site-header__event">
      <span class="site-header__sigle"><?= e($ev['sigle']) ?> · <?= e($ev['ville']) ?></span>
      <span><?= e($ev['dates']) ?></span>
    </div>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu" onclick="var n=document.getElementById('menu');var o=n.classList.toggle('open');this.setAttribute('aria-expanded',o)">
      <span></span><span></span><span></span><span class="sr-only">Menu</span>
    </button>
  </div>
  <nav class="main-nav" id="menu" aria-label="Menu principal">
    <div class="container">
      <ul>
        <li><a href="<?= e($m['site']) ?>">Accueil Ministère</a></li>
        <li class="<?= $page === 'forum' ? 'active' : '' ?>"><a href="<?= e($r) ?>index.php">La FNJ 2026</a></li>
        <li class="<?= $page === 'inscription' ? 'active' : '' ?>"><a href="<?= e($r) ?>index.php#inscriptions">S'inscrire</a></li>
        <li class="<?= $page === 'retrouver' ? 'active' : '' ?>"><a href="<?= e($r) ?>retrouver.php">Retrouver mon badge</a></li>
        <li class="<?= $page === 'verifier' ? 'active' : '' ?>"><a href="<?= e($r) ?>verifier.php">Vérifier un badge</a></li>
        <li><a href="<?= e($m['site']) ?>/?page_id=923">Contact</a></li>
        <?php if (utilisateur()): ?>
        <li class="<?= $page === 'admin' ? 'active' : '' ?> nav-admin"><a href="<?= e($r) ?>admin/index.php">Administration</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
</header>

<main id="contenu">
