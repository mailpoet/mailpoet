<?php declare(strict_types = 1);

namespace MailPoet\Test\Router;

use MailPoet\Referrals\ReferralDetector;
use MailPoet\Referrals\UrlDecorator;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class UrlDecoratorTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var UrlDecorator */
  private $urlDecorator;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $this->urlDecorator = new UrlDecorator(WPFunctions::get(), $this->settings);
  }

  public function testItDoesntDoAnythingWhenNoReferralId() {
    $this->settings->set(ReferralDetector::REFERRAL_SETTING_NAME, null);
    $url = 'http://example.com';
    verify($this->urlDecorator->decorate($url))->equals($url);
  }

  public function testItCorrectlyAddsReferralId() {
    $this->settings->set(ReferralDetector::REFERRAL_SETTING_NAME, 'abcdefgh');
    verify($this->urlDecorator->decorate('http://example.com/'))
      ->equals('http://example.com/?ref=abcdefgh');
    verify($this->urlDecorator->decorate('http://example.com/?param=value'))
      ->equals('http://example.com/?param=value&ref=abcdefgh');
    verify($this->urlDecorator->decorate('http://example.com/?param=value#hash/?param=val'))
      ->equals('http://example.com/?param=value&ref=abcdefgh#hash/?param=val');
  }
}
