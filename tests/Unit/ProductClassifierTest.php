<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductClassifier;

/**
 * @group FirearmsEcommerce
 * @group FirearmsEcommerce_Unit
 * @group Plugins
 */
class ProductClassifierTest extends TestCase
{
    /**
     * @dataProvider getTitles
     */
    public function testClassifiesProductNames(string $name, string $type, string $brand, string $caliber, ?int $rounds)
    {
        $result = (new ProductClassifier())->classify($name, 'SKU-1');

        $this->assertSame(
            ['type' => $type, 'brand' => $brand, 'caliber' => $caliber, 'rounds' => $rounds],
            ['type' => $result['type'], 'brand' => $result['brand'], 'caliber' => $result['caliber'], 'rounds' => $result['rounds']],
            $name
        );
    }

    public function getTitles(): array
    {
        $firearm = ProductClassifier::TYPE_FIREARM;
        $ammo = ProductClassifier::TYPE_AMMO;
        $other = ProductClassifier::TYPE_OTHER;

        return [
            // firearms: model known to the library
            ['Glock 19 Gen 5 MOS 9mm 4.02in 15rd Pistol PA195S203MOS', $firearm, 'Glock', '9×19mm Parabellum', null],
            ['Sig Sauer P365 XL 9mm 3.7" 12+1 Optic Ready', $firearm, 'Sig Sauer', '9×19mm Parabellum', null],
            ['S&W Model 686 357 Magnum 4"', $firearm, 'Smith & Wesson', '.357 Mag', null],
            ['Mossberg 590 Persuader 12/20 BL/SY 3"', $firearm, 'Mossberg', '12 Gauge', null],
            ['Ruger 10/22 Carbine .22 LR 18.5in 10rd', $firearm, 'Ruger', '.22 LR', null],
            ['Henry Big Boy .44 Mag/.44 Spl 20in', $firearm, 'Henry', '.44 Magnum', null],
            // firearms: brand not in the model dictionaries
            ['Palmetto State Armory Dagger Compact 9mm 3.9in 15rd', $firearm, 'Palmetto State Armory', '9×19mm Parabellum', null],
            ['PSA PA-15 16" 5.56 Nitride Carbine-Length Rifle', $firearm, 'Palmetto State Armory', '5.56×45mm NATO', null],
            ['Bersa Thunder 380', $firearm, 'Bersa', '.380 ACP', null],
            ['PSA Dagger 9mm 15rd w/ 2 magazines', $firearm, 'Palmetto State Armory', '9×19mm Parabellum', null],
            // ammunition
            ['Federal American Eagle 5.56x45mm 55gr FMJ 1000 Rounds', $ammo, 'Federal', '5.56×45mm NATO', 1000],
            ['Winchester USA 9mm Luger 115 Grain FMJ 50 Round Box', $ammo, 'Winchester', '9×19mm Parabellum', 50],
            ['Winchester 9mm 50 rounds', $ammo, 'Winchester', '9×19mm Parabellum', 50],
            ['CCI Mini-Mag 22 LR 40gr Copper Plated Round Nose 100rd', $ammo, 'CCI', '.22 LR', 100],
            ['Sellier & Bellot 7.62x39 123gr FMJ 20rd', $ammo, 'Sellier & Bellot', '7.62x39mm', 20],
            ['Remington 12 Gauge 2-3/4" 00 Buck 5 Rounds', $ammo, 'Remington', '12 Gauge', 5],
            ['Fiocchi 12ga 2.75" #8 Shot 25 Shells', $ammo, 'Fiocchi', '12 Gauge', 25],
            ['Federal Top Gun 12 Gauge Shotgun Shells 25rd', $ammo, 'Federal', '12 Gauge', 25],
            ['Speer Gold Dot 40 S&W 180gr JHP 20/box', $ammo, 'Speer', '.40 S&W', 20],
            ['Hornady Critical Defense 9mm 115gr FTX', $ammo, 'Hornady', '9×19mm Parabellum', null],
            // other products: accessories and parts, even when they name a caliber or a gun
            ['Magpul PMAG 30 AR/M4 Gen M2 MOE 5.56x45 30rd Magazine', $other, 'Magpul', '', null],
            ['Glock 19 OEM 15 Round Magazine 9mm', $other, 'Glock', '', null],
            ['Ruger 10/22 25rd BX-25 Magazine', $other, 'Ruger', '', null],
            ['Safariland 6360 ALS Holster Glock 17', $other, 'Safariland', '', null],
            ['Holster for Glock 19', $other, '', '', null],
            ['Vortex Crossfire II 3-9x40 Riflescope', $other, 'Vortex', '', null],
            ['Holosun HS507C X2 Red Dot', $other, 'Holosun', '', null],
            ['Hornady Lock-N-Load .308 Win Die Set', $other, 'Hornady', '', null],
            ['MTM Ammo Box 9mm 100 Round', $other, '', '', null],
            ['Gift Card $50', $other, '', '', null],
        ];
    }

    public function testDecodesHtmlEntities()
    {
        $result = (new ProductClassifier())->classify('Sellier &amp; Bellot 9mm 124gr FMJ 50rd');
        $this->assertSame(ProductClassifier::TYPE_AMMO, $result['type']);
        $this->assertSame('Sellier & Bellot', $result['brand']);
        $this->assertSame(50, $result['rounds']);
    }

    public function testCategoriesDecideTheType()
    {
        $classifier = new ProductClassifier();

        $this->assertSame(ProductClassifier::TYPE_AMMO, $classifier->classify('Winchester 9mm 50', '', ['Ammunition', 'Handgun Ammo'])['type']);
        $this->assertSame(ProductClassifier::TYPE_OTHER, $classifier->classify('Glock 19 Gen5 9mm', '', ['Magazines'])['type']);
        $this->assertSame(ProductClassifier::TYPE_OTHER, $classifier->classify('MTM 9mm 100', '', ['Ammo Cans & Boxes'])['type']);
        $this->assertSame(ProductClassifier::TYPE_FIREARM, $classifier->classify('Taurus GX4 9mm', '', ['Firearms', 'Handguns'])['type']);
        // categories that say nothing about the type leave it to the name
        $this->assertSame(ProductClassifier::TYPE_FIREARM, $classifier->classify('Taurus GX4 9mm', '', ['Sale', 'New arrivals'])['type']);
    }

    public function testCategoriesCanBeIgnored()
    {
        $classifier = new ProductClassifier(ProductClassifier::FIELD_NAME, false);
        $result = $classifier->classify('Glock 19 Gen5 9mm', '', ['Magazines']);
        $this->assertSame(ProductClassifier::TYPE_FIREARM, $result['type']);
        $this->assertSame('9×19mm Parabellum', $result['caliber']);
    }

    public function testReadsTheChosenField()
    {
        $bySku = new ProductClassifier(ProductClassifier::FIELD_SKU);
        $result = $bySku->classify('Pistol', 'GLOCK-19-GEN5-9MM');
        $this->assertSame(ProductClassifier::TYPE_FIREARM, $result['type']);
        $this->assertSame('Glock', $result['brand']);
        $this->assertSame('9×19mm Parabellum', $result['caliber']);
        $this->assertSame('GLOCK-19-GEN5-9MM', $result['text']);

        // an empty SKU falls back to the name
        $this->assertSame('Glock 17 9mm', $bySku->text('Glock 17 9mm', ''));

        $both = new ProductClassifier(ProductClassifier::FIELD_NAME_SKU);
        $result = $both->classify('Hornady Critical Defense 115gr FTX 25rd', '9MM-LUGER');
        $this->assertSame(ProductClassifier::TYPE_AMMO, $result['type']);
        $this->assertSame('9×19mm Parabellum', $result['caliber']);
        $this->assertSame(25, $result['rounds']);

        // the name alone has no caliber
        $byName = new ProductClassifier(ProductClassifier::FIELD_NAME);
        $this->assertSame('', $byName->classify('Hornady Critical Defense 115gr FTX 25rd', '9MM-LUGER')['caliber']);
    }

    public function testUnknownFieldFallsBackToName()
    {
        $classifier = new ProductClassifier('price');
        $this->assertSame('Glock 17', $classifier->text('Glock 17', 'SKU'));
    }

    public function testEmptyAndInvalidInput()
    {
        $classifier = new ProductClassifier();
        $this->assertSame(ProductClassifier::TYPE_OTHER, $classifier->classify('', '')['type']);
        $result = $classifier->classify("Glock 19 \xff\xfe 9mm", '');
        $this->assertSame(ProductClassifier::TYPE_FIREARM, $result['type']);
        $this->assertSame('Glock', $result['brand']);
    }
}
