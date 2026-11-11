<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce;

/**
 * Ecommerce reports for gun and ammunition shops: revenue, quantity and orders by product type, brand and caliber,
 * and the cost per round of ammunition.
 *
 * Reports, record builders, dimensions and settings are found by Matomo from their directories, so this class has no
 * code of its own.
 */
class FirearmsEcommerce extends \Piwik\Plugin
{
    public function isTrackerPlugin()
    {
        return false;
    }
}
