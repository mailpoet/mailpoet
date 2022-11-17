<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TranslationsController {
  private const API_IMPORT_BASE_URI = 'https://translate.wordpress.com/api/import-transifex/';
  private const API_CHECK_BASE_URI = 'https://translate.wordpress.com/api/translations-updates/mailpoet/';

  public function importTransifex(string $version, string $project = 'mailpoet'): array {
    $httpClient = new Client([
      'base_uri' => self::API_IMPORT_BASE_URI . $project . '/',
    ]);
    $response = $httpClient->post($version);
    if ($response->getStatusCode() !== 200) {
      return [
        'success' => false,
        'data' => 'Failed preparing translations',
      ];
    }
    $result = json_decode($response->getBody()->getContents(), true);
    if (!is_array($result) || !isset($result['success']) || !isset($result['data'])) {
      return [
        'success' => false,
        'data' => 'Failed preparing translations - malformed response: ' . $response->getBody()->getContents(),
      ];
    }
    return $result;
  }

  public function checkIfTranslationsAreReady(string $version, string $project = 'mailpoet'): array {
    $httpClient = new Client();
    $payload = [
      'locales' => ['de_DE_formal', 'fr_FR'],
      'plugins' => [],
    ];
    $payload['plugins'][$project] = ['version' => $version];
    $response = $httpClient->post(self::API_CHECK_BASE_URI, [RequestOptions::JSON => $payload]);
    if ($response->getStatusCode() !== 200) {
      return [
        'success' => false,
        'data' => 'Failed downloading response, status code: ' . $response->getStatusCode(),
      ];
    }
    $result = json_decode($response->getBody()->getContents(), true);
    if (!is_array($result) || !isset($result['success']) || !isset($result['data']) || !isset($result['data'][$project])) {
      return [
        'success' => false,
        'data' => 'Failed preparing translations - malformed response: ' . $response->getBody()->getContents(),
      ];
    }

    $data = $result['data'][$project];
    $locales = [];
    foreach ($data as $languageResponse) {
      $locales[$languageResponse['wp_locale']] = $languageResponse['wp_locale'];
    }

    if (isset($locales['de_DE_formal']) && isset($locales['fr_FR'])) {
      return [
        'success' => true,
        'data' => '',
      ];
    } else {
      return [
        'success' => false,
        'data' => '',
      ];
    }
  }
}
