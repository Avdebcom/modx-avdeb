AVDEB Products for MODX
=======================

Show AVDEB products (a creator's shop, a category, a search or a single product) on your MODX site,
with your affiliate referral code on every link.

Setup
-----
System -> System Settings -> namespace "avdeb":
- avdeb.ref_code: your referral code from https://avdeb.com/affiliate/dashboard/developers/
- avdeb.api_key (optional): a secret key (avdeb_sk_...), or set the AVDEB_SECRET_KEY environment variable
- display defaults: layout, columns, card style, colour theme, accent colour and more

Use it
------
In any resource content, template or chunk:

  [[!AvdebProducts? &url=`https://avdeb.com/category/stickers/` &layout=`carousel`]]
  [[!AvdebProducts? &tag=`3d-prints` &layout=`spotlight` &limit=`4` &heading=`Fresh off the 3D printer`]]
  [[!AvdebProduct? &product=`https://avdeb.com/store/custom-qr-code-keychain/` &variant=`hero`]]
  [[!AvdebButton? &href=`/creators/cleo/` &label=`Visit my shop` &variant=`outline`]]

Or, in rich text editors that escape MODX tags, type:

  {avdeb-products https://avdeb.com/category/stickers/ layout="carousel"}
  {avdeb-product https://avdeb.com/store/custom-qr-code-keychain/ variant="hero"}
  {avdeb-button href="/creators/cleo/" label="Visit my shop"}

Your own markup: [[!AvdebProducts? &tag=`stickers` &tpl=`myProductTpl`]] renders each product with
your chunk ([[+href]], [[+title]], [[+image]], [[+price]], [[+creator]], [[+rel]], [[+idx]]...).

Become an AVDEB affiliate (free, 5% commission, 30-day cookie): https://avdeb.com/affiliate/apply/
Documentation: https://avdeb.com/developers/#modx
Source and issues: https://github.com/Avdebcom/modx-avdeb
