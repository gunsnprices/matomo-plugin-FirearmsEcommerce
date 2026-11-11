<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\RecordBuilders;

use Piwik\ArchiveProcessor;
use Piwik\ArchiveProcessor\Record;
use Piwik\ArchiveProcessor\RecordBuilder;
use Piwik\Config;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\Plugin\Manager;
use Piwik\Plugins\FirearmsEcommerce\Archiver;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductAggregator;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductClassifier;
use Piwik\Plugins\FirearmsEcommerce\MeasurableSettings;
use Piwik\Site;
use Piwik\Tracker\GoalManager;

/**
 * Builds the five reports of the plugin from the ecommerce items of orders (log_conversion_item) and the product
 * page views (log_link_visit_action) of one day. Week, month, year and range reports are sums of the day reports,
 * which RecordBuilder does on its own.
 *
 * Abandoned carts are not counted: only items of orders (idorder other than '0').
 */
class FirearmsProducts extends RecordBuilder
{
    public function __construct()
    {
        $general = Config::getInstance()->General;
        $maxRows = (int) ($general['datatable_archiving_maximum_rows_products'] ?? 10000);

        parent::__construct($maxRows, null, ProductAggregator::REVENUE);
    }

    public function isEnabled(ArchiveProcessor $archiveProcessor): bool
    {
        return Manager::getInstance()->isPluginActivated('Ecommerce')
            && Manager::getInstance()->isPluginActivated('Goals');
    }

    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        $records = [];
        foreach (Archiver::recordNames() as $recordName) {
            $records[] = Record::make(Record::TYPE_BLOB, $recordName);
        }
        return $records;
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        $idSite = (int) $archiveProcessor->getParams()->getSite()->getId();

        $records = [];
        foreach (Archiver::recordNames() as $recordName) {
            $records[$recordName] = new DataTable();
        }
        if (!Site::isEcommerceEnabledFor($idSite)) {
            return $records;
        }

        $settings = new MeasurableSettings($idSite);
        $classifier = new ProductClassifier(
            (string) $settings->productField->getValue(),
            (bool) $settings->useCategories->getValue()
        );
        $aggregator = new ProductAggregator($classifier);

        $logAggregator = $archiveProcessor->getLogAggregator();
        $this->readOrderItems($logAggregator, $aggregator);
        $this->readProductViews($logAggregator, $aggregator);

        foreach ($aggregator->getReports() as $report => $rows) {
            $table = $records[Archiver::recordNames()[$report]];
            foreach ($rows as $label => $columns) {
                $table->sumRowWithLabel($label, $columns);
            }
        }
        return $records;
    }

    /**
     * One row per order and product: the order ID, the product name, SKU and first two categories, and the price
     * and quantity.
     */
    private function readOrderItems(LogAggregator $logAggregator, ProductAggregator $aggregator): void
    {
        $price = 'log_conversion_item.price';
        if (defined(GoalManager::class . '::MAX_ALLOWED_REVENUE')) {
            // Newer Matomo versions ignore item prices outside the range the tracker accepts; do the same.
            $price = sprintf('CASE WHEN ABS(log_conversion_item.price) > %d THEN 0 ELSE log_conversion_item.price END', GoalManager::MAX_ALLOWED_REVENUE);
        }

        $query = $logAggregator->generateQuery(
            implode(', ', [
                'log_conversion_item.idorder AS idorder',
                'fe_name.name AS name',
                'fe_sku.name AS sku',
                'fe_cat1.name AS category1',
                'fe_cat2.name AS category2',
                "MAX($price) AS price",
                'SUM(log_conversion_item.quantity) AS quantity',
            ]),
            [
                'log_conversion_item',
                ['table' => 'log_action', 'tableAlias' => 'fe_name', 'joinOn' => 'log_conversion_item.idaction_name = fe_name.idaction'],
                ['table' => 'log_action', 'tableAlias' => 'fe_sku', 'joinOn' => 'log_conversion_item.idaction_sku = fe_sku.idaction'],
                ['table' => 'log_action', 'tableAlias' => 'fe_cat1', 'joinOn' => 'log_conversion_item.idaction_category = fe_cat1.idaction'],
                ['table' => 'log_action', 'tableAlias' => 'fe_cat2', 'joinOn' => 'log_conversion_item.idaction_category2 = fe_cat2.idaction'],
            ],
            implode(' AND ', [
                'log_conversion_item.server_time >= ?',
                'log_conversion_item.server_time <= ?',
                'log_conversion_item.idsite IN (' . implode(',', array_fill(0, count($logAggregator->getGeneralQueryBindParams()) - 2, '?')) . ')',
                'log_conversion_item.deleted = 0',
                "log_conversion_item.idorder <> '0'",
            ]),
            'log_conversion_item.idorder, log_conversion_item.idaction_name, log_conversion_item.idaction_sku, '
                . 'log_conversion_item.idaction_category, log_conversion_item.idaction_category2',
            false
        );

        $statement = $logAggregator->getDb()->query($query['sql'], $query['bind']);
        while ($row = $statement->fetch()) {
            $aggregator->addOrderItem(
                (string) $row['idorder'],
                (string) $row['name'],
                (string) $row['sku'],
                [(string) $row['category1'], (string) $row['category2']],
                (float) $row['price'],
                (float) $row['quantity']
            );
        }
    }

    /** Product page views (tracked with setEcommerceView), grouped by product. */
    private function readProductViews(LogAggregator $logAggregator, ProductAggregator $aggregator): void
    {
        $query = $logAggregator->generateQuery(
            implode(', ', [
                'fe_name.name AS name',
                'fe_sku.name AS sku',
                'fe_cat1.name AS category1',
                'fe_cat2.name AS category2',
                'COUNT(*) AS views',
            ]),
            [
                'log_link_visit_action',
                ['table' => 'log_action', 'tableAlias' => 'fe_name', 'joinOn' => 'log_link_visit_action.idaction_product_name = fe_name.idaction'],
                ['table' => 'log_action', 'tableAlias' => 'fe_sku', 'joinOn' => 'log_link_visit_action.idaction_product_sku = fe_sku.idaction'],
                ['table' => 'log_action', 'tableAlias' => 'fe_cat1', 'joinOn' => 'log_link_visit_action.idaction_product_cat = fe_cat1.idaction'],
                ['table' => 'log_action', 'tableAlias' => 'fe_cat2', 'joinOn' => 'log_link_visit_action.idaction_product_cat2 = fe_cat2.idaction'],
            ],
            implode(' AND ', [
                'log_link_visit_action.server_time >= ?',
                'log_link_visit_action.server_time <= ?',
                'log_link_visit_action.idsite IN (' . implode(',', array_fill(0, count($logAggregator->getGeneralQueryBindParams()) - 2, '?')) . ')',
                '(log_link_visit_action.idaction_product_name IS NOT NULL OR log_link_visit_action.idaction_product_sku IS NOT NULL)',
            ]),
            'log_link_visit_action.idaction_product_name, log_link_visit_action.idaction_product_sku, '
                . 'log_link_visit_action.idaction_product_cat, log_link_visit_action.idaction_product_cat2',
            false
        );

        $statement = $logAggregator->getDb()->query($query['sql'], $query['bind']);
        while ($row = $statement->fetch()) {
            $aggregator->addProductViews(
                (string) $row['name'],
                (string) $row['sku'],
                [(string) $row['category1'], (string) $row['category2']],
                (int) $row['views']
            );
        }
    }
}
