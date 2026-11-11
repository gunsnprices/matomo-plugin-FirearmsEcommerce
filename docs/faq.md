## FAQ

__Which shops is this plugin for?__

Shops that sell firearms, ammunition, or both, and track their orders with Matomo Ecommerce tracking
(`addEcommerceItem` and `trackEcommerceOrder`). It works with any shop software that sends Ecommerce orders to Matomo,
such as WooCommerce, Shopify, Magento or a custom shop.

__Where are the reports?__

In Ecommerce > Firearms & Ammo. The Ecommerce menu is shown for websites with Ecommerce enabled (Administration >
Websites > Manage > edit the website > Ecommerce). Each report can also be added to a dashboard as a widget.

__The reports are empty. Why?__

Check that the website has Ecommerce enabled and has orders in the selected period. Reports are built when Matomo
archives data, so orders tracked before the plugin was activated are only included after their reports are archived
again. To rebuild them, run
`./console core:invalidate-report-data --dates=YYYY-MM-DD,YYYY-MM-DD --sites=ID --plugin=FirearmsEcommerce`
and let the next archiving run (or browser archiving) build them.

__How does the plugin know what a product is?__

It reads the product name (or the SKU, see the website settings) with the open source parser of GunsNPrices. The
parser finds the caliber ("9mm Luger", "5.56 NATO", "12ga" and 290 more), the firearm model for 50 brands, and the
round count of ammunition ("50 Rounds", "20rd", "Box of 25"). The plugin adds a list of 126 brands and rules for
accessories and shotgun shells. If the product categories say what the product is ("Ammunition", "Handguns",
"Optics", "Magazines"), they decide the type.

__Some products are in the wrong group. What can I do?__

Make sure the "Use product categories" setting is on and that your categories name the product type. If the product
names are short and the SKUs are descriptive, choose "Product name and SKU" as the product field. Then rebuild the
reports as described above. You can also report the product name in the issue tracker.

__How is the cost per round calculated?__

For each caliber, the revenue of the ammunition boxes whose round count is known is divided by the number of rounds
sold. A box of 50 rounds sold at $15.99 adds $15.99 and 50 rounds; two such boxes add $31.98 and 100 rounds. Boxes
whose name has no round count add revenue to the caliber but are left out of the cost per round.

__Why don't the orders add up to my total number of orders?__

"Unique purchases" counts the orders that contain at least one product of the row. An order with a pistol and a box
of ammunition counts once for Firearms and once for Ammunition.

__Are abandoned carts included?__

No. The reports count orders only.

__Does the plugin send data to GunsNPrices or anyone else?__

No. The parser and its data are bundled with the plugin and run on your Matomo server when reports are archived. The
plugin makes no network requests and loads no external files.

__Does it slow down tracking?__

No. The plugin does nothing when visits are tracked. It reads product names when reports are archived, once per
distinct product and day; in our tests this took about 0.2 ms per product.

__Which licenses apply?__

The plugin is GPL v3 or later. The bundled parser library is MIT, and its data files are CC BY 4.0, credited to
GunsNPrices (gunsnprices.com). Both licenses are compatible with the GPL.
