<?php

/** System settings of the "avdeb" namespace: key => [xtype, default value, area]. */
return [
    'ref_code' => ['textfield', '', 'credentials'],
    'api_key' => ['text-password', '', 'credentials'],
    'layout' => ['textfield', 'grid', 'display'],
    'columns' => ['numberfield', '3', 'display'],
    'card_style' => ['textfield', 'elevated', 'display'],
    'theme' => ['textfield', 'site', 'display'],
    'accent_color' => ['textfield', '', 'display'],
    'radius' => ['numberfield', '16', 'display'],
    'image_ratio' => ['textfield', 'square', 'display'],
    'cta_label' => ['textfield', 'View product', 'display'],
    'show_price' => ['combo-boolean', '1', 'display'],
    'show_creator' => ['combo-boolean', '1', 'display'],
    'show_badges' => ['combo-boolean', '1', 'display'],
    'new_tab' => ['combo-boolean', '0', 'display'],
    'locale' => ['textfield', '', 'display'],
    'include_assets' => ['combo-boolean', '1', 'display'],
    'show_disclosure' => ['combo-boolean', '1', 'disclosure'],
    'disclosure_text' => ['textarea', '', 'disclosure'],
    'cache_minutes' => ['numberfield', '360', 'cache'],
];
