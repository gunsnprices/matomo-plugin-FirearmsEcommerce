<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Reports;

use Piwik\Piwik;
use Piwik\Plugins\FirearmsEcommerce\Columns\AmmoProduct;
use Piwik\Plugins\FirearmsEcommerce\Columns\Metrics\CostPerRound;
use Piwik\Plugins\FirearmsEcommerce\Columns\Metrics\RoundsPerBox;

/**
 * Ammunition products sold, with rounds per box and cost per round. Lists the products whose round count is known.
 */
class GetAmmoCostPerRound extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension = new AmmoProduct();
        $this->name = Piwik::translate('FirearmsEcommerce_ReportAmmoCostPerRound');
        $this->documentation = Piwik::translate('FirearmsEcommerce_ReportAmmoCostPerRoundDocumentation');
        $this->metrics = ['revenue', 'quantity', 'orders', 'rounds', 'rounds_revenue'];
        $this->processedMetrics[] = new RoundsPerBox();
        $this->processedMetrics[] = new CostPerRound();
        $this->order = 5;
    }

    public function getMetrics()
    {
        $metrics = parent::getMetrics() + [
            'rounds' => Piwik::translate('FirearmsEcommerce_ColumnRounds'),
            'rounds_revenue' => Piwik::translate('FirearmsEcommerce_ColumnRoundsRevenue'),
        ];
        unset($metrics['product_views']);
        return $metrics;
    }

    public function getMetricsDocumentation()
    {
        $docs = parent::getMetricsDocumentation() + [
            'rounds' => Piwik::translate('FirearmsEcommerce_ColumnRoundsDocumentation'),
            'rounds_revenue' => Piwik::translate('FirearmsEcommerce_ColumnRoundsRevenueDocumentation'),
            'rounds_per_box' => Piwik::translate('FirearmsEcommerce_ColumnRoundsPerBoxDocumentation'),
            'cost_per_round' => Piwik::translate('FirearmsEcommerce_ColumnCostPerRoundDocumentation'),
        ];
        unset($docs['product_views']);
        return $docs;
    }

    protected function getColumnsToDisplay(): array
    {
        return ['label', 'revenue', 'quantity', 'avg_price', 'rounds_per_box', 'cost_per_round'];
    }

    protected function getMoneyColumns(): array
    {
        return ['revenue', 'rounds_revenue'];
    }
}
