<?php

namespace MailPoet\Test\Cron\Workers\KeyCheck;

use Codeception\Util\Stub;
use MailPoet\Cron\Workers\KeyCheck\PremiumKeyCheck;
use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class PremiumKeyCheckTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController();
    $this->premium_key = '123457890abcdef';
    $this->worker = new PremiumKeyCheck($this->settings, microtime(true));
  }

  function testItRequiresPremiumKeyToBeSpecified() {
    expect($this->worker->checkProcessingRequirements())->false();
    $this->fillPremiumKey();
    expect($this->worker->checkProcessingRequirements())->true();
  }

  function testItChecksPremiumKey() {
    $response = ['code' => Bridge::KEY_VALID];
    $this->worker->bridge = Stub::make(
      new Bridge,
      [
        'checkPremiumKey' => $response,
        'storePremiumKeyAndState' => null,
      ],
      $this
    );
    $this->worker->bridge->expects($this->once())
      ->method('checkPremiumKey')
      ->with($this->equalTo($this->premium_key));
    $this->worker->bridge->expects($this->once())
      ->method('storePremiumKeyAndState')
      ->with(
        $this->equalTo($this->premium_key),
        $this->equalTo($response)
      );
    $this->fillPremiumKey();
    expect($this->worker->checkKey())->equals($response);
  }

  private function fillPremiumKey() {
    $this->settings->set(
      Bridge::PREMIUM_KEY_SETTING_NAME,
      $this->premium_key
    );
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Setting::$_table);
  }
}
