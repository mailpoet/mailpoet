<?php

namespace MailPoet\Util\Notices;

use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;
use MailPoet\Newsletter\Renderer\EscapeHelper;

class UnauthorizedEmailNotice {

  const OPTION_NAME = 'unauthorized-email-addresses-notice';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  function __construct(SettingsController $settings, WPFunctions $wp) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  function init($should_display) {
    $validation_error = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);
    if (
      $should_display
      && (isset($validation_error['invalid_sender_address']) || isset($validation_error['invalid_confirmation_address']))
     ) {
      return $this->display($validation_error);
    }
  }

  function display($validation_error) {
    $message = $this->getMessageText($validation_error);
    $message .= $this->getSettingsButtons($validation_error);
    $message .= $this->getAuthorizationLink($validation_error);
    $message .= $this->getResumeSendingButton();
    $extra_classes = 'mailpoet-js-error-unauthorized-emails-notice';
    Notice::displayError($message, $extra_classes, self::OPTION_NAME, false, false);
  }

  private function getMessageText($validation_error) {
    if (
      !empty($validation_error['invalid_sender_address'])
      && !empty($validation_error['invalid_confirmation_address'])
      && $validation_error['invalid_sender_address'] !== $validation_error['invalid_confirmation_address']
    ) {
      $text = $this->wp->_x('<b>Sending all of your emails has been paused</b> because your email addresses %1$s and %2$s have not been authorized yet.',
        'Email addresses have to be authorized to be used to send emails. %1$s and %2$s will be replaced by email addresses.'
      );
      $message = str_replace('%1$s', EscapeHelper::escapeHtmlText($validation_error['invalid_sender_address']), $text);
      $message = str_replace('%2$s', EscapeHelper::escapeHtmlText($validation_error['invalid_confirmation_address']), $message);
    } else {
      $text = $this->wp->_x('<b>Sending all of your emails has been paused</b> because your email address %s hasn’t been authorized yet.</b>',
        'Email addresses have to be authorized to be used to send emails. %s will be replaced by an email address.'
      );
      $email = isset($validation_error['invalid_sender_address']) ? $validation_error['invalid_sender_address'] : $validation_error['invalid_confirmation_address'];
      $message = str_replace('%s', EscapeHelper::escapeHtmlText($email), $text);
    }
    return "<p>$message</p>";
  }

  private function getSettingsButtons($validation_error) {
    $buttons = '';
    if (!empty($validation_error['invalid_sender_address'])) {
      $button = $this->wp->_x('Update my Default sender email address', 'Please reuse the current translation of “Default sender”');
      $button = Helpers::replaceLinkTags("[link]{$button}[/link]", 'admin.php?page=mailpoet-settings', ['class' => 'button button-secondary']);
      $buttons .= "<p>$button</p>";
    }
    if (!empty($validation_error['invalid_confirmation_address'])) {
      $button = $this->wp->_x('Update my Sign-up Confirmation email address', 'Please reuse the current translation of “Sign-up Confirmation”');
      $button = Helpers::replaceLinkTags("[link]{$button}[/link]", 'admin.php?page=mailpoet-settings#signup', ['class' => 'button button-secondary']);
      $buttons .= "<p>$button</p>";
    }
    return $buttons;
  }

  private function getAuthorizationLink($validation_error) {
    if (
      !empty($validation_error['invalid_sender_address'])
      && !empty($validation_error['invalid_confirmation_address'])
      && $validation_error['invalid_sender_address'] !== $validation_error['invalid_confirmation_address']
    ) {
      $authorize_link = $this->wp->_x('Authorize %1$s and %2$s', 'Link for user to authorize their email address');
      $authorize_link = str_replace('%1$s', EscapeHelper::escapeHtmlText($validation_error['invalid_sender_address']), $authorize_link);
      $authorize_link = str_replace('%2$s', EscapeHelper::escapeHtmlText($validation_error['invalid_confirmation_address']), $authorize_link);
    } else {
      $email = isset($validation_error['invalid_sender_address']) ? $validation_error['invalid_sender_address'] : $validation_error['invalid_confirmation_address'];
      $authorize_link = $this->wp->_x('Authorize %s', 'Link for user to authorize their email address');
      $authorize_link = str_replace('%s', EscapeHelper::escapeHtmlText($email), $authorize_link);
    }
    $authorize_link = Helpers::replaceLinkTags("[link]{$authorize_link}[/link]", 'https://account.mailpoet.com/authorization', ['target' => '_blank']);
    $html = '<p><b>' . $this->wp->_x('OR', 'User has to choose between two options') . '</b></p>';
    $html .= "<p>$authorize_link</p>";
    return $html;
  }

  private function getResumeSendingButton() {
    $button = '<button class="button button-primary mailpoet-js-button-resume-sending">' . $this->wp->__('Resume sending', 'mailpoet') . '</button>';
    return "<p>$button</p>";
  }
}
