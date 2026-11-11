<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce;

use Piwik\Piwik;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductClassifier;
use Piwik\Settings\FieldConfig;
use Piwik\Settings\Measurable\MeasurableSetting;

/**
 * Website settings, shown on the website's edit form (Administration > Websites > Manage).
 */
class MeasurableSettings extends \Piwik\Settings\Measurable\MeasurableSettings
{
    /** @var MeasurableSetting */
    public $productField;

    /** @var MeasurableSetting */
    public $useCategories;

    protected function init()
    {
        $this->title = Piwik::translate('FirearmsEcommerce_PluginName');

        $this->productField = $this->makeSetting(
            'product_field',
            ProductClassifier::FIELD_NAME,
            FieldConfig::TYPE_STRING,
            function (FieldConfig $field) {
                $field->title = Piwik::translate('FirearmsEcommerce_SettingProductField');
                $field->inlineHelp = Piwik::translate('FirearmsEcommerce_SettingProductFieldHelp');
                $field->uiControl = FieldConfig::UI_CONTROL_SINGLE_SELECT;
                $field->availableValues = [
                    ProductClassifier::FIELD_NAME => Piwik::translate('FirearmsEcommerce_FieldName'),
                    ProductClassifier::FIELD_SKU => Piwik::translate('FirearmsEcommerce_FieldSku'),
                    ProductClassifier::FIELD_NAME_SKU => Piwik::translate('FirearmsEcommerce_FieldNameSku'),
                ];
                $field->validate = function ($value) {
                    $allowed = [ProductClassifier::FIELD_NAME, ProductClassifier::FIELD_SKU, ProductClassifier::FIELD_NAME_SKU];
                    if (!in_array($value, $allowed, true)) {
                        throw new \Exception(Piwik::translate('FirearmsEcommerce_SettingProductFieldInvalid'));
                    }
                };
            }
        );

        $this->useCategories = $this->makeSetting(
            'use_categories',
            true,
            FieldConfig::TYPE_BOOL,
            function (FieldConfig $field) {
                $field->title = Piwik::translate('FirearmsEcommerce_SettingUseCategories');
                $field->inlineHelp = Piwik::translate('FirearmsEcommerce_SettingUseCategoriesHelp');
                $field->uiControl = FieldConfig::UI_CONTROL_CHECKBOX;
            }
        );
    }
}
