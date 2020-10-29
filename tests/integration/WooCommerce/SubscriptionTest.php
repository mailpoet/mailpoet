<?php

namespace MailPoet\WooCommerce;

use Codeception\Util\Fixtures;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\Source;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;
use PHPUnit\Framework\MockObject\MockObject;

class SubscriptionTest extends \MailPoetTest {
  public $originalSettings;
  /** @var int */
  private $orderId;

  /** @var Subscription */
  private $subscription;

  /** @var SettingsController */
  private $settings;

  /** @var Segment */
  private $wcSegment;

  /** @var Subscriber */
  private $subscriber;

  /** @var ConfirmationEmailMailer & MockObject */
  private $confirmationEmailMailer;

  /** @var WP */
  private $wpSegment;

  public function _before() {
    $this->orderId = 123; // dummy
    $this->settings = SettingsController::getInstance();
    $wp = WPFunctions::get();
    $this->confirmationEmailMailer = $this->createMock(ConfirmationEmailMailer::class);
    $this->subscription = new Subscription($this->settings, $this->confirmationEmailMailer, $wp);
    $this->wcSegment = Segment::getWooCommerceSegment();
    $this->wpSegment = $this->diContainer->get(WP::class);

    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->isWoocommerceUser = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $this->subscriber = $subscriber->save();

    // back up settings
    $this->originalSettings = $this->settings->get('woocommerce');
  }

  public function testItDisplaysACheckedCheckboxIfCurrentUserIsSubscribed() {
    $this->wpSegment->synchronizeUsers();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $subscriber = Subscriber::getCurrentWPUser();
    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$this->wcSegment->id]
    );
    expect($this->getRenderedOptinField())->stringContainsString('checked');
  }

  public function testItDisplaysAnUncheckedCheckboxIfCurrentUserIsNotSubscribed() {
    $this->wpSegment->synchronizeUsers();
    $wpUsers = get_users();
    wp_set_current_user($wpUsers[0]->ID);
    $subscriber = Subscriber::getCurrentWPUser();
    SubscriberSegment::unsubscribeFromSegments(
      $subscriber,
      [$this->wcSegment->id]
    );
    expect($this->getRenderedOptinField())->stringNotContainsString('checked');
  }

  public function testItDisplaysAnUncheckedCheckboxIfCurrentUserIsNotLoggedIn() {
    wp_set_current_user(0);
    expect($this->getRenderedOptinField())->stringNotContainsString('checked');
  }

  public function testItDisplaysCheckboxOptinMessageFromSettings() {
    $newMessage = 'This is a test message.';
    $this->settings->set(Subscription::OPTIN_MESSAGE_SETTING_NAME, $newMessage);
    expect($this->getRenderedOptinField())->stringContainsString($newMessage);
  }

  public function testItsTemplateCanBeOverriddenByAHook() {
    $newTemplate = 'This is a new template';
    add_filter(
      'mailpoet_woocommerce_checkout_optin_template',
      function ($template, $inputName, $checked, $labelString) use ($newTemplate) {
        return $newTemplate . $inputName . $checked . $labelString;
      },
      10,
      4
    );
    $result = $this->getRenderedOptinField();
    expect($result)->stringContainsString($newTemplate);
    expect($result)->stringContainsString(Subscription::CHECKOUT_OPTIN_INPUT_NAME);
  }

  public function testItDoesNotTryToSubscribeIfThereIsNoEmailInOrderData() {
    $data = [];
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(null);
  }

  public function testItDoesNotTryToSubscribeIfSubscriberWithTheEmailWasNotSynced() {
    // non-existent
    $data['billing_email'] = 'non-existent-subscriber@example.com';
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(null);
    // not a WooCommerce user
    $this->subscriber->isWoocommerceUser = 0;
    $this->subscriber->save();
    $data['billing_email'] = $this->subscriber->email;
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(null);
  }

  public function testItUnsubscribesIfCheckoutOptinIsDisabled() {
    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      [$this->wcSegment->id]
    );
    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(1);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, false);
    $data['billing_email'] = $this->subscriber->email;
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(false);

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(0);
  }

  public function testItUnsubscribesIfCheckboxIsNotChecked() {
    SubscriberSegment::subscribeToSegments(
      $this->subscriber,
      [$this->wcSegment->id]
    );
    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(1);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $data['billing_email'] = $this->subscriber->email;
    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);
    expect($subscribed)->equals(false);

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(0);

    $subscriber = Subscriber::where('id', $this->subscriber->id)->findOne();
    expect($subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItSubscribesIfCheckboxIsChecked() {
    // double opt-in disabled, no email
    $this->settings->set('signup_confirmation', ['enabled' => false]);
    $this->confirmationEmailMailer
      ->expects($this->never())
      ->method('sendConfirmationEmail');

    $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $this->subscriber->save();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(0);

    // extra segment to subscribe to
    $segmentData = [
      'name' => 'some name',
      'description' => 'some description',
    ];
    $segment = Segment::createOrUpdate($segmentData);
    $this->settings->set(Subscription::OPTIN_SEGMENTS_SETTING_NAME, [$segment->id]);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME] = 'on';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $data['billing_email'] = $this->subscriber->email;

    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);

    expect($subscribed)->equals(true);
    unset($_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME]);

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(2);

    $subscribedSegmentIds = array_column($subscribedSegments, 'id');
    expect(in_array($this->wcSegment->id, $subscribedSegmentIds))->true();
    expect(in_array($segment->id, $subscribedSegmentIds))->true();

    $subscriber = Subscriber::findOne($this->subscriber->id);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_CHECKOUT);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->confirmedIp)->notEmpty();
    expect($subscriber->confirmedAt)->notEmpty();
  }

  public function testItSendsConfirmationEmail() {
    // double opt-in enabled
    $this->settings->set('signup_confirmation', ['enabled' => true]);
    $this->confirmationEmailMailer
      ->expects($this->once())
      ->method('sendConfirmationEmailOnce');

    $this->subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $this->subscriber->save();

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(0);

    $this->settings->set(Subscription::OPTIN_ENABLED_SETTING_NAME, true);
    $_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME] = 'on';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $data['billing_email'] = $this->subscriber->email;

    $subscribed = $this->subscription->subscribeOnCheckout($this->orderId, $data);

    expect($subscribed)->equals(true);
    unset($_POST[Subscription::CHECKOUT_OPTIN_INPUT_NAME]);

    $subscribedSegments = $this->subscriber->segments()->findArray();
    expect($subscribedSegments)->count(1);

    $subscriber = Subscriber::findOne($this->subscriber->id);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_CHECKOUT);
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  private function getRenderedOptinField() {
    ob_start();
    $this->subscription->extendWooCommerceCheckoutForm();
    $result = ob_get_clean();
    return $result;
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    // restore settings
    $this->settings->set('woocommerce', $this->originalSettings);
  }
}
