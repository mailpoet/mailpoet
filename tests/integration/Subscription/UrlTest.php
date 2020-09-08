<?php

namespace MailPoet\Test\Subscription;

use MailPoet\Config\Populator;
use MailPoet\Form\FormFactory;
use MailPoet\Form\FormsRepository;
use MailPoet\Models\Subscriber;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscription\Captcha;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetTest {

  /** @var SubscriptionUrlFactory */
  private $url;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = SettingsController::getInstance();
    $referralDetector = new ReferralDetector(WPFunctions::get(), $this->settings);
    $populator = new Populator(
      $this->settings,
      WPFunctions::get(),
      new Captcha,
      $referralDetector,
      $this->diContainer->get(FormsRepository::class),
      $this->diContainer->get(FormFactory::class)
    );
    $populator->up();
    $this->url = new SubscriptionUrlFactory(WPFunctions::get(), $this->settings, new LinkTokens);
  }

  public function testItReturnsTheDefaultPageUrlIfNoPageIsSetInSettings() {
    $this->settings->delete('subscription');

    $url = $this->url->getCaptchaUrl('abc');
    expect($url)->notNull();
    expect($url)->contains('action=captcha');
    expect($url)->contains('endpoint=subscription');

    $url = $this->url->getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');
  }

  public function testItReturnsTheCaptchaUrl() {
    $url = $this->url->getCaptchaUrl('abc');
    expect($url)->notNull();
    expect($url)->contains('action=captcha');
    expect($url)->contains('endpoint=subscription');
  }

  public function testItReturnsTheCaptchaImageUrl() {
    $url = $this->url->getCaptchaImageUrl(250, 100, 'abc');
    expect($url)->notNull();
    expect($url)->contains('action=captchaImage');
    expect($url)->contains('endpoint=subscription');
  }

  public function testItReturnsTheConfirmationUrl() {
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

    $this->checkSubscriberData($url);
  }

  public function testItReturnsTheManageSubscriptionUrl() {
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

    $this->checkSubscriberData($url);
  }

  public function testItReturnsTheUnsubscribeUrl() {
    // preview
    $url = $this->url->getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');
    $data = $this->getUrlData($url);
    expect($data['preview'])->equals(1);

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = $this->url->getUnsubscribeUrl($subscriber);
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');

    $this->checkSubscriberData($url);

    // subscriber and query id
    $url = $this->url->getUnsubscribeUrl($subscriber, 10);
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');

    $data = $this->checkSubscriberData($url);
    expect($data['queueId'])->equals(10);

    // no subscriber but query id
    $url = $this->url->getUnsubscribeUrl(null, 10);
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');

    $data = $this->getUrlData($url);
    expect(isset($data['data']['queueId']))->false();
    expect($data['preview'])->equals(1);
  }

  public function testItReturnsConfirmUnsubscribeUrl() {
    // preview
    $url = $this->url->getConfirmUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->contains('action=confirm_unsubscribe');
    expect($url)->contains('endpoint=subscription');
    $data = $this->getUrlData($url);
    expect($data['preview'])->equals(1);

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'john@mailpoet.com',
    ]);
    $url = $this->url->getConfirmUnsubscribeUrl($subscriber);
    expect($url)->contains('action=confirm_unsubscribe');
    expect($url)->contains('endpoint=subscription');

    $this->checkSubscriberData($url);

    // subscriber and query id
    $url = $this->url->getConfirmUnsubscribeUrl($subscriber, 10);
    expect($url)->contains('action=confirm_unsubscribe');
    expect($url)->contains('endpoint=subscription');

    $data = $this->checkSubscriberData($url);
    expect($data['queueId'])->equals(10);

    // no subscriber but query id
    $url = $this->url->getConfirmUnsubscribeUrl(null, 10);
    expect($url)->contains('action=confirm_unsubscribe');
    expect($url)->contains('endpoint=subscription');

    $data = $this->getUrlData($url);
    expect(isset($data['data']['queueId']))->false();
    expect($data['preview'])->equals(1);
  }

  private function checkSubscriberData(string $url) {
    $data = $this->getUrlData($url);
    expect($data['email'])->contains('john@mailpoet.com');
    expect($data['token'])->notEmpty();
    return $data;
  }

  private function getUrlData(string $url) {
    // extract & decode data from url
    $urlParamsQuery = parse_url($url, PHP_URL_QUERY);
    parse_str((string)$urlParamsQuery, $params);
    return Router::decodeRequestData($params['data']);
  }

  public function _after() {
    $this->diContainer->get(SettingsRepository::class)->truncate();
    Subscriber::deleteMany();
  }
}
