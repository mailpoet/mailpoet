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

  function __construct($api_key) {
    $this->api_key = $this->getAPIKey($api_key);
    $this->max_post_size = Helpers::getMaxPostSize('bytes');
    $this->data_center = $this->getDataCenter($this->api_key);
    $this->lists_url = 'https://%s.api.mailchimp.com/2.0/lists/list?apikey=%s';
    $this->export_url = 'https://%s.api.mailchimp.com/export/1.0/list/?apikey=%s&id=%s';
  }

  function getLists() {
    if (!$this->api_key || !$this->data_center) {
      return $this->throwException('API');
    }

    $url = sprintf($this->lists_url, $this->data_center, $this->api_key);
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

  function getSubscribers($lists = []) {
    if (!$this->api_key || !$this->data_center) {
      return $this->throwException('API');
    }

    if (!$lists) {
      return $this->throwException('lists');
    }

    $bytes_fetched = 0;
    $subscribers = [];
    $duplicate = [];
    $header = [];
    foreach ($lists as $list) {
      $url = sprintf($this->export_url, $this->data_center, $this->api_key, $list);
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
            if (!isset($header_hash)) {
              $header_hash = md5(implode(',', $header));
            } elseif (md5(implode(',', $header)) !== $header_hash) {
              return $this->throwException('headers');
            }
          } elseif (isset($subscribers[$obj[0]])) {
            $duplicate[] = $obj[0];
          } else {
            $subscribers[$obj[0]] = $obj;
          }
          $i++;
        }
        $bytes_fetched += strlen($buffer);
        if ($bytes_fetched > $this->max_post_size) {
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

  function getDataCenter($api_key) {
    if (!$api_key) return false;
    $api_key_parts = explode('-', $api_key);
    return end($api_key_parts);
  }

  function getAPIKey($api_key) {
    return (preg_match(self::API_KEY_REGEX, $api_key)) ? $api_key : false;
  }

  function throwException($error) {
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
