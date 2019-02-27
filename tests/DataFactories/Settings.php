<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;

class Settings {
  /** @var SettingsController */
  private $settings;

  public function __construct() {
    SettingsController::resetCache();
    $this->settings = new SettingsController();
  }

  function withConfirmationEmailSubject($subject = null) {
    if ($subject === null) {
      $subject = sprintf('Confirm your subscription to %1$s', get_option('blogname'));
    }
    $this->settings->set('signup_confirmation.subject', $subject);
    return $this;
  }

  function withConfirmationEmailBody($body = null) {
    if ($body === null) {
      $body = "Hello,\n\nWelcome to our newsletter!\n\nPlease confirm your subscription to the list(s): [lists_to_confirm] by clicking the link below: \n\n[activation_link]Click here to confirm your subscription.[/activation_link]\n\nThank you,\n\nThe Team";
    }
    $this->settings->set('signup_confirmation.body', $body);
    return $this;
  }

  function withConfirmationEmailEnabled() {
    $this->settings->set('signup_confirmation.enabled', '1');
    return $this;
  }

  function withConfirmationEmailFrom($name, $address) {
    $this->settings->set('signup_confirmation.from', ['name' => $name, 'address' => $address]);
    return $this;
  }

  function withConfirmationEmailDisabled() {
    $this->settings->set('signup_confirmation.enabled', '');
    return $this;
  }

  function withTrackingDisabled() {
    $this->settings->set('tracking.enabled', false);
    return $this;
  }

  function withSkippedTutorials() {
    $this->settings->set('show_intro', 0);
    $this->settings->set('display_nps_poll', 0);
    $this->settings->set('user_seen_editor_tutorial1', 1);
    $this->settings->set('show_congratulate_after_first_newsletter', 0);
    return $this;
  }

  function withSendingMethod($sending_method) {
    $this->settings->set('mta.method', $sending_method);
    $this->settings->set('mta_group', $sending_method === Mailer::METHOD_SMTP ? 'smtp' : 'website');
  }

  function withSendingError($error_message, $operation = 'send') {
    $this->settings->set('mta_log.status', 'paused');
    $this->settings->set('mta_log.error.operation', $operation);
    $this->settings->set('mta_log.error.error_message', $error_message);
    return $this;
  }
}
