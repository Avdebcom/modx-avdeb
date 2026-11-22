<?php

/**
 * AvdebProducts: AVDEB products from a creator, category, search or the newest products.
 *
 *   [[!AvdebProducts? &url=`https://avdeb.com/category/stickers/` &layout=`carousel`]]
 *   [[!AvdebProducts? &tag=`3d-prints` &layout=`spotlight` &limit=`4` &heading=`Fresh off the 3D printer`]]
 *
 * With &tpl, each product is rendered with your chunk instead ([[+href]], [[+title]], [[+image]],
 * [[+price]], [[+compareAt]], [[+discount]], [[+creator]], [[+rel]], [[+target]], [[+idx]]).
 * Other options: &outputSeparator, &toPlaceholder. Every value is HTML-escaped.
 *
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

if (!class_exists(\Avdeb\Embeds::class)) {
    return '<!-- AVDEB Products: the avdeb namespace is not loaded -->';
}

$embeds = \Avdeb\Embeds::instance($modx);
$tpl = (string) ($scriptProperties['tpl'] ?? '');

if ($tpl === '') {
    $output = $embeds->render($scriptProperties);
} else {
    $data = $embeds->data($scriptProperties);
    $items = [];
    foreach ($data['cards'] as $i => $card) {
        $placeholders = ['idx' => $i + 1];
        foreach ($card as $key => $value) {
            $placeholders[$key] = is_string($value) ? htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $value;
        }
        $items[] = $modx->getChunk($tpl, $placeholders);
    }
    $output = implode((string) ($scriptProperties['outputSeparator'] ?? "\n"), $items);
    $modx->setPlaceholders([
        'avdeb.viewAll' => htmlspecialchars((string) $data['viewAll'], ENT_QUOTES),
        'avdeb.disclosure' => htmlspecialchars((string) $data['disclosure'], ENT_QUOTES),
        'avdeb.total' => count($data['cards']),
    ]);
}

if (!empty($scriptProperties['toPlaceholder'])) {
    $modx->setPlaceholder((string) $scriptProperties['toPlaceholder'], $output);

    return '';
}

return $output;
