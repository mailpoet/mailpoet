<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\CarbonImmutable;

class TranslationUpdater {
  const API_UPDATES_BASE_URI = 'https://translate.wordpress.com/api/translations-updates/mailpoet/';

  /** @var WPFunctions */
  private $wpFunctions;

  /** @var string */
  private $freeSlug;

  /** @var string */
  private $freeVersion;

  /** @var string */
  private $premiumSlug;

  /** @var string|null */
  private $premiumVersion;

  public function __construct(
    WPFunctions $wpFunctions,
    string $freeSlug,
    string $freeVersion,
    string $premiumSlug,
    ?string $premiumVersion
  ) {
    $this->wpFunctions = $wpFunctions;
    $this->freeSlug = $freeSlug;
    $this->freeVersion = $freeVersion;
    $this->premiumSlug = $premiumSlug;
    $this->premiumVersion = $premiumVersion;
  }

  public function init(): void {
    $this->wpFunctions->addFilter('pre_set_site_transient_update_plugins', [$this, 'checkForTranslations']);
  }

  public function checkForTranslations($transient) {
    if (!$transient instanceof \stdClass) {
      $transient = new \stdClass;
    }

    $locales = $this->getLocales();
    if (empty($locales)) {
      return $transient;
    }

    $languagePacksData = $this->getAvailableTranslations($locales);
    $translations = $this->selectUpdatesToInstall($languagePacksData);

    $transient->translations = array_merge($transient->translations ?? [], $translations);
    return $transient;
  }

  /**
   * Find available languages
   * @return array
   */
  private function getLocales(): array {
    $locales = array_values($this->wpFunctions->getAvailableLanguages());
    $locales = apply_filters('plugins_update_check_locales', $locales);
    return array_unique($locales);
  }

  private function getAvailableTranslations(array $locales): array {
    $requestBody = [
      'locales' => $locales,
      'plugins' => [
        $this->freeSlug => ['version' => $this->freeVersion],
      ],
    ];
    if ($this->premiumVersion) {
      $requestBody['plugins'][$this->premiumSlug] = ['version' => $this->premiumVersion];
    }

    // Ten seconds, plus one extra second for every 10 locales.
    $timeout = 10 + (int)(count($locales) / 10);
    $rawResponse = $this->wpFunctions->wpRemotePost(self::API_UPDATES_BASE_URI, [
      'body' => json_encode($requestBody),
      'headers' => ['Content-Type: application/json'],
      'timeout' => $timeout,
    ]);

    // Don't continue when API request failed.
    $responseCode = $this->wpFunctions->wpRemoteRetrieveResponseCode($rawResponse);
    if ($responseCode !== 200) {
      return [];
    }
    $response = json_decode($this->wpFunctions->wpRemoteRetrieveBody($rawResponse), true);
    if (!is_array($response) || (array_key_exists('success', $response) && $response['success'] === false)) {
      return [];
    }

    return $response['data'];
  }

  private function selectUpdatesToInstall(array $responseData) {
    $installedTranslations = $this->wpFunctions->wpGetInstalledTranslations('plugins');
    $translationsToInstall = [];
    foreach ($responseData as $pluginName => $languagePacks) {
      foreach ($languagePacks as $languagePack) {
        // Check revision date if translation is already installed.
        if (array_key_exists($pluginName, $installedTranslations) && array_key_exists($languagePack['wp_locale'], $installedTranslations[$pluginName])) {
          $installedFromWpOrg = strpos($installedTranslations[$pluginName][$languagePack['wp_locale']]['Project-Id-Version'] ?? '', 'Stable (latest release)') !== false;
          $installedTranslationRevisionTime = new CarbonImmutable($installedTranslations[$pluginName][$languagePack['wp_locale']]['PO-Revision-Date']);
          $newTranslationRevisionTime = new CarbonImmutable($languagePack['last_modified']);

          // In case installed translation pack comes from WP.org make sure that the one coming from WP.com has newer date
          if ($installedFromWpOrg && $newTranslationRevisionTime <= $installedTranslationRevisionTime) {
            $languagePack['last_modified'] = $installedTranslationRevisionTime->addSecond()->toDateTimeString();
            $newTranslationRevisionTime = new CarbonImmutable($languagePack['last_modified']);
          }

          // Skip if translation language pack is not newer than what is installed already.
          if ($newTranslationRevisionTime <= $installedTranslationRevisionTime) {
            continue;
          }
        }
        $translationsToInstall[] = [
          'type' => 'plugin',
          'slug' => $pluginName,
          'language' => $languagePack['wp_locale'],
          'version' => $languagePack['version'],
          'updated' => $languagePack['last_modified'],
          'package' => $languagePack['package'],
          'autoupdate' => true,
        ];
      }
    }

    return $translationsToInstall;
  }
}
