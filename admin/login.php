<?php
define('FNJ_ROOT', '../');
require __DIR__ . '/../inc/app.php';

$erreur = null;
$retour = (string) ($_GET['retour'] ?? $_POST['retour'] ?? '');
// N'accepter qu'un chemin local comme page de retour
if (!preg_match('#^/[^/\\\\]#', $retour)) {
    $retour = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['essais_login'] = ($_SESSION['essais_login'] ?? 0) + 1;
    if (!csrf_check()) {
        $erreur = 'Session expirée, veuillez réessayer.';
    } elseif ($_SESSION['essais_login'] > 10) {
        $erreur = 'Trop de tentatives. Fermez le navigateur et réessayez plus tard.';
    } elseif (connexion(trim((string) ($_POST['login'] ?? '')), (string) ($_POST['mdp'] ?? ''))) {
        $_SESSION['essais_login'] = 0;
        if (utilisateur()['role'] !== 'admin' && $retour === 'index.php') {
            $retour = 'scanner.php';
        }
        header('Location: ' . $retour);
        exit;
    } else {
        $erreur = 'Identifiant ou mot de passe incorrect.';
    }
}

$titre = 'Espace organisateurs';
$page = 'admin';
require __DIR__ . '/../inc/header.php';
?>
<section class="page-titre">
  <div class="container">
    <h1>Espace organisateurs</h1>
    <p>Accès réservé au comité d'organisation et aux agents de contrôle.</p>
  </div>
</section>
<section class="section section--gris" style="padding-top:36px">
  <div class="container" style="max-width:460px">
    <form class="form-carte" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="retour" value="<?= e($retour) ?>">
      <?php if ($erreur): ?><div class="alerte alerte-erreur"><?= e($erreur) ?></div><?php endif; ?>
      <div class="champ" style="margin-bottom:14px"><label for="login">Identifiant</label><input type="text" id="login" name="login" required autocomplete="username"></div>
      <div class="champ" style="margin-bottom:20px"><label for="mdp">Mot de passe</label><input type="password" id="mdp" name="mdp" required autocomplete="current-password"></div>
      <button class="btn btn-bleu" type="submit" style="width:100%">Se connecter</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/../inc/footer.php'; ?>
