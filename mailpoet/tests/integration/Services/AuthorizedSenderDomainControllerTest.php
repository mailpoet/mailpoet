<?php

namespace MailPoet\Test\Services;

use MailPoet\Services\AuthorizedSenderDomainController;
use MailPoet\Util\DmarcPolicyChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\API;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class AuthorizedSenderDomainControllerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var Bridge */
  private $bridge;

  public function _before() {
    parent::_before();

    $apiKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    if (!$apiKey) {
      $this->markTestSkipped("Skipping, 'WP_TEST_MAILER_MAILPOET_API' not set.");
    }

    $this->bridge = new Bridge();
    $this->bridge->api = new API($apiKey, new WPFunctions());

    $this->settings = SettingsController::getInstance();
    $this->settings->set(
      Mailer::MAILER_CONFIG_SETTING_NAME,
      [
        'method' => 'MailPoet',
        'mailpoet_api_key' => $apiKey,
      ]
    );
  }

  public function testItFetchSenderDomains() {
    $domains =  ['mailpoet.com', 'GOOD', 'testdomain.com'];

    $controller = $this->getController();
    $allDomains = $controller->getAllSenderDomains();
    expect($allDomains)->same($domains);
  }

  public function testItReturnsVerifiedSenderDomains() {
    $controller = $this->getController();
    $verifiedDomains = $controller->getVerifiedSenderDomains();
    expect($verifiedDomains)->same(['mailpoet.com']); // only this is Verified for now
  }

  public function testItReturnsTrueWhenDmarcIsEnabled() {
    $controller = $this->getController();
    $isRetricted = $controller->isDomainDmarcRetricted('mailpoet.com');
    expect($isRetricted)->same(true);
  }

  public function testItReturnsFalseWhenDmarcIsNotEnabled() {
    $controller = $this->getController();
    $isRetricted = $controller->isDomainDmarcRetricted('example.com');
    expect($isRetricted)->same(false);
  }

  private function getController(): AuthorizedSenderDomainController {
    $dmarcPolicyChecker = $this->diContainer->get(DmarcPolicyChecker::class);
    return new AuthorizedSenderDomainController($this->bridge, $dmarcPolicyChecker);
  }
}
