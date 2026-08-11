<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\WooCommerce;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscribers\TrackingConsentCapture;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Subscription {
  const CHECKOUT_OPTIN_INPUT_NAME = 'mailpoet_woocommerce_checkout_optin';
  const CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME = 'mailpoet_woocommerce_checkout_optin_present';
  const CHECKOUT_TRACKING_CONSENT_INPUT_NAME = 'mailpoet_woocommerce_checkout_tracking_consent';
  const OPTIN_ENABLED_SETTING_NAME = 'woocommerce.optin_on_checkout.enabled';
  const OPTIN_SEGMENTS_SETTING_NAME = 'woocommerce.optin_on_checkout.segments';
  const OPTIN_MESSAGE_SETTING_NAME = 'woocommerce.optin_on_checkout.message';
  const OPTIN_POSITION_SETTING_NAME = 'woocommerce.optin_on_checkout.position';

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

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var TrackingConsentCapture */
  private $trackingConsentCapture;

  public function __construct(
    SettingsController $settings,
    ConfirmationEmailMailer $confirmationEmailMailer,
    WPFunctions $wp,
    Helper $wcHelper,
    SubscribersRepository $subscribersRepository,
    SegmentsRepository $segmentsRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    TrackingConsentCapture $trackingConsentCapture
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->wcHelper = $wcHelper;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->subscribersRepository = $subscribersRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->trackingConsentCapture = $trackingConsentCapture;
  }

  public function extendWooCommerceCheckoutForm() {
    $inputName = self::CHECKOUT_OPTIN_INPUT_NAME;
    $checked = false;
    if (!empty($_POST[self::CHECKOUT_OPTIN_INPUT_NAME])) {
      $checked = true;
    }
    $labelString = $this->settings->get(self::OPTIN_MESSAGE_SETTING_NAME);
    $defaultTemplate = wp_kses(
      $this->getSubscriptionField($inputName, $checked, $labelString),
      $this->allowedHtml
    );
    $filtered = $this->wp->applyFilters(
      'mailpoet_woocommerce_checkout_optin_template',
      $defaultTemplate,
      $inputName,
      $checked,
      $labelString
    );
    $template = is_string($filtered) ? $filtered : $defaultTemplate;
    // The template has been sanitized above and can be considered safe.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $template;
    if ($template) {
      $field = $this->getSubscriptionPresenceCheckField();
      echo wp_kses($field, $this->allowedHtml);
      // A second, independent control. Consent to open and click tracking can
      // never be bundled with the marketing opt-in above it.
      echo wp_kses($this->getTrackingConsentField(), $this->allowedHtml);
    }
  }

  /**
   * The tracking-consent checkbox, shown only on sites that chose to ask. It is
   * never pre-ticked: a pre-ticked consent box is not valid consent (CJEU
   * Planet49).
   */
  private function getTrackingConsentField(): string {
    if (!$this->trackingConsentCapture->isCaptureEnabled()) {
      return '';
    }
    $inputName = self::CHECKOUT_TRACKING_CONSENT_INPUT_NAME;
    $copy = $this->trackingConsentCapture->getCopy(
      SubscriberEntity::TRACKING_CONSENT_METHOD_WOOCOMMERCE_CHECKOUT
    );

    return '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" data-automation-id="woo-commerce-tracking-consent">
      <input id="' . $this->wp->escAttr($inputName) . '" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="' . $this->wp->escAttr($inputName) . '" value="1" />
      <span>' . $this->wp->escHtml($copy) . '</span>
    </label>';
  }

  private function getSubscriptionField($inputName, $checked, $labelString) {
    $checked = checked($checked, true, false);

    return '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" data-automation-id="woo-commerce-subscription-opt-in">
      <input id="mailpoet_woocommerce_checkout_optin" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" ' . $checked . ' type="checkbox" name="' . $this->wp->escAttr($inputName) . '" value="1" />
      <span>' . $this->wp->escHtml($labelString) . '</span>
    </label>';
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

  public function subscribeOnOrderPay($orderId) {
    $wcOrder = $this->wcHelper->wcGetOrder($orderId);
    if (!$wcOrder instanceof \WC_Order) {
      return null;
    }

    $data['billing_email'] = $wcOrder->get_billing_email();
    $this->subscribeOnCheckout($orderId, $data);
  }

  public function subscribeOnCheckout($orderId, $data) {
    $this->triggerAutomateWooOptin();
    if (empty($data['billing_email'])) {
      // no email in posted order data
      return null;
    }

    $subscriber = $this->subscribersRepository->findOneBy(
      ['email' => $data['billing_email'], 'isWoocommerceUser' => 1]
    );

    if (!$subscriber) {
      // no subscriber: WooCommerce sync didn't work
      return null;
    }

    $checkoutOptin = !empty($_POST[self::CHECKOUT_OPTIN_INPUT_NAME]);
    $trackingConsent = !empty($_POST[self::CHECKOUT_TRACKING_CONSENT_INPUT_NAME]);

    return $this->handleSubscriberOptin($subscriber, $checkoutOptin, $trackingConsent);
  }

  /**
   * Subscribe a subscriber.
   *
   * @param SubscriberEntity $subscriber Subscriber object
   * @param bool $shouldSubscribe Whether the subscriber should be subscribed
   * @param bool $trackingConsent Whether the separate tracking-consent box was ticked
   */
  public function handleSubscriberOptin(SubscriberEntity $subscriber, bool $shouldSubscribe, bool $trackingConsent = false): bool {
    // Recorded before the opt-in branch, and independently of it: consenting to
    // tracking and subscribing are two separate decisions, so a customer who
    // declines the newsletter can still allow tracking and vice versa.
    $this->applyTrackingConsent($subscriber, $trackingConsent);

    $wcSegment = $this->segmentsRepository->getWooCommerceSegment();

    $segmentIds = (array)$this->settings->get(self::OPTIN_SEGMENTS_SETTING_NAME, []);
    $moreSegmentsToSubscribe = [];
    if (!empty($segmentIds)) {
      $moreSegmentsToSubscribe = $this->segmentsRepository->findByIds($segmentIds);
    }
    $signupConfirmation = $this->settings->get('signup_confirmation');

    if ($shouldSubscribe) {
      $subscriber->setSource(Source::WOOCOMMERCE_CHECKOUT);

      if (
        ($subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED)
        || ((bool)$signupConfirmation['enabled'] === false)
      ) {
        $this->subscribe($subscriber);
      } else {
        $this->requireSubscriptionConfirmation($subscriber);
      }

      $this->subscriberSegmentRepository->subscribeToSegments($subscriber, array_merge([$wcSegment], $moreSegmentsToSubscribe));

      return true;
    } else {
      return false;
    }
  }

  /**
   * Checkout always acts on a subscriber row that already exists (the
   * WooCommerce customer sync creates it), so an unticked box leaves an earlier
   * choice alone rather than revoking it. Persisted here because this path
   * writes the entity itself instead of going through SubscriberSaveController.
   */
  private function applyTrackingConsent(SubscriberEntity $subscriber, bool $granted): void {
    $method = SubscriberEntity::TRACKING_CONSENT_METHOD_WOOCOMMERCE_CHECKOUT;
    $before = $subscriber->getTrackingConsent();

    $this->trackingConsentCapture->applyToSubscriber(
      $subscriber,
      $granted,
      $method,
      $this->trackingConsentCapture->getCopy($method),
      false
    );

    if ($subscriber->getTrackingConsent() !== $before) {
      $this->subscribersRepository->persist($subscriber);
      $this->subscribersRepository->flush();
    }
  }

  public function hideAutomateWooOptinCheckbox(): void {
    if (!$this->wp->isPluginActive('automatewoo/automatewoo.php')) {
      return;
    }
    // Hide AutomateWoo checkout opt-in so we won't end up with two opt-ins
    $this->wp->removeAction(
      'woocommerce_checkout_after_terms_and_conditions',
      ['AutomateWoo\Frontend', 'output_checkout_optin_checkbox']
    );
  }

  private function triggerAutomateWooOptin(): void {
    if (
      !$this->wp->isPluginActive('automatewoo/automatewoo.php')
      || empty($_POST[self::CHECKOUT_OPTIN_INPUT_NAME])
    ) {
      return;
    }
    // Emulate checkout opt-in triggering for AutomateWoo
    $_POST['automatewoo_optin'] = 'On';
  }

  private function subscribe(SubscriberEntity $subscriber) {
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    if (empty($subscriber->getConfirmedIp()) && empty($subscriber->getConfirmedAt())) {
      $subscriber->setConfirmedIp(Helpers::getIP());
      $subscriber->setConfirmedAt(new Carbon());
    }

    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
  }

  private function requireSubscriptionConfirmation(SubscriberEntity $subscriber) {
    $subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();

    try {
      // Per-list confirmation settings are not resolved here because this path
      // subscribes to the WooCommerce Customers segment (TYPE_WC_USERS),
      // which does not support custom confirmation overrides.
      $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriber, null, null, true);
    } catch (\Exception $e) {
      // ignore errors
    }
  }
}
