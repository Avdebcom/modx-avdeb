<?php

$_lang['avdeb'] = 'AVDEB Products';

$_lang['area_credentials'] = 'Account';
$_lang['area_display'] = 'Display defaults';
$_lang['area_disclosure'] = 'Affiliate disclosure';
$_lang['area_cache'] = 'Caching';

$_lang['setting_avdeb.ref_code'] = 'Referral code';
$_lang['setting_avdeb.ref_code_desc'] = 'Your AVDEB affiliate referral code, added as ?ref= to every product link. Copy it from the partner dashboard: https://avdeb.com/affiliate/dashboard/developers/';
$_lang['setting_avdeb.api_key'] = 'Secret key (optional)';
$_lang['setting_avdeb.api_key_desc'] = 'An AVDEB secret key (avdeb_sk_…). Your referral code is then added automatically and embeds can show up to 48 products (12 without). Leave empty to use the AVDEB_SECRET_KEY environment variable. Used on the server only.';
$_lang['setting_avdeb.layout'] = 'Layout';
$_lang['setting_avdeb.layout_desc'] = 'Default layout for AvdebProducts: grid, carousel, list or spotlight.';
$_lang['setting_avdeb.columns'] = 'Columns';
$_lang['setting_avdeb.columns_desc'] = 'Columns for grid and carousel (1–6). Narrow columns get fewer automatically.';
$_lang['setting_avdeb.card_style'] = 'Card style';
$_lang['setting_avdeb.card_style_desc'] = 'elevated, outline or minimal.';
$_lang['setting_avdeb.theme'] = 'Colour theme';
$_lang['setting_avdeb.theme_desc'] = 'site (inherit your colours and fonts), light, dark or auto (follow the visitor\'s system setting).';
$_lang['setting_avdeb.accent_color'] = 'Accent colour';
$_lang['setting_avdeb.accent_color_desc'] = 'Hex colour for buttons and highlights, e.g. #7c3aed. Empty: AVDEB green.';
$_lang['setting_avdeb.radius'] = 'Corner radius';
$_lang['setting_avdeb.radius_desc'] = 'Card corner radius in pixels (0–32).';
$_lang['setting_avdeb.image_ratio'] = 'Image shape';
$_lang['setting_avdeb.image_ratio_desc'] = 'square, portrait or landscape.';
$_lang['setting_avdeb.cta_label'] = 'Button text';
$_lang['setting_avdeb.cta_label_desc'] = 'Text of the button on each card.';
$_lang['setting_avdeb.show_price'] = 'Show prices';
$_lang['setting_avdeb.show_price_desc'] = '';
$_lang['setting_avdeb.show_creator'] = 'Show designer names';
$_lang['setting_avdeb.show_creator_desc'] = '';
$_lang['setting_avdeb.show_badges'] = 'Show "New" and sale badges';
$_lang['setting_avdeb.show_badges_desc'] = '';
$_lang['setting_avdeb.new_tab'] = 'Open links in a new tab';
$_lang['setting_avdeb.new_tab_desc'] = '';
$_lang['setting_avdeb.locale'] = 'Price locale';
$_lang['setting_avdeb.locale_desc'] = 'Locale for price formatting, e.g. en-US or de-DE. Empty: the site\'s culture key.';
$_lang['setting_avdeb.include_assets'] = 'Load the AVDEB stylesheet and carousel script';
$_lang['setting_avdeb.include_assets_desc'] = 'Added only to pages that show products. Turn off to use your own CSS.';
$_lang['setting_avdeb.show_disclosure'] = 'Show an affiliate disclosure';
$_lang['setting_avdeb.show_disclosure_desc'] = 'A short line under embeds whose links carry your referral code.';
$_lang['setting_avdeb.disclosure_text'] = 'Disclosure text';
$_lang['setting_avdeb.disclosure_text_desc'] = 'Empty: "As an AVDEB affiliate, I earn a commission from qualifying purchases."';
$_lang['setting_avdeb.cache_minutes'] = 'Refresh products every (minutes)';
$_lang['setting_avdeb.cache_minutes_desc'] = 'How long fetched products stay fresh (5–2880). If AVDEB can\'t be reached, the last good copy is shown for up to a week. Clearing the site cache refreshes products right away.';
