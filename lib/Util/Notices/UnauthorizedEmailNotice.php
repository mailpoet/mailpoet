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

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp
  ) {
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
    $message .= sprintf(
      '<p>%s &nbsp; %s &nbsp; %s</p>',
      $this->getAuthorizeEmailButton($validationError),
      $this->getDifferentEmailButton(),
      $this->getResumeSendingButton($validationError)
    );
    $extraClasses = 'mailpoet-js-error-unauthorized-emails-notice';
    Notice::displayError($message, $extraClasses, self::OPTION_NAME, false, false);
  }

  private function getMessageText($validationError) {
    $text = $this->wp->_x('<b>Sending all of your emails has been paused</b> because your email address <b>%s</b> hasn’t been authorized yet.',
      'Email addresses have to be authorized to be used to send emails. %s will be replaced by an email address.',
      'mailpoet');
    $message = str_replace('%s', EscapeHelper::escapeHtmlText($validationError['invalid_sender_address']), $text);
    return "<p>$message</p>";
  }

  private function getAuthorizeEmailButton($validationError) {
    $email = $this->wp->escAttr($validationError['invalid_sender_address']);
    $button = '<a target="_blank" href="https://account.mailpoet.com/authorization?email=' . $email . '" class="button button-primary">' . $this->wp->__('Authorize this email address', 'mailpoet') . '</a>';
    return $button;
  }

  private function getDifferentEmailButton() {
    $button = '<button class="button button-secondary mailpoet-js-button-fix-this">' . $this->wp->__('Use a different email address', 'mailpoet') . '</button>';
    return $button;
  }

  private function getResumeSendingButton($validationError) {
    $email = $this->wp->escAttr($validationError['invalid_sender_address']);
    $button = '<button class="button button-secondary mailpoet-js-button-resume-sending" value="' . $email . '">' . $this->wp->__('Resume sending', 'mailpoet') . '</button>';
    return $button;
  }
}
