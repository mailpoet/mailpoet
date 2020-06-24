<?php

namespace MailPoet\WooCommerce;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class Subscription {
  const CHECKOUT_OPTIN_INPUT_NAME = 'mailpoet_woocommerce_checkout_optin';
  const OPTIN_ENABLED_SETTING_NAME = 'woocommerce.optin_on_checkout.enabled';
  const OPTIN_SEGMENTS_SETTING_NAME = 'woocommerce.optin_on_checkout.segments';
  const OPTIN_MESSAGE_SETTING_NAME = 'woocommerce.optin_on_checkout.message';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  public function __construct(
    SettingsController $settings,
    ConfirmationEmailMailer $confirmationEmailMailer,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
  }

  public function extendWooCommerceCheckoutForm() {
    $inputName = self::CHECKOUT_OPTIN_INPUT_NAME;
    $checked = $this->isCurrentUserSubscribed();
    if (!empty($_POST[self::CHECKOUT_OPTIN_INPUT_NAME])) {
      $checked = true;
    }
    $labelString = $this->settings->get(self::OPTIN_MESSAGE_SETTING_NAME);
    $template = $this->wp->applyFilters(
      'mailpoet_woocommerce_checkout_optin_template',
      $this->getSubscriptionField($inputName, $checked, $labelString),
      $inputName,
      $checked,
      $labelString
    );
    echo $template;
  }

  private function getSubscriptionField($inputName, $checked, $labelString) {
    return '<p class="form-row">
      <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" data-automation-id="woo-commerce-subscription-opt-in">
      <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="' . $this->wp->escAttr($inputName) . '" id="' . $this->wp->escAttr($inputName) . '" ' . ($checked ? 'checked' : '') . ' />
        <span class="woocommerce-terms-and-conditions-checkbox-text">' . $this->wp->escHtml($labelString) . '</label>
    </p>';
  }

  private function isCurrentUserSubscribed() {
    $subscriber = Subscriber::getCurrentWPUser();
    if (!$subscriber instanceof Subscriber) {
      return false;
    }
    $wcSegment = Segment::getWooCommerceSegment();
    $subscriberSegment = SubscriberSegment::where('subscriber_id', $subscriber->id)
      ->where('segment_id', $wcSegment->id)
      ->findOne();
    return $subscriberSegment instanceof SubscriberSegment
      && $subscriberSegment->status === Subscriber::STATUS_SUBSCRIBED;
  }

  public function subscribeOnCheckout($orderId, $data) {
    if (empty($data['billing_email'])) {
      // no email in posted order data
      return null;
    }

    $subscriber = Subscriber::where('email', $data['billing_email'])
      ->where('is_woocommerce_user', 1)
      ->findOne();
    if (!$subscriber) {
      // no subscriber: WooCommerce sync didn't work
      return null;
    }

    $checkoutOptinEnabled = (bool)$this->settings->get(self::OPTIN_ENABLED_SETTING_NAME);
    $wcSegment = Segment::getWooCommerceSegment();
    $moreSegmentsToSubscribe = (array)$this->settings->get(self::OPTIN_SEGMENTS_SETTING_NAME, []);

    if (!$checkoutOptinEnabled || empty($_POST[self::CHECKOUT_OPTIN_INPUT_NAME])) {
      // Opt-in is disabled or checkbox is unchecked
      SubscriberSegment::unsubscribeFromSegments(
        $subscriber,
        [$wcSegment->id]
      );
      $this->updateSubscriberStatus($subscriber);
      return false;
    }
    $subscriber->source = Source::WOOCOMMERCE_CHECKOUT;

    $signupConfirmation = $this->settings->get('signup_confirmation');
    // checkbox is checked
    if (
      ($subscriber->status === Subscriber::STATUS_SUBSCRIBED)
      || ((bool)$signupConfirmation['enabled'] === false)
    ) {
      $this->subscribe($subscriber);
    } else {
      $this->requireSubscriptionConfirmation($subscriber);
    }

    SubscriberSegment::subscribeToSegments(
      $subscriber,
      array_merge([$wcSegment->id], $moreSegmentsToSubscribe)
    );

    return true;
  }

  private function subscribe(Subscriber $subscriber) {
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    if (empty($subscriber->confirmedIp) && empty($subscriber->confirmedAt)) {
      $subscriber->confirmedIp = Helpers::getIP();
      $subscriber->setExpr('confirmed_at', 'NOW()');
    }
    $subscriber->save();
  }

  private function requireSubscriptionConfirmation(Subscriber $subscriber) {
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->save();

    $this->confirmationEmailMailer->sendConfirmationEmail($subscriber);
  }

  private function updateSubscriberStatus(Subscriber $subscriber) {
    $segmentsCount = $subscriber->segments()->count();
    if (!$segmentsCount) {
      $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
      $subscriber->save();
    }
  }
}
