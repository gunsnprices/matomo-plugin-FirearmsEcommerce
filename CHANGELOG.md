## Changelog

### 5.0.0

First release, for Matomo 5.

* New page Ecommerce > Firearms & Ammo with five reports, also available as dashboard widgets and in the Reporting
  API: product types (firearms, ammunition, other products), brands, firearms by caliber, ammunition by caliber with
  rounds sold and cost per round, and cost per round by ammunition product.
* Metrics: product revenue, quantity, unique purchases, average price, product views, rounds sold, cost per round and
  rounds per box. Only orders are counted, not abandoned carts.
* Website settings: the product field to read (name, SKU, or both) and whether product categories decide the product
  type.
* Product names are read with a bundled copy of the gunsnprices/gunsnprices 0.1.0 parser (293 calibers, models of 50
  firearm brands) plus a list of 126 brands. Runs locally, makes no network requests.
