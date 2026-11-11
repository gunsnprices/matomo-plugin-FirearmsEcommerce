<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\FirearmsEcommerce\Classifier\BrandFinder;

/**
 * @group FirearmsEcommerce
 * @group FirearmsEcommerce_Unit
 * @group Plugins
 */
class BrandFinderTest extends TestCase
{
    /**
     * @dataProvider getTexts
     */
    public function testFindsBrands(string $text, bool $anywhere, string $expected)
    {
        $this->assertSame($expected, (new BrandFinder())->find($text, $anywhere));
    }

    public function getTexts(): array
    {
        return [
            ['Federal American Eagle 9mm', true, 'Federal'],
            ['American Eagle 9mm 115gr', true, 'Federal'],
            ['Smith and Wesson M&P 2.0', true, 'Smith & Wesson'],
            ['S&W 686', true, 'Smith & Wesson'],
            ['Heckler & Koch VP9', true, 'HK'],
            ['H&K VP9', true, 'HK'],
            ['SIG SAUER P320', true, 'Sig Sauer'],
            ['Springfield  Armory Hellcat', true, 'Springfield'],
            ['Sellier &amp; Bellot 9mm', true, 'Sellier & Bellot'],
            ['NEW: Glock 19', false, 'Glock'],
            ['Holster for Glock 19', true, 'Glock'],
            ['Holster for Glock 19', false, ''],
            ['Wolff gunsprings', true, ''],
            ['Sigma', true, ''],
            ['', true, ''],
        ];
    }

    public function testListsBrandsSorted()
    {
        $brands = BrandFinder::listBrands();
        $this->assertContains('Glock', $brands);
        $this->assertContains('Sellier & Bellot', $brands);
        $this->assertGreaterThan(100, count($brands));
        $sorted = $brands;
        sort($sorted, SORT_STRING | SORT_FLAG_CASE);
        $this->assertSame($sorted, $brands);
    }
}
