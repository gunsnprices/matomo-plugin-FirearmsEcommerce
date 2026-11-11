<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Categories;

use Piwik\Category\Subcategory;
use Piwik\Piwik;

/**
 * The "Firearms & Ammo" page in the Ecommerce menu.
 */
class FirearmsSubcategory extends Subcategory
{
    protected $categoryId = 'Goals_Ecommerce';
    protected $id = 'FirearmsEcommerce_Subcategory';
    protected $order = 12;

    public function getHelp()
    {
        return '<p>' . Piwik::translate('FirearmsEcommerce_SubcategoryHelp1') . '</p>'
            . '<p>' . Piwik::translate('FirearmsEcommerce_SubcategoryHelp2') . '</p>';
    }
}
