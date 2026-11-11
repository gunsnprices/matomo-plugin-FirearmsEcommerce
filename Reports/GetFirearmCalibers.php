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

/**
 * Firearm sales by caliber.
 */
class GetFirearmCalibers extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension = new Caliber();
        $this->name = Piwik::translate('FirearmsEcommerce_ReportFirearmCalibers');
        $this->documentation = Piwik::translate('FirearmsEcommerce_ReportFirearmCalibersDocumentation');
        $this->order = 2;
    }
}
