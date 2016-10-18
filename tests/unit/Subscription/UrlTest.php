<?php
use MailPoet\Router\Router;
use \MailPoet\Subscription\Url;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Setting;
use \MailPoet\Config\Populator;

class UrlTest extends MailPoetTest {
  function _before() {
    $populator = new Populator();
    $populator->up();
  }

  function testItReturnsTheConfirmationUrl() {
    // preview
    $url = Url::getConfirmationUrl(false);
    expect($url)->notNull();
    expect($url)->contains('action=confirm');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com'
   ));
    $url = Url::getConfirmationUrl($subscriber);
    expect($url)->contains('action=confirm');
    expect($url)->contains('endpoint=subscription');

    $this->checkData($url);
  }

  function testItReturnsTheManageSubscriptionUrl() {
    // preview
    $url = Url::getManageUrl(false);
    expect($url)->notNull();
    expect($url)->contains('action=manage');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com'
    ));
    $url = Url::getManageUrl($subscriber);
    expect($url)->contains('action=manage');
    expect($url)->contains('endpoint=subscription');

    $this->checkData($url);
  }

  function testItReturnsTheUnsubscribeUrl() {
    // preview
    $url = Url::getUnsubscribeUrl(false);
    expect($url)->notNull();
    expect($url)->contains('action=unsubscribe');
    expect($url)->contains('endpoint=subscription');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com'
    ));
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
