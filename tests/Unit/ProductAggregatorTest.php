<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductAggregator as A;
use Piwik\Plugins\FirearmsEcommerce\Classifier\ProductClassifier;

/**
 * @group FirearmsEcommerce
 * @group FirearmsEcommerce_Unit
 * @group Plugins
 */
class ProductAggregatorTest extends TestCase
{
    private const GLOCK = 'Glock 19 Gen 5 MOS 9mm 4.02in 15rd Pistol';
    private const SIG = 'Sig Sauer P365 XL 9mm 3.7" 12+1';
    private const AMMO_9 = 'Winchester USA 9mm Luger 115 Grain FMJ 50 Round Box';
    private const AMMO_9_NO_COUNT = 'Hornady Critical Defense 9mm 115gr FTX';
    private const AMMO_556 = 'Federal American Eagle 5.56x45mm 55gr FMJ 1000 Rounds';
    private const MAG = 'Magpul PMAG 30 AR/M4 Gen M2 MOE 5.56x45 30rd Magazine';
    private const GIFT = 'Gift Card';

    private function aggregate(): array
    {
        $a = new A(new ProductClassifier());
        // order 1: two 9mm pistols and two boxes of 9mm
        $a->addOrderItem('1', self::GLOCK, 'G19', [], 579.99, 1);
        $a->addOrderItem('1', self::SIG, 'P365', [], 629.00, 1);
        $a->addOrderItem('1', self::AMMO_9, 'Q4318', [], 15.99, 2);
        // order 2: 5.56 ammo, a magazine, a gift card, and 9mm ammo without a round count
        $a->addOrderItem('2', self::AMMO_556, 'AE223', [], 449.99, 1);
        $a->addOrderItem('2', self::MAG, 'MAG571', [], 14.95, 3);
        $a->addOrderItem('2', self::GIFT, 'GIFT50', [], 50, 1);
        $a->addOrderItem('2', self::AMMO_9_NO_COUNT, '90250', [], 24.99, 1);
        // order 3: one more Glock
        $a->addOrderItem('3', self::GLOCK, 'G19', [], 569.99, 1);
        // product page views
        $a->addProductViews(self::GLOCK, 'G19', [], 10);
        $a->addProductViews(self::AMMO_9, 'Q4318', [], 4);
        $a->addProductViews('Taurus G3C 9mm 3.2in 12rd', 'G3C', [], 3); // viewed, never bought
        $a->addProductViews(self::GIFT, 'GIFT50', [], 0);
        return $a->getReports();
    }

    public function testProductTypes()
    {
        $types = $this->aggregate()[A::REPORT_TYPES];

        $this->assertSame(['revenue' => 1778.98, 'quantity' => 3, 'orders' => 2, 'product_views' => 13], $types['firearm']);
        $this->assertSame(['revenue' => 506.96, 'quantity' => 4, 'orders' => 2, 'product_views' => 4], $types['ammo']);
        $this->assertSame(['revenue' => 94.85, 'quantity' => 4, 'orders' => 1, 'product_views' => 0], $types['other']);
    }

    public function testRevenueAndQuantityAddUpInEveryReport()
    {
        $reports = $this->aggregate();
        $expectedRevenue = 579.99 + 629.00 + 2 * 15.99 + 449.99 + 3 * 14.95 + 50 + 24.99 + 569.99;

        foreach ([A::REPORT_TYPES, A::REPORT_BRANDS] as $name) {
            $this->assertEqualsWithDelta($expectedRevenue, array_sum(array_column($reports[$name], 'revenue')), 0.001, $name);
            $this->assertEquals(11, array_sum(array_column($reports[$name], 'quantity')), $name);
        }
    }

    public function testOrdersAreCountedOncePerRow()
    {
        $calibers = $this->aggregate()[A::REPORT_FIREARM_CALIBERS];
        // order 1 has two 9mm pistols, order 3 one: two orders, not three
        $this->assertSame(['revenue' => 1778.98, 'quantity' => 3, 'orders' => 2, 'product_views' => 13], $calibers['9×19mm Parabellum']);
        $this->assertSame(['9×19mm Parabellum'], array_keys($calibers));
    }

    public function testAmmoCalibersHaveRoundsAndCostPerRoundInputs()
    {
        $calibers = $this->aggregate()[A::REPORT_AMMO_CALIBERS];

        // 2 boxes of 50 rounds counted; the box without a round count adds revenue but no rounds
        $this->assertSame(
            ['revenue' => 56.97, 'quantity' => 3, 'orders' => 2, 'product_views' => 4, 'rounds' => 100, 'rounds_revenue' => 31.98],
            $calibers['9×19mm Parabellum']
        );
        $this->assertSame(
            ['revenue' => 449.99, 'quantity' => 1, 'orders' => 1, 'product_views' => 0, 'rounds' => 1000, 'rounds_revenue' => 449.99],
            $calibers['5.56×45mm NATO']
        );
    }

    public function testAmmoProductsListOnlyKnownRoundCounts()
    {
        $products = $this->aggregate()[A::REPORT_AMMO_PRODUCTS];

        $this->assertSame([self::AMMO_9, self::AMMO_556], array_keys($products));
        $this->assertSame(
            ['revenue' => 31.98, 'quantity' => 2, 'orders' => 1, 'rounds' => 100, 'rounds_revenue' => 31.98],
            $products[self::AMMO_9]
        );
    }

    public function testBrandsAndUnknownLabel()
    {
        $brands = $this->aggregate()[A::REPORT_BRANDS];

        $this->assertSame(1149.98, $brands['Glock']['revenue']);
        $this->assertSame(2, $brands['Glock']['orders']);
        $this->assertSame(10, $brands['Glock']['product_views']);
        $this->assertSame(3, $brands['Taurus']['product_views']);
        $this->assertSame(0, $brands['Taurus']['orders']);
        $this->assertSame(['revenue' => 50.0, 'quantity' => 1, 'orders' => 1, 'product_views' => 0], $brands[A::LABEL_UNKNOWN]);
    }

    public function testFractionalQuantitiesAreKept()
    {
        $a = new A(new ProductClassifier());
        $a->addOrderItem('1', self::AMMO_9, 'Q4318', [], 15.99, 1.5);
        $row = $a->getReports()[A::REPORT_AMMO_CALIBERS]['9×19mm Parabellum'];
        $this->assertSame(1.5, $row['quantity']);
        $this->assertSame(75, $row['rounds']);
    }

    public function testEmptyAggregatorHasAllReports()
    {
        $reports = (new A(new ProductClassifier()))->getReports();
        $this->assertSame(A::reportNames(), array_keys($reports));
        foreach ($reports as $rows) {
            $this->assertSame([], $rows);
        }
    }
}
