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
      return $this->processError('API');
    }

    $connection = @fopen(sprintf($this->lists_url, $this->data_center, $this->api_key), 'r');

    if(!$connection) {
      return $this->processError('connection');
    } else {
      $response = '';
      while (!feof($connection)) {
        $buffer = fgets($connection, 4096);
        if(trim($buffer) !== '') {
          $response .= $buffer;
        }
      }
      fclose($connection);
    }

    $response = json_decode($response);

    if(!$response) {
      return $this->processError('API');
    }

    foreach ($response->data as $list) {
      $lists[] = array(
        'id' => $list->id,
        'name' => $list->name
      );
    }

    return array(
      'result' => true,
      'data' => $lists
    );
  }

  function getSubscribers($lists = array()) {
    if(!$this->api_key || !$this->data_center) {
      return $this->processError('API');
    }

    if(!$lists) {
      return $this->processError('lists');
    }

    $bytes_fetched = 0;
    foreach ($lists as $list) {
      $url = sprintf($this->export_url, $this->data_center, $this->api_key, $list);
      $connection = @fopen($url, 'r');
      if(!$connection) {
        return $this->processError('connection');
      } else {
        $i = 0;
        $header = array();
        while (!feof($connection)) {
          $buffer = fgets($connection, 4096);
          if(trim($buffer) !== '') {
            $obj = json_decode($buffer);
            if($i === 0) {
              $header = $obj;
              if(is_object($header) && isset($header->error)) {
                return $this->processError('lists');
              }
              if(!isset($header_hash)) {
                $header_hash = md5(implode(',', $header));
              } else {
                if(md5(implode(',', $header) !== $header_hash)) {
                  return $this->processError('headers');
                }
              }
            } else {
              $subscribers[] = $obj;
            }
            $i++;
          }

          $bytes_fetched += strlen($buffer);
          if($bytes_fetched > $this->max_post_size) {
            return $this->processError('size');

          }
        }
        fclose($connection);
      }
    }

    if(!count($subscribers)) {
      return $this->processError('subscribers');

    }

    return array(
      'result' => true,
      'data' => array(
        'subscribers' => $subscribers,
        'invalid' => false,
        'duplicate' => false,
        'header' => $header,
        'subscribersCount' => count($subscribers)
      )
    );
  }

  function getDataCenter($APIKey) {
    if (!preg_match('/-[a-zA-Z0-9]{3,}/', $APIKey)) return false;
    // double parantheses: http://phpsadness.com/sad/51
    return end((explode('-', $APIKey)));
  }

  function getAPIKey($APIKey) {
    return (preg_match('/[a-zA-Z0-9]{32}-[a-zA-Z0-9]{3,}/', $APIKey)) ? $APIKey : false;
  }

  function processError($error) {
    switch ($error) {
      case 'API':
        $errorMessage = __('Invalid API key.');
        break;
      case 'connection':
        $errorMessage = __('Could not connect to your MailChimp account.');
        break;
      case 'headers':
        $errorMessage = __('The selected lists do not have matching columns (headers).');
        break;
      case 'size':
        $errorMessage = __('Information received from MailChimp is too large for processing. Please limit the number of lists.');
        break;
      case 'subscribers':
        $errorMessage = __('Did not find any active subscribers.');
        break;
      case 'lists':
        $errorMessage = __('Did not find any valid lists');
        break;
    }
    return array(
      'result' => false,
      'error' => $errorMessage
    );
  }
}