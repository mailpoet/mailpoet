<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class TranslationsController {
  private const API_IMPORT_BASE_URI = 'https://translate.wordpress.com/api/import-transifex/';

  public function importTransifex(string $version, $project = 'mailpoet'): array {
    $httpClient = new Client([
      'base_uri' => self::API_IMPORT_BASE_URI . $project . '/',
    ]);
    $response = $httpClient->post($version);
    $response->getStatusCode();
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

  public function checkIfTranslationsAreReady(string $version): bool {
    return true;
  }
}
