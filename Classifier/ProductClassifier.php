<?php

/**
 * FirearmsEcommerce plugin for Matomo.
 *
 * @link    https://github.com/gunsnprices/matomo-plugin-FirearmsEcommerce
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FirearmsEcommerce\Classifier;

use Matomo\Dependencies\FirearmsEcommerce\GunsNPrices\Parser;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Reads the product type (firearm, ammunition or other), brand, caliber and rounds per box of an ecommerce product
 * from its name, SKU and categories.
 *
 * Calibers, firearm models and round counts come from the gunsnprices parser library. The rules in this class decide
 * the product type where the library alone cannot: accessories that name a caliber ("9mm magazine"), and ammunition
 * titles without a bullet weight ("Winchester 9mm 50 rounds").
 *
 * The class has no Matomo dependencies, so it can be unit tested on its own.
 */
final class ProductClassifier
{
    public const TYPE_FIREARM = 'firearm';
    public const TYPE_AMMO = 'ammo';
    public const TYPE_OTHER = 'other';

    public const FIELD_NAME = 'name';
    public const FIELD_SKU = 'sku';
    public const FIELD_NAME_SKU = 'name_sku';

    /** Product nouns that make a title an accessory or part, unless they follow "with", "w/", "+", a count... */
    private const ACCESSORY = '/\b(?:holsters?|magazines?|pmags?|mag(?:azine)?\s+(?:loaders?|pouch(?:es)?|couplers?)|slings?|'
        . '(?:rifle\s*)?scopes?|riflescopes?|red\s*dots?|reflex\s+sights?|night\s+sights?|iron\s+sights?|sights?|optics?|'
        . 'binoculars?|rangefinders?|scope\s+(?:mounts?|rings?)|mounts?|bipods?|'
        . '(?:gun|rifle|pistol|shotgun|hard|soft|carry|range)\s+(?:cases?|bags?)|range\s+bags?|ammo\s+(?:cans?|box(?:es)?|crates?)|'
        . 'shell\s+(?:holders?|carriers?)|'
        . 'cleaning|bore\s*snakes?|solvents?|lubricants?|gun\s+oil|targets?|ear\s*muffs?|hearing\s+protection|'
        . 'eye\s+protection|shooting\s+glasses|safes?|trigger\s+locks?|cable\s+locks?|lights?|flashlights?|lasers?|'
        . 'choke\s+tubes?|chokes?|muzzle\s+brakes?|compensators?|suppressors?|silencers?|handguards?|charging\s+handles?|'
        . 'bolt\s+carrier(?:\s+groups?)?|bcg|buffer\s+(?:tubes?|springs?|kits?)|upper\s+receivers?|uppers?|'
        . 'lower\s+parts\s+kits?|parts\s+kits?|conversion\s+kits?|replacement\s+barrels?|speed\s*loaders?|snap\s+caps?|'
        . 'dummy\s+rounds?|reloading|dies?|die\s+sets?|primers?|powder|brass\s+cases?|recoil\s+pads?|grip\s+panels?|'
        . 'gloves?|vests?|plate\s+carriers?|knives|knife|apparel|t-shirts?|hats?)\b/i';

    /** Accessories for ammunition, which the library can read as ammunition ("MTM Ammo Box 9mm 100 Round"). */
    private const AMMO_ACCESSORY = '/\b(?:ammo\s+(?:cans?|box(?:es)?|crates?|pouch(?:es)?|belts?|carriers?|holders?)|'
        . 'shell\s+(?:holders?|carriers?)|speed\s*loaders?|snap\s+caps?|dummy\s+rounds?|mag(?:azine)?\s+loaders?|bandoleers?|'
        . 'cartridge\s+(?:belts?|holders?|boxes|box)|reloading|dies?|die\s+sets?|bullet\s+molds?|molds?)\b/i';

    /** Words before an accessory noun that mean the item comes with the firearm ("15rd w/ 2 magazines"). */
    private const INCLUDED = '/(?:\bw\/|\bwith\b|\bwithout\b|\bno\b|\band\b|&|\+|\bincl\w*|\bplus\b|\(\d\)|\b\d\b|\btwo\b|'
        . '\bthree\b|\bfour\b|,|\bextra\b|\bspare\b|\bfactory\b|\bfits\b|\btakes\b|\baccepts\b|\buses\b)[^,]{0,14}$|\/\s*$/i';

    /** Nouns that name a firearm, unless followed by an ammunition word ("shotgun shells", "rifle ammo"). */
    private const GUN_NOUNS = '/\b(?:pistols?|rifles?|shotguns?|revolvers?|handguns?|carbines?|firearms?|semi-?auto(?:matic)?|'
        . 'bolt[- ]?action|pump[- ]action|lever[- ]?action|single[- ]?shot|break[- ]action|o\/u|over\/under|side[- ]by[- ]side|'
        . 'muzzleloaders?|derringers?)\b(?!\s+(?:shells?|ammo|ammunition|rounds?|cartridges?|loads?)\b)/i';

    /** Shotgun shells and other ammunition words the library does not use to detect ammunition. */
    private const AMMO_WORDS = '/\b(?:ammo(?!\s+(?:cans?|box(?:es)?|crates?|storage|pouch(?:es)?|belts?|carriers?|holders?)\b)|'
        . 'ammunition|cartridges?|shotshells?|shells(?!\s+(?:holders?|carriers?)\b)|buckshot|birdshot|\d{1,2}\s*pellets?|'
        . '0{1,3}\s*buck|slugs?|#\s*\d(?:\.\d)?\s*shot|target\s+loads?|grains?|\d+\s*gr)\b/i';

    /** A round count such as "50 rounds", "20rd", "1000 rds". */
    private const ROUND_COUNT = '/\b\d[\d,]*\s*-?\s*(?:rounds?|rds?|rnds?|ct|count)\b/i';

    /** Category names that mark ammunition, accessories and firearms. */
    private const CAT_AMMO = '/\b(?:ammo(?!\s+(?:cans?|boxes|box|storage|crates?|pouch(?:es)?|belts?|carriers?))|ammunition|cartridges?|shotshells?)\b/i';
    private const CAT_OTHER = '/\b(?:accessor\w*|parts?|optics?|scopes?|sights?|holsters?|magazines?|mags|cases?|bags?|cleaning|'
        . 'gear|apparel|clothing|knives|knife|safes?|storage|reloading|lights?|lasers?|mounts?|slings?|targets?|hearing|'
        . 'protection|grips?|stocks?|triggers?|barrels?|chokes?|suppressors?|silencers?|airguns?|air\s+guns?|airsoft|'
        . 'gift\s+cards?|ammo\s+(?:cans?|boxes|box|storage))\b/i';
    private const CAT_FIREARM = '/\b(?:firearms?|guns?|handguns?|pistols?|rifles?|shotguns?|revolvers?|carbines?|'
        . 'muzzleloaders?|long\s+guns?)\b/i';

    /** Ammunition brands that, at the start of a title with a round count, mark a box of ammunition. */
    private const AMMO_BRANDS = [
        'Aguila', 'Ammo Inc', 'Armscor', 'Barnaul', 'Barnes', 'Berger', 'Black Hills', 'Blazer', 'Brown Bear', 'Buffalo Bore',
        'CCI', 'Federal', 'Fiocchi', 'Geco', 'Hornady', 'HSM', 'Kent', 'Lapua', 'Magtech', 'Norma', 'Nosler', 'PMC',
        'Prvi Partizan', 'Remington', 'Rio', 'Sellier & Bellot', 'Silver Bear', 'Speer', 'Tula', 'Underwood', 'Winchester',
        'Wolf',
    ];

    /** @var string */
    private $field;

    /** @var bool */
    private $useCategories;

    /** @var BrandFinder */
    private $brands;

    /** @var array<string, array> */
    private $cache = [];

    public function __construct(string $field = self::FIELD_NAME, bool $useCategories = true, ?BrandFinder $brands = null)
    {
        $this->field = in_array($field, [self::FIELD_NAME, self::FIELD_SKU, self::FIELD_NAME_SKU], true) ? $field : self::FIELD_NAME;
        $this->useCategories = $useCategories;
        $this->brands = $brands ?? new BrandFinder();
    }

    /**
     * @param string   $name       product name as tracked
     * @param string   $sku        product SKU as tracked
     * @param string[] $categories product categories as tracked
     * @return array{type: string, brand: string, caliber: string, rounds: int|null, text: string}
     *         caliber is '' for products of type "other" and when none is found; rounds is set for ammunition only
     */
    public function classify(string $name, string $sku = '', array $categories = []): array
    {
        $name = self::clean($name);
        $sku = self::clean($sku);
        $categories = $this->useCategories ? array_values(array_filter(array_map([self::class, 'clean'], $categories))) : [];
        $key = $name . "\x1f" . $sku . "\x1f" . implode("\x1e", $categories);
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        if (count($this->cache) >= 20000) {
            $this->cache = [];
        }

        $text = $this->text($name, $sku);
        $opts = $sku !== '' ? ['sku' => $sku] : [];
        $info = Parser::parseTitle($text, $opts);
        $type = $this->typeFromCategories($categories) ?? $this->typeFromTitle($text, $info);

        if ($type === self::TYPE_AMMO && $info['kind'] !== 'ammo') {
            $info = Parser::parseTitle($text, $opts + ['kind' => 'ammo']);
        } elseif ($type === self::TYPE_FIREARM && $info['kind'] !== 'firearm') {
            $info = Parser::parseTitle($text, $opts + ['kind' => 'firearm']);
        }

        $brand = $info['model'] !== null ? (string) $info['model']['brand'] : '';
        if ($brand === '') {
            $brand = $this->brands->find($text, $type !== self::TYPE_OTHER);
        }
        if ($brand === '' && $text !== $name && $name !== '') {
            $brand = $this->brands->find($name, $type !== self::TYPE_OTHER);
        }

        $rounds = null;
        if ($type === self::TYPE_AMMO && !empty($info['rounds']) && $info['rounds'] > 0) {
            $rounds = (int) $info['rounds'];
        }

        return $this->cache[$key] = [
            'type' => $type,
            'brand' => $brand,
            'caliber' => $type !== self::TYPE_OTHER && $info['caliber'] !== null ? (string) $info['caliber']['name'] : '',
            'rounds' => $rounds,
            'text' => $text,
        ];
    }

    /** The text the parser reads, chosen by the "product field" setting. */
    public function text(string $name, string $sku): string
    {
        switch ($this->field) {
            case self::FIELD_SKU:
                return $sku !== '' ? $sku : $name;
            case self::FIELD_NAME_SKU:
                return trim($name . ' ' . $sku);
            default:
                return $name !== '' ? $name : $sku;
        }
    }

    /**
     * @param string[] $categories
     */
    private function typeFromCategories(array $categories): ?string
    {
        if ($categories === []) {
            return null;
        }
        $all = implode(' / ', $categories);
        if (preg_match(self::CAT_AMMO, $all)) {
            return self::TYPE_AMMO;
        }
        if (preg_match(self::CAT_OTHER, $all)) {
            return self::TYPE_OTHER;
        }
        if (preg_match(self::CAT_FIREARM, $all)) {
            return self::TYPE_FIREARM;
        }
        return null;
    }

    private function typeFromTitle(string $text, array $info): string
    {
        if ($info['kind'] === 'ammo') {
            return preg_match(self::AMMO_ACCESSORY, $text) ? self::TYPE_OTHER : self::TYPE_AMMO;
        }
        if ($info['model'] !== null) {
            return self::TYPE_FIREARM;
        }
        $hasCaliber = $info['caliber'] !== null;
        $hasGunNouns = (bool) preg_match(self::GUN_NOUNS, $text);
        // "Fiocchi 12ga 2.75in #8 Shot 25 Shells": shell words win over the shell length, which reads like a barrel.
        if ($hasCaliber && !$hasGunNouns && preg_match(self::AMMO_WORDS, $text)) {
            return self::TYPE_AMMO;
        }
        if ($this->isAccessory($text)) {
            return self::TYPE_OTHER;
        }
        // "Winchester 9mm 50 rounds": an ammunition brand, a caliber and a round count, nothing that names a gun.
        if (
            $hasCaliber && !$hasGunNouns && $info['barrelLength'] === null
            && preg_match(self::ROUND_COUNT, $text) && $this->startsWithAmmoBrand($text)
        ) {
            return self::TYPE_AMMO;
        }
        if ($hasCaliber || $hasGunNouns) {
            return self::TYPE_FIREARM;
        }
        return self::TYPE_OTHER;
    }

    private function isAccessory(string $text): bool
    {
        // "Barrel for Glock 19", "Grips fits Ruger 10/22": a product made for another brand's firearm
        if (preg_match('/\b(?:for|fits|compatible\s+with)\s+(.{2,60})$/i', $text, $for) && $this->brands->find($for[1], false) !== '') {
            return true;
        }
        if (!preg_match_all(self::ACCESSORY, $text, $m, PREG_OFFSET_CAPTURE)) {
            return false;
        }
        foreach ($m[0] as $match) {
            $before = substr($text, max(0, $match[1] - 16), min(16, $match[1]));
            if (!preg_match(self::INCLUDED, $before)) {
                return true;
            }
        }
        return false;
    }

    private function startsWithAmmoBrand(string $text): bool
    {
        $brand = $this->brands->find($text, false);
        return $brand !== '' && in_array($brand, self::AMMO_BRANDS, true);
    }

    /** Decodes HTML entities (Matomo may store "&amp;") and collapses white space. */
    private static function clean($value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }
}
