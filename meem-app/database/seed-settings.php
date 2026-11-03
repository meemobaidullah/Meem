<?php
declare(strict_types=1);
/** Usage: php database/seed-settings.php   Fills the public site settings ONLY where they are still empty. Public IDs only: never put API tokens here. */
require __DIR__ . '/../app/bootstrap.php';
use App\Core\Guard;
$defaults = [
    'gtm_id'        => 'GTM-K7JZXFJ3',
    'ga4_id'        => 'G-GDFMZYLR1W',
    'gsc_verification' => 'rfcyoVUYFdYOcYk64jAwX2EwGdkCNq5RrHjZi_-6HR4',
    'clarity_id'    => 'z6zlgq2maq',
    'wa_url'        => 'https://wa.me/8801731558195',
    'contact_email' => 'meemobaidullah@gmail.com',
    'office_hours'  => '8:30 AM – 7:00 PM EST',
];
foreach ($defaults as $k => $v) {
    if (Guard::setting($k) === null) { Guard::saveSetting($k, $v); echo "set $k\n"; } else { echo "kept $k (already set)\n"; }
}
