<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\tests\Unit;

use Matomo\Dependencies\FirearmsEcommerce\GunsNPrices\Parser;
use PHPUnit\Framework\TestCase;

/**
 * Checks the copy of gunsnprices/gunsnprices in vendor/prefixed.
 *
 * @group FirearmsEcommerce
 * @group FirearmsEcommerce_Unit
 * @group Plugins
 */
class BundledLibraryTest extends TestCase
{
    public function testLoadsUnderThePluginNamespace()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $this->assertSame('0.1.0', Parser::VERSION);
        $info = Parser::parseTitle('Federal American Eagle 5.56x45mm 55gr FMJ 1000 Rounds', ['price' => 449.99]);
        $this->assertSame('ammo', $info['kind']);
        $this->assertSame(0.45, $info['costPerRound']);
        $this->assertFalse(class_exists('GunsNPrices\\Parser', false), 'the unprefixed class must not be defined');
    }

    public function testEveryNamespaceIsPrefixed()
    {
        $dir = __DIR__ . '/../../vendor/prefixed/gunsnprices/gunsnprices/src';
        $files = array_merge(glob($dir . '/*.php'), glob($dir . '/Internal/*.php'));
        $this->assertCount(7, $files);
        foreach ($files as $file) {
            $code = file_get_contents($file);
            $this->assertSame(0, preg_match('/^(?:namespace|use) GunsNPrices\b/m', $code), $file);
            $this->assertSame(1, preg_match('/^namespace Matomo\\\\Dependencies\\\\FirearmsEcommerce\\\\GunsNPrices(\\\\Internal)?;/m', $code), $file);
        }
    }

    public function testShipsLicenses()
    {
        $dir = __DIR__ . '/../../vendor/prefixed/gunsnprices/gunsnprices';
        $this->assertStringContainsString('MIT License', file_get_contents($dir . '/LICENSE'));
        $this->assertStringContainsString('CC BY 4.0', file_get_contents($dir . '/LICENSE-DATA'));
    }
}
