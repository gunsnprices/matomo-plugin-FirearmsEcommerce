<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\FirearmsEcommerce\Columns\ProductType;

/**
 * Sales by product type: firearms, ammunition and other products.
 */
class GetProductTypes extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension = new ProductType();
        $this->name = Piwik::translate('FirearmsEcommerce_ReportProductTypes');
        $this->documentation = Piwik::translate('FirearmsEcommerce_ReportProductTypesDocumentation');
        $this->order = 1;
    }

    public function configureView(ViewDataTable $view)
    {
        parent::configureView($view);
        $view->config->show_search = false;
        $view->config->show_limit_control = false;
        $view->config->show_pagination_control = false;
    }
}
