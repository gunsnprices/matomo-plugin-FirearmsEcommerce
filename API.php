<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\Piwik;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductAggregator;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductClassifier;

/**
 * Ecommerce reports for gun and ammunition shops. Products are grouped by the type, brand and caliber read from
 * their name or SKU (see the website setting "Product field to read"). Only orders are counted, not abandoned carts.
 *
 * Every method returns these columns: revenue (product revenue), quantity, orders (orders that contain the row's
 * products), product_views (product page views, if the shop tracks them). The ammunition reports add rounds (rounds
 * sold) and rounds_revenue (revenue of the boxes whose round count is known), from which cost_per_round is computed.
 *
 * @method static \Piwik\Plugins\FirearmsEcommerce\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Sales by product type: firearms, ammunition, other products.
     *
     * @param int|string $idSite
     * @param string $period
     * @param string $date
     * @param string|false $segment
     * @return DataTable|DataTable\Map
     */
    public function getProductTypes($idSite, $period, $date, $segment = false)
    {
        $table = $this->getDataTable(Archiver::PRODUCT_TYPES_RECORD, $idSite, $period, $date, $segment);
        $names = [
            ProductClassifier::TYPE_FIREARM => Piwik::translate('FirearmsEcommerce_TypeFirearms'),
            ProductClassifier::TYPE_AMMO => Piwik::translate('FirearmsEcommerce_TypeAmmunition'),
            ProductClassifier::TYPE_OTHER => Piwik::translate('FirearmsEcommerce_TypeOther'),
        ];
        $table->filter('ColumnCallbackReplace', ['label', function ($label) use ($names) {
            return $names[$label] ?? $label;
        }]);
        return $table;
    }

    /**
     * Sales by brand, for all products. Products without a known brand are grouped as "Unknown".
     *
     * @param int|string $idSite
     * @param string $period
     * @param string $date
     * @param string|false $segment
     * @return DataTable|DataTable\Map
     */
    public function getBrands($idSite, $period, $date, $segment = false)
    {
        return $this->getDataTable(Archiver::BRANDS_RECORD, $idSite, $period, $date, $segment);
    }

    /**
     * Firearm sales by caliber.
     *
     * @param int|string $idSite
     * @param string $period
     * @param string $date
     * @param string|false $segment
     * @return DataTable|DataTable\Map
     */
    public function getFirearmCalibers($idSite, $period, $date, $segment = false)
    {
        return $this->getDataTable(Archiver::FIREARM_CALIBERS_RECORD, $idSite, $period, $date, $segment);
    }

    /**
     * Ammunition sales by caliber, with rounds sold and cost per round.
     *
     * @param int|string $idSite
     * @param string $period
     * @param string $date
     * @param string|false $segment
     * @return DataTable|DataTable\Map
     */
    public function getAmmoCalibers($idSite, $period, $date, $segment = false)
    {
        return $this->getDataTable(Archiver::AMMO_CALIBERS_RECORD, $idSite, $period, $date, $segment);
    }

    /**
     * Ammunition products whose round count is known, with rounds per box and cost per round.
     *
     * @param int|string $idSite
     * @param string $period
     * @param string $date
     * @param string|false $segment
     * @return DataTable|DataTable\Map
     */
    public function getAmmoCostPerRound($idSite, $period, $date, $segment = false)
    {
        return $this->getDataTable(Archiver::AMMO_PRODUCTS_RECORD, $idSite, $period, $date, $segment);
    }

    /**
     * @param int|string $idSite
     * @param string|false $segment
     * @return DataTable|DataTable\Map
     */
    private function getDataTable(string $recordName, $idSite, $period, $date, $segment)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $archive = Archive::build($idSite, $period, $date, $segment);
        $table = $archive->getDataTable($recordName);
        $table->queueFilter('ReplaceSummaryRowLabel');

        $unknown = Piwik::translate('General_Unknown');
        $table->filter('ColumnCallbackReplace', ['label', function ($label) use ($unknown) {
            return $label === ProductAggregator::LABEL_UNKNOWN ? $unknown : $label;
        }]);
        // Sums of daily reports can carry float noise (11836.600000000002).
        $table->filter(function (DataTable $dataTable) {
            foreach ($dataTable->getRows() as $row) {
                foreach ([ProductAggregator::REVENUE, ProductAggregator::ROUNDS_REVENUE] as $column) {
                    $value = $row->getColumn($column);
                    if ($value !== false && is_numeric($value)) {
                        $row->setColumn($column, round((float) $value, 2));
                    }
                }
            }
        });
        return $table;
    }
}
