# 27ᵉ Fête Nationale de la Jeunesse (FNJ 2026 – Pala) – Inscriptions & badges électroniques

Plateforme d'inscription de la **27ᵉ Fête Nationale de la Jeunesse** (Pala, Mayo-Kebbi Ouest, du 12 au 14 octobre 2026) du Ministère de la Jeunesse et des Sports (République du Tchad), dont la charte graphique reprend celle de [jeunesse.gouv.td](https://jeunesse.gouv.td) : barre de contact, liseré tricolore bleu-or-rouge, en-tête avec logo et devise « Unité – Travail – Progrès », menu bleu national.

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

## Déploiement sur Vercel (https://fnj2026.vercel.app)

Vercel ne conserve aucun fichier entre deux requêtes : l'application y stocke donc **tout dans PostgreSQL** (inscriptions, photos, sessions, entrées). Le PHP tourne avec le runtime communautaire [`vercel-php`](https://github.com/vercel-community/php) déclaré dans `vercel.json`, et toutes les pages passent par `api/index.php`.

1. **Base de données** : dans le projet Vercel → *Storage* → *Create Database* → **Neon (Postgres)**, puis connectez-la au projet. Vercel crée alors automatiquement la variable `DATABASE_URL` (ou `POSTGRES_URL`). Les tables sont créées au premier chargement de page.
2. **Variables d'environnement** (*Settings → Environment Variables*) :

   | Variable | Valeur |
   |---|---|
   | `FNJ_BASE_URL` | `https://fnj2026.vercel.app` (adresse encodée dans les QR codes) |
   | `FNJ_SECRET` | une clé aléatoire de 64 caractères, à ne plus changer ensuite (`openssl rand -hex 32`) |
   | `FNJ_ADMIN_HASH` | hash du mot de passe admin (`php -r "echo password_hash('MotDePasse', PASSWORD_DEFAULT);"`) |
   | `FNJ_AGENT_HASH` | hash du mot de passe des agents de contrôle |

   Sans `FNJ_ADMIN_HASH` / `FNJ_AGENT_HASH`, les mots de passe par défaut `fnj2026admin` et `fnj2026controle` restent actifs.
3. **Déployer** : le dépôt GitHub est relié au projet Vercel ; chaque `git push` sur la branche de production déclenche un déploiement (*Settings → Git → Production Branch*). Après avoir ajouté des variables, relancez un déploiement (*Deployments → Redeploy*).

Particularités de Vercel :
- les photos sont recadrées et compressées **dans le navigateur** (480 × 600 JPEG, ~40 Ko) avant l'envoi, ce qui respecte la limite de 4,5 Mo par requête et économise les données mobiles ;
- l'envoi d'e-mails (`mail()`) n'est pas disponible ;
- le HTTPS est fourni automatiquement, ce qui permet la caméra du scanner.

## Installation sur un hébergement PHP classique (Apache)

Prérequis : PHP 8.0+ avec `pdo_sqlite` (ou `pdo_pgsql`) et idéalement `gd`.

1. Copier le dossier sur le serveur, donner les droits d'écriture sur `data/`.
2. Modifier **`config.php`** (clé secrète, mots de passe, `validation_requise`). Les informations de l'événement (dates, lieu, thème, logo `assets/img/logo-fnj27.png`, date limite) se trouvent dans la section `event`.
3. Sans `DATABASE_URL`, une base SQLite est créée dans `data/`.

## Test en local

```bash
php -S 127.0.0.1:8080 api/index.php          # mode Vercel (routeur), base SQLite
DATABASE_URL=postgres://user:mdp@localhost:5432/fnj php -S 127.0.0.1:8080 api/index.php
# http://127.0.0.1:8080          -> site public
# http://127.0.0.1:8080/admin/   -> espace organisateurs
```

## Structure

```
index.php          Page d'accueil de la FNJ
inscription.php    Formulaires (?type=jeune|partenaire|sponsor|exposant|presse|officiel|benevole)
badge.php          Badge électronique (lien personnel signé)
retrouver.php      Retrouver son badge
verifier.php       Page ouverte par le QR code
photo.php          Sert les photos (lien signé)
admin/             Tableau de bord, fiche, export CSV, scanner d'entrée
admin/checkin.php  API JSON du scanner
api/index.php      Point d'entrée Vercel (routeur vers les pages)
vercel.json        Configuration Vercel (runtime vercel-php)
inc/               Noyau, champs des formulaires, contrôle d'accès, gabarits
assets/            CSS, génération du badge (canvas), librairies QR
data/              Base SQLite locale (non versionnée)
```

Librairies incluses : [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) et [html5-qrcode](https://github.com/mebjas/html5-qrcode) (licences MIT / Apache 2.0).
