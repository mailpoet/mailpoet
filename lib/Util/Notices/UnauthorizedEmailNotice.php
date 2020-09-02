<?php

namespace MailPoet\Util\Notices;

use MailPoet\Newsletter\Renderer\EscapeHelper;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class UnauthorizedEmailNotice {

  const OPTION_NAME = 'unauthorized-email-addresses-notice';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  public function __construct(SettingsController $settings, WPFunctions $wp) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function init($shouldDisplay) {
    $validationError = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    if ($shouldDisplay && isset($validationError['invalid_sender_address'])) {
      return $this->display($validationError);
    }
  }

  public function display($validationError) {
    $message = $this->getMessageText($validationError);
    $message .= $this->getFixThisButton();
    $extraClasses = 'mailpoet-js-error-unauthorized-emails-notice';
    Notice::displayError($message, $extraClasses, self::OPTION_NAME, false, false);
  }

  private function getMessageText($validationError) {
    $text = $this->wp->_x('<b>Sending all of your emails has been paused</b> because your email address %s hasn’t been authorized yet.',
      'Email addresses have to be authorized to be used to send emails. %s will be replaced by an email address.',
      'mailpoet');
    $message = str_replace('%s', EscapeHelper::escapeHtmlText($validationError['invalid_sender_address']), $text);
    return "<p>$message</p>";
  }

  private function getFixThisButton() {
    $button = '<button class="button button-primary mailpoet-js-button-fix-this">' . $this->wp->__('Fix this!', 'mailpoet') . '</button>';
    return "<p>$button</p>";
  }
}
