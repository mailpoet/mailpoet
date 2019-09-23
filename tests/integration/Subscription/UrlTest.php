<?php
namespace MailPoet\Test\Subscription;

use Codeception\Util\Stub;
use MailPoet\Features\FeaturesController;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Router\Router;
use MailPoet\Subscription\Url;
use MailPoet\Models\Subscriber;
use MailPoet\Models\Setting;
use MailPoet\Config\Populator;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetTest {

  /** @var Url */
  private $url;

  /** @var SettingsController */
  private $settings;

  function _before() {
    parent::_before();
    $this->settings = new SettingsController;
    $referral_detector = new ReferralDetector(WPFunctions::get(), $this->settings);
    $features_controller = Stub::makeEmpty(FeaturesController::class);
    $wc_transactional_emails = new TransactionalEmails(WPFunctions::get(), $this->settings);
    $populator = new Populator($this->settings, WPFunctions::get(), new Captcha, $referral_detector, $features_controller, $wc_transactional_emails);
    $populator->up();
    $this->url = new Url(WPFunctions::get(), $this->settings);
  }

  function testItReturnsTheDefaultPageUrlIfNoPageIsSetInSettings() {
    $this->settings->delete('subscription');

    $url = $this->url->getCaptchaUrl();
    expect($url)->notNull();
    expect($url)->contains('action=captcha');
    expect($url)->contains('endpoint=subscription');

    $url = $this->url->getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');
  }

  function testItReturnsTheCaptchaUrl() {
    $url = $this->url->getCaptchaUrl();
    expect($url)->notNull();
    expect($url)->contains('action=captcha');
    expect($url)->contains('endpoint=subscription');
  }

  function testItReturnsTheCaptchaImageUrl() {
    $url = $this->url->getCaptchaImageUrl(250, 100);
    expect($url)->notNull();
    expect($url)->contains('action=captchaImage');
    expect($url)->contains('endpoint=subscription');
  }

  function testItReturnsTheConfirmationUrl() {
    // preview
    $url = $this->url->getConfirmationUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=confirm');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = $this->url->getConfirmationUrl($subscriber);
    expect($url)->contains('action=confirm');
    expect($url)->contains('endpoint=subscription');

    $this->checkData($url);
  }

  function testItReturnsTheManageSubscriptionUrl() {
    // preview
    $url = $this->url->getManageUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=manage');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = $this->url->getManageUrl($subscriber);
    expect($url)->contains('action=manage');
    expect($url)->contains('endpoint=subscription');

    $this->checkData($url);
  }

  function testItReturnsTheUnsubscribeUrl() {
    // preview
    $url = $this->url->getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = $this->url->getUnsubscribeUrl($subscriber);
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
