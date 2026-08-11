<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\SubscriberHandler;
use MailPoet\Subscribers\SubscriberActions;
use MailPoet\Subscribers\TrackingConsentCapture;
use MailPoet\WP\Functions as WPFunctions;

class Registration {

  /** @var SettingsController */
  private $settings;

  /** @var SubscriberActions */
  private $subscriberActions;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscriberHandler */
  private $subscriberHandler;

  /** @var TrackingConsentCapture */
  private $trackingConsentCapture;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    SubscriberActions $subscriberActions,
    SubscriberHandler $subscriberHandler,
    TrackingConsentCapture $trackingConsentCapture
  ) {
    $this->settings = $settings;
    $this->subscriberActions = $subscriberActions;
    $this->wp = $wp;
    $this->subscriberHandler = $subscriberHandler;
    $this->trackingConsentCapture = $trackingConsentCapture;
  }

  public function extendForm() {
    $label = $this->settings->get(
      'subscribe.on_register.label',
      __('Yes, please add me to your mailing list.', 'mailpoet')
    );

    $form = '<p class="registration-form-mailpoet">
      <label for="mailpoet_subscribe_on_register">
        <input
          type="hidden"
          id="mailpoet_subscribe_on_register_active"
          value="1"
          name="mailpoet[subscribe_on_register_active]"
        />
        <input
          type="checkbox"
          id="mailpoet_subscribe_on_register"
          value="1"
          name="mailpoet[subscribe_on_register]"
        />&nbsp;' . esc_html($label) . '
      </label>
    </p>' . $this->getTrackingConsentField();

    $filtered = $this->wp->applyFilters('mailpoet_register_form_extend', $form);
    $form = is_string($filtered) ? $filtered : $form;

    // We control the template and $form can be considered safe.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $form;
  }

  public function onMultiSiteRegister($result) {
    if (empty($result['errors']->errors)) {
      // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- type narrowing only, value is read as bool
      $mailpoetPost = isset($_POST['mailpoet']) && is_array($_POST['mailpoet']) ? $_POST['mailpoet'] : [];
      if (
        isset($mailpoetPost['subscribe_on_register'])
        && (bool)$mailpoetPost['subscribe_on_register'] === true
        && !empty($result['user_email'])
      ) {
        $this->subscribeNewUser(
          $result['user_name'],
          $result['user_email'],
          !empty($mailpoetPost['tracking_consent'])
        );
      }
    }
    return $result;
  }

  public function onRegister(
    $errors,
    $userLogin,
    $userEmail = null
  ) {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- type narrowing only, value is read as bool
    $mailpoetPost = isset($_POST['mailpoet']) && is_array($_POST['mailpoet']) ? $_POST['mailpoet'] : [];
    if (
      empty($errors->errors)
      && isset($mailpoetPost['subscribe_on_register'])
      && (bool)$mailpoetPost['subscribe_on_register'] === true
      && !empty($userEmail)
    ) {
      $this->subscribeNewUser(
        $userLogin,
        $userEmail,
        !empty($mailpoetPost['tracking_consent'])
      );
    }
    return $errors;
  }

  /**
   * A second, independent checkbox. Consent to open and click tracking is never
   * inferred from the "add me to your mailing list" box above it, and it is
   * only shown on sites that chose to ask. Never pre-ticked: a pre-ticked
   * consent box is not valid consent (CJEU Planet49).
   */
  private function getTrackingConsentField(): string {
    if (!$this->trackingConsentCapture->isCaptureEnabled()) {
      return '';
    }
    $copy = $this->trackingConsentCapture->getCopy(
      SubscriberEntity::TRACKING_CONSENT_METHOD_REGISTRATION
    );

    return '<p class="registration-form-mailpoet-tracking-consent">
      <label for="mailpoet_tracking_consent">
        <input
          type="checkbox"
          id="mailpoet_tracking_consent"
          value="1"
          name="mailpoet[tracking_consent]"
        />&nbsp;' . esc_html($copy) . '
      </label>
    </p>';
  }

  private function subscribeNewUser($name, $email, bool $trackingConsent = false) {
    $segmentIds = $this->settings->get(
      'subscribe.on_register.segments',
      []
    );
    $method = SubscriberEntity::TRACKING_CONSENT_METHOD_REGISTRATION;
    $consentData = $this->trackingConsentCapture->getConsentData(
      $trackingConsent,
      $method,
      $this->trackingConsentCapture->getCopy($method),
      $this->trackingConsentCapture->isNewSubscriber($email)
    );

    $this->subscriberActions->subscribe(
      array_merge([
        'email' => $email,
        'first_name' => $name,
      ], $consentData),
      $segmentIds
    );


    /**
     * On multisite headers are already sent at this point, tracking will start
     * once the user has activated his account at a later stage.
     **/
    if (!headers_sent()) {
      // start subscriber tracking (by email, we don't have WP user ID yet)
      $this->subscriberHandler->identifyByEmail($email);
    }
  }
}
