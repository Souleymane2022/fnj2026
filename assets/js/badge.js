/**
 * Génération du badge électronique sur <canvas> (format carte 86 × 136 mm, 300 dpi).
 * Dépend de vendor/qrcode.js (qrcode-generator, licence MIT).
 *
 * FNJBadge.dessiner(canvas, donnees) -> Promise
 * donnees : { prenom, nom, organisation, fonction, categorie, couleur, code, qr,
 *             photo, logo, evenement, annee, dates, lieu, pays, ministere, province, statut }
 */
(function (global) {
  'use strict';

  var W = 1016, H = 1606;
  var BLEU = '#002664', OR = '#FECB00', ROUGE = '#C60C30';
  var POLICE_TITRE = "'Montserrat', 'Segoe UI', Arial, sans-serif";
  var POLICE_CORPS = "'Open Sans', 'Segoe UI', Arial, sans-serif";

  function chargerImage(src) {
    return new Promise(function (resolve) {
      if (!src) return resolve(null);
      var img = new Image();
      img.onload = function () { resolve(img); };
      img.onerror = function () { resolve(null); };
      img.src = src;
    });
  }

  function chargerPolices() {
    if (!document.fonts || !document.fonts.load) return Promise.resolve();
    return Promise.all([
      document.fonts.load('800 60px Montserrat'),
      document.fonts.load('700 40px Montserrat'),
      document.fonts.load('600 30px "Open Sans"'),
    ]).catch(function () {});
  }

  function rectArrondi(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  /** Écrit un texte centré en réduisant la police pour tenir dans maxW. */
  function texteAjuste(ctx, texte, x, y, maxW, taille, poids, police, couleur, espacement) {
    var t = taille;
    do {
      ctx.font = poids + ' ' + t + 'px ' + police;
      if ('letterSpacing' in ctx) ctx.letterSpacing = (espacement || 0) + 'px';
      if (ctx.measureText(texte).width <= maxW || t <= 18) break;
      t -= 2;
    } while (true);
    ctx.fillStyle = couleur;
    ctx.fillText(texte, x, y);
    if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';
    return t;
  }

  function bandeTricolore(ctx, y, h) {
    ctx.fillStyle = BLEU; ctx.fillRect(0, y, W / 3, h);
    ctx.fillStyle = OR; ctx.fillRect(W / 3, y, W / 3, h);
    ctx.fillStyle = ROUGE; ctx.fillRect(2 * W / 3, y, W / 3 + 1, h);
  }

  function dessinerQR(ctx, texte, x, y, taille) {
    var qr = global.qrcode(0, 'M');
    qr.addData(texte);
    qr.make();
    var n = qr.getModuleCount();
    var marge = 2;
    var m = taille / (n + marge * 2);
    ctx.fillStyle = '#fff';
    ctx.fillRect(x, y, taille, taille);
    ctx.fillStyle = '#000';
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (qr.isDark(r, c)) {
          ctx.fillRect(Math.floor(x + (c + marge) * m), Math.floor(y + (r + marge) * m), Math.ceil(m), Math.ceil(m));
        }
      }
    }
  }

  function initiales(d) {
    return ((d.prenom || '').charAt(0) + (d.nom || '').charAt(0)).toUpperCase();
  }

  function dessiner(canvas, d) {
    canvas.width = W;
    canvas.height = H;
    var ctx = canvas.getContext('2d');
    var couleur = d.couleur || BLEU;

    return Promise.all([chargerPolices(), chargerImage(d.logo), chargerImage(d.photo)]).then(function (res) {
      var logo = res[1], photo = res[2];
      ctx.textAlign = 'center';
      ctx.textBaseline = 'alphabetic';

      // Fond
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, W, H);

      // Motif léger en fond
      ctx.save();
      ctx.globalAlpha = 0.04;
      ctx.strokeStyle = BLEU;
      ctx.lineWidth = 2;
      for (var i = -H; i < W; i += 36) {
        ctx.beginPath(); ctx.moveTo(i, H); ctx.lineTo(i + H, 0); ctx.stroke();
      }
      ctx.restore();

      // En-tête bleu avec bord incurvé
      bandeTricolore(ctx, 0, 18);
      ctx.fillStyle = BLEU;
      ctx.beginPath();
      ctx.moveTo(0, 18);
      ctx.lineTo(W, 18);
      ctx.lineTo(W, 440);
      ctx.quadraticCurveTo(W / 2, 540, 0, 440);
      ctx.closePath();
      ctx.fill();
      // liseré doré
      ctx.strokeStyle = OR;
      ctx.lineWidth = 8;
      ctx.beginPath();
      ctx.moveTo(0, 452);
      ctx.quadraticCurveTo(W / 2, 552, W, 452);
      ctx.stroke();

      // Logo
      if (logo) {
        ctx.save();
        ctx.beginPath(); ctx.arc(W / 2, 118, 78, 0, Math.PI * 2); ctx.fillStyle = '#fff'; ctx.fill();
        ctx.drawImage(logo, W / 2 - 70, 48, 140, 140);
        ctx.restore();
      }

      texteAjuste(ctx, (d.pays || '').toUpperCase(), W / 2, 240, 900, 26, '700', POLICE_TITRE, OR, 4);
      texteAjuste(ctx, d.ministere || '', W / 2, 280, 900, 28, '600', POLICE_CORPS, '#ffffff');
      texteAjuste(ctx, (d.evenement || '').toUpperCase(), W / 2, 350, 920, 50, '800', POLICE_TITRE, '#ffffff', 1);
      texteAjuste(ctx, d.annee || '', W / 2, 420, 400, 64, '800', POLICE_TITRE, OR, 6);

      // Photo
      var pw = 330, ph = 412, px = (W - pw) / 2, py = 500;
      ctx.save();
      ctx.shadowColor = 'rgba(0,0,0,.25)'; ctx.shadowBlur = 24; ctx.shadowOffsetY = 8;
      rectArrondi(ctx, px - 12, py - 12, pw + 24, ph + 24, 26);
      ctx.fillStyle = couleur; ctx.fill();
      ctx.restore();
      rectArrondi(ctx, px - 4, py - 4, pw + 8, ph + 8, 20);
      ctx.fillStyle = '#fff'; ctx.fill();
      ctx.save();
      rectArrondi(ctx, px, py, pw, ph, 16);
      ctx.clip();
      if (photo) {
        ctx.drawImage(photo, px, py, pw, ph);
      } else {
        ctx.fillStyle = '#e8eef8'; ctx.fillRect(px, py, pw, ph);
        ctx.fillStyle = BLEU; ctx.font = '800 130px ' + POLICE_TITRE; ctx.textBaseline = 'middle';
        ctx.fillText(initiales(d), W / 2, py + ph / 2);
        ctx.textBaseline = 'alphabetic';
      }
      ctx.restore();

      // Identité
      texteAjuste(ctx, d.prenom || '', W / 2, 1000, 900, 50, '600', POLICE_CORPS, '#1f2937');
      texteAjuste(ctx, (d.nom || '').toUpperCase(), W / 2, 1068, 900, 68, '800', POLICE_TITRE, BLEU, 1);
      var sousTitre = [d.fonction, d.organisation].filter(Boolean).join(' – ');
      if (sousTitre) texteAjuste(ctx, sousTitre, W / 2, 1118, 900, 32, '600', POLICE_CORPS, '#6b7280');

      // Bande de catégorie
      var by = 1150, bh = 118;
      ctx.fillStyle = couleur;
      ctx.fillRect(0, by, W, bh);
      ctx.fillStyle = 'rgba(255,255,255,.15)';
      ctx.fillRect(0, by, W, 8);
      texteAjuste(ctx, (d.categorie || '').toUpperCase(), W / 2, by + 82, 940, 64, '800', POLICE_TITRE, '#ffffff', 6);

      // QR code + informations
      var qs = 262, qx = 60, qy = 1292;
      rectArrondi(ctx, qx - 8, qy - 8, qs + 16, qs + 16, 14);
      ctx.fillStyle = '#fff'; ctx.fill();
      ctx.strokeStyle = '#dde3ec'; ctx.lineWidth = 3; ctx.stroke();
      dessinerQR(ctx, d.qr, qx, qy, qs);

      var tx = qx + qs + 48, maxT = W - tx - 40;
      ctx.textAlign = 'left';
      ctx.fillStyle = '#6b7280'; ctx.font = '700 22px ' + POLICE_TITRE;
      if ('letterSpacing' in ctx) ctx.letterSpacing = '3px';
      ctx.fillText('N° DE BADGE', tx, qy + 30);
      if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';
      texteAjusteGauche(ctx, d.code, tx, qy + 82, maxT, 46, '800', "'Courier New', monospace", BLEU);

      ctx.fillStyle = '#6b7280'; ctx.font = '700 22px ' + POLICE_TITRE;
      if ('letterSpacing' in ctx) ctx.letterSpacing = '3px';
      ctx.fillText('DATES & LIEU', tx, qy + 142);
      if ('letterSpacing' in ctx) ctx.letterSpacing = '0px';
      texteAjusteGauche(ctx, d.dates || '', tx, qy + 184, maxT, 30, '700', POLICE_CORPS, '#1f2937');
      var lieu = coupeLignes(ctx, d.lieu || '', maxT, '600 26px ' + POLICE_CORPS, 2);
      ctx.fillStyle = '#374151'; ctx.font = '600 26px ' + POLICE_CORPS;
      lieu.forEach(function (l, i) { ctx.fillText(l, tx, qy + 224 + i * 34); });
      ctx.textAlign = 'center';

      // Pied
      ctx.fillStyle = '#6b7280'; ctx.font = '600 20px ' + POLICE_CORPS;
      ctx.fillText('Badge strictement personnel – à présenter à chaque entrée', W / 2, H - 30);
      bandeTricolore(ctx, H - 18, 18);

      // Filigrane selon le statut
      if (d.statut && d.statut !== 'valide') {
        ctx.save();
        ctx.translate(W / 2, H / 2);
        ctx.rotate(-Math.PI / 6);
        ctx.globalAlpha = 0.75;
        ctx.fillStyle = d.statut === 'revoque' ? ROUGE : '#e0a800';
        ctx.fillRect(-W, -60, W * 2, 120);
        ctx.globalAlpha = 1;
        ctx.fillStyle = '#fff';
        ctx.font = '800 58px ' + POLICE_TITRE;
        ctx.fillText(d.statut === 'revoque' ? 'BADGE ANNULÉ' : 'EN ATTENTE DE VALIDATION', 0, 20);
        ctx.restore();
      }
    });
  }

  function texteAjusteGauche(ctx, texte, x, y, maxW, taille, poids, police, couleur) {
    var t = taille;
    do {
      ctx.font = poids + ' ' + t + 'px ' + police;
      if (ctx.measureText(texte).width <= maxW || t <= 16) break;
      t -= 2;
    } while (true);
    ctx.fillStyle = couleur;
    ctx.fillText(texte, x, y);
  }

  function coupeLignes(ctx, texte, maxW, font, maxLignes) {
    ctx.font = font;
    var mots = texte.split(/\s+/), lignes = [], ligne = '';
    mots.forEach(function (m) {
      var essai = ligne ? ligne + ' ' + m : m;
      if (ctx.measureText(essai).width > maxW && ligne) { lignes.push(ligne); ligne = m; }
      else ligne = essai;
    });
    if (ligne) lignes.push(ligne);
    if (lignes.length > maxLignes) {
      lignes = lignes.slice(0, maxLignes);
      lignes[maxLignes - 1] = lignes[maxLignes - 1].replace(/\s*\S*$/, '') + '…';
    }
    return lignes;
  }

  global.FNJBadge = { dessiner: dessiner, largeur: W, hauteur: H };
})(window);
