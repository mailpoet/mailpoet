<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use MailPoet\Config\Populator;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Router\Router;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscription\SubscriptionUrlFactory;
use MailPoet\WP\Functions as WPFunctions;

class UrlTest extends \MailPoetTest {

  /** @var SubscriptionUrlFactory */
  private $url;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $populator = $this->diContainer->get(Populator::class);
    $linkTokens = $this->diContainer->get(LinkTokens::class);
    $populator->up();
    $this->url = new SubscriptionUrlFactory(WPFunctions::get(), $this->settings, $linkTokens);
  }

  public function testItReturnsTheDefaultPageUrlIfNoPageIsSetInSettings() {
    $this->settings->delete('subscription');

    $url = $this->url->getCaptchaUrl('abc');
    expect($url)->notNull();
    expect($url)->stringContainsString('action=captcha');
    expect($url)->stringContainsString('endpoint=subscription');

    $url = $this->url->getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->stringContainsString('action=unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');
  }

  public function testItReturnsTheCaptchaUrl() {
    $url = $this->url->getCaptchaUrl('abc');
    expect($url)->notNull();
    expect($url)->stringContainsString('action=captcha');
    expect($url)->stringContainsString('endpoint=subscription');
  }

  public function testItReturnsTheCaptchaImageUrl() {
    $url = $this->url->getCaptchaImageUrl(250, 100, 'abc');
    expect($url)->notNull();
    expect($url)->stringContainsString('action=captchaImage');
    expect($url)->stringContainsString('endpoint=subscription');
  }

  public function testItReturnsTheConfirmationUrl() {
    // preview
    $url = $this->url->getConfirmationUrl(null);
    expect($url)->notNull();
    expect($url)->stringContainsString('action=confirm');
    expect($url)->stringContainsString('endpoint=subscription');

    // actual subscriber
    $subscriber = $this->createSubscriber();
    $url = $this->url->getConfirmationUrl($subscriber);
    expect($url)->stringContainsString('action=confirm');
    expect($url)->stringContainsString('endpoint=subscription');

    $this->checkSubscriberData($url);
  }

  public function testItReturnsTheManageSubscriptionUrl() {
    // preview
    $url = $this->url->getManageUrl(null);
    expect($url)->notNull();
    expect($url)->stringContainsString('action=manage');
    expect($url)->stringContainsString('endpoint=subscription');

    // actual subscriber
    $subscriber = $this->createSubscriber();
    $url = $this->url->getManageUrl($subscriber);
    expect($url)->stringContainsString('action=manage');
    expect($url)->stringContainsString('endpoint=subscription');

    $this->checkSubscriberData($url);
  }

  public function testItReturnsTheUnsubscribeUrl() {
    // preview
    $url = $this->url->getUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->stringContainsString('action=unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');
    $data = $this->getUrlData($url);
    expect($data['preview'])->equals(1);

    // actual subscriber
    $subscriber = $this->createSubscriber();
    $url = $this->url->getUnsubscribeUrl($subscriber);
    expect($url)->stringContainsString('action=unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');

    $this->checkSubscriberData($url);

    // subscriber and query id
    $url = $this->url->getUnsubscribeUrl($subscriber, 10);
    expect($url)->stringContainsString('action=unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');

    $data = $this->checkSubscriberData($url);
    expect($data['queueId'])->equals(10);

    // no subscriber but query id
    $url = $this->url->getUnsubscribeUrl(null, 10);
    expect($url)->stringContainsString('action=unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');

    $data = $this->getUrlData($url);
    expect(isset($data['data']['queueId']))->false();
    expect($data['preview'])->equals(1);
  }

  public function testItReturnsConfirmUnsubscribeUrl() {
    // preview
    $url = $this->url->getConfirmUnsubscribeUrl(null);
    expect($url)->notNull();
    expect($url)->stringContainsString('action=confirm_unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');
    $data = $this->getUrlData($url);
    expect($data['preview'])->equals(1);

    // actual subscriber
    $subscriber = $this->createSubscriber();
    $url = $this->url->getConfirmUnsubscribeUrl($subscriber);
    expect($url)->stringContainsString('action=confirm_unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');

    $this->checkSubscriberData($url);

    // subscriber and query id
    $url = $this->url->getConfirmUnsubscribeUrl($subscriber, 10);
    expect($url)->stringContainsString('action=confirm_unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');

    $data = $this->checkSubscriberData($url);
    expect($data['queueId'])->equals(10);

    // no subscriber but query id
    $url = $this->url->getConfirmUnsubscribeUrl(null, 10);
    expect($url)->stringContainsString('action=confirm_unsubscribe');
    expect($url)->stringContainsString('endpoint=subscription');

    $data = $this->getUrlData($url);
    expect(isset($data['data']['queueId']))->false();
    expect($data['preview'])->equals(1);
  }

  private function checkSubscriberData(string $url) {
    $data = $this->getUrlData($url);
    expect($data['email'])->stringContainsString('john@mailpoet.com');
    expect($data['token'])->notEmpty();
    return $data;
  }

  private function getUrlData(string $url) {
    // extract & decode data from url
    $urlParamsQuery = parse_url($url, PHP_URL_QUERY);
    parse_str((string)$urlParamsQuery, $params);
    return Router::decodeRequestData($params['data']);
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail('john@mailpoet.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }
}
