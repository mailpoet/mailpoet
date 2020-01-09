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

use function MailPoetVendor\array_column;

class UnauthorizedEmailInNewslettersNotice {

  const OPTION_NAME = 'unauthorized-email-in-newsletters-addresses-notice';

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
    if ($shouldDisplay && isset($validationError['invalid_senders_in_newsletters'])) {
      return $this->display($validationError);
    }
  }

  public function display($validationError) {
    $message = $this->getMessageText();
    $message .= $this->getNewslettersLinks($validationError);
    $message .= $this->getAuthorizationLink($validationError);
    $message .= $this->getResumeSendingButton();
    // Use Mailer log errors display system to display this notice
    $mailerLog = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_AUTHORIZATION, $message);
    MailerLog::updateMailerLog($mailerLog);
  }

  private function getMessageText() {
    $message = $this->wp->__('<b>Your automatic emails have been paused,</b> because some email addresses haven’t been authorized yet.', 'mailpoet');
    return "<p>$message</p>";
  }

  private function getNewslettersLinks($validationError) {
    $links = '';
    foreach ($validationError['invalid_senders_in_newsletters'] as $error) {
      $linkText = $this->wp->_x('Update the from address of %s', '%s will be replaced by a newsletter subject', 'mailpoet');
      $linkText = str_replace('%s', EscapeHelper::escapeHtmlText($error['subject']), $linkText);
      $linkUrl = $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG . '#/send/' . $error['newsletter_id']);
      $link = Helpers::replaceLinkTags("[link]{$link_text}[/link]", $linkUrl, ['target' => '_blank']);
      $links .= "<p>$link</p>";
    }
    return $links;
  }

  private function getAuthorizationLink($validationError) {
    $emails = array_unique(array_column($validationError['invalid_senders_in_newsletters'], 'sender_address'));
    if (count($emails) > 1) {
      $authorizeLink = $this->wp->_x('Authorize %1$s and %2$s', 'Link for user to authorize their email address', 'mailpoet');
      $authorizeLink = str_replace('%2$s', EscapeHelper::escapeHtmlText(array_pop($emails)), $authorizeLink);
      $authorizeLink = str_replace('%1$s', EscapeHelper::escapeHtmlText(implode(', ', $emails)), $authorizeLink);
    } else {
      $authorizeLink = $this->wp->_x('Authorize %s', 'Link for user to authorize their email address', 'mailpoet');
      $authorizeLink = str_replace('%s', EscapeHelper::escapeHtmlText($emails[0]), $authorizeLink);
    }

    $authorizeLink = Helpers::replaceLinkTags("[link]{$authorize_link}[/link]", 'https://account.mailpoet.com/authorization', ['target' => '_blank']);
    $html = '<p><b>' . $this->wp->_x('OR', 'User has to choose between two options', 'mailpoet') . '</b></p>';
    $html .= "<p>$authorize_link</p>";
    return $html;
  }

  private function getResumeSendingButton() {
    $button = '<button class="button button-primary mailpoet-js-button-resume-sending">' . $this->wp->__('Resume sending', 'mailpoet') . '</button>';
    return "<p>$button</p>";
  }
}
