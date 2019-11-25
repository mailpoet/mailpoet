<?php

namespace MailPoet\WooCommerce;

use Codeception\Util\Fixtures;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoetVendor\Idiorm\ORM;

class SubscriptionTest extends \MailPoetTest {
  /** @var int */
  private $order_id;

  /** @var Subscription */
  private $subscription;

  /** @var SettingsController */
  private $settings;

  /** @var Segment */
  private $wc_segment;

  /** @var Subscriber */
  private $subscriber;

  function _before() {
    $this->order_id = 123; // dummy
    $this->subscription = ContainerWrapper::getInstance()->get(Subscription::class);
    $this->settings = SettingsController::getInstance();
    $this->wc_segment = Segment::getWooCommerceSegment();

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->is_woocommerce_user = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber = $subscriber->save();

    // back up settings
    $this->original_settings = $this->settings->get('woocommerce');
  }

  function testItDisplaysACheckedCheckboxIfCurrentUserIsSubscribed() {
    WP::synchronizeUsers();
    $wp_users = get_users();
    wp_set_current_user($wp_users[0]->ID);
    $subscriber = Subscriber::getCurrentWPUser();
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$this->wc_segment->id]
    );
    expect($this->getRenderedOptinField())->contains('checked');
  }

  function testItDisplaysAnUncheckedCheckboxIfCurrentUserIsNotSubscribed() {
    WP::synchronizeUsers();
    $wp_users = get_users();
    wp_set_current_user($wp_users[0]->ID);
    $subscriber = Subscriber::getCurrentWPUser();
    SubscriberSegment::unsubscribeFromSegments(
      $subscriber,
      [$this->wc_segment->id]
    );
    expect($this->getRenderedOptinField())->notContains('checked');
  }

  function testItDisplaysAnUncheckedCheckboxIfCurrentUserIsNotLoggedIn() {
    wp_set_current_user(0);
    expect($this->getRenderedOptinField())->notContains('checked');
  }

  function testItDisplaysCheckboxOptinMessageFromSettings() {
    $new_message = 'This is a test message.';
    $this->settings->set(Subscription::OPTIN_MESSAGE_SETTING_NAME, $new_message);
    expect($this->getRenderedOptinField())->contains($new_message);
  }

  function testItsTemplateCanBeOverriddenByAHook() {
    $new_template = 'This is a new template';
    add_filter(
      'mailpoet_woocommerce_checkout_optin_template',
      function ($template, $input_name, $checked, $label_string) use ($new_template) {
        return $new_template . $input_name . $checked . $label_string;
      },
      10,
      4
    );
    $result = $this->getRenderedOptinField();
    expect($result)->contains($new_template);
    expect($result)->contains(Subscription::CHECKOUT_OPTIN_INPUT_NAME);
  }

  function testItDoesNotTryToSubscribeIfThereIsNoEmailInOrderData() {
    $data = [];
    $subscribed = $this->subscription->subscribeOnCheckout($this->order_id, $data);
    expect($subscribed)->equals(null);
  }

  function testItDoesNotTryToSubscribeIfSubscriberWithTheEmailWasNotSynced() {
    // non-existent
    $data['billing_email'] = 'non-existent-subscriber@example.com';
    $subscribed = $this->subscription->subscribeOnCheckout($this->order_id, $data);
    expect($subscribed)->equals(null);
    // not a WooCommerce user
    $this->subscriber->is_woocommerce_user = 0;
    $this->subscriber->save();
    $data['billing_email'] = $this->subscriber->email;
    $subscribed = $this->subscription->subscribeOnCheckout($this->order_id, $data);
    expect($subscribed)->equals(null);
  }

  function testItUnsubscribesIfCheckoutOptinIsDisabled() {
    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      [$this->wc_segment->id]
    );
    $subscribed_segments = $this->subscriber->segments()->findArray();
    expect($subscribed_segments)->count(1);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, false);
    $data['billing_email'] = $this->subscriber->email;
    $subscribed = $this->subscription->subscribeOnCheckout($this->order_id, $data);
    expect($subscribed)->equals(false);

    $subscribed_segments = $this->subscriber->segments()->findArray();
    expect($subscribed_segments)->count(0);
  }

  function testItUnsubscribesIfCheckboxIsNotChecked() {
    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      [$this->wc_segment->id]
    );
    $subscribed_segments = $this->subscriber->segments()->findArray();
    expect($subscribed_segments)->count(1);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $data['billing_email'] = $this->subscriber->email;
    $subscribed = $this->subscription->subscribeOnCheckout($this->order_id, $data);
    expect($subscribed)->equals(false);

    $subscribed_segments = $this->subscriber->segments()->findArray();
    expect($subscribed_segments)->count(0);

    $subscriber = Subscriber::where('id', $this->subscriber->id)->findOne();
    expect($subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  function testItSubscribesIfCheckboxIsChecked() {
    $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $this->subscriber->save();

    $subscribed_segments = $this->subscriber->segments()->findArray();
    expect($subscribed_segments)->count(0);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME] = 'on';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $data['billing_email'] = $this->subscriber->email;
    $subscribed = $this->subscription->subscribeOnCheckout($this->order_id, $data);
    expect($subscribed)->equals(true);
    unset($_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME]);

    $subscribed_segments = $this->subscriber->segments()->findArray();
    expect($subscribed_segments)->count(1);

    $subscriber = Subscriber::findOne($this->subscriber->id);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_CHECKOUT);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->confirmed_ip)->notEmpty();
    expect($subscriber->confirmed_at)->notEmpty();
  }

  private function getRenderedOptinField() {
    ob_start();
    $this->subscription->extendWooCommerceCheckoutForm();
    $result = ob_get_clean();
    return $result;
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    // restore settings
    $this->settings->set('woocommerce', $this->original_settings);
  }
}
