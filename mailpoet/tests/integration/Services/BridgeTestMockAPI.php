<?php declare(strict_types = 1);

namespace MailPoet\Services\Bridge;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class BridgeTestMockAPI extends API {
  const VERIFIED_DOMAIN_RESPONSE = [ 'dns' => [
    [
      'host' => 'mailpoet1._domainkey.example.com',
      'value' => 'dkim1.sendingservice.net',
      'type' => 'CNAME',
      'status' => 'valid',
      'message' => '',
    ],
    [
      'host' => 'mailpoet2._domainkey.example.com',
      'value' => 'dkim2.sendingservice.net',
      'type' => 'CNAME',
      'status' => 'valid',
      'message' => '',
    ],
    [
      'host' => '_mailpoet.example.com',
      'value' => '34567abc876556abc8754',
      'type' => 'TXT',
      'status' => 'valid',
      'message' => '',
    ],
  ],
  ];

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

  public function createAuthorizedSenderDomain(string $domain): array {
    switch ($domain) {
      case 'existing.com':
        return [
          'status' => false,
          'error' => 'This domain was already added to the list.',
        ];
      default:
        return self::VERIFIED_DOMAIN_RESPONSE;
    }
  }

  public function getAuthorizedSenderDomains(string $domain = 'all'): ?array {
    $result = self::VERIFIED_DOMAIN_RESPONSE;
    $result['domain'] = 'mailpoet.com';
    if ($domain === 'all') {
      return [ $result ];
    }
    return $result;
  }

  public function verifyAuthorizedSenderDomain(string $domain): array {
    //always valid
    $result = self::VERIFIED_DOMAIN_RESPONSE;
    $result['ok'] = true;
    return $result;
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
