<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Reports;

use Piwik\Piwik;
use Piwik\Plugins\FirearmsEcommerce\Columns\Brand;

/**
 * Sales by brand, for all products.
 */
class GetBrands extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension = new Brand();
        $this->name = Piwik::translate('FirearmsEcommerce_ReportBrands');
        $this->documentation = Piwik::translate('FirearmsEcommerce_ReportBrandsDocumentation');
        $this->order = 3;
    }
}
