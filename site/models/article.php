<?php

/**
 * Page model pour les pages "article"
 * Ajoute une méthode pour récupérer le layout avec les fichiers résolus
 * et les images au format responsive (srcset WebP + fallback).
 */
class ArticlePage extends Page
{
    /**
     * Retourne le layout avec les images résolues au format responsive.
     * Chaque image passe par $file->historiaImage('column') pour obtenir
     * fallback + WebP + AVIF srcset au lieu de l'URL brute du fichier original.
     *
     * Utilisable dans KQL : page.layoutWithResolvedFiles
     *
     * @kql-allowed
     */
    public function layoutWithResolvedFiles(): array
    {
        $layouts = $this->layout()->toLayouts();
        $result = [];

        foreach ($layouts as $layout) {
            $row = [
                'id' => $layout->id(),
                'columns' => []
            ];

            foreach ($layout->columns() as $column) {
                $col = [
                    'id' => $column->id(),
                    'width' => $column->width(),
                    'blocks' => []
                ];

                foreach ($column->blocks() as $block) {
                    $blockData = [
                        'id' => $block->id(),
                        'type' => $block->type(),
                        'isHidden' => $block->isHidden(),
                        'content' => []
                    ];

                    // Traiter selon le type de bloc
                    switch ($block->type()) {
                        case 'heading':
                            $blockData['content'] = [
                                'level' => $block->level()->value(),
                                'text' => $block->text()->value()
                            ];
                            break;

                        case 'text':
                            $blockData['content'] = [
                                'text' => $block->text()->toBlocks()->toHtml()
                            ];
                            break;

                        case 'image':
                            $image = $block->image()->toFile();
                            $blockData['content'] = [
                                // Image responsive : fallback + WebP + AVIF srcset
                                // au lieu de l'URL brute (fichiers originaux souvent 5–12 Mo)
                                'image' => $image
                                    ? $image->historiaImage('column')
                                    : null,
                                'caption' => $block->caption()->value(),
                                'alt' => $block->alt()->value()
                            ];
                            // Surcharger le alt si renseigné au niveau du bloc
                            if ($blockData['content']['image'] && $block->alt()->isNotEmpty()) {
                                $blockData['content']['image']['alt'] = $block->alt()->value();
                            }
                            break;

                        case 'gallery':
                            $images = [];
                            foreach ($block->images()->toFiles() as $img) {
                                // Chaque image de galerie au format responsive
                                $images[] = $img->historiaImage('column');
                            }
                            $blockData['content'] = [
                                'images' => $images,
                                'caption' => $block->caption()->value()
                            ];
                            break;

                        case 'quote':
                            $blockData['content'] = [
                                'text' => $block->text()->value(),
                                'citation' => $block->citation()->value()
                            ];
                            break;

                        default:
                            // Pour les autres types de blocs, on garde le contenu brut
                            $blockData['content'] = $block->content()->toArray();
                            break;
                    }

                    $col['blocks'][] = $blockData;
                }

                $row['columns'][] = $col;
            }

            $result[] = $row;
        }

        return $result;
    }
}
