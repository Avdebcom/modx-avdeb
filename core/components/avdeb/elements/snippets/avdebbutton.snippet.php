<?php

/**
 * AvdebButton: a referral button to any avdeb.com page.
 *
 *   [[!AvdebButton? &href=`/creators/cleo/` &label=`Visit my shop` &variant=`outline` &align=`center`]]
 *
 * @var \MODX\Revolution\modX $modx
 * @var array $scriptProperties
 */

if (!class_exists(\Avdeb\Embeds::class)) {
    return '<!-- AVDEB Button: the avdeb namespace is not loaded -->';
}

return \Avdeb\Embeds::instance($modx)->button($scriptProperties);
