<?php
namespace MailPoet\Services\Bridge;

use Carbon\Carbon;

if(!defined('ABSPATH')) exit;

class MockAPI {
  public $api_key;

  function __construct($api_key) {
    $this->setKey($api_key);
  }

  function checkKey() {
    // if a key begins with these codes, return them
    $regex = '/^(401|402|503)/';
    $code = preg_match($regex, $this->api_key, $m) ? $m[1] : 200;
    return $this->processResponse($code);
  }

  function checkPremiumKey() {
    // if a key begins with these codes, return them
    $regex = '/^(expiring|401|402|503)/';
    $code = preg_match($regex, $this->api_key, $m) ? $m[1] : 200;
    return $this->processPremiumResponse($code);
  }

  function updateSubscriberCount($count) {
    return true;
  }

  function setKey($api_key) {
    $this->api_key = $api_key;
  }

  private function processResponse($code) {
    switch($code) {
      case 200:
        $body = array('subscriber_limit' => 10000);
        break;
      case 402:
        $body = array(
          'subscriber_limit' => 10000,
          'expire_at' => Carbon::createFromTimestamp(current_time('timestamp'))
            ->addMonth()->format('c')
        );
        break;
      case 401:
      default:
        $body = null;
        break;
    }

    return array('code' => $code, 'data' => $body);
  }

  private function processPremiumResponse($code) {
    switch($code) {
      case 'expiring':
        // a special case of a valid key
        $code = 200;
        $body = array(
          'expire_at' => Carbon::createFromTimestamp(current_time('timestamp'))
            ->addMonth()->format('c')
        );
        break;
      case 200:
      case 401:
      case 402:
      default:
        $body = null;
        break;
    }

    return array('code' => $code, 'data' => $body);
  }
}
