<?php

namespace MailPoet\Subscription;

use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\SubscriberHandler;
use MailPoet\Subscribers\SubscriberActions;
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

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp,
    SubscriberActions $subscriberActions,
    SubscriberHandler $subscriberHandler
  ) {
    $this->settings = $settings;
    $this->subscriberActions = $subscriberActions;
    $this->wp = $wp;
    $this->subscriberHandler = $subscriberHandler;
  }

  public function extendForm() {
    $label = $this->settings->get(
      'subscribe.on_register.label',
      WPFunctions::get()->__('Yes, please add me to your mailing list.', 'mailpoet')
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
        />&nbsp;' . esc_attr($label) . '
      </label>
    </p>';

    $form = (string)$this->wp->applyFilters('mailpoet_register_form_extend', $form);
    $allowedHtml = [
      'p' => [
        'class' => true,
      ],
      'label' => [
        'for' => true,
      ],
      'input' => [
        'type' => true,
        'id' => true,
        'value' => true,
        'name' => true,
      ],
    ];
    print wp_kses($form, $allowedHtml);
  }

  public function onMultiSiteRegister($result) {
    if (empty($result['errors']->errors)) {
      if (
        isset($_POST['mailpoet']['subscribe_on_register'])
        && (bool)$_POST['mailpoet']['subscribe_on_register'] === true
      ) {
        $this->subscribeNewUser(
          $result['user_name'],
          $result['user_email']
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
    if (
      empty($errors->errors)
      && isset($_POST['mailpoet']['subscribe_on_register'])
      && (bool)$_POST['mailpoet']['subscribe_on_register'] === true
    ) {
      $this->subscribeNewUser(
        $userLogin,
        $userEmail
      );
    }
    return $errors;
  }

  private function subscribeNewUser($name, $email) {
    $segmentIds = $this->settings->get(
      'subscribe.on_register.segments',
      []
    );
    $this->subscriberActions->subscribe(
      [
        'email' => $email,
        'first_name' => $name,
      ],
      $segmentIds
    );

    // start subscriber tracking (by email, we don't have WP user ID yet)
    $this->subscriberHandler->identifyByEmail($email);
  }
}
