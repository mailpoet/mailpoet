<?php

namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class MailChimp {
  public $api_key;
  public $max_post_size;
  public $data_center;
  private $export_url;
  private $lists_url;
  const API_KEY_REGEX = '/[a-zA-Z0-9]{32}-[a-zA-Z0-9]{2,4}$/';

  public function __construct($apiKey) {
    $this->apiKey = $this->getAPIKey($apiKey);
    $this->maxPostSize = Helpers::getMaxPostSize('bytes');
    $this->dataCenter = $this->getDataCenter($this->apiKey);
    $this->listsUrl = 'https://%s.api.mailchimp.com/2.0/lists/list?apikey=%s';
    $this->exportUrl = 'https://%s.api.mailchimp.com/export/1.0/list/?apikey=%s&id=%s';
  }

  public function getLists() {
    if (!$this->apiKey || !$this->dataCenter) {
      return $this->throwException('API');
    }

    $url = sprintf($this->listsUrl, $this->dataCenter, $this->apiKey);
    $connection = @fopen($url, 'r');

    if (!$connection) {
      return $this->throwException('connection');
    } else {
      $response = '';
      while (!feof($connection)) {
        $buffer = fgets($connection, 4096);
        if (trim($buffer) !== '') {
          $response .= $buffer;
        }
      }
      fclose($connection);
    }

    $response = json_decode($response);

    if (!$response) {
      return $this->throwException('API');
    }

    $lists = [];
    foreach ($response->data as $list) {
      $lists[] = [
        'id' => $list->id,
        'name' => $list->name,
      ];
    }

    return $lists;
  }

  public function getSubscribers($lists = []) {
    if (!$this->apiKey || !$this->dataCenter) {
      return $this->throwException('API');
    }

    if (!$lists) {
      return $this->throwException('lists');
    }

    $bytesFetched = 0;
    $subscribers = [];
    $duplicate = [];
    $header = [];
    foreach ($lists as $list) {
      $url = sprintf($this->exportUrl, $this->dataCenter, $this->apiKey, $list);
      $connection = @fopen($url, 'r');
      if (!$connection) {
        return $this->throwException('connection');
      }
      $i = 0;
      while (!feof($connection)) {
        $buffer = fgets($connection, 4096);
        if (trim($buffer) !== '') {
          $obj = json_decode($buffer);
          if ($i === 0) {
            $header = $obj;
            if (is_object($header) && isset($header->error)) {
              return $this->throwException('lists');
            }
            if (!isset($headerHash)) {
              $headerHash = md5(implode(',', $header));
            } elseif (md5(implode(',', $header)) !== $headerHash) {
              return $this->throwException('headers');
            }
          } elseif (isset($subscribers[$obj[0]])) {
            $duplicate[] = $obj[0];
          } else {
            $subscribers[$obj[0]] = $obj;
          }
          $i++;
        }
        $bytesFetched += strlen($buffer);
        if ($bytesFetched > $this->maxPostSize) {
          return $this->throwException('size');
        }
      }
      fclose($connection);
    }

    if (!count($subscribers)) {
      return $this->throwException('subscribers');
    }

    return [
      'subscribers' => array_values($subscribers),
      'invalid' => [],
      'duplicate' => $duplicate,
      'role' => [],
      'header' => $header,
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

  public function throwException($error) {
    $errorMessage = WPFunctions::get()->__('Unknown MailChimp error.', 'mailpoet');
    switch ($error) {
      case 'API':
        $errorMessage = WPFunctions::get()->__('Invalid API Key.', 'mailpoet');
        break;
      case 'connection':
        $errorMessage = WPFunctions::get()->__('Could not connect to your MailChimp account.', 'mailpoet');
        break;
      case 'headers':
        $errorMessage = WPFunctions::get()->__('The selected lists do not have matching columns (headers).', 'mailpoet');
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
}
