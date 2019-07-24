<?php
namespace MailPoet\Test\Subscription;

use MailPoet\Referrals\ReferralDetector;
use MailPoet\Router\Router;
use MailPoet\Subscription\Url;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Setting;
use MailPoet\Config\Populator;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetTest {
  function _before() {
    parent::_before();
    $this->settings = new SettingsController;
    $referral_detector = new ReferralDetector(WPFunctions::get(), $this->settings);
    $populator = new Populator($this->settings, WPFunctions::get(), new Captcha, $referral_detector);
    $populator->up();
  }

  function testItReturnsTheDefaultPageUrlIfNoPageIsSetInSettings() {
    $this->settings->delete('subscription');

    $url = Url::getCaptchaUrl();
    expect($url)->notNull();
    expect($url)->contains('action=captcha');
    expect($url)->contains('endpoint=subscription');

    $url = Url::getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');
  }

  function testItReturnsTheCaptchaUrl() {
    $url = Url::getCaptchaUrl();
    expect($url)->notNull();
    expect($url)->contains('action=captcha');
    expect($url)->contains('endpoint=subscription');
  }

  function testItReturnsTheCaptchaImageUrl() {
    $url = Url::getCaptchaImageUrl(250, 100);
    expect($url)->notNull();
    expect($url)->contains('action=captchaImage');
    expect($url)->contains('endpoint=subscription');
  }

  function testItReturnsTheConfirmationUrl() {
    // preview
    $url = Url::getConfirmationUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=confirm');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = Url::getConfirmationUrl($subscriber);
    expect($url)->contains('action=confirm');
    expect($url)->contains('endpoint=subscription');

    $this->checkData($url);
  }

  function testItReturnsTheManageSubscriptionUrl() {
    // preview
    $url = Url::getManageUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=manage');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = Url::getManageUrl($subscriber);
    expect($url)->contains('action=manage');
    expect($url)->contains('endpoint=subscription');

    $this->checkData($url);
  }

  function testItReturnsTheUnsubscribeUrl() {
    // preview
    $url = Url::getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = Url::getUnsubscribeUrl($subscriber);
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');

    $this->checkData($url);
  }

  private function checkData($url) {
    // extract & decode data from url
    $url_params = parse_url($url);
    parse_str($url_params['query'], $params);
    $data = Router::decodeRequestData($params['data']);

    expect($data['email'])->contains('john@mailpoet.com');
    expect($data['token'])->notEmpty();
  }

  function _after() {
    Setting::deleteMany();
    Subscriber::deleteMany();
  }
}
