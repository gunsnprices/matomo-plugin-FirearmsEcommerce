<?php
/*
 * Copy of gunsnprices/gunsnprices (MIT License, see ../LICENSE) bundled with the FirearmsEcommerce Matomo plugin.
 * Changed by tools/vendor-library.php: the namespace GunsNPrices was renamed to Matomo\Dependencies\FirearmsEcommerce\GunsNPrices.
 */
namespace Matomo\Dependencies\FirearmsEcommerce\GunsNPrices\Internal;

/**
 * Loads data/*.json once per process, on first use.
 *
 * @internal
 */
final class Data
{
    /** @var array<string, array> */
    private static $cache = [];

    /** @var string|null */
    private static $dir = null;

    public static function get(string $name): array
    {
        if (!isset(self::$cache[$name])) {
            $path = self::dir() . '/' . $name . '.json';
            $json = @file_get_contents($path);
            $data = $json === false ? null : json_decode($json, true);
            if (!is_array($data)) {
                throw new \RuntimeException('GunsNPrices: cannot read ' . $path);
            }
            self::$cache[$name] = $data;
        }
        return self::$cache[$name];
    }

    public static function dir(): string
    {
        if (self::$dir === null) {
            self::$dir = dirname(__DIR__, 2) . '/data';
        }
        return self::$dir;
    }
}
