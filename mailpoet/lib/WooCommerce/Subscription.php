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

  private $allowedHtml = [
    'input' => [
      'type' => true,
      'name' => true,
      'id' => true,
      'class' => true,
      'value' => true,
      'checked' => true,
    ],
    'span' => [
      'class' => true,
    ],
    'label' => [
      'class' => true,
      'data-automation-id' => true,
      'for' => true,
    ],
    'p' => [
      'class' => true,
      'id' => true,
      'data-priority' => true,
    ],
  ];

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
    $template = (string)$this->wp->applyFilters(
      'mailpoet_woocommerce_checkout_optin_template',
      wp_kses(
        $this->getSubscriptionField($inputName, $checked, $labelString),
        $this->allowedHtml
      ),
      $inputName,
      $checked,
      $labelString
    );
    // The template has been sanitized above and can be considered safe.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPressDotOrg.sniffs.OutputEscaping.UnescapedOutputParameter
    echo $template;
    if ($template) {
      $field = $this->getSubscriptionPresenceCheckField();
      echo wp_kses($field, $this->allowedHtml);
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
    return str_replace('type="text"', 'type="hidden"', $field);
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

    $subscriberEntity = $this->subscribersRepository->findOneById($subscriber->id);

    if ($subscriberEntity instanceof SubscriberEntity) {
      return $this->handleSubscriberOptin($subscriberEntity, $checkoutOptinEnabled, $checkoutOptin);
    }
  }

  /**
   * Subscribe or unsubscribe a subscriber.
   *
   * @param SubscriberEntity $subscriberEntity Subscriber object
   * @param bool $checkoutOptinEnabled
   * @param bool $checkoutOptin
   */
  public function handleSubscriberOptin(SubscriberEntity $subscriberEntity, bool $checkoutOptinEnabled, bool $checkoutOptin): bool {
    $subscriber = Subscriber::findOne($subscriberEntity->getId());

    $wcSegment = Segment::getWooCommerceSegment();
    $moreSegmentsToSubscribe = (array)$this->settings->get(self::OPTIN_SEGMENTS_SETTING_NAME, []);
    $signupConfirmation = $this->settings->get('signup_confirmation');

    if (!$checkoutOptin) {
      // Opt-in is disabled or checkbox is unchecked
      SubscriberSegment::unsubscribeFromSegments(
        $subscriber,
        [$wcSegment->id]
      );
      // Unsubscribe from configured segment only when opt-in is enabled
      if ($checkoutOptinEnabled && $moreSegmentsToSubscribe) {
        SubscriberSegment::unsubscribeFromSegments(
          $subscriber,
          $moreSegmentsToSubscribe
        );
      }
      // Update global status only in case the opt-in is enabled
      if ($checkoutOptinEnabled) {
        $this->updateSubscriberStatus($subscriber);
      }

      return false;
    }

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

  private function subscribe(Subscriber $subscriber) {
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    if (empty($subscriber->confirmedIp) && empty($subscriber->confirmedAt)) {
      $subscriber->confirmedIp = Helpers::getIP();
      $subscriber->setExpr('confirmed_at', 'NOW()');
    }
    $subscriber->save();
  }

  private function requireSubscriptionConfirmation(Subscriber $subscriber) {
    // we need to save the subscriber here since handleSubscriberOptin() sets the source but doesn't save the model.
    // when we migrate this class to use Doctrine we can probably remove this call to save() as the call to persist() below should be enough.
    $subscriber->save();
    $subscriberEntity = $this->subscribersRepository->findOneById($subscriber->id);

    if ($subscriberEntity instanceof SubscriberEntity) {
      $subscriberEntity->setStatus(Subscriber::STATUS_UNCONFIRMED);
      $this->subscribersRepository->persist($subscriberEntity);
      $this->subscribersRepository->flush();

      try {
        $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriberEntity);
      } catch (\Exception $e) {
        // ignore errors
      }
    }
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
