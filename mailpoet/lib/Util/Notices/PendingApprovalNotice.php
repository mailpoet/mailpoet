<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Notice as WPNotice;

class PendingApprovalNotice {

  const OPTION_NAME = 'mailpoet-pending-approval-notice';

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  public function init($shouldDisplay): ?string {
    // We should display the notice if the user is using MSS and the subscription is not approved
    if (
      $shouldDisplay
      && $this->settings->get('mta.method') === Mailer::METHOD_MAILPOET
      && $this->settings->get('mta.mailpoet_api_key_state')
      && $this->settings->get('mta.mailpoet_api_key_state.state', null) === Bridge::KEY_VALID
      && !$this->settings->get('mta.mailpoet_api_key_state.data.is_approved', false)
    ) {
      return $this->display();
    }

    return null;
  }

  private function display(): string {
    // translators: %s is the email subject, which will always be in English
    $message = sprintf(__("MailPoet is [link1]reviewing your subscription[/link1]. You can use all MailPoet features and send [link2]email previews[/link2] to your [link3]authorized email addresses[/link3], but sending to your email list contacts is temporarily paused until we review your subscription. If you don't hear from us within 48 hours, please check the inbox and spam folders of your MailPoet account email for follow-up emails with the subject \"%s\" and reply, or [link4]contact us[/link4].", 'mailpoet'), 'Your MailPoet Subscription Review');
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/379-our-approval-process',
      [
        'target' => '_blank',
      ],
      'link1'
    );
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/290-check-your-newsletter-before-sending-it',
      [
        'target' => '_blank',
      ],
      'link2'
    );
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/266-how-to-add-an-authorized-email-address-as-the-from-address#how-to-authorize-an-email-address',
      [
        'target' => '_blank',
      ],
      'link3'
    );
    $message = Helpers::replaceLinkTags(
      $message,
      'https://www.mailpoet.com/support/',
      [
        'target' => '_blank',
      ],
      'link4'
    );

    WPNotice::displayWarning($message, '', self::OPTION_NAME);
    return $message;
  }
}
