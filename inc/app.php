<?php
/**
 * Noyau de l'application : configuration, base de données, catégories,
 * sécurité (CSRF, signatures des badges, authentification).
 */

declare(strict_types=1);

date_default_timezone_set('Africa/Ndjamena');

function config(?string $key = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config.php';
    }
    if ($key === null) {
        return $cfg;
    }
    $val = $cfg;
    foreach (explode('.', $key) as $part) {
        if (!is_array($val) || !array_key_exists($part, $val)) {
            return null;
        }
        $val = $val[$part];
    }
    return $val;
}

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Chemin relatif vers la racine de l'application depuis la page courante. */
function root(): string
{
    return defined('FNJ_ROOT') ? FNJ_ROOT : '';
}

function est_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function base_url(): string
{
    $cfg = config('base_url');
    if ($cfg) {
        return rtrim($cfg, '/');
    }
    $https  = est_https();
    $host   = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appDir = realpath(__DIR__ . '/..');
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $path = '';
    if ($docRoot && strpos($appDir, $docRoot) === 0) {
        $path = str_replace('\\', '/', substr($appDir, strlen($docRoot)));
    }
    return ($https ? 'https' : 'http') . '://' . $host . rtrim($path, '/');
}

// ---------------------------------------------------------------------------
// Base de données
// ---------------------------------------------------------------------------

/** URL PostgreSQL (Vercel Postgres / Neon / Supabase…) ; vide = SQLite local. */
function db_url(): string
{
    foreach (['DATABASE_URL', 'POSTGRES_URL', 'POSTGRES_PRISMA_URL'] as $k) {
        if ($v = getenv($k)) {
            return $v;
        }
    }
    return '';
}

function db_pgsql(): bool
{
    return db_url() !== '';
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (db_pgsql()) {
        $u = parse_url(db_url());
        parse_str($u['query'] ?? '', $q);
        $host = $u['host'] ?? 'localhost';
        $dsn = 'pgsql:host=' . $host . ';port=' . ($u['port'] ?? 5432) . ';dbname=' . ltrim($u['path'] ?? '/postgres', '/')
            . ';sslmode=' . ($q['sslmode'] ?? ($host === 'localhost' || $host === '127.0.0.1' ? 'prefer' : 'require'));
        // Neon : identifiant de l'endpoint pour les clients sans SNI
        if (preg_match('/^(ep-[a-z0-9-]+?)(-pooler)?\.[^.]+.*neon\.tech$/', $host, $m)) {
            $dsn .= ";options='endpoint=" . $m[1] . "'";
        }
        $pdo = new PDO($dsn, rawurldecode($u['user'] ?? ''), rawurldecode($u['pass'] ?? ''), $opts);
        $id = 'SERIAL PRIMARY KEY';
    } else {
        $path = config('db_path');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        $pdo = new PDO('sqlite:' . $path, null, null, $opts);
        $pdo->exec('PRAGMA journal_mode = WAL; PRAGMA foreign_keys = ON; PRAGMA busy_timeout = 5000;');
        $id = 'INTEGER PRIMARY KEY AUTOINCREMENT';
    }
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS inscriptions (
            id            $id,
            code          TEXT NOT NULL UNIQUE,
            categorie     TEXT NOT NULL,
            civilite      TEXT,
            nom           TEXT NOT NULL,
            prenom        TEXT NOT NULL,
            sexe          TEXT,
            date_naissance TEXT,
            nationalite   TEXT,
            province      TEXT,
            ville         TEXT,
            telephone     TEXT NOT NULL,
            email         TEXT NOT NULL,
            organisation  TEXT,
            fonction      TEXT,
            details       TEXT,          -- JSON des champs propres à la catégorie
            photo         TEXT,          -- non vide si une photo existe (table photos)
            statut        TEXT NOT NULL DEFAULT 'valide', -- valide | en_attente | revoque
            cree_le       TEXT NOT NULL,
            ip            TEXT
        );
        CREATE INDEX IF NOT EXISTS idx_insc_email ON inscriptions(email);
        CREATE INDEX IF NOT EXISTS idx_insc_cat ON inscriptions(categorie);
        CREATE TABLE IF NOT EXISTS entrees (
            id             $id,
            inscription_id INTEGER NOT NULL REFERENCES inscriptions(id) ON DELETE CASCADE,
            jour           TEXT NOT NULL,
            heure          TEXT NOT NULL,
            agent          TEXT,
            point_acces    TEXT
        );
        CREATE INDEX IF NOT EXISTS idx_entrees_insc ON entrees(inscription_id, jour);
        CREATE TABLE IF NOT EXISTS photos (
            code  TEXT PRIMARY KEY,
            data  TEXT NOT NULL      -- JPEG encodé en base64
        );
        CREATE TABLE IF NOT EXISTS sessions (
            id       TEXT PRIMARY KEY,
            data     TEXT NOT NULL,
            modifie  INTEGER NOT NULL
        );
    SQL);
    return $pdo;
}

/**
 * Sessions stockées en base : indispensable sur Vercel, où chaque requête
 * peut être servie par une instance différente sans disque partagé.
 */
class SessionBdd implements SessionHandlerInterface
{
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }

    public function read($id): string
    {
        $st = db()->prepare('SELECT data FROM sessions WHERE id = ? AND modifie > ?');
        $st->execute([$id, time() - 43200]);
        return (string) ($st->fetchColumn() ?: '');
    }

    public function write($id, $data): bool
    {
        if ($data === '') {
            // Pas de session vide en base (visiteurs, robots)
            return $this->destroy($id);
        }
        db()->prepare('INSERT INTO sessions (id, data, modifie) VALUES (?, ?, ?)
                       ON CONFLICT (id) DO UPDATE SET data = excluded.data, modifie = excluded.modifie')
            ->execute([$id, $data, time()]);
        return true;
    }

    public function destroy($id): bool
    {
        db()->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($max)
    {
        return db()->prepare('DELETE FROM sessions WHERE modifie < ?')->execute([time() - 43200]) ? 1 : 0;
    }
}

function demarrer_session(): void
{
    if (session_status() !== PHP_SESSION_NONE || PHP_SAPI === 'cli' && !isset($_SERVER['REQUEST_METHOD'])) {
        return;
    }
    session_set_save_handler(new SessionBdd(), true);
    session_name('fnj2026');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => est_https(),
        'path'     => '/',
    ]);
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');
    session_start();
}

/** LIKE insensible à la casse sur SQLite comme sur PostgreSQL. */
function sql_like(): string
{
    return db_pgsql() ? 'ILIKE' : 'LIKE';
}

// ---------------------------------------------------------------------------
// Catégories de participants
// ---------------------------------------------------------------------------

function categories(): array
{
    return [
        'jeune' => [
            'label'   => 'Jeune participant',
            'badge'   => 'PARTICIPANT',
            'lettre'  => 'J',
            'couleur' => '#002664',
            'icone'   => '🎓',
            'resume'  => 'Jeunes de 15 à 35 ans, élèves, étudiants, entrepreneurs, membres d\'associations et de mouvements de jeunesse.',
        ],
        'partenaire' => [
            'label'   => 'Partenaire',
            'badge'   => 'PARTENAIRE',
            'lettre'  => 'P',
            'couleur' => '#1B7F3B',
            'icone'   => '🤝',
            'resume'  => 'Partenaires techniques et financiers, agences du Système des Nations Unies, ONG, ambassades et institutions.',
        ],
        'sponsor' => [
            'label'   => 'Sponsor',
            'badge'   => 'SPONSOR',
            'lettre'  => 'S',
            'couleur' => '#B8860B',
            'icone'   => '⭐',
            'resume'  => 'Entreprises et opérateurs économiques qui soutiennent financièrement ou matériellement la FNJ.',
        ],
        'exposant' => [
            'label'   => 'Exposant',
            'badge'   => 'EXPOSANT',
            'lettre'  => 'E',
            'couleur' => '#D35400',
            'icone'   => '🏪',
            'resume'  => 'Startups, coopératives, associations et entreprises souhaitant tenir un stand au village de la FNJ.',
        ],
        'presse' => [
            'label'   => 'Presse / Média',
            'badge'   => 'PRESSE',
            'lettre'  => 'M',
            'couleur' => '#C60C30',
            'icone'   => '🎙️',
            'resume'  => 'Journalistes, reporters, photographes, vidéastes et créateurs de contenu accrédités.',
        ],
        'officiel' => [
            'label'   => 'Officiel / Invité',
            'badge'   => 'INVITÉ OFFICIEL',
            'lettre'  => 'O',
            'couleur' => '#5B2C83',
            'icone'   => '🏛️',
            'resume'  => 'Membres du Gouvernement, autorités administratives, élus, diplomates, conférenciers et panélistes.',
        ],
        'benevole' => [
            'label'   => 'Bénévole / Organisation',
            'badge'   => 'ORGANISATION',
            'lettre'  => 'B',
            'couleur' => '#00796B',
            'icone'   => '🙋',
            'resume'  => 'Volontaires et membres du comité d\'organisation (accueil, logistique, protocole, communication).',
        ],
    ];
}

function categorie(string $key): ?array
{
    return categories()[$key] ?? null;
}

function provinces(): array
{
    return [
        'Barh-El-Gazel', 'Batha', 'Borkou', 'Chari-Baguirmi', 'Ennedi-Est', 'Ennedi-Ouest',
        'Guéra', 'Hadjer-Lamis', 'Kanem', 'Lac', 'Logone Occidental', 'Logone Oriental',
        'Mandoul', 'Mayo-Kebbi Est', 'Mayo-Kebbi Ouest', 'Moyen-Chari', 'N\'Djamena',
        'Ouaddaï', 'Salamat', 'Sila', 'Tandjilé', 'Tibesti', 'Wadi Fira',
    ];
}

function thematiques(): array
{
    return [
        'Talents, arts et culture',
        'Entrepreneuriat, emploi et autonomisation',
        'Agriculture, élevage et environnement',
        'Paix, cohésion sociale et vivre-ensemble',
        'Participation citoyenne : faire entendre la voix des jeunes',
        'Innovation, numérique et technologies',
        'Sport et loisirs',
        'Éducation, formation et santé des jeunes',
    ];
}

// ---------------------------------------------------------------------------
// Sécurité
// ---------------------------------------------------------------------------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    return isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
}

/** Signature d'un badge. $usage distingue la clé d'accès au badge de celle du QR. */
function signature(string $code, string $usage = 'qr'): string
{
    return substr(hash_hmac('sha256', $usage . '|' . $code, (string) config('secret')), 0, 20);
}

function signature_ok(string $code, string $sig, string $usage = 'qr'): bool
{
    return $code !== '' && $sig !== '' && hash_equals(signature($code, $usage), $sig);
}

function badge_url(array $insc, bool $absolute = false): string
{
    $q = 'badge.php?c=' . rawurlencode($insc['code']) . '&k=' . signature($insc['code'], 'badge');
    return $absolute ? base_url() . '/' . $q : root() . $q;
}

function verification_url(array $insc): string
{
    return base_url() . '/verifier.php?c=' . rawurlencode($insc['code']) . '&s=' . signature($insc['code'], 'qr');
}

function photo_url(array $insc): ?string
{
    if (empty($insc['photo'])) {
        return null;
    }
    return root() . 'photo.php?c=' . rawurlencode($insc['code']) . '&k=' . signature($insc['code'], 'photo');
}

function generer_code(string $categorie): string
{
    $lettre = categorie($categorie)['lettre'] ?? 'X';
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sans 0/O/1/I
    do {
        $suffixe = '';
        for ($i = 0; $i < 6; $i++) {
            $suffixe .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $code = 'FNJ26-' . $lettre . '-' . $suffixe;
        $st = db()->prepare('SELECT 1 FROM inscriptions WHERE code = ?');
        $st->execute([$code]);
    } while ($st->fetchColumn());
    return $code;
}

function trouver_inscription(string $code): ?array
{
    $st = db()->prepare('SELECT * FROM inscriptions WHERE code = ?');
    $st->execute([strtoupper(trim($code))]);
    $row = $st->fetch();
    return $row ?: null;
}

function inscriptions_ouvertes(): bool
{
    $limite = config('event.date_limite_inscription');
    return !$limite || date('Y-m-d') <= $limite;
}

// ---------------------------------------------------------------------------
// Authentification (espace d'administration)
// ---------------------------------------------------------------------------

function utilisateur(): ?array
{
    return $_SESSION['utilisateur'] ?? null;
}

function connexion(string $login, string $mdp): bool
{
    $users = config('utilisateurs') ?: [];
    $u = $users[$login] ?? null;
    if (!$u) {
        return false;
    }
    $ok = isset($u['hash'])
        ? password_verify($mdp, $u['hash'])
        : (isset($u['mot_de_passe']) && hash_equals((string) $u['mot_de_passe'], $mdp));
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['utilisateur'] = ['login' => $login, 'role' => $u['role'] ?? 'agent'];
    }
    return $ok;
}

function mot_de_passe_par_defaut(): bool
{
    foreach (config('utilisateurs') ?: [] as $u) {
        if (isset($u['mot_de_passe'])) {
            return true;
        }
    }
    return false;
}

function exiger_connexion(string $role = 'agent'): array
{
    $u = utilisateur();
    if ($u && $role === 'admin' && $u['role'] !== 'admin') {
        // Un agent de contrôle n'a accès qu'au scanner
        header('Location: ' . root() . 'admin/scanner.php');
        exit;
    }
    if (!$u) {
        header('Location: ' . root() . 'admin/login.php?retour=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ''));
        exit;
    }
    return $u;
}

function flash(?string $msg = null, string $type = 'succes'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

demarrer_session();
