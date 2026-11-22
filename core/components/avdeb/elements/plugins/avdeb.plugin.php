<?php

/**
 * AVDEB Products plugin.
 *
 * OnWebPagePrerender: turns {avdeb-products …}, {avdeb-product …} and {avdeb-button …} tags in the
 *   page into embeds, and adds the stylesheet (and carousel script) to pages that show products.
 * OnSiteRefresh: clearing the site cache also drops cached products.
 *
 * @var \MODX\Revolution\modX $modx
 */

if (!class_exists(\Avdeb\Embeds::class)) {
    return;
}

$embeds = \Avdeb\Embeds::instance($modx);

switch ($modx->event->name) {
    case 'OnWebPagePrerender':
        $resource = $modx->resource;
        if (!$resource) {
            return;
        }
        $type = $resource->getOne('ContentType');
        if ($type && $type->get('mime_type') !== 'text/html') {
            return;
        }
        $output = &$resource->_output;
        if (!is_string($output) || $output === '') {
            return;
        }
        $output = $embeds->injectAssets($embeds->replaceTags($output));
        break;

    case 'OnSiteRefresh':
        $embeds->refresh();
        break;
}
