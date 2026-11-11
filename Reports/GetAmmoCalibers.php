<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Reports;

use Piwik\Piwik;
use Piwik\Plugins\FirearmsEcommerce\Columns\Caliber;
use Piwik\Plugins\FirearmsEcommerce\Columns\Metrics\CostPerRound;

/**
 * Ammunition sales by caliber, with rounds sold and the average cost per round.
 */
class GetAmmoCalibers extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension = new Caliber();
        $this->name = Piwik::translate('FirearmsEcommerce_ReportAmmoCalibers');
        $this->documentation = Piwik::translate('FirearmsEcommerce_ReportAmmoCalibersDocumentation');
        $this->metrics[] = 'rounds';
        $this->metrics[] = 'rounds_revenue';
        $this->processedMetrics[] = new CostPerRound();
        $this->order = 4;
    }

    public function getMetrics()
    {
        return parent::getMetrics() + [
            'rounds' => Piwik::translate('FirearmsEcommerce_ColumnRounds'),
            'rounds_revenue' => Piwik::translate('FirearmsEcommerce_ColumnRoundsRevenue'),
        ];
    }

    public function getMetricsDocumentation()
    {
        return parent::getMetricsDocumentation() + [
            'rounds' => Piwik::translate('FirearmsEcommerce_ColumnRoundsDocumentation'),
            'rounds_revenue' => Piwik::translate('FirearmsEcommerce_ColumnRoundsRevenueDocumentation'),
            'cost_per_round' => Piwik::translate('FirearmsEcommerce_ColumnCostPerRoundDocumentation'),
        ];
    }

    protected function getColumnsToDisplay(): array
    {
        return ['label', 'revenue', 'quantity', 'orders', 'rounds', 'cost_per_round', 'product_views'];
    }

    protected function getMoneyColumns(): array
    {
        return ['revenue', 'rounds_revenue'];
    }
}
