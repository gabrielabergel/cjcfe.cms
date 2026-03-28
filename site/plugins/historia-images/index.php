<?php

/**
 * Plugin Historia Images
 * 
 * Système d'images responsive : génère des variantes optimisées (fallback JPEG/PNG + WebP)
 * pour chaque preset d'image. Ne retourne JAMAIS l'URL originale du fichier brut
 * (souvent 5–12 Mo) ; toujours un thumb au format max du preset.
 *
 * Presets configurables dans config.php via `historia.images.presets.*`
 *
 * Usage KQL :  page.responsiveImage("cover", "cover")
 * Usage PHP :  $file->historiaImage('cover')
 */

// ─── Constantes par défaut des presets ──────────────────────────────
const HISTORIA_IMAGE_DEFAULTS = [
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
];


Kirby::plugin('historia/images', [

    // NOTE: Le composant thumb custom a été retiré pour permettre à file.resize()
    // de fonctionner correctement via KQL. Kirby utilise maintenant son système
    // de thumbs natif.

    'fileMethods' => [

        // Retourne l'URL de l'image (compatible développement)
        // En mode debug, retourne l'URL vers /content/ au lieu de /media/
        'devUrl' => /** @kql-allowed */ function (): string {
            if (kirby()->option('debug', false)) {
                return kirby()->url() . '/content/' . $this->parent()->diruri() . '/' . $this->filename();
            }
            return $this->url();
        },

        // Génère la structure responsive d'une image pour un preset donné.
        // @param string $preset  Nom du preset : 'cover', 'podcast', 'column'
        // @param array  $overrides  Surcharges ponctuelles (ex: ['defaultSizes' => '50vw'])
        // @return array  Structure JSON-ready pour le frontend <picture>
        'historiaImage' => /** @kql-allowed */ function (string $preset = 'column', array $overrides = []): array {

            // ── 1. Lire la config du preset ─────────────────────────────
            $config = array_merge(
                HISTORIA_IMAGE_DEFAULTS[$preset] ?? HISTORIA_IMAGE_DEFAULTS['column'],
                kirby()->option("historia.images.presets.{$preset}", []),
                $overrides
            );

            $maxWidth         = (int) $config['max'];
            $widths           = (array) $config['widths'];
            $fallbackQuality  = (int) $config['fallbackQuality'];
            $webpQuality      = (int) $config['webpQuality'];
            $defaultSizes     = (string) $config['defaultSizes'];

            // ── 2. Métadonnées de base ──────────────────────────────────
            $alt            = $this->alt()->value() ?? '';
            $originalWidth  = $this->width();
            $originalHeight = $this->height();
            $mime           = $this->mime();

            // ── 3. Fichiers non redimensionnables (SVG, GIF, PDF) ───────
            $nonResizable = ['image/svg+xml', 'image/gif', 'application/pdf'];

            if (in_array($mime, $nonResizable)) {
                // En mode debug, utiliser l'URL vers /content/
                $fileUrl = $this->url();
                if (kirby()->option('debug', false)) {
                    $fileUrl = kirby()->url() . '/content/' . $this->parent()->diruri() . '/' . $this->filename();
                }
                return [
                    'alt'      => $alt,
                    'sizes'    => $defaultSizes,
                    'original' => [
                        'width'  => $originalWidth,
                        'height' => $originalHeight,
                        'mime'   => $mime,
                    ],
                    'fallback' => [
                        'src'    => $fileUrl,
                        'width'  => $originalWidth,
                        'height' => $originalHeight,
                    ],
                ];
            }

            // ── 4. Filtrer les largeurs > original ──────────────────────
            $widths = array_values(array_filter($widths, fn($w) => $w <= $originalWidth));

            // Si l'image est plus petite que tous les breakpoints,
            // on utilise sa largeur native comme unique variante
            if (empty($widths)) {
                $widths = [$originalWidth];
            }

            // Toujours trier
            sort($widths);

            // Largeur du fallback = plus grande variante disponible
            $fallbackWidth = max($widths);

            // ── 5. En mode debug, utiliser l'image originale depuis /content/ ───────────
            // (évite les problèmes de génération des thumbs avec le serveur PHP intégré)
            if (kirby()->option('debug', false)) {
                // Construire l'URL vers le fichier dans /content/
                $contentUrl = kirby()->url() . '/content/' . $this->parent()->diruri() . '/' . $this->filename();
                return [
                    'alt'      => $alt,
                    'sizes'    => $defaultSizes,
                    'original' => [
                        'width'  => $originalWidth,
                        'height' => $originalHeight,
                        'mime'   => $mime,
                    ],
                    'fallback' => [
                        'src'    => $contentUrl,
                        'width'  => $originalWidth,
                        'height' => $originalHeight,
                    ],
                ];
            }

            // ── 6. Thumb fallback (JPEG/PNG selon l'original) ───────────
            $fallbackThumb = $this->thumb([
                'width'   => $fallbackWidth,
                'quality' => $fallbackQuality,
            ]);

            // Calculer la hauteur proportionnelle
            $ratio          = $originalWidth > 0 ? $originalHeight / $originalWidth : 1;
            $fallbackHeight = (int) round($fallbackWidth * $ratio);

            // ── 7. Fallback srcset ──────────────────────────────────────
            $fallbackSrcset = [];
            foreach ($widths as $w) {
                $thumb = $this->thumb([
                    'width'   => $w,
                    'quality' => $fallbackQuality,
                ]);
                $fallbackSrcset[] = $thumb->url() . ' ' . $w . 'w';
            }

            // ── 8. WebP srcset ──────────────────────────────────────────
            $webpSrcset = [];
            foreach ($widths as $w) {
                $thumb = $this->thumb([
                    'width'   => $w,
                    'format'  => 'webp',
                    'quality' => $webpQuality,
                ]);
                $webpSrcset[] = $thumb->url() . ' ' . $w . 'w';
            }

            // ── 9. Construire le résultat (JPEG/PNG + WebP, pas d'AVIF) ──
            return [
                'alt'      => $alt,
                'sizes'    => $defaultSizes,
                'original' => [
                    'width'  => $originalWidth,
                    'height' => $originalHeight,
                    'mime'   => $mime,
                ],
                'fallback' => [
                    'src'    => $fallbackThumb->url(),
                    'srcset' => implode(', ', $fallbackSrcset),
                    'width'  => $fallbackWidth,
                    'height' => $fallbackHeight,
                ],
                'webp' => [
                    'srcset' => implode(', ', $webpSrcset),
                ],
            ];
        },
    ],

    // ─── Méthode sur les pages : wrapper null-safe pour KQL ─────────
    'pageMethods' => [

        // Retourne l'image responsive d'un champ fichier de la page.
        // Gère le cas où le fichier n'existe pas (retourne null au lieu de casser).
        // Usage KQL : page.responsiveImage("cover", "cover")
        //             page.responsiveImage("imagepodcast", "podcast")
        'responsiveImage' => /** @kql-allowed */ function (string $field, string $preset = 'column'): ?array {
            $file = $this->content()->get($field)->toFile();
            return $file ? $file->historiaImage($preset) : null;
        },

        // Retourne les layouts avec les images des blocks résolues en URLs
        // Usage KQL : page.layoutsWithImages("layout", "column")
        'layoutsWithImages' => /** @kql-allowed */ function (string $field, string $preset = 'column'): array {
            $layouts = $this->content()->get($field)->toLayouts();
            $result = [];

            foreach ($layouts as $layout) {
                $layoutData = [
                    'id' => $layout->id(),
                    'attrs' => $layout->attrs()->toArray(),
                    'columns' => [],
                ];

                foreach ($layout->columns() as $column) {
                    $columnData = [
                        'id' => $column->id(),
                        'width' => $column->width(),
                        'blocks' => [],
                    ];

                    foreach ($column->blocks() as $block) {
                        $blockData = [
                            'id' => $block->id(),
                            'type' => $block->type(),
                            'isHidden' => $block->isHidden(),
                            'content' => $block->content()->toArray(),
                        ];

                        // Si c'est un block image, résoudre l'URL
                        if ($block->type() === 'image') {
                            $imageField = $block->image();
                            if ($imageField && $imageField->isNotEmpty()) {
                                $file = $imageField->toFile();
                                if ($file) {
                                    $blockData['content']['imageData'] = $file->historiaImage($preset);
                                }
                            }
                        }

                        // Si c'est un block gallery, résoudre les URLs
                        if ($block->type() === 'gallery') {
                            $imagesField = $block->images();
                            if ($imagesField && $imagesField->isNotEmpty()) {
                                $files = $imagesField->toFiles();
                                $imagesData = [];
                                foreach ($files as $file) {
                                    $imagesData[] = $file->historiaImage($preset);
                                }
                                $blockData['content']['imagesData'] = $imagesData;
                            }
                        }

                        $columnData['blocks'][] = $blockData;
                    }

                    $layoutData['columns'][] = $columnData;
                }

                $result[] = $layoutData;
            }

            return $result;
        },
    ],
]);
