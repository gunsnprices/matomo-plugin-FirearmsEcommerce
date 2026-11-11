<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Columns\Metrics;

use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugin\ProcessedMetric;

/**
 * Rounds per box (or per unit sold): rounds / quantity.
 */
class RoundsPerBox extends ProcessedMetric
{
    public function getName()
    {
        return 'rounds_per_box';
    }

    public function getTranslatedName()
    {
        return Piwik::translate('FirearmsEcommerce_ColumnRoundsPerBox');
    }

    public function getDocumentation()
    {
        return Piwik::translate('FirearmsEcommerce_ColumnRoundsPerBoxDocumentation');
    }

    public function compute(Row $row)
    {
        $quantity = (float) $this->getMetric($row, 'quantity');
        if ($quantity == 0.0) {
            return false;
        }
        return round((float) $this->getMetric($row, 'rounds') / $quantity, 1);
    }

    public function format($value, Formatter $formatter)
    {
        return $formatter->getPrettyNumber($value, floor((float) $value) == (float) $value ? 0 : 1);
    }

    public function getDependentMetrics()
    {
        return ['rounds', 'quantity'];
    }
}
