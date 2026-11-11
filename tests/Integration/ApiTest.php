<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\tests\Integration;

use Piwik\DataTable;
use Piwik\Plugins\FirearmsEcommerce\API;
use Piwik\Plugins\FirearmsEcommerce\MeasurableSettings;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Tracks ecommerce orders with the local tracker, archives them through the API and checks the reports.
 *
 * Run inside Matomo: ./console tests:run FirearmsEcommerce
 *
 * @group FirearmsEcommerce
 * @group FirearmsEcommerce_Integration
 * @group Plugins
 */
class ApiTest extends IntegrationTestCase
{
    private const DATE = '2026-03-02';

    /** @var int */
    private $idSite;

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);
        $fixture->createSuperUser = true; // its token_auth allows tracking with past timestamps
        $fixture->extraTestEnvVars['loadRealTranslations'] = true;
    }

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();
        $this->idSite = Fixture::createWebsite('2026-01-01 00:00:00', $ecommerce = 1);
        Fixture::createWebsite('2026-01-01 00:00:00', $ecommerce = 0);

        $this->trackOrder('ORDER-1', '10:00:00', [
            ['PA195S203MOS', 'Glock 19 Gen 5 MOS 9mm 4.02in 15rd Pistol', ['Firearms', 'Handguns'], 579.99, 1],
            ['365XL-9', 'Sig Sauer P365 XL 9mm 3.7" 12+1 Optic Ready', ['Firearms', 'Handguns'], 629.00, 1],
            ['Q4318', 'Winchester USA 9mm Luger 115 Grain FMJ 50 Round Box', ['Ammunition', 'Handgun Ammo'], 15.99, 2],
        ]);
        $this->trackOrder('ORDER-2', '12:00:00', [
            ['AE223', 'Federal American Eagle 5.56x45mm 55gr FMJ 1000 Rounds', ['Ammunition', 'Rifle Ammo'], 449.99, 1],
            ['MAG571', 'Magpul PMAG 30 AR/M4 Gen M2 MOE 5.56x45 30rd Magazine', ['Magazines'], 14.95, 3],
        ]);
        $this->trackAbandonedCart('14:00:00', ['11707', 'Benelli M4 Tactical 12ga 18.5"', ['Firearms', 'Shotguns'], 1999.00, 1]);
        $this->trackProductView('15:00:00', 'UA1750201', 'Glock 17 Gen 5 9mm 4.49in 17rd', ['Firearms', 'Handguns'], 549.99);
    }

    public function testProductTypes()
    {
        $rows = $this->rows(API::getInstance()->getProductTypes($this->idSite, 'day', self::DATE));

        $this->assertSame(['Firearms', 'Ammunition', 'Other products'], array_keys($rows));
        $this->assertEquals(1208.99, $rows['Firearms']['revenue']);
        $this->assertEquals(2, $rows['Firearms']['quantity']);
        $this->assertEquals(1, $rows['Firearms']['orders']);
        // Glock 19 and Sig P365 viewed before the order, Glock 17 viewed without an order
        $this->assertEquals(3, $rows['Firearms']['product_views']);
        $this->assertEquals(481.97, $rows['Ammunition']['revenue']);
        $this->assertEquals(2, $rows['Ammunition']['orders']);
        $this->assertEquals(44.85, $rows['Other products']['revenue']);
    }

    public function testAbandonedCartsAreNotCounted()
    {
        $rows = $this->rows(API::getInstance()->getBrands($this->idSite, 'day', self::DATE));
        $this->assertArrayNotHasKey('Benelli', $rows);
        $this->assertEquals(579.99, $rows['Glock']['revenue']);
        $this->assertEquals(2, $rows['Glock']['product_views']);
    }

    public function testCalibersAndCostPerRound()
    {
        $firearms = $this->rows(API::getInstance()->getFirearmCalibers($this->idSite, 'day', self::DATE));
        $this->assertSame(['9×19mm Parabellum'], array_keys($firearms));
        $this->assertEquals(1208.99, $firearms['9×19mm Parabellum']['revenue']);

        $ammo = $this->rows(API::getInstance()->getAmmoCalibers($this->idSite, 'day', self::DATE));
        $this->assertEquals(1000, $ammo['5.56×45mm NATO']['rounds']);
        $this->assertEquals(100, $ammo['9×19mm Parabellum']['rounds']);
        $this->assertEquals(31.98, $ammo['9×19mm Parabellum']['rounds_revenue']);

        $products = $this->rows(API::getInstance()->getAmmoCostPerRound($this->idSite, 'day', self::DATE));
        $this->assertEquals(100, $products['Winchester USA 9mm Luger 115 Grain FMJ 50 Round Box']['rounds']);
        $this->assertEquals(2, $products['Winchester USA 9mm Luger 115 Grain FMJ 50 Round Box']['quantity']);
    }

    public function testProcessedMetricsThroughTheHttpApi()
    {
        $request = new \Piwik\API\Request([
            'method' => 'FirearmsEcommerce.getAmmoCalibers',
            'idSite' => $this->idSite,
            'period' => 'day',
            'date' => self::DATE,
            'format' => 'original',
        ]);
        $rows = $this->rows($request->process());
        $this->assertEquals(0.4500, $rows['5.56×45mm NATO']['cost_per_round']);
        $this->assertEquals(0.3198, $rows['9×19mm Parabellum']['cost_per_round']);
        $this->assertEquals(15.99, $rows['9×19mm Parabellum']['avg_price']);
    }

    public function testWeekIsTheSumOfDays()
    {
        $this->trackOrder('ORDER-3', '10:00:00', [
            ['PA195S203MOS', 'Glock 19 Gen 5 MOS 9mm 4.02in 15rd Pistol', ['Firearms', 'Handguns'], 569.99, 1],
        ], '2026-03-03');

        $rows = $this->rows(API::getInstance()->getProductTypes($this->idSite, 'week', self::DATE));
        $this->assertEquals(1778.98, $rows['Firearms']['revenue']);
        $this->assertEquals(2, $rows['Firearms']['orders']);
    }

    public function testProductFieldSetting()
    {
        $settings = new MeasurableSettings($this->idSite);
        $settings->productField->setValue('sku');
        $settings->useCategories->setValue(false);
        $settings->save();

        // read from the SKU only, "Q4318" and "AE223" say nothing; "365XL-9" alone is not a known model either
        $rows = $this->rows(API::getInstance()->getProductTypes($this->idSite, 'day', self::DATE));
        $this->assertArrayNotHasKey('Ammunition', $rows);
        $this->assertArrayHasKey('Other products', $rows);
    }

    public function testSiteWithoutEcommerceHasNoRows()
    {
        $table = API::getInstance()->getProductTypes(2, 'day', self::DATE);
        $this->assertSame(0, $table->getRowsCount());
    }

    /**
     * @return array<string, array<string, mixed>> label => columns
     */
    private function rows(DataTable $table): array
    {
        $rows = [];
        foreach ($table->getRows() as $row) {
            $rows[$row->getColumn('label')] = $row->getColumns();
        }
        return $rows;
    }

    private function tracker(string $dateTime): \MatomoTracker
    {
        $t = Fixture::getTracker($this->idSite, $dateTime, $defaultInit = true, $useLocal = true);
        $t->setTokenAuth(Fixture::getTokenAuth());
        return $t;
    }

    private function trackOrder(string $orderId, string $time, array $items, string $date = self::DATE): void
    {
        $t = $this->tracker($date . ' ' . $time);
        $t->setUrl('https://shop.example.com/product');
        $subtotal = 0;
        foreach ($items as [$sku, $name, $categories, $price, $quantity]) {
            $t->setEcommerceView($sku, $name, $categories, $price);
            Fixture::checkResponse($t->doTrackPageView($name));
            $t->addEcommerceItem($sku, $name, $categories, $price, $quantity);
            $subtotal += $price * $quantity;
        }
        $t->setUrl('https://shop.example.com/thanks');
        Fixture::checkResponse($t->doTrackEcommerceOrder($orderId, $subtotal + 10, $subtotal, 0, 10));
    }

    private function trackAbandonedCart(string $time, array $item): void
    {
        $t = $this->tracker(self::DATE . ' ' . $time);
        $t->setUrl('https://shop.example.com/cart');
        [$sku, $name, $categories, $price, $quantity] = $item;
        $t->addEcommerceItem($sku, $name, $categories, $price, $quantity);
        Fixture::checkResponse($t->doTrackEcommerceCartUpdate($price * $quantity));
    }

    private function trackProductView(string $time, string $sku, string $name, array $categories, float $price): void
    {
        $t = $this->tracker(self::DATE . ' ' . $time);
        $t->setUrl('https://shop.example.com/p/' . strtolower($sku));
        $t->setEcommerceView($sku, $name, $categories, $price);
        Fixture::checkResponse($t->doTrackPageView($name));
    }
}
