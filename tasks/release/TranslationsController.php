<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class TranslationsController {
  private const API_BASE_URI = 'https://translate.wordpress.com/api/import-transifex/';

  /** @var Client */
  private $httpClient;

  public function __construct(
    $project = 'mailpoet'
  ) {
    $this->httpClient = new Client([
      'base_uri' => self::API_BASE_URI . $project . '/',
    ]);
  }

  public function importTransifex(string $version): array {
    $response = $this->httpClient->post($version);
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
}
