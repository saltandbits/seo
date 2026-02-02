# SEO
Simple SEO plugin for Kirby 5.
No framework dependencies and other composer or build faff.
Predefined panel fields with SERP preview.
Adds humans.txt, robots.txt, site.webmanifest, sitemap.xml, all major meta tags and structured data.

## instalation
Download and copy this repository to `/site/plugins/seo.`

## panel
Add tabs to your panel pages:
```yaml
# site/blueprints/site.yml
tabs:
  seo: seosite

# site/blueprints/page.yml
tabs:
  seo: seopage
```

## template
Add metadata and structured data to your templates or snippets:
```php
<?php snippet('meta') ?>
<?php snippet('schema') ?>
```

## config
Change default values in your config file:
```php
return[
  'saltandbits.seo' => [
    'author' => 'Salt&Bits',
    'authorUrl' => 'https://www.saltandbits.com',
    'color' => '#ffffff',
    'colorScheme' => 'light only',
  ],
]
```