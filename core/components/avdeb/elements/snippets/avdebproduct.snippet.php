<?php

/**
 * AvdebProduct: one featured product (horizontal card, hero or compact card).
 *
 *   [[!AvdebProduct? &product=`https://avdeb.com/store/custom-qr-code-keychain/` &variant=`hero` &badge=`Editor's pick`]]
 *
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

if (!class_exists(\Avdeb\Embeds::class)) {
    return '<!-- AVDEB Product: the avdeb namespace is not loaded -->';
}

$attrs = $scriptProperties;
$attrs['product'] = $attrs['product'] ?? $attrs['url'] ?? $attrs['handle'] ?? $attrs['link'] ?? '';
unset($attrs['url'], $attrs['link']);
$attrs['mode'] = 'product';

return \Avdeb\Embeds::instance($modx)->render($attrs);
