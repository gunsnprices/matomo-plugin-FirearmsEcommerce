<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Classifier;

/**
 * Groups ecommerce order items and product views by product type, brand and caliber.
 *
 * The archiver feeds it the rows it reads from the Matomo log tables; getReports() returns one array per report,
 * label => metrics. All metrics are sums, so reports for a week or a month are the sums of the daily reports.
 * "orders" counts each order once per row, even when the order has several products of that row.
 *
 * The class has no Matomo dependencies, so it can be unit tested on its own.
 */
final class ProductAggregator
{
    public const REPORT_TYPES = 'types';
    public const REPORT_BRANDS = 'brands';
    public const REPORT_FIREARM_CALIBERS = 'firearm_calibers';
    public const REPORT_AMMO_CALIBERS = 'ammo_calibers';
    public const REPORT_AMMO_PRODUCTS = 'ammo_products';

    /** Label of the row for products without a known brand or caliber. Reports show it translated. */
    public const LABEL_UNKNOWN = '__unknown__';

    public const REVENUE = 'revenue';
    public const QUANTITY = 'quantity';
    public const ORDERS = 'orders';
    public const PRODUCT_VIEWS = 'product_views';
    public const ROUNDS = 'rounds';
    public const ROUNDS_REVENUE = 'rounds_revenue';

    /** @var ProductClassifier */
    private $classifier;

    /** @var array<string, array<string, array<string, float|int>>> report => label => metrics */
    private $rows = [];

    /** @var array<string, array<string, array<string, bool>>> report => label => order ids */
    private $orders = [];

    public function __construct(ProductClassifier $classifier)
    {
        $this->classifier = $classifier;
        foreach (self::reportNames() as $report) {
            $this->rows[$report] = [];
            $this->orders[$report] = [];
        }
    }

    /** @return string[] */
    public static function reportNames(): array
    {
        return [
            self::REPORT_TYPES,
            self::REPORT_BRANDS,
            self::REPORT_FIREARM_CALIBERS,
            self::REPORT_AMMO_CALIBERS,
            self::REPORT_AMMO_PRODUCTS,
        ];
    }

    /**
     * Adds one product of one order.
     *
     * @param string   $orderId    the order ID
     * @param float    $price      unit price
     * @param float    $quantity   units bought
     * @param string[] $categories product categories, in order
     */
    public function addOrderItem(string $orderId, string $name, string $sku, array $categories, float $price, float $quantity): void
    {
        $product = $this->classifier->classify($name, $sku, $categories);
        $revenue = $price * $quantity;
        $metrics = [self::REVENUE => $revenue, self::QUANTITY => $quantity];

        $ammoMetrics = $metrics;
        if ($product['rounds'] !== null) {
            $ammoMetrics[self::ROUNDS] = $product['rounds'] * $quantity;
            $ammoMetrics[self::ROUNDS_REVENUE] = $revenue;
        }

        foreach ($this->rowsFor($product, $name, $sku, true) as [$report, $label]) {
            $isAmmoReport = $report === self::REPORT_AMMO_CALIBERS || $report === self::REPORT_AMMO_PRODUCTS;
            $this->add($report, $label, $isAmmoReport ? $ammoMetrics : $metrics);
            $this->orders[$report][$label][$orderId] = true;
        }
    }

    /**
     * Adds product page views.
     *
     * @param string[] $categories product categories, in order
     */
    public function addProductViews(string $name, string $sku, array $categories, int $views): void
    {
        if ($views <= 0) {
            return;
        }
        $product = $this->classifier->classify($name, $sku, $categories);
        foreach ($this->rowsFor($product, $name, $sku, false) as [$report, $label]) {
            $this->add($report, $label, [self::PRODUCT_VIEWS => $views]);
        }
    }

    /**
     * @return array<string, array<string, array<string, float|int>>> report => label => metrics. Every row has
     *         revenue, quantity and orders, and all but the ammo_products rows have product_views. Rows of the
     *         ammunition reports also have rounds and rounds_revenue (the revenue of the items whose round count is
     *         known).
     */
    public function getReports(): array
    {
        $reports = [];
        foreach ($this->rows as $report => $rows) {
            $isAmmoReport = $report === self::REPORT_AMMO_CALIBERS || $report === self::REPORT_AMMO_PRODUCTS;
            $reports[$report] = [];
            foreach ($rows as $label => $metrics) {
                $row = [
                    self::REVENUE => round($metrics[self::REVENUE] ?? 0, 2),
                    self::QUANTITY => self::number($metrics[self::QUANTITY] ?? 0),
                    self::ORDERS => isset($this->orders[$report][$label]) ? count($this->orders[$report][$label]) : 0,
                ];
                if ($report !== self::REPORT_AMMO_PRODUCTS) {
                    $row[self::PRODUCT_VIEWS] = (int) ($metrics[self::PRODUCT_VIEWS] ?? 0);
                }
                if ($isAmmoReport) {
                    $row[self::ROUNDS] = self::number($metrics[self::ROUNDS] ?? 0);
                    $row[self::ROUNDS_REVENUE] = round($metrics[self::ROUNDS_REVENUE] ?? 0, 2);
                }
                $reports[$report][(string) $label] = $row;
            }
        }
        return $reports;
    }

    /**
     * The report rows a product belongs to.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function rowsFor(array $product, string $name, string $sku, bool $isOrder): array
    {
        $rows = [
            [self::REPORT_TYPES, $product['type']],
            [self::REPORT_BRANDS, $product['brand'] !== '' ? $product['brand'] : self::LABEL_UNKNOWN],
        ];
        $caliber = $product['caliber'] !== '' ? $product['caliber'] : self::LABEL_UNKNOWN;
        if ($product['type'] === ProductClassifier::TYPE_FIREARM) {
            $rows[] = [self::REPORT_FIREARM_CALIBERS, $caliber];
        } elseif ($product['type'] === ProductClassifier::TYPE_AMMO) {
            $rows[] = [self::REPORT_AMMO_CALIBERS, $caliber];
            // The cost per round report lists sold boxes whose round count is known.
            if ($isOrder && $product['rounds'] !== null) {
                $label = trim(html_entity_decode($name !== '' ? $name : $sku, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $rows[] = [self::REPORT_AMMO_PRODUCTS, $label !== '' ? $label : self::LABEL_UNKNOWN];
            }
        }
        return $rows;
    }

    private function add(string $report, string $label, array $metrics): void
    {
        if (!isset($this->rows[$report][$label])) {
            $this->rows[$report][$label] = [];
        }
        foreach ($metrics as $column => $value) {
            $this->rows[$report][$label][$column] = ($this->rows[$report][$label][$column] ?? 0) + $value;
        }
    }

    /** Integers stay integers in the archive; fractional quantities are kept as floats. */
    private static function number($value)
    {
        $value = (float) $value;
        return floor($value) == $value ? (int) $value : round($value, 4);
    }
}
