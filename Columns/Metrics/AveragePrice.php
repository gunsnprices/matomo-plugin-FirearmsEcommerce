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
 * Average price paid per unit: revenue / quantity.
 */
class AveragePrice extends Base
{
    public function getName()
    {
        return 'avg_price';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('General_AveragePrice');
    }

    public function getDocumentation()
    {
        return Piwik::translate('FirearmsEcommerce_ColumnAveragePriceDocumentation');
    }

    public function compute(Row $row)
    {
        return $this->quotient($this->getMetric($row, 'revenue'), $this->getMetric($row, 'quantity'), 2);
    }

    public function getDependentMetrics()
    {
        return ['revenue', 'quantity'];
    }
}
