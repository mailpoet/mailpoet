<?php

use Codeception\Util\Stub;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

class PremiumKeyCheckTest extends MailPoetTest {
  function _before() {
    $this->worker = new PremiumKeyCheck(microtime(true));
  }

  function testItRequiresPremiumKeyToBeSpecified() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->fillPremiumKey();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  function testItChecksPremiumKey() {
    $response = array('code' => Bridge::PREMIUM_KEY_VALID);
    $this->worker->bridge = Stub::make(
      new Bridge,
      array('checkPremiumKey' => $response),
      $this
    );
    $this->fillPremiumKey();
    expect($this->worker->checkKey())->equals($response);
  }

  private function fillPremiumKey() {
    Setting::setValue(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      '123457890abcdef'
    );
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}