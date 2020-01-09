<?php

namespace MailPoet\Util\Notices;

use MailPoet\Newsletter\Renderer\EscapeHelper;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
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
    $message .= $this->getSettingsButtons($validationError);
    $message .= $this->getAuthorizationLink($validationError);
    $message .= $this->getResumeSendingButton();
    $extraClasses = 'mailpoet-js-error-unauthorized-emails-notice';
    Notice::displayError($message, $extraClasses, self::OPTION_NAME, false, false);
  }

  private function getMessageText($validationError) {
    $text = $this->wp->_x('<b>Sending all of your emails has been paused</b> because your email address %s hasn’t been authorized yet.</b>',
      'Email addresses have to be authorized to be used to send emails. %s will be replaced by an email address.',
      'mailpoet');
    $message = str_replace('%s', EscapeHelper::escapeHtmlText($validationError['invalid_sender_address']), $text);
    return "<p>$message</p>";
  }

  private function getSettingsButtons($validationError) {
    $buttons = '';
    if (!empty($validationError['invalid_sender_address'])) {
      $button = $this->wp->_x('Update my Default sender email address', 'Please reuse the current translation of “Default sender”', 'mailpoet');
      $button = Helpers::replaceLinkTags("[link]{$button}[/link]", 'admin.php?page=mailpoet-settings', ['class' => 'button button-secondary']);
      $buttons .= "<p>$button</p>";
    }
    return $buttons;
  }

  private function getAuthorizationLink($validationError) {
    $email = $validationError['invalid_sender_address'];
    $authorizeLink = $this->wp->_x('Authorize %s', 'Link for user to authorize their email address', 'mailpoet');
    $authorizeLink = str_replace('%s', EscapeHelper::escapeHtmlText($email), $authorizeLink);
    $authorizeLink = Helpers::replaceLinkTags("[link]{$authorizeLink}[/link]", 'https://account.mailpoet.com/authorization', ['target' => '_blank']);
    $html = '<p><b>' . $this->wp->_x('OR', 'User has to choose between two options', 'mailpoet') . '</b></p>';
    $html .= "<p>$authorizeLink</p>";
    return $html;
  }

  private function getResumeSendingButton() {
    $button = '<button class="button button-primary mailpoet-js-button-resume-sending">' . $this->wp->__('Resume sending', 'mailpoet') . '</button>';
    return "<p>$button</p>";
  }
}
