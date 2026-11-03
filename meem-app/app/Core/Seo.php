<?php
declare(strict_types=1);
namespace App\Core;

/** SEO helpers: length-safe titles/descriptions, JSON-LD, legacy slug redirects, sitemap, robots. */
final class Seo
{
    public const SITE = 'Meem Obaidullah';
    public const TITLE_MAX = 60;
    public const DESC_MAX = 155;
    /** Old (misspelled) first path segment => correct segment. One hop, 301, kept permanently. */
    public const LEGACY = ['/case-stady' => '/case-study', '/sucess' => '/success'];
    /** Indexable public URLs. Add a route here only when the page is real and should be indexed. */
    /** Public profiles already linked from the site. */
    public const SOCIAL = ['https://www.instagram.com/meemobaidullah/', 'https://www.linkedin.com/in/meemobaidullah', 'https://www.tiktok.com/@mo.entrepreneurs', 'https://medium.com/@meemobaidullah', 'https://t.me/meemobaidullah'];
    public const SITEMAP = ['/' => 1.0, '/contact' => 0.6];

    public static function base(): string { return rtrim(Env::get('APP_URL', 'https://meemobaidullah.com'), '/'); }

    /** Cut at a word boundary and add an ellipsis only when something was removed. */
    public static function clamp(string $s, int $max): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if (mb_strlen($s) <= $max) return $s;
        $cut = mb_substr($s, 0, $max - 1);
        $sp = mb_strrpos($cut, ' ');
        if ($sp !== false && $sp > $max * 0.6) $cut = mb_substr($cut, 0, $sp);
        return rtrim($cut, " ,;:-|") . '…';
    }

    public static function title(string $t): string
    {
        $t = trim($t);
        if ($t === '' || $t === self::SITE) return self::SITE;
        return self::clamp(str_contains($t, self::SITE) ? $t : $t . ' | ' . self::SITE, self::TITLE_MAX);
    }

    /** If the path starts with a misspelled segment, return the corrected path (query kept), else null. */
    public static function legacyTarget(string $path): ?string
    {
        foreach (self::LEGACY as $old => $new) {
            if ($path === $old || str_starts_with($path, $old . '/')) return $new . substr($path, strlen($old));
        }
        return null;
    }

    /** Structured data using only facts already public on the site. No ratings, awards or reviews. */
    public static function jsonLd(string $path, string $pageName): string
    {
        $b = self::base();
        $graph = [
            ['@type' => 'Organization', '@id' => $b . '/#org', 'name' => self::SITE, 'url' => $b . '/'],
            ['@type' => 'Person', '@id' => $b . '/#person', 'name' => self::SITE, 'url' => $b . '/', 'jobTitle' => 'eCommerce Specialist', 'worksFor' => ['@id' => $b . '/#org'], 'sameAs' => self::SOCIAL],
            ['@type' => 'WebSite', '@id' => $b . '/#site', 'url' => $b . '/', 'name' => self::SITE, 'publisher' => ['@id' => $b . '/#org']],
        ];
        if ($path !== '/') {
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $b . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $pageName, 'item' => $b . $path],
            ]];
        }
        return json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    }

    public static function sitemap(): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (self::SITEMAP as $p => $prio) $x .= '  <url><loc>' . htmlspecialchars(self::base() . ($p === '/' ? '/' : $p), ENT_XML1) . '</loc><priority>' . number_format($prio, 1) . '</priority></url>' . "\n";
        return $x . '</urlset>' . "\n";
    }

    public static function robots(): string
    {
        return "User-agent: *\nDisallow: /admin\nDisallow: /affiliate/dashboard\nDisallow: /affiliate/login\nDisallow: /affiliate/register\nDisallow: /r/\n\nSitemap: " . self::base() . "/sitemap.xml\n";
    }
}
