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

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $urlset = $dom->createElementNS(
          'http://www.sitemaps.org/schemas/sitemap/0.9',
          'urlset'
        );
        $urlset->setAttributeNS(
          'http://www.w3.org/2000/xmlns/',
          'xmlns:image',
          'http://www.google.com/schemas/sitemap-image/1.1'
        );
        $urlset->setAttributeNS(
          'http://www.w3.org/2000/xmlns/',
          'xmlns:xhtml',
          'http://www.w3.org/1999/xhtml'
        );
        $dom->appendChild($urlset);

        $isMultilang = kirby()->multilang();
        $languages = $isMultilang ? kirby()->languages() : [];

        $pages = site()->index();

        foreach ($pages as $page) {

          $availableLanguages = [];

          if ($isMultilang) {
            foreach ($languages as $language) {
              if (!$page->translation($language->code())->exists()) {
                continue;
              }
              $content = $page->content($language->code());
              if (
                $content->has('seorobots') &&
                str_contains(strtolower($content->seorobots()->value()), 'noindex')
              ) {
                continue;
              }
              $availableLanguages[] = $language;
            }
          } else {
            $content = $page->content();
            if (
              $content->has('seorobots') &&
              str_contains(strtolower($content->seorobots()->value()), 'noindex')
            ) {
              continue;
            }
          }

          $urlLanguages = $isMultilang  ? $availableLanguages : [null];
          foreach ($urlLanguages as $language) {

            $url = $dom->createElement('url');
            $urlset->appendChild($url);

            $loc = $dom->createElement('loc', $isMultilang ? $page->url($language->code()) : $page->url() );
            $url->appendChild($loc);

            $lastmod = $dom->createElement('lastmod', $page->modified('c'));
            $url->appendChild($lastmod);

            if ($isMultilang && count($availableLanguages) > 1) {
              foreach ($availableLanguages as $altLanguage) {

                $link = $dom->createElementNS(
                  'http://www.w3.org/1999/xhtml',
                  'xhtml:link'
                );

                $link->setAttribute('rel', 'alternate');
                $link->setAttribute('hreflang', $altLanguage->code());

                $link->setAttribute(
                  'href',
                  $page->url($altLanguage->code())
                );

                $url->appendChild($link);

                // x-default for default language
                if ($altLanguage->isDefault()) {

                  $defaultLink = $dom->createElementNS(
                    'http://www.w3.org/1999/xhtml',
                    'xhtml:link'
                  );

                  $defaultLink->setAttribute('rel', 'alternate');
                  $defaultLink->setAttribute('hreflang', 'x-default');

                  $defaultLink->setAttribute(
                    'href',
                    $page->url($altLanguage->code())
                  );

                  $url->appendChild($defaultLink);
                }
              }
            }

            // images
            foreach ($page->images() as $image) {
              $imageNode = $dom->createElementNS(
                'http://www.google.com/schemas/sitemap-image/1.1',
                'image:image'
              );
              $url->appendChild($imageNode);

              $imageLoc = $dom->createElementNS(
                'http://www.google.com/schemas/sitemap-image/1.1',
                'image:loc',
                $image->url()
              );
              $imageNode->appendChild($imageLoc);
            }
          }
        }

        $xsl = $dom->createProcessingInstruction(
          'xml-stylesheet',
          'type="text/xsl" href="' . url('sitemap.xsl') . '"'
        );
        $dom->insertBefore($xsl, $urlset);

        return new Kirby\Http\Response(
          $dom->saveXML(),
          'application/xml; charset=utf-8'
        );
      }
    ],
    [
      'pattern' => 'sitemap.xsl',
      'method' => 'GET',
      'action' => function () {

        $xsl = '<?xml version="1.0" encoding="UTF-8"?>
        <xsl:stylesheet version="1.0"
          xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
          xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
          xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
          exclude-result-prefixes="sm image">

          <xsl:output method="html" encoding="UTF-8" indent="yes"/>

          <xsl:template match="/">
            <html lang="en">
              <head>
                <meta charset="UTF-8"/>
                <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                <title>Sitemap — ' . site()->title()->value() . '</title>
                <style>
                  * { box-sizing: border-box; margin: 0; padding: 0; }
                  body { font-family: system-ui, sans-serif; font-size: 14px; color: #000; padding: 5dvw; }
                  h1 { font-size: 1.4rem; margin-bottom: 0.25rem; }
                  table { width: 100%; border-collapse: collapse; background: white; overflow: hidden; max-width: 1200px; margin: auto; border: 1px solid #eee; }
                  th { text-align: left; padding: 0.75rem 1rem; text-transform: uppercase; letter-spacing: 0.05em; font-size: 12px; }
                  td { padding: 0.65rem 1rem; border-top: 1px solid #eee; vertical-align: middle; transition: all 0.3s ease; }
                  tr:hover td { background: #fafafa; }
                  a { color: #000; text-decoration: none; font-size: 14px; }
                  a:hover { text-decoration: underline; }
                  .lastmod { color: #999; white-space: nowrap; font-size: 14px; }
                  .right{ text-align: right; }
                </style>
              </head>
              <body>
                <table>
                  <thead>
                    <tr>
                      <th>URL</th>
                      <th class="right">Last modified</th>
                    </tr>
                  </thead>
                  <tbody>
                    <xsl:for-each select="sm:urlset/sm:url">
                      <xsl:sort select="sm:loc"/>
                      <tr>
                        <td><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
                        <td class="lastmod right"><xsl:value-of select="sm:lastmod"/></td>
                      </tr>
                    </xsl:for-each>
                  </tbody>
                </table>
              </body>
            </html>
          </xsl:template>
        </xsl:stylesheet>';

        return new Kirby\Http\Response($xsl, 'application/xslt+xml');
      }
    ],
    // ROBOTS.TXT
    [
      'pattern' => 'robots.txt',
      'method' => 'GET',
      'action' => function () {

        $lines = [];
        $lines[] = "User-agent: *\n";
        $lines[] = 'Allow: /';
        $lines[] = 'Disallow: /kirby/';
        $lines[] = 'Disallow: /site/';
        $lines[] = "\nDisallow: /panel/";
        $lines[] = 'Allow: /media/';
        $lines[] = '';
        $lines[] = "\n\nSitemap: " . url('sitemap.xml');
        $lines[] = '';

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