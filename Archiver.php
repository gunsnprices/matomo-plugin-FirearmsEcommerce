<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce;

use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductAggregator;

/**
 * Archiver of the FirearmsEcommerce plugin. The reports are built by RecordBuilders\FirearmsProducts.
 */
class Archiver extends \Piwik\Plugin\Archiver
{
    public const PRODUCT_TYPES_RECORD = 'FirearmsEcommerce_productTypes';
    public const BRANDS_RECORD = 'FirearmsEcommerce_brands';
    public const FIREARM_CALIBERS_RECORD = 'FirearmsEcommerce_firearmCalibers';
    public const AMMO_CALIBERS_RECORD = 'FirearmsEcommerce_ammoCalibers';
    public const AMMO_PRODUCTS_RECORD = 'FirearmsEcommerce_ammoProducts';

    /**
     * Archive record name of each report of ProductAggregator.
     *
     * @return array<string, string>
     */
    public static function recordNames(): array
    {
        return [
            ProductAggregator::REPORT_TYPES => self::PRODUCT_TYPES_RECORD,
            ProductAggregator::REPORT_BRANDS => self::BRANDS_RECORD,
            ProductAggregator::REPORT_FIREARM_CALIBERS => self::FIREARM_CALIBERS_RECORD,
            ProductAggregator::REPORT_AMMO_CALIBERS => self::AMMO_CALIBERS_RECORD,
            ProductAggregator::REPORT_AMMO_PRODUCTS => self::AMMO_PRODUCTS_RECORD,
        ];
    }
}
