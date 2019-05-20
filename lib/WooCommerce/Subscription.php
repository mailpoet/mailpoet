<?php
namespace MailPoet\WooCommerce;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class Subscription {
  const CHECKOUT_OPTIN_INPUT_NAME = 'mailpoet_woocommerce_checkout_optin';
  const OPTIN_ENABLED_SETTING_NAME = 'woocommerce.optin_on_checkout.enabled';
  const OPTIN_MESSAGE_SETTING_NAME = 'woocommerce.optin_on_checkout.message';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  function __construct(
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  function extendWooCommerceCheckoutForm() {
    $input_name = self::CHECKOUT_OPTIN_INPUT_NAME;
    $checked = $this->isCurrentUserSubscribed();
    $label_string = $this->settings->get(self::OPTIN_MESSAGE_SETTING_NAME);
    $template = $this->wp->applyFilters(
      'mailpoet_woocommerce_checkout_optin_template',
      $this->getSubscriptionField($input_name, $checked, $label_string),
      $input_name,
      $checked,
      $label_string
    );
    echo $template;
  }

  private function getSubscriptionField($input_name, $checked, $label_string) {
    return '<p class="form-row">
      <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
      <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="' . $this->wp->escAttr($input_name) . '" id="' . $this->wp->escAttr($input_name) . '" ' . ($checked ? 'checked' : '') . ' />
        <span class="woocommerce-terms-and-conditions-checkbox-text">' . $this->wp->escHtml($label_string) . '</label>
    </p>';
  }

  private function isCurrentUserSubscribed() {
    $subscriber = Subscriber::getCurrentWPUser();
    if (!$subscriber instanceof Subscriber) {
      return false;
    }
    $wc_segment = Segment::getWooCommerceSegment();
    $subscriber_segment = SubscriberSegment::where('subscriber_id', $subscriber->id)
      ->where('segment_id', $wc_segment->id)
      ->findOne();
    return $subscriber_segment instanceof SubscriberSegment
      && $subscriber_segment->status === Subscriber::STATUS_SUBSCRIBED;
  }

  function subscribeOnCheckout($order_id, $data) {
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

    $checkout_optin_enabled = (bool)$this->settings->get(self::OPTIN_ENABLED_SETTING_NAME);
    $wc_segment = Segment::getWooCommerceSegment();

    if (!$checkout_optin_enabled || empty($_POST[self::CHECKOUT_OPTIN_INPUT_NAME])) {
      // Opt-in is disabled or checkbox is unchecked
      SubscriberSegment::unsubscribeFromSegments(
        $subscriber,
        [$wc_segment->id]
      );
      return false;
    }

    // checkbox is checked
    $subscriber->source = Source::WOOCOMMERCE_CHECKOUT;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    if (empty($subscriber->confirmed_ip) && empty($subscriber->confirmed_at)) {
      $subscriber->confirmed_ip = Helpers::getIP();
      $subscriber->setExpr('confirmed_at', 'NOW()');
    }
    $subscriber->save();

    SubscriberSegment::subscribeToSegments(
      $subscriber,
      [$wc_segment->id]
    );

    return true;
  }
}
