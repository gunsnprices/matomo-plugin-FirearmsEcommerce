<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Reports;

use Piwik\Common;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Piwik\Plugins\FirearmsEcommerce\Columns\Metrics\AveragePrice;
use Piwik\Report\ReportWidgetFactory;
use Piwik\Site;
use Piwik\Widget\WidgetsList;

/**
 * Common settings of the plugin's reports: they are shown on the Ecommerce > Firearms & Ammo page, and only for
 * websites with Ecommerce enabled.
 */
abstract class Base extends Report
{
    /** The reports have up to seven columns, so each one uses the full width of the page. */
    protected $isWide = true;

    protected function init()
    {
        $this->categoryId = 'Goals_Ecommerce';
        $this->subcategoryId = 'FirearmsEcommerce_Subcategory';
        $this->metrics = ['revenue', 'quantity', 'orders', 'product_views'];
        $this->processedMetrics = [new AveragePrice()];
        $this->defaultSortColumn = 'revenue';
    }

    public function isEnabled()
    {
        $idSite = Common::getRequestVar('idSite', false, 'int');
        return !empty($idSite) && $this->isEcommerceEnabled((int) $idSite);
    }

    public function checkIsEnabled()
    {
        if (!$this->isEnabled()) {
            throw new \Exception(Piwik::translate('General_ExceptionReportNotEnabled'));
        }
    }

    public function configureReportMetadata(&$availableReports, $infos)
    {
        $idSite = $infos['idSite'] ?? null;
        if (!empty($idSite) && is_numeric($idSite) && $this->isEcommerceEnabled((int) $idSite)) {
            $availableReports[] = $this->buildReportMetadata();
        }
    }

    public function configureWidgets(WidgetsList $widgetsList, ReportWidgetFactory $factory)
    {
        $widget = $factory->createWidget();
        if ($this->isWide) {
            $widget->setIsWide();
        }
        $widgetsList->addWidgetConfig($widget);
    }

    public function getMetrics()
    {
        return [
            'revenue' => Piwik::translate('General_ProductRevenue'),
            'quantity' => Piwik::translate('General_Quantity'),
            'orders' => Piwik::translate('General_UniquePurchases'),
            'product_views' => Piwik::translate('FirearmsEcommerce_ColumnProductViews'),
        ];
    }

    public function getMetricsDocumentation()
    {
        return [
            'revenue' => Piwik::translate('FirearmsEcommerce_ColumnRevenueDocumentation'),
            'quantity' => Piwik::translate('FirearmsEcommerce_ColumnQuantityDocumentation'),
            'orders' => Piwik::translate('FirearmsEcommerce_ColumnOrdersDocumentation'),
            'product_views' => Piwik::translate('FirearmsEcommerce_ColumnProductViewsDocumentation'),
            'avg_price' => Piwik::translate('FirearmsEcommerce_ColumnAveragePriceDocumentation'),
        ];
    }

    public function configureView(ViewDataTable $view)
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');

        $view->config->show_exclude_low_population = false;
        $view->config->show_table_all_columns = false;
        $view->config->show_goals = false;
        $view->config->addTranslations(['label' => $this->dimension ? $this->dimension->getName() : $this->name]);
        $view->config->addTranslations($this->getMetrics());
        foreach ($this->processedMetrics as $metric) {
            $view->config->addTranslation($metric->getName(), $metric->getTranslatedName());
        }

        if (!($view instanceof Evolution)) {
            $formatter = [new Formatter(), 'getPrettyMoney'];
            $view->config->filters[] = ['ColumnCallbackReplace', [$this->getMoneyColumns(), $formatter, [$idSite]]];
        }

        $view->requestConfig->filter_sort_column = 'revenue';
        $view->requestConfig->filter_sort_order = 'desc';
        $view->config->columns_to_display = $this->getColumnsToDisplay();
    }

    /** @return string[] */
    protected function getColumnsToDisplay(): array
    {
        return ['label', 'revenue', 'quantity', 'orders', 'avg_price', 'product_views'];
    }

    /** @return string[] archived money columns, formatted by configureView */
    protected function getMoneyColumns(): array
    {
        return ['revenue'];
    }

    private function isEcommerceEnabled(int $idSite): bool
    {
        try {
            return Site::isEcommerceEnabledFor($idSite);
        } catch (\Exception $e) {
            return false;
        }
    }
}
