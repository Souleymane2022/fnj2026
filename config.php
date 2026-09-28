<?php
/**
 * Configuration du Forum National de la Jeunesse 2026.
 *
 * Toutes les valeurs ci-dessous peuvent être adaptées sans toucher au reste
 * du code. Les valeurs sensibles (secret, mots de passe) peuvent aussi être
 * fournies par variables d'environnement.
 */

return [
    // --- Événement ----------------------------------------------------------
    'event' => [
        'nom'        => 'Forum National de la Jeunesse',
        'sigle'      => 'FNJ 2026',
        'annee'      => '2026',
        'theme'      => 'Jeunesse tchadienne : actrice du développement, de la paix et de l\'innovation',
        'dates'      => 'Du 10 au 12 décembre 2026',
        'lieu'       => 'Palais de la Culture et des Arts, N\'Djamena',
        'date_limite_inscription' => '2026-12-05', // AAAA-MM-JJ, vide = pas de limite
    ],

    // --- Ministère (repris du site jeunesse.gouv.td) -----------------------
    'ministere' => [
        'pays'      => 'République du Tchad',
        'devise'    => 'Unité – Travail – Progrès',
        'nom'       => 'Ministère de la Jeunesse et des Sports',
        'site'      => 'https://jeunesse.gouv.td',
        'telephone' => '+235 22 52 73 29',
        'email'     => 'contact@jeunesse.gouv.td',
        'adresse'   => 'N\'Djamena, Tchad',
        'facebook'  => 'https://www.facebook.com/mjspetchad/',
        // Remplacez assets/img/logo.svg par le logo officiel (PNG ou SVG).
        'logo'      => 'assets/img/logo.svg',
    ],

    // --- Sécurité -----------------------------------------------------------
    // Clé secrète servant à signer les QR codes des badges. À CHANGER en
    // production (64 caractères aléatoires) : `php -r "echo bin2hex(random_bytes(32));"`
    'secret' => getenv('FNJ_SECRET') ?: 'changez-moi-cle-secrete-fnj2026-0123456789abcdef',

    // Comptes de l'espace d'administration.
    //  - role "admin"  : liste, validation, export, contrôle d'accès
    //  - role "agent"  : uniquement le contrôle d'accès (scanner des badges)
    // En production, remplacez les mots de passe par défaut par un hash :
    //   php -r "echo password_hash('MotDePasse', PASSWORD_DEFAULT);"
    // puis 'hash' => '$2y$10$...'  (ou variables FNJ_ADMIN_HASH / FNJ_AGENT_HASH).
    'utilisateurs' => [
        'admin'    => getenv('FNJ_ADMIN_HASH')
            ? ['role' => 'admin', 'hash' => getenv('FNJ_ADMIN_HASH')]
            : ['role' => 'admin', 'mot_de_passe' => 'fnj2026admin'],
        'controle' => getenv('FNJ_AGENT_HASH')
            ? ['role' => 'agent', 'hash' => getenv('FNJ_AGENT_HASH')]
            : ['role' => 'agent', 'mot_de_passe' => 'fnj2026controle'],
    ],

    // Catégories dont le badge doit être validé par un administrateur avant
    // d'être actif (ex. ['presse', 'officiel']). Vide = badge immédiat.
    'validation_requise' => [],

    // --- Stockage -----------------------------------------------------------
    // En ligne (Vercel) : définir la variable DATABASE_URL (PostgreSQL, ex. Neon).
    // Sans DATABASE_URL, une base SQLite locale est utilisée (développement).
    'db_path'     => __DIR__ . '/data/fnj2026.sqlite',
    'photo_max_mo' => 4, // les fonctions Vercel acceptent 4,5 Mo par requête au maximum

    // --- E-mail de confirmation (fonction mail() de PHP, indisponible sur Vercel)
    'email' => [
        'actif'      => false,
        'expediteur' => 'no-reply@jeunesse.gouv.td',
    ],

    // URL publique de l'application (sans / final), utilisée dans les QR codes.
    // Sur Vercel : variable FNJ_BASE_URL = https://fnj2026.vercel.app
    'base_url' => getenv('FNJ_BASE_URL') ?: '',
];
