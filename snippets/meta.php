<?php
	// Title
	if ($page->seotitle()->isNotEmpty()) {
	  $seoTitle = $page->seotitle()->html();
	} elseif ($page->isHomePage()) {
	  $seoTitle = $site->title()->html();
	} else {
	  $seoTitle = $page->title()->html() . ' | ' . $site->title()->html();
	}

	// Description
	if ($page->seodescription()->isNotEmpty()) {
	  $seoDescription = $page->seodescription()->html();
	} else {
	  $seoDescription = $site->seodescription()->html();
	}

	// Image
	$seoImage = null;
	if ($page->seoimage()->isNotEmpty()) {
	  $seoImage = $page->seoimage()->toFile();
	} elseif ($site->seoimage()->isNotEmpty()) {
	  $seoImage = $site->seoimage()->toFile();
	}
?>

<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="format-detection" content="telephone=no">
<meta name="theme-color" content="<?= option('saltandbits.seo.color') ?>">
<meta name="color-scheme" content="<?= option('saltandbits.seo.colorScheme') ?>">

<title><?= $seoTitle ?></title>
<meta name="description" content="<?= $seoDescription ?>">

<meta property="og:title" content="<?= $seoTitle ?>">
<meta property="og:description" content="<?= $seoDescription ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $page->url() ?>">
<meta property="og:site_name" content="<?= $site->title()->html() ?>">
<meta property="og:locale" content="<?= $kirby->language()->locale(LC_ALL) ?>">

<?php foreach ($kirby->languages() as $language): ?>
  <?php if ($language->code() !== $kirby->language()->code()): ?>
    <meta property="og:locale:alternate" content="<?= $language->locale(LC_ALL) ?>">
  <?php endif ?>
<?php endforeach ?>

<?php if ($seoImage): ?>
  <meta property="og:image" content="<?= $seoImage->crop(1200, 630)->url() ?>">
<?php endif ?>


<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $seoTitle ?>">
<meta name="twitter:description" content="<?= $seoDescription ?>">
<meta name="twitter:url" content="<?= $page->url() ?>">

<?php if ($seoImage): ?>
  <meta name="twitter:image" content="<?= $seoImage->crop(1200, 630)->url() ?>">
<?php endif ?>

<link rel="canonical" href="<?= $page->url($kirby->language()->code()) ?>">
<link rel="alternate" hreflang="x-default" href="<?= $kirby->languages()->default()->url() ?>">

<?php foreach ($kirby->languages() as $language): ?>
  <?php if ($language->code() !== $kirby->language()->code()): ?>
    <link rel="alternate" hreflang="<?= $language->code() ?>" href="<?= $page->url($language->code()) ?>" >
  <?php endif ?>
<?php endforeach ?>

<link rel="manifest" href="<?= $kirby->url() ?>/site.webmanifest">
<meta name="robots" content="<?= $page->seorobots()->value() === 'noindex' ? 'noindex, nofollow' : 'index, follow' ?>">
<link rel="author" type="text/plain" href="humans.txt">
<meta name="copyright" content="<?= option('saltandbits.seo.author') ?>">
