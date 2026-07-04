<?php

return [
  'debug' => true,
  'panel' => [
    'install' => false,
  ],
  'api' => [
    'basicAuth' => false,        // Disable API auth for local dev
    'allowInsecure' => true      // Allow HTTP
  ],

  // SMTP (configurable via .env)
  'email' => [
    'transport' => [
      'type'       => 'smtp',
      'host'       => $_ENV['SMTP_HOST'] ?? 'mail.infomaniak.com',
      'port'       => (int) ($_ENV['SMTP_PORT'] ?? 587),
      'security'   => $_ENV['SMTP_SECURITY'] ?? 'tls',
      'auth'       => true,
      'username'   => $_ENV['SMTP_USERNAME'] ?? '',
      'password'   => $_ENV['SMTP_PASSWORD'] ?? '',
    ],
  ],

  // Contact form
  'cjcfe.contact.to'   => $_ENV['CONTACT_TO'] ?? 'contact@cjcfe.fr',
  'cjcfe.contact.from' => $_ENV['SMTP_USERNAME'] ?? 'noreply@cjcfe.fr',

  'thumbs' => [
    'driver' => 'gd',
  ],
  'kql' => [
    'auth' => false,             // Allow KQL without login
    'methods' => [
      // Liste des méthodes explicitement autorisées en KQL
      // Format : 'namespace\class::method' (insensible à la casse)
      // Note: les page models custom (LieuPage, etc.) sont aussi couverts
      // grâce au @kql-allowed docblock sur les closures du plugin.
      'allowed' => [
        'kirby\cms\file::historiaimage',
        'kirby\cms\file::devurl',
        'kirby\cms\file::resize',
        'kirby\cms\file::thumb',
        'kirby\cms\file::focus',
        'kirby\cms\page::responsiveimage',
        'kirby\cms\page::layoutswithimages',
        'kirby\cms\page::seo',
        'kirby\cms\site::seodefaults',
        // Page models custom (sans namespace → nom de classe brut)
        'lieupage::responsiveimage',
        'articlepage::layoutwithresolvedfiles',
        'articlepage::seo',
      ]
    ]
  ],
  // ─── Presets images responsive (plugin historia/images) ─────────
  // Chaque preset définit : max (largeur max en px), widths (breakpoints srcset),
  // fallbackQuality (jpeg/png), webpQuality, avifQuality, defaultSizes (attribut sizes).
  // On ne sert JAMAIS le fichier original (souvent 5–12 Mo) au frontend.
  'historia.images.presets' => [
    'cover' => [
      'max'              => 2500,
      'widths'           => [640, 960, 1280, 1600, 2000, 2500],
      'fallbackQuality'  => 82,
      'webpQuality'      => 75,
      'defaultSizes'     => '(min-width: 2500px) 2500px, 100vw',
    ],
    'podcast' => [
      'max'              => 480,
      'widths'           => [240, 480],
      'fallbackQuality'  => 82,
      'webpQuality'      => 75,
      'defaultSizes'     => '240px',
    ],
    'column' => [
      'max'              => 800,
      'widths'           => [320, 480, 640, 800],
      'fallbackQuality'  => 82,
      'webpQuality'      => 75,
      'defaultSizes'     => '(min-width: 1024px) 800px, 100vw',
    ],
  ],
];
