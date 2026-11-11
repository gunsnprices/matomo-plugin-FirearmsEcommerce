# Firearms Ecommerce for Matomo

## Description

Ecommerce reports for gun and ammunition shops. The plugin reads the name (or SKU) of every product in your Ecommerce
orders and groups the sales by what the products are:

- **Firearms, ammunition and other products**: revenue, quantity, orders, average price and product views of each
  product type.
- **Brands**: sales by brand, for all products.
- **Firearms by caliber**: "9×19mm Parabellum", "12 Gauge", ".357 Mag" and so on.
- **Ammunition by caliber**: the same, plus the number of rounds sold and the average cost per round.
- **Ammunition cost per round**: each ammunition product sold, with its rounds per box and cost per round.

For example, a box sold as "Federal American Eagle 5.56x45mm 55gr FMJ 1000 Rounds" at $449.99 is counted as
ammunition, brand Federal, caliber 5.56×45mm NATO, 1,000 rounds, $0.450 per round. "Glock 19 Gen 5 MOS 9mm 4.02in
15rd Pistol" is a firearm, brand Glock, caliber 9×19mm Parabellum. "Magpul PMAG 30 AR/M4 5.56x45 30rd Magazine" is an
accessory (other products), brand Magpul, even though it names a caliber.

The reports are on a new page, **Ecommerce > Firearms & Ammo**. Each report is also a dashboard widget, can be sent
in scheduled email reports, and is available in the Reporting API (`FirearmsEcommerce.getProductTypes`,
`getBrands`, `getFirearmCalibers`, `getAmmoCalibers`, `getAmmoCostPerRound`). Reports work for any period, date range
and segment.

### How products are read

The plugin uses the open source title parser of [GunsNPrices](https://gunsnprices.com), a price comparison site for
guns and ammunition that uses it to match the same product across 40+ US stores. The parser knows 293 calibers with
the spellings stores use ("9MM LUGER", "5.56 NATO", "12GA", "300 BLK") and the models of 50 firearm brands. The plugin
adds a list of 126 firearm, ammunition, optics and accessory brands, and rules that tell accessories ("15 round
magazine", "barrel for Glock 19") and shotgun shells ("#8 shot 25 shells") apart from firearms.

If your product categories say what a product is ("Ammunition", "Handguns", "Optics", "Magazines"), the plugin uses
them to decide the product type. You can turn this off.

### Settings

In **Administration > Websites > Manage**, edit the website:

- **Product field to read**: the product name (default), the SKU, or both. Choose the SKU if your SKUs are descriptive
  (such as "GLOCK-19-GEN5-9MM") and your product names are not.
- **Use product categories to tell firearms, ammunition and accessories apart**: on by default.

Changes apply to reports archived after the change. To rebuild older reports, invalidate them:
`./console core:invalidate-report-data --dates=2026-01-01,2026-12-31 --sites=1 --plugin=FirearmsEcommerce`.

### Requirements

- Matomo 5, with Ecommerce enabled for the website.
- Ecommerce orders tracked with their items (`addEcommerceItem` and `trackEcommerceOrder`). Product views are shown if
  the shop tracks them with `setEcommerceView`.
- PHP 7.4 or later.

### Privacy

The plugin reads product names, SKUs and categories that are already in your Matomo database, when Matomo archives
reports. It makes no network requests, loads no external files and collects no visitor data. Nothing is sent to
GunsNPrices or anyone else.

## What is counted

- Only orders. Abandoned carts are not counted.
- Revenue is price times quantity of the products, without tax, shipping and discounts, like the Matomo Products
  report.
- "Unique purchases" (orders) counts each order once per row, even if it has several products of that row. Rows of
  different reports can share an order, so orders do not add up across rows.
- Cost per round is the revenue of the boxes whose round count is known, divided by the rounds sold. Boxes whose name
  has no round count ("50 Rounds", "20rd", "Box of 25", "1,000 rds") add revenue to the caliber but no rounds.
- Products without a known brand or caliber are grouped as "Unknown".

## Limitations

- Product names from US retailers, in English.
- Firearm models (and so the brand of any title that only names a model) are known for 50 brands. Other brands get the
  brand from the list of 126 brands, or "Unknown".
- The product type is a rule-based guess. Some parts are named like the firearm they fit, such as "Glock 19 9mm
  Threaded Barrel", and are counted as firearms. Product categories fix most of these cases.
- Reports archived before the plugin was activated are not rebuilt by themselves; invalidate them (see Settings) to
  include older orders.

## Installation

Install it from the Marketplace (Administration > Platform > Marketplace), or copy the plugin to
`plugins/FirearmsEcommerce` and activate it in Administration > Plugins > Manage Plugins. No Composer step is needed:
the parser library is bundled in `vendor/`.

## Development

The parser is the PHP package [gunsnprices/gunsnprices](https://github.com/gunsnprices/gunsnprices-php) 0.1.0. It is
bundled in `vendor/prefixed/gunsnprices/gunsnprices` under the namespace `Matomo\Dependencies\FirearmsEcommerce\GunsNPrices`
(the convention of Matomo's matomo-scoper), so it cannot clash with another copy of the library. To update it:

```bash
mkdir /tmp/gnp && cd /tmp/gnp && composer require gunsnprices/gunsnprices:0.1.0
php tools/vendor-library.php /tmp/gnp/vendor/gunsnprices/gunsnprices
```

Tests:

```bash
# unit tests, without Matomo
phpunit -c phpunit.xml.dist

# unit and integration tests, inside a Matomo 5 development checkout (git clone, not the release zip)
./console tests:run FirearmsEcommerce
```

The product type, brand and caliber rules are in `Classifier/`, which has no Matomo dependencies.

## License

GPL v3 or later, see [LICENSE](LICENSE).

The bundled parser library is under the MIT License, and its data files (`data/models.json`, `data/calibers.json`) are
under CC BY 4.0, credited to GunsNPrices (gunsnprices.com). See `vendor/prefixed/gunsnprices/gunsnprices/`.

Questions and bug reports: [GitHub issues](https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce/issues) or
app@gunsnprices.com.
