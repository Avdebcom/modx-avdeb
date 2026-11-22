<?php

/**
 * Dev-site setup (run inside the container as www-data, the extra mounted at /opt/avdeb):
 * builds the transport package, installs it through the MODX package manager, applies
 * AVDEB_REF_CODE and seeds a small demo blog (template, home page, one post).
 */

use MODX\Revolution\modDocument;
use MODX\Revolution\modResource;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modTemplate;
use MODX\Revolution\modX;
use MODX\Revolution\Transport\modTransportPackage;

$web = '/var/www/html/';
passthru('php /opt/avdeb/_build/build.transport.php ' . escapeshellarg($web) . ' | tail -1', $code);
if ($code !== 0) {
    exit($code);
}

define('MODX_API_MODE', true);
require $web . 'config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';
$modx = new modX();
$modx->initialize('mgr');

$signature = 'avdeb-1.0.0-pl';
$package = $modx->getObject(modTransportPackage::class, ['signature' => $signature]) ?? $modx->newObject(modTransportPackage::class);
$package->fromArray([
    'signature' => $signature,
    'state' => 1,
    'workspace' => 1,
    'provider' => 0,
    'source' => $signature . '.transport.zip',
    'package_name' => 'avdeb',
    'version_major' => 1,
    'version_minor' => 0,
    'version_patch' => 0,
    'release' => 'pl',
    'created' => date('Y-m-d H:i:s'),
], '', true, true);
$package->save();
if (!$package->install()) {
    fwrite(STDERR, "Package install failed\n");
    exit(1);
}
echo "Installed $signature\n";

$set = static function (string $key, string $value) use ($modx): void {
    $setting = $modx->getObject(modSystemSetting::class, ['key' => $key]);
    if ($setting) {
        $setting->set('value', $value);
        $setting->save();
    }
};
if ($ref = getenv('AVDEB_REF_CODE')) {
    $set('avdeb.ref_code', $ref);
}
$set('friendly_urls', '1');
$set('publish_default', '1');
$set('site_name', 'Small Things Studio');

$css = file_get_contents('/opt/avdeb/tests/site.css');
$template = $modx->getObject(modTemplate::class, ['templatename' => 'Small Things']) ?? $modx->newObject(modTemplate::class);
$template->fromArray([
    'templatename' => 'Small Things',
    'content' => <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="[[++site_url]]">
  <title>[[*pagetitle]] · [[++site_name]]</title>
  <style>$css</style>
</head>
<body>
  <header class="site-header"><a class="brand" href="[[++site_url]]">[[++site_name]]</a><nav><a href="[[++site_url]]">Home</a></nav></header>
  <main class="wrap">[[*id:is=`[[++site_start]]`:then=`[[*content]]`:else=`<article class="post"><h1>[[*pagetitle]]</h1><p class="meta">[[*publishedon:strtotime:date=`%a %b %e %Y`]]</p>[[*content]]</article>`]]</main>
  <footer class="site-footer">Built with MODX</footer>
</body>
</html>
HTML,
], '', true, true);
$template->save();
$set('default_template', (string) $template->get('id'));

$home = $modx->getObject(modResource::class, (int) $modx->getOption('site_start'));
$home->fromArray([
    'pagetitle' => 'Home',
    'template' => $template->get('id'),
    'content' => <<<'HTML'
<section class="hero"><h1>Little things, made with care</h1><p>Notes on desks, gifts and tiny workshops.</p></section>
[[!AvdebProducts? &tag=`3d-prints` &layout=`spotlight` &limit=`4` &heading=`Fresh off the 3D printer`]]
<h2>Latest posts</h2>
<ul class="posts">
  <li><a href="desk-reset.html">My five-minute desk reset</a><p>A tiny routine that keeps the workbench usable.</p></li>
</ul>
HTML,
]);
$home->save();

$post = $modx->getObject(modResource::class, ['alias' => 'desk-reset']) ?? $modx->newObject(modDocument::class);
$post->fromArray([
    'pagetitle' => 'My five-minute desk reset',
    'alias' => 'desk-reset',
    'uri' => 'desk-reset.html',
    'content_type' => 1,
    'template' => $template->get('id'),
    'published' => 1,
    'publishedon' => strtotime('2026-11-10 09:00'),
    'parent' => 0,
    'richtext' => 0,
    'content' => <<<'HTML'
<p>Every evening I clear the desk in five minutes: tools back on the pegboard, scraps in the bin, and one small thing put back where it belongs.</p>
<p>The trick is having fewer, better things. These are the ones that stayed:</p>
<p>{avdeb-products https://avdeb.com/category/3d-prints/ layout="list" limit="3"}</p>
<p>That's it. Five minutes, every day.</p>
HTML,
]);
$post->save();

$modx->cacheManager->refresh();
echo "Demo content ready\n";
