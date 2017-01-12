<?php
namespace MailPoet\Services;

use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Bridge {
  const MAILPOET_KEY_VALID = 200;
  const MAILPOET_KEY_INVALID = 401;
  const MAILPOET_KEY_EXPIRING = 402;

  public $api;

  function __construct($api_key) {
    $this->api = new Bridge\API($api_key);
  }

  static function isMPSendingServiceEnabled() {
    $mailer_config = Mailer::getMailerConfig();
    return !empty($mailer_config['method'])
      && $mailer_config['method'] === Mailer::METHOD_MAILPOET;
  }

  function checkKey() {
    $result = $this->api->checkKey();
    return $this->processResult($result);
  }

  function processResult(array $result) {
    if(empty($result['code'])) {
      return false;
    }
    Setting::setValue(
      'mta.mailpoet_api_key_state',
      array(
        'code' => (int)$result['code'],
        'data' => !empty($result['data']) ? $result['data'] : null,
      )
    );
    return $result;
  }

  static function invalidateKey() {
    Setting::setValue('mta.mailpoet_api_key_state', array('code' => 401));
  }
}
