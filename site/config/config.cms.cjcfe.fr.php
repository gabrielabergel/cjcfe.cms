<?php

/**
 * Configuration de production pour cms.cjcfe.fr
 * Ce fichier est chargé automatiquement par Kirby quand le domaine correspond
 */

return [
  'debug' => false,
  'api' => [
    'basicAuth' => false,
    'allowInsecure' => false,  // Force HTTPS en production
  ],
  'kql' => [
    'methods' => [
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
        'lieupage::responsiveimage',
        'articlepage::layoutwithresolvedfiles',
        'articlepage::seo',
      ]
    ]
  ],
];
