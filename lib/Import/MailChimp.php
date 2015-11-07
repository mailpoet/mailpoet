<?php
namespace MailPoet\Import;

use MailPoet\Util\Helpers;

class MailChimp {
  public function __construct($APIKey, $lists = false) {
    $this->APIKey = $this->getAPIKey($APIKey);
    $this->maxPostSize = Helpers::getMaxPostSize('bytes');
    $this->dataCenter = $this->getDataCenter();
    $this->lists = $lists;
    $this->listsURL = 'https://%s.api.mailchimp.com/2.0/lists/list?apikey=%s';
    $this->exportURL = 'https://%s.api.mailchimp.com/export/1.0/list/?apikey=%s&id=%s';
  }

  public function getLists() {
    if(!$this->APIKey || !$this->dataCenter) {
      return $this->processError('API');
    }

    $connection = @fopen(sprintf($this->listsURL, $this->dataCenter, $this->APIKey), 'r');

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

  public function getSubscribers() {
    if(!$this->APIKey || !$this->dataCenter) {
      return $this->processError('API');
    }

    if(!is_array($this->lists)) {
      return $this->processError('lists');
    }

    $bytesFetched = 0;
    foreach ($this->lists as $list) {
      $url = sprintf($this->exportURL, $this->dataCenter, $this->APIKey, $list);
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
                return $this->processError('API');
              }
              if(!isset($headerHash)) {
                $headerHash = md5(implode(',', $header));
              } else {
                if(md5(implode(',', $header) !== $headerHash)) {
                  return $this->processError('headers');
                }
              }
            } else {
              $subscribers[] = $obj;
            }
            $i++;
          }

          $bytesFetched += strlen($buffer);
          if($bytesFetched > $this->maxPostSize) {
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
        'count' => count($subscribers)
      )
    );
  }

  private function getDataCenter() {
    // double parantheses: http://phpsadness.com/sad/51
    return ($this->APIKey) ? end((explode('-', $this->APIKey))) : false;
  }

  private function getAPIKey($APIKey) {
    return (preg_match('/[a-zA-Z0-9]{32}-[a-zA-Z0-9]{3,}/', $APIKey)) ? $APIKey : false;
  }

  private function processError($error) {
    switch ($error) {
      case 'API':
        $message = __('Invalid API key.');
        break;
      case 'connection':
        $message = __('Could not connect to your MailChimp account.');
        break;
      case 'headers':
        $message = __('The selected lists do not have matching columns (headers).');
        break;
      case 'size':
        $message = __('Information received from MailChimp is too large for processing. Please limit the number of lists.');
        break;
      case 'subscribers':
        $message = __('Did not find any active subscribers.');
        break;
      case 'lists':
        $message = __('Did not find any valid lists');
        break;
    }
    return array(
      'status' => 'error',
      'message' => $message
    );
  }
}