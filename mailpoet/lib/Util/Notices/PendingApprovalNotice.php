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
    $message = __('<b>Your subscription is currently [link1]pending approval[/link1]</b>, which means you can only send [link2]email previews[/link2] to your [link3]authorized emails[/link3] at the moment. Please check your mailbox or [link4]contact us[/link4] if you haven’t heard from our team about your subscription status in the past 48 hours.', 'mailpoet');
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/350-pending-approval-subscription',
      [
        'target' => '_blank',
        'data-beacon-article' => '5fbd3942cff47e00160bd248',
      ],
      'link1'
    );
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/290-check-your-newsletter-before-sending-it',
      [
        'target' => '_blank',
        'data-beacon-article' => '5dadd69b2c7d3a7e9ae2cef2',
      ],
      'link2'
    );
    $message = Helpers::replaceLinkTags(
      $message,
      'https://kb.mailpoet.com/article/266-how-to-add-an-authorized-email-address-as-the-from-address#authorize',
      [
        'target' => '_blank',
        'data-beacon-article' => '5cd2ca2f04286306738ed760',
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
