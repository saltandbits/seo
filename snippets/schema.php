<?php
	// Title
	if ($page->seotitle()->isNotEmpty()) {
	  $schemaTitle = $page->seotitle()->html();
	} elseif ($page->isHomePage()) {
	  $schemaTitle = $site->title()->html();
	} else {
	  $schemaTitle = $page->title()->html() . ' | ' . $site->title()->html();
	}

	// Description
	if ($page->seodescription()->isNotEmpty()) {
	  $schemaDescription = $page->seodescription()->html();
	} else {
	  $schemaDescription = $site->seodescription()->html();
	}

	// Image
	$schemaImage = null;
	if ($page->seoimage()->isNotEmpty()) {
	  $schemaImage = $page->seoimage()->toFile()->crop(1200, 630)->url();
	} elseif ($site->seoimage()->isNotEmpty()) {
	  $schemaImage = $site->seoimage()->toFile()->crop(1200, 630)->url();
	}

	// Url
	$schemaUrl = $page->url();

	// Language
	$schemaLanguage = $kirby->language()->code();
?>

<?php if ($page->isHomePage()): ?>
	<script type="application/ld+json">
		{
		  "@context": "https://schema.org",
		  "@type": "WebSite",
		  "@id": "<?= $site->url() ?>#website",
		  "url": "<?= $site->url() ?>",
		  "name": "<?= $site->title()->html() ?>",
		  "inLanguage": "<?= $schemaLanguage ?>"
		}
	</script>
<?php endif ?>
<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "WebPage",
	  "@id": "<?= $schemaUrl ?>#webpage",
	  "url": "<?= $schemaUrl ?>",
	  "name": "<?= $schemaTitle ?>",
	  "description": "<?= $schemaDescription ?>",
	  "inLanguage": "<?= $schemaLanguage ?>",
	  <?php if ($schemaImage): ?>
	  "primaryImageOfPage": {
	    "@type": "ImageObject",
	    "url": "<?= $schemaImage ?>"
	  },
	  <?php endif ?>
	  "dateModified": "<?= $page->modified('c') ?>"
	}
</script>