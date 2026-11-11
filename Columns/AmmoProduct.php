<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Columns;

use Piwik\Columns\Dimension;

/**
 * Ammunition product (its name as tracked) whose round count is known.
 *
 * The value is not stored in the log tables; it is computed when reports are archived.
 */
class AmmoProduct extends Dimension
{
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'FirearmsEcommerce_AmmoProduct';
    protected $namePlural = 'FirearmsEcommerce_AmmoProducts';
    protected $category = 'Goals_Ecommerce';
}
