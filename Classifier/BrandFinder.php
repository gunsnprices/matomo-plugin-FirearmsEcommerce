<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Classifier;

/**
 * Finds a known brand in a product name.
 *
 * The parser library names the brand of the 50 firearm brands whose models it knows. This list adds the other common
 * makers of firearms, the ammunition makers and the large optics and accessory brands, so that the Brands report
 * also covers ammunition and products whose model is not known.
 */
final class BrandFinder
{
    /**
     * Brand name => other spellings found in store titles. The brand name itself is always matched.
     * The first block uses the same names as the parser library, so both give the same label.
     */
    private const BRANDS = [
        // Brands with model dictionaries in the gunsnprices library.
        'Barrett' => [],
        'Benelli' => [],
        'Beretta' => [],
        'Bergara' => [],
        'Browning' => [],
        'Cadex' => ['Cadex Defence', 'Cadex Defense'],
        'Canik' => [],
        'Charles Daly' => [],
        'Chiappa' => [],
        'Christensen Arms' => ['Christensen'],
        'Cimarron' => [],
        'CMMG' => [],
        'Colt' => [],
        'CVA' => [],
        'CZ' => ['CZ-USA', 'CZ USA', 'Ceska Zbrojovka'],
        'Daniel Defense' => [],
        'Desert Eagle' => [],
        'Diamondback' => ['Diamondback Firearms'],
        'Fierce' => ['Fierce Firearms'],
        'FN' => ['FN Herstal', 'FN America', 'FNH', 'FNH USA'],
        'Glock' => [],
        'Great Lakes' => ['Great Lakes Firearms'],
        'Henry' => ['Henry Repeating Arms'],
        'Heritage' => ['Heritage Manufacturing', 'Heritage Mfg'],
        'HK' => ['Heckler & Koch', 'Heckler and Koch', 'Heckler&Koch', 'H&K'],
        'Howa' => [],
        'Kel-Tec' => ['KelTec', 'Kel Tec'],
        'Kimber' => [],
        'Kriss' => ['Kriss USA'],
        'Marlin' => [],
        'Mossberg' => ['O.F. Mossberg'],
        'Remington' => [],
        'Retay' => [],
        'Rossi' => [],
        'Ruger' => ['Sturm Ruger', 'Sturm, Ruger'],
        'Savage' => ['Savage Arms'],
        'SCCY' => [],
        'Seekins' => ['Seekins Precision'],
        'Shadow Systems' => [],
        'Sig Sauer' => ['Sig', 'SIG-Sauer', 'SigSauer'],
        'Springfield' => ['Springfield Armory'],
        'Stevens' => [],
        'Stoeger' => [],
        'Smith & Wesson' => ['S&W', 'Smith and Wesson', 'Smith&Wesson', 'Smith & Wesson Performance Center'],
        'Taurus' => [],
        'Tikka' => [],
        'Tristar' => ['TriStar Arms'],
        'Walther' => [],
        'Weatherby' => [],
        'Winchester' => [],

        // Other firearm makers.
        'Aero Precision' => [],
        'Anderson Manufacturing' => ['Anderson Mfg', 'Anderson'],
        'Bersa' => [],
        'Bond Arms' => [],
        'Bushmaster' => [],
        'Century Arms' => ['Century International Arms'],
        'Charter Arms' => [],
        'Dan Wesson' => [],
        'Franchi' => [],
        'Girsan' => [],
        'Hi-Point' => ['Hi Point', 'HiPoint'],
        'IWI' => ['IWI US', 'Israel Weapon Industries'],
        'Kahr' => ['Kahr Arms'],
        'Lewis Machine & Tool' => ['LMT'],
        'Magnum Research' => [],
        'Nighthawk Custom' => ['Nighthawk'],
        'North American Arms' => ['NAA'],
        'Palmetto State Armory' => ['PSA'],
        'Pietta' => [],
        'Rock Island Armory' => ['Rock Island', 'RIA'],
        'Sako' => [],
        'SAR USA' => ['Sarsilmaz'],
        'Staccato' => [],
        'Thompson/Center' => ['Thompson Center', 'T/C Arms'],
        'Traditions' => ['Traditions Firearms'],
        'Uberti' => [],
        'Wilson Combat' => [],
        'Windham Weaponry' => [],
        'Zastava' => ['Zastava Arms'],

        // Ammunition makers.
        'Aguila' => [],
        'Ammo Inc' => ['Ammo Inc.', 'AMMO Inc'],
        'Armscor' => [],
        'Barnaul' => [],
        'Barnes' => [],
        'Berger' => [],
        'Black Hills' => ['Black Hills Ammunition'],
        'Blazer' => [],
        'Brown Bear' => [],
        'Buffalo Bore' => [],
        'CCI' => [],
        'Federal' => ['Federal Premium', 'American Eagle'],
        'Fiocchi' => [],
        'Geco' => [],
        'Hornady' => [],
        'HSM' => [],
        'Kent' => ['Kent Cartridge'],
        'Lapua' => [],
        'Magtech' => [],
        'Norma' => [],
        'Nosler' => [],
        'PMC' => [],
        'Prvi Partizan' => ['PPU', 'Prvi'],
        'Rio' => ['Rio Ammunition'],
        'Sellier & Bellot' => ['Sellier and Bellot', 'S&B'],
        'Silver Bear' => [],
        'Speer' => [],
        'Tula' => ['TulAmmo', 'Tulammo'],
        'Underwood' => ['Underwood Ammo'],
        'Wolf' => ['Wolf Ammo'],

        // Optics and accessories.
        'Aimpoint' => [],
        'Bushnell' => [],
        'Burris' => [],
        'Crimson Trace' => [],
        'EOTech' => ['EOTECH'],
        'Holosun' => [],
        'Hogue' => [],
        'Leupold' => [],
        'Magpul' => [],
        'Nightforce' => [],
        'Primary Arms' => [],
        'Safariland' => [],
        'Sightmark' => [],
        'Streamlight' => [],
        'SureFire' => [],
        'Trijicon' => [],
        'Vortex' => ['Vortex Optics'],
    ];

    /** @var string|null */
    private $pattern = null;

    /** @var array<string, string> lowercased spelling => brand */
    private $byAlias = [];

    /**
     * The brand named at the start of the text, or, with $anywhere, the first brand named anywhere in it.
     * Returns '' if no known brand is found.
     *
     * Stores usually start a title with the brand. Products such as "Magazine for Glock 19" name another brand later,
     * so the plugin searches the whole title only for firearms and ammunition.
     */
    public function find(string $text, bool $anywhere = true): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($text === '') {
            return '';
        }
        if (!preg_match_all($this->pattern(), $text, $m, PREG_OFFSET_CAPTURE)) {
            return '';
        }
        foreach ($m[1] as $match) {
            $isStart = trim(substr($text, 0, $match[1]), " \t\n\r\0\x0B-|:[](){}\"'*") === ''
                || preg_match('/^\s*(?:new|used|nib|sale|clearance)\b[\s:-]*$/i', substr($text, 0, $match[1]));
            if (!$isStart && !$anywhere) {
                return '';
            }
            return $this->byAlias[self::key($match[0])] ?? '';
        }
        return '';
    }

    /**
     * All brand names this finder knows, sorted.
     *
     * @return string[]
     */
    public static function listBrands(): array
    {
        $names = array_keys(self::BRANDS);
        sort($names, SORT_STRING | SORT_FLAG_CASE);
        return $names;
    }

    private function pattern(): string
    {
        if ($this->pattern === null) {
            foreach (self::BRANDS as $brand => $aliases) {
                foreach (array_merge([$brand], $aliases) as $alias) {
                    $this->byAlias[self::key($alias)] = $brand;
                }
            }
            $aliases = array_keys($this->byAlias);
            usort($aliases, static function (string $a, string $b): int {
                return strlen($b) <=> strlen($a);
            });
            $parts = array_map(static function (string $alias): string {
                return str_replace(' ', '\\s+', preg_quote($alias, '/'));
            }, $aliases);
            $this->pattern = '/(?<![a-z0-9&])(' . implode('|', $parts) . ')(?![a-z0-9&])/i';
        }
        return $this->pattern;
    }

    private static function key(string $alias): string
    {
        return strtolower((string) preg_replace('/\s+/', ' ', $alias));
    }
}
