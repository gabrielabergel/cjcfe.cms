<?php

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Toolkit\Str;

function cjcfe_seo_field(Page|Site $model, array $names)
{
    foreach ($names as $name) {
        $field = $model->content()->get($name);

        if ($field->isNotEmpty()) {
            return $field;
        }
    }

    return $model->content()->get($names[0]);
}

function cjcfe_seo_text($field): string
{
    $value = trim((string) $field->value());

    if (
        strlen($value) >= 2 &&
        (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))
    ) {
        return trim(substr($value, 1, -1));
    }

    return $value;
}

function cjcfe_seo_template(string $template, Page $page): string
{
    $site = $page->site();
    $seoTitle = cjcfe_seo_text(cjcfe_seo_field($page, ['seo_title', 'metaTitle']));
    $siteTitle = cjcfe_seo_text(cjcfe_seo_field($site, ['site_title', 'title']));

    $values = [
        '{{ title }}' => $seoTitle !== '' ? $seoTitle : $page->title()->value(),
        '{{ page.title }}' => $page->title()->value(),
        '{{ site.title }}' => $siteTitle,
    ];

    return trim(strtr($template, $values));
}

function cjcfe_seo_bool($field, bool $fallback = true): bool
{
    $value = strtolower(trim((string) $field->value()));

    if ($value === '' || $value === 'default') {
        return $fallback;
    }

    return in_array($value, ['1', 'true', 'yes', 'oui', 'on'], true);
}

function cjcfe_seo_image_url(Page|Site $model): ?string
{
    $file = cjcfe_seo_field($model, ['og_image', 'ogImage'])->toFile();

    if (!$file && $model instanceof Page) {
        $file = cjcfe_seo_field($model->site(), ['og_image', 'ogImage'])->toFile();
    }

    return $file?->url();
}

App::plugin('cjcfe/seo', [
    'siteMethods' => [
        'seoDefaults' => function (): array {
            $siteTitle = cjcfe_seo_text(cjcfe_seo_field($this, ['site_title', 'title']));
            $description = cjcfe_seo_text(cjcfe_seo_field($this, ['seo_description', 'metaDescription']));
            $ogDescription = cjcfe_seo_text(cjcfe_seo_field($this, ['og_description', 'ogDescription']));

            return [
                'siteTitle' => $siteTitle,
                'titleTemplate' => '{{ title }} — ' . $siteTitle,
                'description' => $description,
                'ogDescription' => $ogDescription !== '' ? $ogDescription : $description,
                'ogImage' => cjcfe_seo_image_url($this),
                'robots' => [
                    'index' => cjcfe_seo_bool(cjcfe_seo_field($this, ['seo_index', 'robotsIndex']), true),
                    'follow' => cjcfe_seo_bool(cjcfe_seo_field($this, ['seo_follow', 'robotsFollow']), true),
                    'imageindex' => cjcfe_seo_bool(cjcfe_seo_field($this, ['seo_index_images', 'robotsImageindex']), true),
                ],
            ];
        },
    ],
    'pageMethods' => [
        'seo' => function (): array {
            $site = $this->site();
            $siteTitle = cjcfe_seo_text(cjcfe_seo_field($site, ['site_title', 'title']));
            $seoTitle = cjcfe_seo_text(cjcfe_seo_field($this, ['seo_title', 'metaTitle']));
            $title = $seoTitle !== '' ? $seoTitle : $this->title()->value();

            if ($siteTitle !== '') {
                $title .= ' — ' . $siteTitle;
            }

            $description = cjcfe_seo_text(cjcfe_seo_field($this, ['seo_description', 'metaDescription']));

            if ($description === '') {
                $description = cjcfe_seo_text(cjcfe_seo_field($site, ['seo_description', 'metaDescription']));
            }

            $ogTitle = cjcfe_seo_text(cjcfe_seo_field($this, ['og_title', 'ogTemplate']));

            if ($ogTitle === '') {
                $ogTitle = $seoTitle !== '' ? $seoTitle : $this->title()->value();
            }

            $ogDescription = cjcfe_seo_text(cjcfe_seo_field($this, ['og_description', 'ogDescription']));

            if ($ogDescription === '') {
                $ogDescription = $description;
            }

            $robots = [];
            $defaultIndex = cjcfe_seo_bool(cjcfe_seo_field($site, ['seo_index', 'robotsIndex']), true);
            $defaultFollow = cjcfe_seo_bool(cjcfe_seo_field($site, ['seo_follow', 'robotsFollow']), true);
            $defaultImageindex = cjcfe_seo_bool(cjcfe_seo_field($site, ['seo_index_images', 'robotsImageindex']), true);

            if (!cjcfe_seo_bool(cjcfe_seo_field($this, ['seo_index', 'robotsIndex']), $defaultIndex)) {
                $robots[] = 'noindex';
            }

            if (!cjcfe_seo_bool(cjcfe_seo_field($this, ['seo_follow', 'robotsFollow']), $defaultFollow)) {
                $robots[] = 'nofollow';
            }

            if (!cjcfe_seo_bool(cjcfe_seo_field($this, ['seo_index_images', 'robotsImageindex']), $defaultImageindex)) {
                $robots[] = 'noimageindex';
            }

            return [
                'title' => $title,
                'description' => $description,
                'canonical' => $this->url(),
                'robots' => count($robots) > 0 ? implode(',', $robots) : 'all',
                'og' => [
                    'title' => $ogTitle,
                    'description' => $ogDescription,
                    'image' => cjcfe_seo_image_url($this),
                    'url' => $this->url(),
                    'siteName' => $siteTitle,
                    'type' => 'website',
                ],
            ];
        },
    ],
    'routes' => [
        [
            'pattern' => 'robots.txt',
            'action' => function () {
                $site = site();
                $index = cjcfe_seo_bool(cjcfe_seo_field($site, ['seo_index', 'robotsIndex']), !option('debug'));
                $lines = ['User-agent: *'];
                $lines[] = $index ? 'Allow: /' : 'Disallow: /';
                $lines[] = 'Sitemap: ' . url('sitemap.xml');

                return new Kirby\Cms\Response(implode(PHP_EOL, $lines), 'text/plain');
            },
        ],
        [
            'pattern' => 'sitemap.xml',
            'action' => function () {
                $pages = site()->index()->listed()->filter(fn ($page) => $page->seo()['robots'] !== 'noindex');
                $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
                $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

                foreach ($pages as $page) {
                    $xml[] = '  <url>';
                    $xml[] = '    <loc>' . Str::esc($page->url(), 'xml') . '</loc>';
                    $xml[] = '    <lastmod>' . date('c', $page->modified()) . '</lastmod>';
                    $xml[] = '  </url>';
                }

                $xml[] = '</urlset>';

                return new Kirby\Cms\Response(implode(PHP_EOL, $xml), 'application/xml');
            },
        ],
    ],
]);
