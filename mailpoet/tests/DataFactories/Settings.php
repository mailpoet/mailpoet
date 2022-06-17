<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class Settings {
  /** @var SettingsController */
  private $settings;

  private $authorizedEmailsController;

  public function __construct() {
    $this->settings = SettingsController::getInstance();
    $this->authorizedEmailsController = ContainerWrapper::getInstance()->get(AuthorizedEmailsController::class);
    $this->settings->resetCache();
  }

  public function withDefaultSettings() {
    $this->withCronTriggerMethod('Linux Cron');
    $this->withSendingMethodSmtpMailhog();
    $this->withSender('admin', 'wp@example.com');
    $this->withSkippedTutorials();
    $this->withCookieRevenueTracking();
    $this->withEmailNotificationsDisabled();
  }

  public function withCronTriggerMethod($method) {
    $this->settings->set('cron_trigger.method', $method);
    return $this;
  }

  public function withSender($name, $address) {
    $this->settings->set('sender.name', $name);
    $this->settings->set('sender.address', $address);
  }

  public function withEmailNotificationsDisabled() {
    $this->settings->set('stats_notifications.enabled', 0);
    $this->settings->set('subscriber_email_notification.enabled', 0);
    return $this;
  }

  public function withConfirmationEmailSubject($subject = null) {
    if ($subject === null) {
      $subject = sprintf('Confirm your subscription to %1$s', get_option('blogname'));
    }
    $this->settings->set('signup_confirmation.subject', $subject);
    return $this;
  }

  public function withConfirmationEmailBody($body = null) {
    if ($body === null) {
      $body = "Hello,\n\nWelcome to our newsletter!\n\nPlease confirm your subscription to our list by clicking the link below: \n\n[activation_link]I confirm my subscription![/activation_link]\n\nThank you,\n\nThe Team";
    }
    $this->settings->set('signup_confirmation.body', $body);
    return $this;
  }

  public function withConfirmationEmailEnabled() {
    $this->settings->set('signup_confirmation.enabled', '1');
    return $this;
  }

  public function withConfirmationEmailDisabled() {
    $this->settings->set('signup_confirmation.enabled', '');
    return $this;
  }

  public function withTrackingDisabled() {
    $this->settings->set('tracking.level', 'basic');
    return $this;
  }

  public function withTrackingEnabled() {
    $this->settings->set('tracking.level', 'partial');
    return $this;
  }

  public function withTodayInstallationDate() {
    $this->settings->set('installed_at', date("Y-m-d H:i:s"));
  }

  public function withSkippedTutorials() {
    $this->settings->set('show_intro', 0);
    $this->settings->set('display_nps_poll', 0);
    $this->settings->set('show_congratulate_after_first_newsletter', 0);
    return $this;
  }

  public function withSendingMethod($sendingMethod) {
    $this->settings->set('mta.method', $sendingMethod);
    $this->settings->set('mta_group', $sendingMethod === Mailer::METHOD_SMTP ? 'smtp' : 'website');
    return $this;
  }

  public function withSendingMethodMailPoet() {
    $mailPoetSendingKey = getenv('WP_TEST_MAILER_MAILPOET_API');
    $this->settings->set('mta_group', 'mailpoet');
    $this->settings->set('mta.method', 'MailPoet');
    $this->settings->set('mta.mailpoet_api_key', $mailPoetSendingKey);
    $this->settings->set('mta.mailpoet_api_key_state.state', 'valid');
    $this->settings->set('mta.mailpoet_api_key_state.code', 200);
    $this->authorizedEmailsController->checkAuthorizedEmailAddresses();
    return $this;
  }

  public function withValidPremiumKey($key) {
    $this->settings->set(Bridge::PREMIUM_KEY_SETTING_NAME, $key);
    $this->settings->set(Bridge::PREMIUM_KEY_STATE_SETTING_NAME, ['state' => Bridge::PREMIUM_KEY_VALID]);
    return $this;
  }

  public function withValidMssKey($key) {
    $this->settings->set(Bridge::API_KEY_SETTING_NAME, $key);
    $this->settings->set(Bridge::API_KEY_STATE_SETTING_NAME, ['state' => Bridge::KEY_VALID]);
    return $this;
  }

  public function withMssKeyPendingApproval() {
    $this->settings->set(Bridge::API_KEY_STATE_SETTING_NAME . '.data.is_approved', false);
    return $this;
  }

  public function withMisconfiguredSendingMethodSmtp() {
    $this->withSendingMethodSmtpMailhog();
    $this->settings->set('mta.host', 'unknown_server');
  }

  public function withSendingMethodSmtpMailhog() {
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

  public function withSendingError($errorMessage, $operation = 'send') {
    $this->settings->set('mta_log.status', 'paused');
    $this->settings->set('mta_log.error.operation', $operation);
    $this->settings->set('mta_log.error.error_message', $errorMessage);
    return $this;
  }

  public function withCookieRevenueTracking() {
    $this->settings->set('tracking.level', 'full');
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.set', "1");
    return $this;
  }

  public function withCookieRevenueTrackingDisabled() {
    $this->settings->set('tracking.level', 'partial');
    $this->settings->set('woocommerce.accept_cookie_revenue_tracking.set', "1");
    return $this;
  }

  public function withWooCommerceListImportPageDisplayed($wasShown) {
    $this->settings->set('woocommerce_import_screen_displayed', $wasShown ? 1 : 0);
    return $this;
  }

  public function withWooCommerceCheckoutOptinEnabled() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', true);
    $this->settings->set('woocommerce.optin_on_checkout.message', 'Yes, I would like to be added to your mailing list');
    return $this;
  }

  public function withWooCommerceCheckoutOptinDisabled() {
    $this->settings->set('woocommerce.optin_on_checkout.enabled', false);
    $this->settings->set('woocommerce.optin_on_checkout.message', '');
    return $this;
  }

  public function withWooCommerceEmailCustomizerEnabled() {
    $this->settings->set('woocommerce.use_mailpoet_editor', true);
    return $this;
  }

  public function withWooCommerceEmailCustomizerDisabled() {
    $this->settings->set('woocommerce.use_mailpoet_editor', false);
    return $this;
  }

  public function withDeactivateSubscriberAfter3Months() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', '90');
    return $this;
  }

  public function withDeactivateSubscriberAfter6Months() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', '180');
    return $this;
  }

  public function withDeactivateSubscriberAfter12Months() {
    $this->settings->set('deactivate_subscriber_after_inactive_days', '365');
    return $this;
  }

  public function withSubscribeOnRegisterEnabled() {
    $this->settings->set('subscribe.on_register.enabled', true);
    return $this;
  }

  public function withSubscribeOnRegisterDisabled() {
    $this->settings->set('subscribe.on_register.enabled', false);
    return $this;
  }

  public function withCaptchaType($type = null) {
    $this->settings->set('captcha.type', $type);
    return $this;
  }

  public function withInstalledAt(\DateTime $date) {
    $this->settings->set('installed_at', $date);
    return $this;
  }

  public function withTransactionEmailsViaMailPoet() {
    $this->settings->set('send_transactional_emails', true);
  }
}
