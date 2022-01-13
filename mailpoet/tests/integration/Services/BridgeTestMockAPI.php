<?php

namespace MailPoet\Services\Bridge;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class BridgeTestMockAPI extends API {
  public $apiKey;

  public function __construct(
    $apiKey
  ) {
    parent::__construct($apiKey);
    $this->setKey($apiKey);
  }

  public function checkMSSKey() {
    // if a key begins with these codes, return them
    $regex = '/^(expiring|401|402|403|503)/';
    $code = preg_match($regex, $this->apiKey, $m) ? $m[1] : 200;
    return $this->processAPICheckResponse($code);
  }

  public function checkPremiumKey() {
    // if a key begins with these codes, return them
    $regex = '/^(expiring|401|402|403|503)/';
    $code = preg_match($regex, $this->apiKey, $m) ? $m[1] : 200;
    return $this->processPremiumResponse($code);
  }

  public function updateSubscriberCount($count): bool {
    return true;
  }

  public function setKey($apiKey) {
    $this->apiKey = $apiKey;
  }

  public function getKey() {
    return $this->apiKey;
  }

  private function processAPICheckResponse($code) {
    switch ($code) {
      case 'expiring':
        // a special case of a valid key
        $code = 200;
        $body = [
          'subscriber_limit' => 10000,
          'expire_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
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
          'expire_at' => Carbon::createFromTimestamp(WPFunctions::get()->currentTime('timestamp'))
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
