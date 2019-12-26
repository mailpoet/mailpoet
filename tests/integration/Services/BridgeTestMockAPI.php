<?php

namespace MailPoet\Services\Bridge;

use MailPoetVendor\Carbon\Carbon;

class BridgeTestMockAPI extends API {
  public $api_key;

  public function __construct($api_key) {
    $this->setKey($api_key);
  }

  public function checkMSSKey() {
    // if a key begins with these codes, return them
    $regex = '/^(expiring|401|402|403|503)/';
    $code = preg_match($regex, $this->api_key, $m) ? $m[1] : 200;
    return $this->processAPICheckResponse($code);
  }

  public function checkPremiumKey() {
    // if a key begins with these codes, return them
    $regex = '/^(expiring|401|402|403|503)/';
    $code = preg_match($regex, $this->api_key, $m) ? $m[1] : 200;
    return $this->processPremiumResponse($code);
  }

  public function updateSubscriberCount($count) {
    return true;
  }

  public function setKey($api_key) {
    $this->api_key = $api_key;
  }

  public function getKey() {
    return $this->api_key;
  }

  private function processAPICheckResponse($code) {
    switch ($code) {
      case 'expiring':
        // a special case of a valid key
        $code = 200;
        $body = [
          'subscriber_limit' => 10000,
          'expire_at' => Carbon::createFromTimestamp(current_time('timestamp'))
            ->addMonth()->format('c'),
        ];
        break;
      default:
        $body = null;
        break;
    }

    return ['code' => $code, 'data' => $body];
  }

  private function processPremiumResponse($code) {
    switch ($code) {
      case 'expiring':
        // a special case of a valid key
        $code = 200;
        $body = [
          'expire_at' => Carbon::createFromTimestamp(current_time('timestamp'))
            ->addMonth()->format('c'),
        ];
        break;
      default:
        $body = null;
        break;
    }

    return ['code' => $code, 'data' => $body];
  }
}
