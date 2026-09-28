<?php
define('FNJ_ROOT', '../');
require __DIR__ . '/../inc/app.php';
$u = exiger_connexion('agent');

$st = db()->prepare('SELECT COUNT(DISTINCT inscription_id) FROM entrees WHERE jour = ?');
$st->execute([date('Y-m-d')]);
$presents = (int) $st->fetchColumn();

$titre = 'Scanner d\'entrée';
$page = 'admin';
require __DIR__ . '/../inc/header.php';
?>
<section class="page-titre">
  <div class="container">
    <h1>Contrôle d'accès – Scanner des badges</h1>
    <p>Agent : <strong><?= e($u['login']) ?></strong> · <span id="nb-presents"><?= $presents ?></span> participant(s) entré(s) aujourd'hui · <a href="logout.php" style="color:var(--or)">Se déconnecter</a></p>
  </div>
</section>

<section class="section section--gris" style="padding-top:30px">
  <div class="container">
    <?php if ($u['role'] === 'admin'): ?>
    <div class="admin-onglets" style="margin-bottom:20px">
      <a href="index.php">Inscriptions</a>
      <a class="actif" href="scanner.php">Scanner d'entrée</a>
    </div>
    <?php endif; ?>

    <div class="scanner-layout">
      <div class="form-carte">
        <div class="grille" style="margin-bottom:14px">
          <div class="champ"><label for="point">Point d'accès</label><input type="text" id="point" placeholder="Ex. : Porte principale" maxlength="60"></div>
          <div class="champ"><span class="label">Mode</span>
            <label style="font-weight:400;display:flex;gap:8px;align-items:center;margin-top:10px"><input type="checkbox" id="enregistrer" checked> Enregistrer l'entrée à chaque scan</label>
          </div>
        </div>
        <div id="lecteur"></div>
        <div class="badge-actions" style="margin:14px 0">
          <button class="btn btn-bleu" id="btn-camera" type="button">📷 Démarrer la caméra</button>
          <button class="btn btn-ligne-bleu" id="btn-stop" type="button" hidden>Arrêter</button>
        </div>
        <form id="form-manuel" class="filtres" style="margin:0">
          <input type="search" id="code-manuel" placeholder="Saisie manuelle : FNJ26-J-XXXXXX" autocomplete="off" style="text-transform:uppercase">
          <button class="btn btn-bleu btn-sm" type="submit">Vérifier</button>
        </form>
        <p id="info-camera" class="aide" style="font-size:13px;color:var(--gris)">La caméra nécessite une connexion HTTPS. Un lecteur de QR code USB (douchette) peut aussi être utilisé dans le champ de saisie manuelle.</p>
      </div>

      <div>
        <div class="verif scan-resultat" id="resultat" style="max-width:none">
          <div class="verif__entete" style="background:var(--bleu)">EN ATTENTE DE SCAN<small>Présentez le QR code du badge devant la caméra.</small></div>
        </div>
        <div class="form-carte" style="margin-top:20px">
          <h3 style="margin-top:0">Derniers scans</h3>
          <ul class="historique" id="historique"><li style="color:var(--gris)">Aucun scan pour le moment.</li></ul>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="../assets/js/vendor/html5-qrcode.min.js"></script>
<script>
(function () {
  var CSRF = <?= json_encode(csrf_token()) ?>;
  var resultat = document.getElementById('resultat');
  var historique = document.getElementById('historique');
  var point = document.getElementById('point');
  var enregistrer = document.getElementById('enregistrer');
  var dernier = { contenu: '', t: 0 };
  var enCours = false;
  var lecteur = null;

  try { point.value = localStorage.getItem('fnj_point') || ''; } catch (e) {}
  point.addEventListener('change', function () { try { localStorage.setItem('fnj_point', point.value); } catch (e) {} });

  var titres = { ok: '✔ ACCÈS AUTORISÉ', deja: '⚠ DÉJÀ ENTRÉ(E)', attente: '⏳ ACCÈS REFUSÉ', revoque: '✖ ACCÈS REFUSÉ', invalide: '✖ BADGE INVALIDE', erreur: '⚠ ERREUR' };
  var fonds = { ok: 'var(--succes)', deja: '#e0a800', attente: '#e0a800', revoque: 'var(--rouge)', invalide: 'var(--rouge)', erreur: 'var(--rouge)' };

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  function bip(ok) {
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.frequency.value = ok ? 880 : 220; o.type = ok ? 'sine' : 'square';
      g.gain.value = 0.15; o.connect(g); g.connect(ctx.destination);
      o.start(); o.stop(ctx.currentTime + (ok ? 0.15 : 0.45));
    } catch (e) {}
    if (navigator.vibrate) navigator.vibrate(ok ? 80 : [200, 100, 200]);
  }

  function afficher(r) {
    var p = r.participant;
    var html = '<div class="verif__entete" style="background:' + fonds[r.etat] + '">' + titres[r.etat] + '<small>' + esc(r.message) + '</small></div>';
    if (p) {
      html += '<div class="verif__corps" style="--c:' + esc(p.couleur) + '">'
        + (p.photo ? '<img class="verif__photo" src="' + esc(p.photo) + '" alt="">' : '')
        + '<div class="verif__nom">' + esc(p.prenom) + ' ' + esc(p.nom) + '</div>'
        + '<p><span class="pastille" style="--c:' + esc(p.couleur) + '">' + esc(p.categorie) + '</span></p>'
        + ((p.fonction || p.organisation) ? '<p style="color:var(--gris)">' + esc([p.fonction, p.organisation].filter(Boolean).join(' – ')) + '</p>' : '')
        + '<p><code>' + esc(p.code) + '</code>' + (r.entrees ? ' · ' + r.entrees + ' passage(s) aujourd\'hui' : '') + '</p>'
        + '</div>';
    }
    resultat.innerHTML = html;
    bip(r.etat === 'ok');

    if (historique.firstElementChild && !historique.firstElementChild.dataset.scan) historique.innerHTML = '';
    var li = document.createElement('li');
    li.dataset.scan = '1';
    li.innerHTML = '<span>' + new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' · '
      + esc(p ? p.prenom + ' ' + p.nom : 'Inconnu') + '</span><span style="font-weight:700;color:' + fonds[r.etat] + '">' + titres[r.etat].replace(/^\S+\s/, '') + '</span>';
    historique.insertBefore(li, historique.firstChild);
    while (historique.children.length > 15) historique.removeChild(historique.lastChild);
    if (r.etat === 'ok' && enregistrer.checked) {
      var n = document.getElementById('nb-presents'); n.textContent = parseInt(n.textContent, 10) + 1;
    }
  }

  function verifier(contenu) {
    contenu = (contenu || '').trim();
    if (!contenu || enCours) return;
    var now = Date.now();
    if (contenu === dernier.contenu && now - dernier.t < 5000) return; // évite les doubles lectures
    dernier = { contenu: contenu, t: now };
    enCours = true;
    fetch('../api/checkin.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf: CSRF, contenu: contenu, point: point.value, enregistrer: enregistrer.checked })
    }).then(function (r) { return r.json(); })
      .then(afficher)
      .catch(function () { afficher({ etat: 'erreur', message: 'Connexion au serveur impossible.' }); })
      .finally(function () { enCours = false; });
  }

  document.getElementById('form-manuel').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var champ = document.getElementById('code-manuel');
    dernier.contenu = '';
    verifier(champ.value);
    champ.value = '';
    champ.focus();
  });

  var btnCam = document.getElementById('btn-camera'), btnStop = document.getElementById('btn-stop');
  btnCam.addEventListener('click', function () {
    if (typeof Html5Qrcode === 'undefined') return;
    lecteur = lecteur || new Html5Qrcode('lecteur');
    lecteur.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 250, height: 250 } }, verifier, function () {})
      .then(function () { btnCam.hidden = true; btnStop.hidden = false; })
      .catch(function (err) {
        document.getElementById('info-camera').innerHTML = '<span style="color:var(--rouge)">Caméra indisponible : ' + esc(err) + '. Vérifiez l\'autorisation et l\'utilisation du HTTPS.</span>';
      });
  });
  btnStop.addEventListener('click', function () {
    if (lecteur) lecteur.stop().then(function () { btnCam.hidden = false; btnStop.hidden = true; });
  });
})();
</script>
<?php require __DIR__ . '/../inc/footer.php'; ?>
