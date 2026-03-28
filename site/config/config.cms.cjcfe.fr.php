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
];
