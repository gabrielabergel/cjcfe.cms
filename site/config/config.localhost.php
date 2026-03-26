<?php

return [
  'debug' => true,
  'api' => [
    'basicAuth' => false,
    'allowInsecure' => true,
  ],
  'kql' => [
    'auth' => false,
    'methods' => [
      'allowed' => [
        'Kirby\\Cms\\Layouts::toHtml',
        'Kirby\\Cms\\Layout::toHtml',
        'Kirby\\Cms\\LayoutColumns::toHtml',
        'Kirby\\Cms\\LayoutColumn::toHtml',
        'Kirby\\Cms\\Blocks::toHtml',
        'Kirby\\Cms\\Block::toHtml',
      ]
    ]
  ],
  // Désactiver les jobs asynchrones pour les thumbs (problème avec serveur PHP intégré)
  'thumbs' => [
    'driver' => 'gd',
    'quality' => 80,
    'async' => false,
  ],
];
