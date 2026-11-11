<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Piwik;

/**
 * Average cost per round of the ammunition sold: revenue of the boxes whose round count is known / rounds sold.
 */
class CostPerRound extends Base
{
    protected $precision = 3;

    public function getName()
    {
        return 'cost_per_round';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('FirearmsEcommerce_ColumnCostPerRound');
    }

    public function getDocumentation()
    {
        return Piwik::translate('FirearmsEcommerce_ColumnCostPerRoundDocumentation');
    }

    public function compute(Row $row)
    {
        return $this->quotient($this->getMetric($row, 'rounds_revenue'), $this->getMetric($row, 'rounds'), 4);
    }

    public function getDependentMetrics()
    {
        return ['rounds_revenue', 'rounds'];
    }
}
