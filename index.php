<?php

Kirby::plugin('saltandbits/seo', [
  'blueprints' => [
    'seosite' => __DIR__ . '/blueprints/sections/site.yml',
    'seopage' => __DIR__ . '/blueprints/sections/page.yml',
  ],
  'snippets' => [
    'meta' => __DIR__ . '/snippets/meta.php',
    'schema' => __DIR__ . '/snippets/schema.php',
  ],
  'sections' => [
    'seo' => [
      'computed' => [
        'isSite' => function () {
          return $this->model() instanceof Kirby\Cms\Site;
        },
        'isHome' => function () {
          return $this->model()->isHomePage();
        },
        'siteTitle' => function(){
          return site()->title()->value();
        },
        'siteDescription' => function(){
          return site()->seodescription()->value();
        },
        'siteImage' => function(){
          if ($image = site()->seoimage()->toFile()) {
            return $image->thumb([
              'width' => 1200,
              'height' => 630,
              'crop' => true,
            ])->url();
          }

          return null;
        },
        'siteUrl' => function(){
          return site()->url();
        },
        'pageTitle' => function(){
          return $this->model()->title()->value();
        },
        'pageUrl' => function(){
          return $this->model()->url();
        }
      ]
    ]
  ],
  'routes' => [
    // SITEMAP.XML
    [
      'pattern' => 'sitemap.xml',
      'method' => 'GET',
      'action' => function () {
        $xml = new SimpleXMLElement(
          '<?xml version="1.0" encoding="UTF-8"?><urlset/>'
        );
        $xml->addAttribute(
          'xmlns',
          'http://www.sitemaps.org/schemas/sitemap/0.9'
        );
        $xml->addAttribute(
          'xmlns:image',
          'http://www.google.com/schemas/sitemap-image/1.1'
        );
        $languages = kirby()->languages();
        $isMultilang = kirby()->multilang();

        foreach (site()->pages()->index() as $page) {
          foreach ($languages as $language) {
            if ($isMultilang && !$page->translation($language->code())->exists()) {
              continue;
            }

            $getContent = function ($p) use ($isMultilang, $language) {
              return $isMultilang
                ? $p->content($language->code())
                : $p->content();
            };

            // Recursively determine if a page should be included
            $shouldInclude = function ($p) use (&$shouldInclude, $getContent) {
              $content = $getContent($p);
              $robots = $content->has('seorobots')
                ? strtolower(trim($content->seorobots()->value()))
                : '';

              // Explicit "noindex" on this page — always exclude, no override possible
              if (str_contains($robots, 'noindex')) {
                return false;
              }

              // Check parent — blocking conditions bubble down unconditionally
              $parent = $p->parent();
              if ($parent) {
                $parentContent = $getContent($parent);
                $parentRobots = $parentContent->has('seorobots')
                  ? strtolower(trim($parentContent->seorobots()->value()))
                  : '';

                // Parent "noindex" cannot be overruled by any child
                if (str_contains($parentRobots, 'noindex')) {
                  return false;
                }

                // Unlisted parent with no explicit "index" blocks children
                // unless this child has explicit "index"
                if (!$parent->isListed() && $parentRobots !== 'index') {
                  if ($robots !== 'index') {
                    return false;
                  }
                }

                // Recurse further up the tree
                if (!$shouldInclude($parent)) {
                  return false;
                }
              }

              // Explicit "index" on this page — include regardless of listed status
              if ($robots === 'index') {
                return true;
              }

              // No explicit value — fall back to listed status
              return $p->isListed();
            };

            if (!$shouldInclude($page)) {
              continue;
            }

            $url = $xml->addChild('url');
            $url->addChild('loc', html($page->url($language->code())));
            $url->addChild('lastmod', $page->modified('c'));
            foreach ($page->images() as $image) {
              $imageNode = $url->addChild(
                'image:image',
                null,
                'http://www.google.com/schemas/sitemap-image/1.1'
              );
              $imageNode->addChild(
                'image:loc',
                html($image->url()),
                'http://www.google.com/schemas/sitemap-image/1.1'
              );
            }
          }
        }

        return new Response(
          $xml->asXML(),
          'application/xml'
        );
      }
    ],
    // ROBOTS.TXT
    [
      'pattern' => 'robots.txt',
      'method' => 'GET',
      'action' => function () {

        $lines = [];

        // Default rules
        $lines[] = 'User-agent: *';
        $lines[] = 'Disallow: /kirby';
        $lines[] = 'Disallow: /site';
        $lines[] = 'Disallow: /panel';
        $lines[] = 'Allow: /media';
        $lines[] = '';

        // Block AI crawlers
        $allowAiCrawlers = site()->aicrawlers()->value() === 'index';

        if ($allowAiCrawlers === false) {
          $aiBots = [
            'GPTBot',
            'CCBot',
            'anthropic-ai',
            'ClaudeBot',
            'Google-Extended',
            'FacebookBot',
            'Amazonbot',
            'Applebot-Extended',
            'Bytespider',
            'Diffbot',
            'OAI-SearchBot',
            'PerplexityBot'
          ];

          foreach ($aiBots as $bot) {
            $lines[] = 'User-agent: ' . $bot;
            $lines[] = 'Disallow: /';
            $lines[] = '';
          }
        }

        // Sitemap
        $lines[] = 'Sitemap: ' . url('sitemap.xml');

        return new Kirby\Http\Response(
          implode(PHP_EOL, $lines),
          'text/plain'
        );
      }
    ],
    // HUMANS.TXT
    [
      'pattern' => 'humans.txt',
      'method'  => 'GET',
      'action'  => function () {
        $humans = 'Creator: ' . option('saltandbits.seo.author') . PHP_EOL;
        $humans .= 'URL: ' . option('saltandbits.seo.authorUrl') . PHP_EOL;
        return kirby()
          ->response()
          ->type('text')
          ->body($humans);
      }
    ],
    // MANIFEST
    [
      'pattern' => 'site.webmanifest',
      'method'  => 'GET',
      'action'  => function () {

        $manifest = [
          'name' => site()->title()->value(),
          'short_name' => site()->title()->value(),
          'start_url' => '/',
          'scope' => '/',
          'display' => 'fullscreen',
          'background_color' => option('saltandbits.seo.color', '#ffffff'),
          'theme_color' => option('saltandbits.seo.color', '#ffffff'),
          'icons' => [
            [
              'src' => url('assets/images/favicons/favicon.png'),
              'type' => 'image/png',
              'sizes' => '96x96'
            ],
            [
              'src' => url('assets/images/favicons/apple-touch-icon.png'),
              'type' => 'image/png',
              'sizes' => '180x180'
            ]
          ]
        ];

        return kirby()
          ->response()
          ->type('application/manifest+json')
          ->body(json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
      }
    ]
  ],
]);