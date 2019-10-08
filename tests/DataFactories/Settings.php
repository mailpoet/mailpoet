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

  function withDefaultSettings() {
    $this->withCronTriggerMethod('Linux Cron');
    $this->withSendingMethodSmtpMailhog();
    $this->withSender('admin', 'wp@example.com');
    $this->withSkippedTutorials();
    $this->withCookieRevenueTracking();
    $this->withEmailNotificationsDisabled();
  }

  function withCronTriggerMethod($method) {
    $this->settings->set('cron_trigger.method', $method);
    return $this;
  }

  function withSender($name, $address) {
    $this->settings->set('sender.name', $name);
    $this->settings->set('sender.address', $address);
  }

  function withEmailNotificationsDisabled() {
    $this->settings->set('stats_notifications.enabled', 0);
    $this->settings->set('subscriber_email_notification.enabled', 0);
    return $this;
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
      $body = "Hello,\n\nWelcome to our newsletter!\n\nPlease confirm your subscription to our list by clicking the link below: \n\n[activation_link]I confirm my subscription![/activation_link]\n\nThank you,\n\nThe Team";
    }
    $this->settings->set('signup_confirmation.body', $body);
    return $this;
  }

  function withConfirmationEmailEnabled() {
    $this->settings->set('signup_confirmation.enabled', '1');
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

  function withTrackingEnabled() {
    $this->settings->set('tracking.enabled', true);
    return $this;
  }

  function withTodayInstallationDate() {
    $this->settings->set('installed_at', date("Y-m-d H:i:s"));
  }

  function withSkippedTutorials() {
    $this->settings->set('show_intro', 0);
    $this->settings->set('display_nps_poll', 0);
    $this->settings->set('show_congratulate_after_first_newsletter', 0);
    return $this;
  }

  function withSendingMethod($sending_method) {
    $this->settings->set('mta.method', $sending_method);
    $this->settings->set('mta_group', $sending_method === Mailer::METHOD_SMTP ? 'smtp' : 'website');
    return $this;
  }

  function withSendingMethodMailPoet() {
    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    $this->settings->set('mta_group', 'mailpoet');
    $this->settings->set('mta.method', 'MailPoet');
    $this->settings->set('mta.mailpoet_api_key', $mailPoetSendingKey);
    $this->settings->set('mta.mailpoet_api_key_state.state', 'valid');
    $this->settings->set('mta.mailpoet_api_key_state.code', 200);
    return $this;
  }

  function withSendingMethodSmtpMailhog() {
    $this->settings->set('mta_group', 'smtp');
    $this->settings->set('mta.method', Mailer::METHOD_SMTP);
    $this->settings->set('mta.port', 1025);
    $this->settings->set('mta.host', 'mailhog');
    $this->settings->set('mta.authentication', 0);
    $this->settings->set('mta.login', '');
    $this->settings->set('mta.password', '');
    $this->settings->set('mta.encryption', '');
    $this->settings->set('mailpoet_sending_frequency', 'auto');
    $this->settings->set('mailpoet_smtp_provider', 'manual');
    $this->settings->set('smtp_provider', 'manual');
  }

  function withSendingError($error_message, $operation = 'send') {
    $this->settings->set('mta_log.status', 'paused');
    $this->settings->set('mta_log.error.operation', $operation);
    $this->settings->set('mta_log.error.error_message', $error_message);
    return $this;
  }

  function withCookieRevenueTracking() {
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "1");
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.set', "1");
    return $this;
  }

  function withCookieRevenueTrackingDisabled() {
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.enabled', "0");
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.set', "1");
    return $this;
  }

  function withWooCommerceListImportPageDisplayed($was_shown) {
    $this->settings->set('woocommerce_import_screen_displayed', $was_shown ? 1 : 0);
    return $this;
  }

  function withWooCommerceCheckoutOptinEnabled() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $this->settings->set('woocommerce.optin_on_checkout.message', 'Yes, I would like to be added to your mailing list');
    return $this;
  }

  function withWooCommerceCheckoutOptinDisabled() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', false);
    $this->settings->set('woocommerce.optin_on_checkout.message', '');
    return $this;
  }

  function withWooCommerceEmailCustomizerEnabled() {
    $this->settings->set('woocommerce.use_mailpoet_editor', true);
    return $this;
  }

  function withWooCommerceEmailCustomizerDisabled() {
    $this->settings->set('woocommerce.use_mailpoet_editor', false);
    return $this;
  }

  function withDeactivateSubscriberAfter3Months() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 90);
    return $this;
  }

  function withDeactivateSubscriberAfter6Months() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', 180);
    return $this;
  }

  function withCaptchaType($type = null) {
    $this->settings->set('captcha.type', $type);
    return $this;
  }

  function withInstalledAt(\DateTime $date) {
    $this->settings->set('installed_at', $date);
    return $this;
  }
}
