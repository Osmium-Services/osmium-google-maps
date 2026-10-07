<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMaps\Models;

/**
 * Google Maps configuration helper.
 *
 * Loads this service's own settings file, matching the file-based config
 * convention used by the other services (app/config/services/{id}.json.php).
 */
class GoogleMapsConfig
{
    public const DEMO_MAP_ID = 'DEMO_MAP_ID';

    private static ?object $config = null;
    private static string $configPath = 'app/config/services/google-maps.json.php';

    /**
     * Falls back to defaults (disabled, no key) if the config file is missing, so
     * installing this service renders nothing until a key is saved.
     */
    public static function get(): object
    {
        $configLoaded = self::$config !== null;
        if ($configLoaded) return self::$config;

        $configFile = self::$configPath;

        $configExists = \file_exists($configFile);
        if (!$configExists) {
            self::$config = self::defaults();
            return self::$config;
        }

        $content = \file_get_contents($configFile);
        $jsonStart = \strpos(haystack: $content, needle: '{');

        $noJsonFound = $jsonStart === false;
        if ($noJsonFound) {
            self::$config = self::defaults();
            return self::$config;
        }

        $json = \substr(string: $content, offset: $jsonStart);
        $decoded = \json_decode($json);

        self::$config = $decoded->googleMaps ?? self::defaults();

        return self::$config;
    }

    public static function clearCache(): void
    {
        self::$config = null;
    }

    /**
     * Deliberately loose: Google keys are letters, digits, "_" and "-", but the
     * exact length and prefix are not guaranteed, so this only keeps a pasted
     * snippet or URL out of the inline script.
     */
    public static function isValidApiKey(string $key): bool
    {
        return \preg_match(pattern: '/^[A-Za-z0-9_-]{20,100}$/', subject: $key) === 1;
    }

    public static function isValidMapId(string $mapId): bool
    {
        return \preg_match(pattern: '/^[A-Za-z0-9_]{1,64}$/', subject: $mapId) === 1;
    }

    private static function defaults(): object
    {
        return (object) ['enabled' => false, 'apiKey' => '', 'mapId' => ''];
    }
}
