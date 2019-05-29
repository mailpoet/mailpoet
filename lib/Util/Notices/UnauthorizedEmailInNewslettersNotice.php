<?php

namespace MailPoet\Util\Notices;

use MailPoet\Config\Menu;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Newsletter\Renderer\EscapeHelper;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use function MailPoet\Util\array_column;

class UnauthorizedEmailInNewslettersNotice {

  const OPTION_NAME = 'unauthorized-email-in-newsletters-addresses-notice';

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
    if ($should_display && isset($validation_error['invalid_senders_in_newsletters'])) {
      return $this->display($validation_error);
    }
  }

  function display($validation_error) {
    $message = $this->getMessageText();
    $message .= $this->getNewslettersLinks($validation_error);
    $message .= $this->getAuthorizationLink($validation_error);
    $message .= $this->getResumeSendingButton();
    // Use Mailer log errors display system to display this notice
    $mailer_log = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, $message);
    MailerLog::updateMailerLog($mailer_log);
  }

  private function getMessageText() {
    $message = $this->wp->__('<b>Your automatic emails have been paused,</b> because some email addresses haven’t been authorized yet.');
    return "<p>$message</p>";
  }

  private function getNewslettersLinks($validation_error) {
    $links = '';
    foreach ($validation_error['invalid_senders_in_newsletters'] as $error) {
      $link_text = $this->wp->_x('Update the from address of %subject', '%subject will be replaced by a newsletter subject');
      $link_text = str_replace('%subject', EscapeHelper::escapeHtmlText($error['subject']), $link_text);
      $link_url = $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG . '#/send/' . $error['newsletter_id']);
      $link = Helpers::replaceLinkTags("[link]{$link_text}[/link]", $link_url, ['target' => '_blank']);
      $links .= "<p>$link</p>";
    }
    return $links;
  }

  private function getAuthorizationLink($validation_error) {
    $emails = array_unique(array_column($validation_error['invalid_senders_in_newsletters'], 'sender_address'));
    if (count($emails) > 1) {
      $authorize_link = $this->wp->_x('Authorize %email1 and %email2', 'Link for user to authorize their email address');
      $authorize_link = str_replace('%email2', EscapeHelper::escapeHtmlText(array_pop($emails)), $authorize_link);
      $authorize_link = str_replace('%email1', EscapeHelper::escapeHtmlText(implode(', ', $emails)), $authorize_link);
    } else {
      $authorize_link = $this->wp->_x('Authorize %email', 'Link for user to authorize their email address');
      $authorize_link = str_replace('%email', EscapeHelper::escapeHtmlText($emails[0]), $authorize_link);
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
