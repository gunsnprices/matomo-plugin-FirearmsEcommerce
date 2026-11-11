<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Columns\Metrics;

use Piwik\Archive\DataTableFactory;
use Piwik\DataTable;
use Piwik\NumberFormatter;
use Piwik\Plugin\ProcessedMetric;
use Piwik\Site;

/**
 * Processed metrics that are quotients of two archived columns and are shown as money.
 */
abstract class Base extends ProcessedMetric
{
    /** @var int|null */
    protected $idSite;

    /** Digits after the decimal point when the value is shown. */
    protected $precision = 2;

    public function beforeFormat($report, DataTable $table)
    {
        $this->idSite = DataTableFactory::getSiteIdFromMetadata($table);
        return !empty($this->idSite);
    }

    public function format($value, \Piwik\Metrics\Formatter $formatter)
    {
        if (empty($this->idSite)) {
            return $value;
        }
        return NumberFormatter::getInstance()->formatCurrency($value, Site::getCurrencySymbolFor($this->idSite), $this->precision);
    }

    public function getSemanticType(): ?string
    {
        return \Piwik\Columns\Dimension::TYPE_MONEY;
    }

    /**
     * @return float|false the quotient, or false (no value) when the divisor is zero
     */
    protected function quotient($dividend, $divisor, int $precision)
    {
        $divisor = (float) $divisor;
        if ($divisor == 0.0) {
            return false;
        }
        return round((float) $dividend / $divisor, $precision);
    }
}
