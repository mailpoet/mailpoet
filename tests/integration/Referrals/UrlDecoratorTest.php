<?php

namespace MailPoet\Test\Router;

use MailPoet\Referrals\ReferralDetector;
use MailPoet\Referrals\UrlDecorator;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class UrlDecoratorTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var UrlDecorator */
  private $url_decorator;

  function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->url_decorator = new UrlDecorator(WPFunctions::get(), $this->settings);
  }

  function testItDoesntDoAnythingWhenNoReferralId() {
    $this->settings->set(ReferralDetector::REFERRAL_SETTING_NAME, null);
    $url = 'http://example.com';
    expect($this->url_decorator->decorate($url))->equals($url);
  }

  function testItCorrectlyAddsReferralId() {
    $this->settings->set(ReferralDetector::REFERRAL_SETTING_NAME, 'abcdefgh');
    expect($this->url_decorator->decorate('http://example.com/'))
      ->equals('http://example.com/?ref=abcdefgh');
    expect($this->url_decorator->decorate('http://example.com/?param=value'))
      ->equals('http://example.com/?param=value&ref=abcdefgh');
    expect($this->url_decorator->decorate('http://example.com/?param=value#hash/?param=val'))
      ->equals('http://example.com/?param=value&ref=abcdefgh#hash/?param=val');
  }
}
