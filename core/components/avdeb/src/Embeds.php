<?php

namespace Avdeb;

use Avdeb\Lib\Catalog;
use Avdeb\Lib\Client;
use Avdeb\Lib\Options;
use Avdeb\Lib\Renderer;
use Avdeb\Lib\Settings;
use Avdeb\Lib\Shortcodes;
use Avdeb\Lib\View;
use GuzzleHttp\Client as Guzzle;
use MODX\Revolution\modX;

/**
 * Settings, a cached catalog client and rendering, shared by the snippets and the plugin.
 * Identical embeds on one page share one lookup.
 */
final class Embeds
{
    public const VERSION = '1.0.0';

    /** System settings (namespace "avdeb"), without the "avdeb." prefix. */
    public const SETTING_KEYS = [
        'ref_code', 'api_key', 'layout', 'columns', 'card_style', 'theme', 'accent_color', 'radius', 'image_ratio',
        'cta_label', 'show_price', 'show_creator', 'show_badges', 'new_tab', 'show_disclosure', 'disclosure_text',
        'cache_minutes', 'locale',
    ];

    private static ?self $instance = null;

    private ?array $settings = null;
    private ?Client $client = null;

    /** @var array<string, array> Products per query within this request. */
    private array $memo = [];

    public function __construct(private readonly modX $modx)
    {
    }

    public static function instance(modX $modx): self
    {
        return self::$instance ??= new self($modx);
    }

    /** Validated settings for the shared library. The secret key falls back to AVDEB_SECRET_KEY. */
    public function settings(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }
        $params = [];
        foreach (self::SETTING_KEYS as $key) {
            $value = $this->modx->getOption('avdeb.' . $key, null, '');
            if ($value !== '' && $value !== null) {
                $params[$key] = $value;
            }
        }
        if (empty($params['api_key']) && ($env = getenv('AVDEB_SECRET_KEY'))) {
            $params['api_key'] = $env;
        }
        if (empty($params['ref_code']) && ($env = getenv('AVDEB_REF_CODE'))) {
            $params['ref_code'] = $env;
        }

        return $this->settings = Settings::fromParams($params);
    }

    public function client(): Client
    {
        if ($this->client) {
            return $this->client;
        }
        $http = static function (string $url, array $headers, int $timeout): array {
            $response = (new Guzzle())->request('GET', $url, [
                'headers' => $headers,
                'timeout' => $timeout,
                'connect_timeout' => min(5, $timeout),
                'http_errors' => false,
            ]);

            return ['status' => $response->getStatusCode(), 'body' => (string) $response->getBody()];
        };
        $s = $this->settings();

        return $this->client = new Client(new ModxCacheStore($this->modx), $http, $s['apiKey'], $s['refCode'], $s['cacheMinutes']);
    }

    /** Locale for prices: the avdeb.locale setting, else the site's culture key. */
    public function locale(): string
    {
        $locale = (string) $this->modx->getOption('avdeb.locale', null, '');
        if ($locale === '') {
            $locale = (string) $this->modx->getOption('cultureKey', null, 'en');
        }

        return preg_match('/^[a-z]{2,3}(?:[-_][A-Za-z0-9]{2,8})*$/', $locale) ? str_replace('_', '-', $locale) : 'en-US';
    }

    /** Products for a query, de-duplicated within the request. Never throws. */
    public function lookup(array $query): array
    {
        $key = Catalog::queryKey($query);
        if (!isset($this->memo[$key])) {
            try {
                $this->memo[$key] = $this->client()->products($query);
            } catch (\Throwable $e) {
                $this->modx->log(modX::LOG_LEVEL_WARN, '[AVDEB] catalog request failed: ' . $e->getMessage());
                $this->memo[$key] = ['products' => [], 'ref' => '', 'tier' => 'anonymous', 'source' => 'empty'];
            }
        }

        return $this->memo[$key];
    }

    /**
     * HTML for a collection (`mode` "collection") or one product (`mode` "product"). Never throws:
     * problems become an HTML comment so a page never breaks because of an embed.
     */
    public function render(array $attrs): string
    {
        try {
            $settings = $this->settings();
            $level = (int) ($attrs['headingLevel'] ?? $attrs['heading-level'] ?? $attrs['heading_level'] ?? 2);
            $o = Options::parse($attrs, $settings['display']);
            if ($o['mode'] === 'product' && Options::productHandle($o['value']) === '') {
                return '<!-- AVDEB Product: add a product link or handle, e.g. &product=`cat-sticker` -->';
            }
            $query = Catalog::sanitizeQuery(Options::queryFor($o), Catalog::maxLimit($settings['apiKey'] !== ''));
            $out = $this->lookup($query);

            return Renderer::embed([
                'products' => $out['products'],
                'ref' => $out['ref'],
                'query' => $query,
                'disclosure' => Settings::disclosureFor($settings, $out['ref']),
            ], $o, $this->locale(), max(2, min(5, $level ?: 2)));
        } catch (\Throwable $e) {
            $this->modx->log(modX::LOG_LEVEL_WARN, '[AVDEB] embed failed: ' . $e->getMessage());

            return '<!-- AVDEB Products: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . ' -->';
        }
    }

    /** The referral code links carry: the key owner's with a secret key, else the setting. */
    public function ref(): string
    {
        $settings = $this->settings();
        try {
            return $this->client()->ref() ?: $settings['refCode'];
        } catch (\Throwable) {
            return $settings['refCode'];
        }
    }

    /** HTML for a referral button. */
    public function button(array $attrs): string
    {
        return Renderer::button(Options::button($attrs), $this->ref(), $this->settings()['display']);
    }

    /** Replace `{avdeb-products …}`, `{avdeb-product …}` and `{avdeb-button …}` tags in HTML. */
    public function replaceTags(string $html): string
    {
        if (!Shortcodes::contains($html)) {
            return $html;
        }

        return Shortcodes::replace($html, function (string $tag, array $attrs): string {
            if ($tag === 'button') {
                return $this->button($attrs);
            }
            if ($tag === 'product') {
                $attrs['mode'] = 'product';
            }

            return $this->render($attrs);
        });
    }

    /**
     * Product cards for your own markup.
     *
     * @return array{cards: array, products: array, ref: string, viewAll: ?string, disclosure: ?string}
     */
    public function data(array $attrs): array
    {
        $settings = $this->settings();
        $o = Options::parse($attrs, $settings['display']);
        $query = Catalog::sanitizeQuery(Options::queryFor($o), Catalog::maxLimit($settings['apiKey'] !== ''));
        $out = $this->lookup($query);

        return [
            'cards' => View::buildCards($out['products'], $o, $this->locale()),
            'products' => $out['products'],
            'ref' => $out['ref'],
            'viewAll' => $o['viewAll'] ? View::viewAllHref($query, $out['products'], $out['ref']) : null,
            'disclosure' => Settings::disclosureFor($settings, $out['ref']),
        ];
    }

    /** Add the stylesheet (and the carousel script when needed) to a page that shows embeds. */
    public function injectAssets(string $html): string
    {
        if (!(bool) $this->modx->getOption('avdeb.include_assets', null, true) || !preg_match('/class="avdeb[ "-]/', $html)) {
            return $html;
        }
        $head = stripos($html, '</head>');
        if ($head === false) {
            return $html;
        }
        $base = rtrim((string) $this->modx->getOption('assets_url', null, '/assets/'), '/') . '/components/avdeb/';
        $tags = '';
        if (!str_contains($html, $base . 'avdeb.css')) {
            $tags .= '<link rel="stylesheet" href="' . $base . 'avdeb.css?v=' . self::VERSION . '">';
        }
        if (str_contains($html, 'data-avdeb-carousel') && !str_contains($html, $base . 'avdeb.js')) {
            $tags .= '<script src="' . $base . 'avdeb.js?v=' . self::VERSION . '" defer></script>';
        }

        return $tags === '' ? $html : substr_replace($html, $tags, $head, 0);
    }

    /** Drop cached products and the key check (runs when the site cache is cleared). */
    public function refresh(): void
    {
        try {
            $this->client()->flush();
        } catch (\Throwable) {
            (new ModxCacheStore($this->modx))->clear();
        }
        $this->memo = [];
    }
}
