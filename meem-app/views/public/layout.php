<?php use App\Core\{Http, Guard, Seo}; $e = [Http::class, 'e']; $gtm = Guard::setting('gtm_id'); $gsc = Guard::setting('gsc_verification'); $clar = Guard::setting('clarity_id'); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php $__p = Http::path(); $__t = Seo::title($title ?? ''); $__d = Seo::clamp($desc ?? 'Owner-led management for Amazon, Walmart, TikTok Shop, Temu, eBay, Etsy and Shopify stores. Tell me about your store and ask for a quote.', Seo::DESC_MAX); $__idx = in_array($__p, array_keys(Seo::SITEMAP), true) && http_response_code() === 200; ?>
<title><?= Http::e($__t) ?></title>
<meta name="description" content="<?= Http::e($__d) ?>">
<meta name="robots" content="<?= $__idx ? 'index,follow' : 'noindex,follow' ?>">
<link rel="canonical" href="<?= Http::e(Seo::base() . ($__p === '/' ? '/' : $__p)) ?>">
<meta property="og:type" content="website"><meta property="og:site_name" content="Meem Obaidullah"><meta property="og:title" content="<?= Http::e($__t) ?>"><meta property="og:description" content="<?= Http::e($__d) ?>"><meta property="og:url" content="<?= Http::e(Seo::base() . ($__p === '/' ? '/' : $__p)) ?>">
<?php if ($__idx): ?><script type="application/ld+json"><?= Seo::jsonLd($__p, $pageName ?? Seo::SITE) ?></script><?php endif; ?>
<?php if ($gsc): ?><meta name="google-site-verification" content="<?= Http::e($gsc) ?>"><?php endif; ?>
<link rel="stylesheet" href="/app.css">
<?php if ($gtm): ?><script nonce="<?= Http::nonce() ?>">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= Http::e($gtm) ?>');</script><?php endif; ?>
<?php if ($clar): ?><script nonce="<?= Http::nonce() ?>">(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","<?= Http::e($clar) ?>");</script><?php endif; ?>
</head><body>
<?php if ($gtm): ?><noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= Http::e($gtm) ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript><?php endif; ?>
<nav class="nav"><a href="/">Meem Obaidullah</a><a href="/contact">Contact</a><a href="/affiliate">Affiliate</a></nav>
<main class="wrap"><?php foreach (Http::flashes() as [$t, $m]): ?><div class="flash <?= Http::e($t) ?>"><?= Http::e($m) ?></div><?php endforeach; ?><?= $content ?></main>
</body></html>
