<?php

namespace MailPoet\WooCommerce;

use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class Subscription {
  const CHECKOUT_OPTIN_INPUT_NAME = 'mailpoet_woocommerce_checkout_optin';
  const CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME = 'mailpoet_woocommerce_checkout_optin_present';
  const OPTIN_ENABLED_SETTING_NAME = 'woocommerce.optin_on_checkout.enabled';
  const OPTIN_SEGMENTS_SETTING_NAME = 'woocommerce.optin_on_checkout.segments';
  const OPTIN_MESSAGE_SETTING_NAME = 'woocommerce.optin_on_checkout.message';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var Helper */
  private $wcHelper;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var Unsubscribes */
  private $unsubscribesTracker;

  public function __construct(
    SettingsController $settings,
    ConfirmationEmailMailer $confirmationEmailMailer,
    WPFunctions $wp,
    Helper $wcHelper,
    SubscribersRepository $subscribersRepository,
    Unsubscribes $unsubscribesTracker
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->wcHelper = $wcHelper;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->subscribersRepository = $subscribersRepository;
    $this->unsubscribesTracker = $unsubscribesTracker;
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
    if ($template) {
      echo $this->getSubscriptionPresenceCheckField();
    }
  }

  private function getSubscriptionField($inputName, $checked, $labelString) {
    return $this->wcHelper->woocommerceFormField(
      $this->wp->escAttr($inputName),
      [
        'type' => 'checkbox',
        'label' => $this->wp->escHtml($labelString),
        'input_class' => ['woocommerce-form__input', 'woocommerce-form__input-checkbox', 'input-checkbox'],
        'label_class' => ['woocommerce-form__label', 'woocommerce-form__label-for-checkbox', 'checkbox'],
        'custom_attributes' => ['data-automation-id' => 'woo-commerce-subscription-opt-in'],
        'return' => true,
      ],
      $checked ? '1' : '0'
    );
  }

  private function getSubscriptionPresenceCheckField() {
    $field = $this->wcHelper->woocommerceFormField(
      self::CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME,
      [
        'type' => 'hidden',
        'return' => true,
      ],
      1
    );
    if ($field) {
      return $field;
    }
    // Workaround for older WooCommerce versions (below 4.6.0) that don't support hidden fields
    // We can remove it after we drop support of older WooCommerce
    $field = $this->wcHelper->woocommerceFormField(
      self::CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME,
      [
        'type' => 'text',
        'return' => true,
      ],
      1
    );
    return str_replace('type="text', 'type="hidden"', $field);
  }

  public function isCurrentUserSubscribed() {
    $subscriber = $this->subscribersRepository->getCurrentWPUser();
    if (!$subscriber instanceof SubscriberEntity) {
      return false;
    }
    $wcSegment = Segment::getWooCommerceSegment();
    $subscriberSegment = SubscriberSegment::where('subscriber_id', $subscriber->getId())
      ->where('segment_id', $wcSegment->id)
      ->findOne();
    return $subscriberSegment instanceof SubscriberSegment
      && $subscriberSegment->status === Subscriber::STATUS_SUBSCRIBED;
  }

  public function subscribeOnOrderPay($orderId) {
    $wcOrder = $this->wcHelper->wcGetOrder($orderId);
    if (!$wcOrder instanceof \WC_Order) {
      return null;
    }

    $data['billing_email'] = $wcOrder->get_billing_email();
    $this->subscribeOnCheckout($orderId, $data);
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
    $checkoutOptin = !empty($_POST[self::CHECKOUT_OPTIN_INPUT_NAME]);

    return $this->handleSubscriberOptin($subscriber, $checkoutOptinEnabled && $checkoutOptin);
  }

  /**
   * Subscribe or unsubscribe a subscriber.
   *
   * @param Subscriber $subscriber Subscriber object
   * @param bool $optin Opting in or opting out.
   */
  public function handleSubscriberOptin( Subscriber $subscriber, $optin = true ) {
    $wcSegment = Segment::getWooCommerceSegment();
    $moreSegmentsToSubscribe = (array)$this->settings->get(self::OPTIN_SEGMENTS_SETTING_NAME, []);
    $signupConfirmation = $this->settings->get('signup_confirmation');

    if ( ! $optin ) {
      // Opt-in is disabled or checkbox is unchecked
      SubscriberSegment::unsubscribeFromSegments(
        $subscriber,
        [$wcSegment->id]
      );
      $this->updateSubscriberStatus($subscriber);

      return false;
    }

    if ( $optin ) {
      $subscriber->source = Source::WOOCOMMERCE_CHECKOUT;

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

    $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriber);
  }

  private function updateSubscriberStatus(Subscriber $subscriber) {
    $ss = Subscriber::findOne($subscriber->id);
    $segmentsCount = $ss->segments()->count();
    if (!$segmentsCount) {
      $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
      $subscriber->save();
      $this->unsubscribesTracker->track($subscriber->id, StatisticsUnsubscribeEntity::SOURCE_ORDER_CHECKOUT);
    }
  }
}
