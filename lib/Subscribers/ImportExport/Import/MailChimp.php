<?php

namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class MailChimp {
  private const API_BASE_URI = 'https://user:%s@%s.api.mailchimp.com/3.0/';
  private const API_KEY_REGEX = '/[a-zA-Z0-9]{32}-[a-zA-Z0-9]{2,4}$/';
  private const API_BATCH_SIZE = 100;

  /** @var false|string  */
  public $apiKey;
  /** @var int */
  public $maxPostSize;
  /** @var false|string  */
  public $dataCenter;
  /** @var MailChimpDataMapper */
  private $mapper;

  public function __construct($apiKey) {
    $this->apiKey = $this->getAPIKey($apiKey);
    $this->maxPostSize = (int)Helpers::getMaxPostSize('bytes');
    $this->dataCenter = $this->getDataCenter($this->apiKey);
    $this->mapper = new MailChimpDataMapper();
  }

  public function getLists(): array {
    if (!$this->apiKey || !$this->dataCenter) {
      $this->throwException('API');
    }

    $lists = [];
    $count = 0;
    while (true) {
      $data = $this->getApiData('lists', $count);
      if ($data === null) {
        $this->throwException('lists');
        break;
      }

      $count += count($data['lists']);
      foreach ($data['lists'] as $list) {
        $lists[] = [
          'id' => $list['id'],
          'name' => $list['name'],
        ];
      }

      if ($data['total_items'] <= $count) {
        break;
      }
    }

    return $lists;
  }

  public function getSubscribers($lists = []): array {
    if (!$this->apiKey || !$this->dataCenter) {
      $this->throwException('API');
    }

    if (!$lists) {
      $this->throwException('lists');
    }

    $subscribers = [];
    $duplicate = [];
    foreach ($lists as $list) {
      $count = 0;
      while (true) {
        $data = $this->getApiData("lists/{$list}/members", $count);
        if ($data === null) {
          $this->throwException('lists');
          break;
        }
        $count += count($data['members']);
        foreach ($data['members'] as $member) {
          $emailAddress = $member['email_address'];
          if (isset($subscribers[$emailAddress])) {
            $duplicate[$emailAddress] = $this->mapper->mapMember($member);
          } else {
            $subscribers[$emailAddress] = $this->mapper->mapMember($member);
          }
        }

        if ($data['total_items'] <= $count) {
          break;
        }
      }
    }

    if (!count($subscribers)) {
      $this->throwException('subscribers');
    }

    return [
      'subscribers' => array_values($subscribers),
      'invalid' => [],
      'duplicate' => $duplicate,
      'role' => [],
      'header' => $this->mapper->getMembersHeader(),
      'subscribersCount' => count($subscribers),
    ];
  }

  public function getDataCenter($apiKey) {
    if (!$apiKey) return false;
    $apiKeyParts = explode('-', $apiKey);
    return end($apiKeyParts);
  }

  public function getAPIKey($apiKey) {
    return (preg_match(self::API_KEY_REGEX, $apiKey)) ? $apiKey : false;
  }

  /**
   * @param string $error
   * @throws \Exception
   */
  public function throwException(string $error): void {
    $errorMessage = WPFunctions::get()->__('Unknown MailChimp error.', 'mailpoet');
    switch ($error) {
      case 'API':
        $errorMessage = WPFunctions::get()->__('Invalid API Key.', 'mailpoet');
        break;
      case 'size':
        $errorMessage = WPFunctions::get()->__('The information received from MailChimp is too large for processing. Please limit the number of lists!', 'mailpoet');
        break;
      case 'subscribers':
        $errorMessage = WPFunctions::get()->__('Did not find any active subscribers.', 'mailpoet');
        break;
      case 'lists':
        $errorMessage = WPFunctions::get()->__('Did not find any valid lists.', 'mailpoet');
        break;
    }
    throw new \Exception($errorMessage);
  }

  private function getApiData(string $endpoint, int $offset): ?array {
    $url = sprintf(self::API_BASE_URI, $this->apiKey, $this->dataCenter);
    $url .= $endpoint . '?' . http_build_query([
      'count' => self::API_BATCH_SIZE,
      'offset' => $offset,
    ]);

    $connection = @fopen($url, 'r');
    if (!$connection) {
      return null;
    }

    $bytesFetched = 0;
    $response = '';
    while (!feof($connection)) {
      $buffer = fgets($connection, 4096);
      if (!is_string($buffer)) {
        return null;
      }
      if (trim($buffer) !== '') {
        $response .= $buffer;
      }
      $bytesFetched += strlen((string)$buffer);
      if ($bytesFetched > $this->maxPostSize) {
        $this->throwException('size');
      }
    }
    fclose($connection);

    return json_decode($response, true);
  }
}
