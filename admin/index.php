<?php
define('FNJ_ROOT', '../');
require __DIR__ . '/../inc/app.php';
$u = exiger_connexion('admin');

// --- Actions ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash('Session expirée, action annulée.', 'erreur');
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $statuts = ['valider' => 'valide', 'revoquer' => 'revoque', 'attente' => 'en_attente'];
        if (isset($statuts[$action])) {
            db()->prepare('UPDATE inscriptions SET statut = ? WHERE id = ?')->execute([$statuts[$action], $id]);
            flash('Statut mis à jour.');
        } elseif ($action === 'supprimer') {
            db()->prepare('DELETE FROM photos WHERE code = (SELECT code FROM inscriptions WHERE id = ?)')->execute([$id]);
            db()->prepare('DELETE FROM entrees WHERE inscription_id = ?')->execute([$id]);
            db()->prepare('DELETE FROM inscriptions WHERE id = ?')->execute([$id]);
            flash('Inscription supprimée.');
        }
    }
    header('Location: index.php?' . ($_POST['qs'] ?? ''));
    exit;
}

// --- Filtres ---------------------------------------------------------------
$f = [
    'q'         => trim((string) ($_GET['q'] ?? '')),
    'categorie' => (string) ($_GET['categorie'] ?? ''),
    'statut'    => (string) ($_GET['statut'] ?? ''),
    'province'  => (string) ($_GET['province'] ?? ''),
];
[$where, $params] = filtres_sql($f);

function filtres_sql(array $f): array
{
    $where = [];
    $params = [];
    if ($f['q'] !== '') {
        $conds = [];
        foreach (['nom', 'prenom', 'code', 'email', 'telephone', 'organisation'] as $i => $col) {
            $conds[] = "$col " . sql_like() . " :q$i";
            $params[":q$i"] = '%' . $f['q'] . '%';
        }
        $where[] = '(' . implode(' OR ', $conds) . ')';
    }
    foreach (['categorie', 'statut', 'province'] as $k) {
        if ($f[$k] !== '') {
            $where[] = "$k = :$k";
            $params[":$k"] = $f[$k];
        }
    }
    return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
}

$parPage = 50;
$pageNum = max(1, (int) ($_GET['p'] ?? 1));
$st = db()->prepare("SELECT COUNT(*) FROM inscriptions $where");
$st->execute($params);
$total = (int) $st->fetchColumn();
$pages = max(1, (int) ceil($total / $parPage));
$pageNum = min($pageNum, $pages);

$st = db()->prepare("SELECT i.*, (SELECT COUNT(*) FROM entrees e WHERE e.inscription_id = i.id) AS nb_entrees
                     FROM inscriptions i $where ORDER BY i.id DESC LIMIT $parPage OFFSET " . (($pageNum - 1) * $parPage));
$st->execute($params);
$lignes = $st->fetchAll();

// --- Statistiques ------------------------------------------------------------
$parCat = db()->query("SELECT categorie, COUNT(*) n FROM inscriptions WHERE statut != 'revoque' GROUP BY categorie")->fetchAll(PDO::FETCH_KEY_PAIR);
$enAttente = (int) db()->query("SELECT COUNT(*) FROM inscriptions WHERE statut = 'en_attente'")->fetchColumn();
$st = db()->prepare('SELECT COUNT(DISTINCT inscription_id) FROM entrees WHERE jour = ?');
$st->execute([date('Y-m-d')]);
$presentsJour = (int) $st->fetchColumn();
$totalValides = array_sum($parCat);

$qs = http_build_query(array_filter($f + ['p' => $pageNum > 1 ? $pageNum : '']));
$statutsLabels = ['valide' => 'Actif', 'en_attente' => 'En attente', 'revoque' => 'Annulé'];

$titre = 'Administration des inscriptions';
$page = 'admin';
require __DIR__ . '/../inc/header.php';
$flash = flash();
?>

<section class="page-titre">
  <div class="container">
    <h1>Administration – Inscriptions</h1>
    <p>Connecté en tant que <strong><?= e($u['login']) ?></strong> · <a href="logout.php" style="color:var(--or)">Se déconnecter</a></p>
  </div>
</section>

<section class="section section--gris" style="padding-top:30px">
  <div class="container">
    <?php if ($flash): ?><div class="alerte alerte-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
    <?php if (mot_de_passe_par_defaut() || config('secret') === 'changez-moi-cle-secrete-fnj2026-0123456789abcdef'): ?>
      <div class="alerte alerte-attention"><strong>Sécurité :</strong> les mots de passe par défaut et/ou la clé secrète d'origine sont encore utilisés. Modifiez-les dans <code>config.php</code> avant la mise en ligne.</div>
    <?php endif; ?>

    <div class="admin-barre">
      <div class="admin-onglets">
        <a class="actif" href="index.php">Inscriptions</a>
        <a href="scanner.php">Scanner d'entrée</a>
        <a href="export.php?<?= e(http_build_query(array_filter($f))) ?>">Exporter (CSV / Excel)</a>
      </div>
    </div>

    <div class="stats">
      <div class="stat"><b><?= $totalValides ?></b><span>Inscrits (hors annulés)</span></div>
      <div class="stat" style="--c: var(--succes)"><b><?= $presentsJour ?></b><span>Présents aujourd'hui</span></div>
      <?php if ($enAttente): ?><div class="stat" style="--c:#e0a800"><b><?= $enAttente ?></b><span>En attente</span></div><?php endif; ?>
      <?php foreach (categories() as $k => $c): ?>
        <div class="stat" style="--c: <?= e($c['couleur']) ?>"><b><?= (int) ($parCat[$k] ?? 0) ?></b><span><?= e($c['label']) ?></span></div>
      <?php endforeach; ?>
    </div>

    <form class="filtres" method="get">
      <input type="search" name="q" placeholder="Nom, code, e-mail, téléphone, organisation…" value="<?= e($f['q']) ?>">
      <select name="categorie"><option value="">Toutes catégories</option>
        <?php foreach (categories() as $k => $c): ?><option value="<?= e($k) ?>" <?= $f['categorie'] === $k ? 'selected' : '' ?>><?= e($c['label']) ?></option><?php endforeach; ?>
      </select>
      <select name="statut"><option value="">Tous statuts</option>
        <?php foreach ($statutsLabels as $k => $l): ?><option value="<?= e($k) ?>" <?= $f['statut'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
      </select>
      <select name="province"><option value="">Toutes provinces</option>
        <?php foreach (array_merge(provinces(), ['Hors du Tchad']) as $p): ?><option <?= $f['province'] === $p ? 'selected' : '' ?>><?= e($p) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-bleu btn-sm" type="submit">Filtrer</button>
      <?php if (array_filter($f)): ?><a class="btn btn-ligne-bleu btn-sm" href="index.php">Réinitialiser</a><?php endif; ?>
    </form>

    <p style="color:var(--gris);font-size:13px"><?= $total ?> résultat(s)</p>

    <div class="table-wrap">
      <table class="liste">
        <thead><tr><th></th><th>Code</th><th>Participant</th><th>Catégorie</th><th>Organisation</th><th>Contact</th><th>Province</th><th>Statut</th><th>Entrées</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!$lignes): ?><tr><td colspan="10" style="text-align:center;padding:30px;color:var(--gris)">Aucune inscription.</td></tr><?php endif; ?>
        <?php foreach ($lignes as $l): $c = categorie($l['categorie']); ?>
          <tr>
            <td><?php if ($l['photo']): ?><img class="mini-photo" src="<?= e(photo_url($l)) ?>" alt="" loading="lazy"><?php else: ?><span class="mini-photo"></span><?php endif; ?></td>
            <td><code><?= e($l['code']) ?></code><br><small style="color:var(--gris)"><?= e(date('d/m/Y H:i', strtotime($l['cree_le']))) ?></small></td>
            <td><strong><?= e($l['nom']) ?></strong> <?= e($l['prenom']) ?><br><small style="color:var(--gris)"><?= e($l['sexe']) ?><?= $l['date_naissance'] ? ' · ' . (new DateTime($l['date_naissance']))->diff(new DateTime())->y . ' ans' : '' ?></small></td>
            <td><span class="pastille" style="--c: <?= e($c['couleur'] ?? '#002664') ?>"><?= e($c['label'] ?? $l['categorie']) ?></span></td>
            <td><?= e($l['organisation']) ?><?php if ($l['fonction']): ?><br><small style="color:var(--gris)"><?= e($l['fonction']) ?></small><?php endif; ?></td>
            <td><?= e($l['telephone']) ?><br><small><a href="mailto:<?= e($l['email']) ?>"><?= e($l['email']) ?></a></small></td>
            <td><?= e($l['province']) ?></td>
            <td><span class="pastille statut-<?= e($l['statut']) ?>"><?= e($statutsLabels[$l['statut']] ?? $l['statut']) ?></span></td>
            <td style="text-align:center"><?= (int) $l['nb_entrees'] ?></td>
            <td>
              <div class="actions-inline">
                <a class="btn btn-sm btn-bleu" href="fiche.php?id=<?= (int) $l['id'] ?>">Fiche</a>
                <a class="btn btn-sm btn-ligne-bleu" href="<?= e(badge_url($l)) ?>" target="_blank">Badge</a>
                <form method="post">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $l['id'] ?>"><input type="hidden" name="qs" value="<?= e($qs) ?>">
                  <?php if ($l['statut'] !== 'valide'): ?>
                    <button class="btn btn-sm btn-vert" name="action" value="valider">Valider</button>
                  <?php else: ?>
                    <button class="btn btn-sm btn-rouge" name="action" value="revoquer" onclick="return confirm('Annuler ce badge ?')">Annuler</button>
                  <?php endif; ?>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): $lien = http_build_query(array_filter($f + ['p' => $i])); ?>
          <?php if ($i === $pageNum): ?><span class="courant"><?= $i ?></span><?php else: ?><a href="?<?= e($lien) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../inc/footer.php'; ?>
