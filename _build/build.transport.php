<?php

/**
 * Builds the MODX transport package: core/packages/avdeb-<version>-pl.transport.zip
 *
 *   php _build/build.transport.php /path/to/modx      (MODX 3 site whose config.core.php is used)
 *
 * The package registers the "avdeb" namespace, its system settings, the AvdebProducts,
 * AvdebProduct and AvdebButton snippets and the "AVDEB Products" plugin (category "AVDEB
 * Products"), and copies core/components/avdeb and assets/components/avdeb.
 */

use MODX\Revolution\modCategory;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modSnippet;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modX;
use MODX\Revolution\Transport\modPackageBuilder;
use xPDO\Transport\xPDOTransport;

const PKG_NAME = 'AVDEB Products';
const PKG_NAMESPACE = 'avdeb';
const PKG_VERSION = '1.0.0';
const PKG_RELEASE = 'pl';

$root = dirname(__DIR__) . '/';
$modxRoot = rtrim($argv[1] ?? getenv('MODX_ROOT') ?: '', '/') . '/';
if (!is_file($modxRoot . 'config.core.php')) {
    fwrite(STDERR, "Usage: php _build/build.transport.php /path/to/modx\n");
    exit(1);
}

define('MODX_API_MODE', true);
require $modxRoot . 'config.core.php';
require MODX_CORE_PATH . 'vendor/autoload.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

$sources = [
    'core' => $root . 'core/components/' . PKG_NAMESPACE . '/',
    'assets' => $root . 'assets/components/' . PKG_NAMESPACE . '/',
    'snippets' => $root . 'core/components/' . PKG_NAMESPACE . '/elements/snippets/',
    'plugins' => $root . 'core/components/' . PKG_NAMESPACE . '/elements/plugins/',
    'docs' => $root . 'core/components/' . PKG_NAMESPACE . '/docs/',
];

/** Element code without the opening tag (MODX stores snippets and plugins that way). */
$code = static function (string $file): string {
    $src = trim((string) file_get_contents($file));
    $src = preg_replace('/^<\?php\s*/', '', $src);

    return trim((string) preg_replace('/\?>\s*$/', '', $src));
};

$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAMESPACE, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAMESPACE, false, true, '{core_path}components/' . PKG_NAMESPACE . '/', '{assets_path}components/' . PKG_NAMESPACE . '/');

// System settings: new ones get their defaults; existing values are kept on upgrade.
foreach (require __DIR__ . '/settings.php' as $key => [$xtype, $value, $area]) {
    $setting = $modx->newObject(modSystemSetting::class);
    $setting->fromArray([
        'key' => PKG_NAMESPACE . '.' . $key,
        'value' => $value,
        'xtype' => $xtype,
        'namespace' => PKG_NAMESPACE,
        'area' => $area,
    ], '', true, true);
    $builder->putVehicle($builder->createVehicle($setting, [
        xPDOTransport::UNIQUE_KEY => 'key',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => false,
    ]));
}

$category = $modx->newObject(modCategory::class);
$category->set('category', PKG_NAME);

$snippets = [];
foreach ([
    'AvdebProducts' => ['avdebproducts', 'AVDEB products from a creator, category, search or the newest products, with your referral code on every link.'],
    'AvdebProduct' => ['avdebproduct', 'One featured AVDEB product (horizontal card, hero or compact card).'],
    'AvdebButton' => ['avdebbutton', 'A referral button to any avdeb.com page.'],
] as $name => [$file, $description]) {
    $snippet = $modx->newObject(modSnippet::class);
    $snippet->fromArray([
        'name' => $name,
        'description' => $description,
        'snippet' => $code($sources['snippets'] . $file . '.snippet.php'),
    ], '', true, true);
    $snippets[] = $snippet;
}
$category->addMany($snippets);

$plugin = $modx->newObject(modPlugin::class);
$plugin->fromArray([
    'name' => PKG_NAME,
    'description' => 'Turns {avdeb-products …} tags into product embeds and loads the styles on pages that show products.',
    'plugincode' => $code($sources['plugins'] . 'avdeb.plugin.php'),
], '', true, true);
$events = [];
foreach (['OnWebPagePrerender', 'OnSiteRefresh'] as $event) {
    $pluginEvent = $modx->newObject(modPluginEvent::class);
    $pluginEvent->fromArray(['event' => $event, 'priority' => 0, 'propertyset' => 0], '', true, true);
    $events[] = $pluginEvent;
}
$plugin->addMany($events, 'PluginEvents');
$plugins = [$plugin];
$category->addMany($plugins);

$vehicle = $builder->createVehicle($category, [
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
        'Snippets' => [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ],
        'Plugins' => [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => false,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                ],
            ],
        ],
    ],
]);
$vehicle->resolve('file', ['source' => rtrim($sources['core'], '/'), 'target' => "return MODX_CORE_PATH . 'components/';"]);
$vehicle->resolve('file', ['source' => rtrim($sources['assets'], '/'), 'target' => "return MODX_ASSETS_PATH . 'components/';"]);
$builder->putVehicle($vehicle);

$builder->setPackageAttributes([
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
]);

if (!$builder->pack()) {
    fwrite(STDERR, "Packing failed\n");
    exit(1);
}
echo 'Built ' . MODX_CORE_PATH . 'packages/' . $builder->getSignature() . ".transport.zip\n";
