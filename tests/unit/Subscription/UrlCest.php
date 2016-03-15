<?php
/*
use \MailPoet\Subscription\Url;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\Setting;
use \MailPoet\Config\Populator;

class UrlCest {
  function _before() {
    $populator = new Populator();
    $populator->up();
  }

  function itReturnsTheConfirmationUrl() {
    // preview
    $url = Url::getConfirmationUrl(false);
    expect($url)->contains('mailpoet_action=confirm');
    expect($url)->contains('preview');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com'
   ));
    $url = Url::getConfirmationUrl($subscriber);
    expect($url)->contains('mailpoet_action=confirm');
    expect($url)->contains('mailpoet_token=');
    expect($url)->contains('mailpoet_email=');
  }

  function itReturnsTheManageSubscriptionUrl() {
    // preview
    $url = Url::getManageUrl(false);
    expect($url)->contains('mailpoet_action=manage');
    expect($url)->contains('preview');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com'
    ));
    $url = Url::getManageUrl($subscriber);
    expect($url)->contains('mailpoet_action=manage');
    expect($url)->contains('mailpoet_token=');
    expect($url)->contains('mailpoet_email=');
  }

  function itReturnsTheUnsubscribeUrl() {
    // preview
    $url = Url::getUnsubscribeUrl(false);
    expect($url)->contains('mailpoet_action=unsubscribe');
    expect($url)->contains('preview');

    // actual subscriber
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'john@mailpoet.com'
    ));
    $url = Url::getUnsubscribeUrl($subscriber);
    expect($url)->contains('mailpoet_action=unsubscribe');
    expect($url)->contains('mailpoet_token=');
    expect($url)->contains('mailpoet_email=');
  }

  function _after() {
    Setting::deleteMany();
    Subscriber::deleteMany();
  }
}
*/