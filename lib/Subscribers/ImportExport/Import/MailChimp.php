<?php
namespace MailPoet\Subscribers\ImportExport\Import;

use MailPoet\Util\Helpers;

class MailChimp {
  function __construct($APIKey, $lists = false) {
    $this->api_key = $this->getAPIKey($APIKey);
    $this->max_post_size = Helpers::getMaxPostSize('bytes');
    $this->data_center = $this->getDataCenter($this->api_key);
    $this->lists_url = 'https://%s.api.mailchimp.com/2.0/lists/list?apikey=%s';
    $this->export_url = 'https://%s.api.mailchimp.com/export/1.0/list/?apikey=%s&id=%s';
  }

  function getLists() {
    if(!$this->api_key || !$this->data_center) {
      return $this->throwException('API');
    }

    $connection = @fopen(sprintf($this->lists_url, $this->data_center, $this->api_key), 'r');

    if(!$connection) {
      return $this->throwException('connection');
    } else {
      $response = '';
      while(!feof($connection)) {
        $buffer = fgets($connection, 4096);
        if(trim($buffer) !== '') {
          $response .= $buffer;
        }
      }
      fclose($connection);
    }

    $response = json_decode($response);

    if(!$response) {
      return $this->throwException('API');
    }

    foreach($response->data as $list) {
      $lists[] = array(
        'id' => $list->id,
        'name' => $list->name
      );
    }

    return $lists;
  }

  function getSubscribers($lists = array()) {
    if(!$this->api_key || !$this->data_center) {
      return $this->throwException('API');
    }

    if(!$lists) {
      return $this->throwException('lists');
    }

    $bytes_fetched = 0;
    foreach($lists as $list) {
      $url = sprintf($this->export_url, $this->data_center, $this->api_key, $list);
      $connection = @fopen($url, 'r');
      if(!$connection) {
        return $this->throwException('connection');
      }
      $i = 0;
      $header = array();
      while(!feof($connection)) {
        $buffer = fgets($connection, 4096);
        if(trim($buffer) !== '') {
          $obj = json_decode($buffer);
          if($i === 0) {
            $header = $obj;
            if(is_object($header) && isset($header->error)) {
              return $this->throwException('lists');
            }
            if(!isset($header_hash)) {
              $header_hash = md5(implode(',', $header));
            } else {
              if(md5(implode(',', $header) !== $header_hash)) {
                return $this->throwException('headers');
              }
            }
          } else {
            $subscribers[] = $obj;
          }
          $i++;
        }
        $bytes_fetched += strlen($buffer);
        if($bytes_fetched > $this->max_post_size) {
          return $this->throwException('size');
        }
      }
      fclose($connection);
    }

    if(!count($subscribers)) {
      return $this->throwException('subscribers');
    }

    return array(
      'subscribers' => $subscribers,
      'invalid' => false,
      'duplicate' => false,
      'header' => $header,
      'subscribersCount' => count($subscribers)
    );
  }

  function getDataCenter($APIKey) {
    if(!preg_match('/-[a-zA-Z0-9]{3,}/', $APIKey)) return false;
    // double parantheses: http://phpsadness.com/sad/51
    $key_parts = explode('-', $APIKey);
    return end($key_parts);
  }

  function getAPIKey($APIKey) {
    return (preg_match('/[a-zA-Z0-9]{32}-[a-zA-Z0-9]{3,}/', $APIKey)) ? $APIKey : false;
  }

  function throwException($error) {
    switch($error) {
      case 'API':
        $errorMessage = __('Invalid API Key.', MAILPOET);
        break;
      case 'connection':
        $errorMessage = __('Could not connect to your MailChimp account.', MAILPOET);
        break;
      case 'headers':
        $errorMessage = __('The selected lists do not have matching columns (headers).', MAILPOET);
        break;
      case 'size':
        $errorMessage = __('The information received from MailChimp is too large for processing. Please limit the number of lists!', MAILPOET);
        break;
      case 'subscribers':
        $errorMessage = __('Did not find any active subscribers.', MAILPOET);
        break;
      case 'lists':
        $errorMessage = __('Did not find any valid lists.', MAILPOET);
        break;
    }
    throw new \Exception($errorMessage);
  }
}
