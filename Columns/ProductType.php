<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Columns;

use Piwik\Columns\Dimension;

/**
 * Product type: firearm, ammunition or other, read from the product name or SKU.
 *
 * The value is not stored in the log tables; it is computed when reports are archived.
 */
class ProductType extends Dimension
{
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'FirearmsEcommerce_ProductType';
    protected $namePlural = 'FirearmsEcommerce_ProductTypes';
    protected $category = 'Goals_Ecommerce';
}
