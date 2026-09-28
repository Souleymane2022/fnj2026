# Forum National de la Jeunesse 2026 – Inscriptions & badges électroniques

Plateforme d'inscription du **Forum National de la Jeunesse 2026** du Ministère de la Jeunesse et des Sports (République du Tchad), dont la charte graphique reprend celle de [jeunesse.gouv.td](https://jeunesse.gouv.td) : barre de contact, liseré tricolore bleu-or-rouge, en-tête avec logo et devise « Unité – Travail – Progrès », menu bleu national.

## Fonctionnalités

- **7 formulaires d'inscription**, chacun avec ses propres champs :
  jeune participant (15–35 ans), partenaire, sponsor, exposant, presse/média, officiel/invité, bénévole/organisation.
- **Badge électronique d'entrée** généré dès la validation : photo, nom, fonction, catégorie (une couleur par catégorie), numéro unique `FNJ26-X-XXXXXX` et **QR code signé** (HMAC, impossible à falsifier).
  - Téléchargement en PNG haute définition (300 dpi) pour l'enregistrer sur un téléphone
  - Impression / PDF au format carte 86 × 136 mm
- **Retrouver mon badge** avec l'e-mail et le téléphone.
- **Vérification publique** : scanner le QR code avec n'importe quel téléphone affiche l'authenticité du badge et la photo du titulaire.
- **Espace organisateurs** (`/admin`) :
  - tableau de bord par catégorie, recherche, filtres, fiche détaillée
  - validation / annulation / suppression des inscriptions
  - export CSV compatible Excel
  - **scanner d'entrée** (caméra du téléphone ou douchette USB) : accès autorisé / déjà entré / refusé, signal sonore, historique, décompte des présents du jour
- Anti-doublon (e-mail + catégorie), protection CSRF, champ anti-robot, photos stockées hors accès direct.

## Installation

Prérequis : PHP 8.0+ avec les extensions `pdo_sqlite` et `gd` (disponibles chez la plupart des hébergeurs, y compris ceux qui font tourner WordPress). Aucune base MySQL ni dépendance à installer.

1. Copier le dossier sur le serveur (ex. `https://jeunesse.gouv.td/fnj2026/`).
2. Donner les droits d'écriture au serveur web sur `data/`.
3. Modifier **`config.php`** :
   - `event` : dates, lieu, thème, date limite d'inscription ;
   - `secret` : une clé aléatoire (`php -r "echo bin2hex(random_bytes(32));"`) — ne plus la changer ensuite, sinon les QR codes déjà émis deviennent invalides ;
   - `utilisateurs` : remplacer les mots de passe par défaut (`admin` / `fnj2026admin`, `controle` / `fnj2026controle`) par des hash ;
   - `validation_requise` : catégories dont le badge doit être approuvé avant d'être actif (ex. `['presse']`).
4. Remplacer `assets/img/logo.svg` par le **logo officiel** du Ministère (ou modifier `ministere.logo`).
5. Servir le site en **HTTPS** : c'est obligatoire pour que la caméra du scanner fonctionne.

Test en local :

```bash
php -S 127.0.0.1:8080
# http://127.0.0.1:8080          -> site public
# http://127.0.0.1:8080/admin/   -> espace organisateurs
```

Avec Nginx, interdire l'accès aux dossiers `data/` et `inc/` et au fichier `config.php` (les fichiers `.htaccess` fournis s'en chargent sous Apache).

## Structure

```
index.php          Page d'accueil du Forum
inscription.php    Formulaires (?type=jeune|partenaire|sponsor|exposant|presse|officiel|benevole)
badge.php          Badge électronique (lien personnel signé)
retrouver.php      Retrouver son badge
verifier.php       Page ouverte par le QR code
photo.php          Sert les photos (lien signé)
admin/             Tableau de bord, fiche, export CSV, scanner d'entrée
api/checkin.php    API JSON du scanner
inc/               Noyau, champs des formulaires, contrôle d'accès, gabarits
assets/            CSS, génération du badge (canvas), librairies QR
data/              Base SQLite et photos (non versionnées)
```

Librairies incluses : [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) et [html5-qrcode](https://github.com/mebjas/html5-qrcode) (licences MIT / Apache 2.0).
