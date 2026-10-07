<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMaps\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\GoogleMaps\Models\GoogleMapsConfig;

/**
 * Google Maps settings controller - a full-page form POST/redirect flow,
 * matching the Google Maps Embed and Meta Pixel services.
 *
 * Routes:
 *   - index() → /admin/settings/google-maps/  (GET shows the form, POST saves it)
 */
class GoogleMapsController extends AdminController
{
    private const CONFIG_FILE_PATH = 'app/config/services/google-maps.json.php';
    private const DEFAULT_CONFIG = <<<'JSON'
        <?php exit(); ?>
        {
            "googleMaps": {
                "enabled": false,
                "apiKey": "",
                "mapId": ""
            }
        }
        JSON;

    public function index(): void
    {
        $isPost = $this->isPost();
        if ($isPost) $this->handleSubmit();

        $this->data['admin']['config']['googleMaps'] = (array) GoogleMapsConfig::get();
        $this->data['admin']['settingsSaved'] = $_SESSION['google_maps_settings_saved'] ?? false;
        $this->data['admin']['settingsError'] = $_SESSION['google_maps_settings_error'] ?? false;
        unset($_SESSION['google_maps_settings_saved'], $_SESSION['google_maps_settings_error']);

        $this->setView('google-maps/index.phtml');
    }

    private function handleSubmit(): void
    {
        $csrfValid = $this->admin->auth->validateCsrf();
        if (!$csrfValid) {
            $_SESSION['google_maps_settings_error'] = 'Invalid form submission. Please try again.';
            $this->redirect('settings/google-maps/');
        }

        $enabled = !empty($_POST['enabled']);
        $apiKey = \trim($_POST['api_key'] ?? '');
        $mapId = \trim($_POST['map_id'] ?? '');

        $keyGiven = $apiKey !== '';
        if ($keyGiven && !GoogleMapsConfig::isValidApiKey($apiKey)) {
            $_SESSION['google_maps_settings_error'] = 'The API key must be 20-100 letters, digits, "_" or "-". Paste only the key, not a URL or script.';
            $this->redirect('settings/google-maps/');
        }

        $mapIdGiven = $mapId !== '';
        if ($mapIdGiven && !GoogleMapsConfig::isValidMapId($mapId)) {
            $_SESSION['google_maps_settings_error'] = 'The Map ID may only contain letters, digits and "_".';
            $this->redirect('settings/google-maps/');
        }

        $enabledWithoutKey = $enabled && !$keyGiven;
        if ($enabledWithoutKey) {
            $_SESSION['google_maps_settings_error'] = 'Enter an API key before enabling Google Maps.';
            $this->redirect('settings/google-maps/');
        }

        $this->saveConfig(enabled: $enabled, apiKey: $apiKey, mapId: $mapId);

        $this->admin->model->changelog->log(
            description: 'Updated Google Maps settings',
            recordType: 'settings',
        );

        GoogleMapsConfig::clearCache();

        $_SESSION['google_maps_settings_saved'] = true;
        $this->redirect('settings/google-maps/');
    }

    private function saveConfig(bool $enabled, string $apiKey, string $mapId): void
    {
        $configExists = \file_exists(self::CONFIG_FILE_PATH);
        if (!$configExists) $this->ensureConfigDirectoryExists();

        $content = $configExists ? \file_get_contents(self::CONFIG_FILE_PATH) : self::DEFAULT_CONFIG;

        $jsonStart = \strpos(haystack: $content, needle: '{');
        $phpHeader = \substr(string: $content, offset: 0, length: $jsonStart);
        $data = \json_decode(\substr(string: $content, offset: $jsonStart), associative: true) ?? [];

        $data['googleMaps'] = ['enabled' => $enabled, 'apiKey' => $apiKey, 'mapId' => $mapId];

        $newJson = \json_encode(
            value: $data,
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        \file_put_contents(self::CONFIG_FILE_PATH, $phpHeader . $newJson . "\n");
    }

    private function ensureConfigDirectoryExists(): void
    {
        $dir = \dirname(self::CONFIG_FILE_PATH);
        $alreadyExists = \is_dir($dir);
        if (!$alreadyExists) \mkdir(directory: $dir, permissions: 0755, recursive: true);
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
