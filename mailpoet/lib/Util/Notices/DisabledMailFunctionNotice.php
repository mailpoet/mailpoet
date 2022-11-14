<?php

namespace MailPoet\Util\Notices;

use MailPoet\Mailer\Mailer;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class DisabledMailFunctionNotice {

  const OPTION_NAME = 'disabled-mail-function';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    SubscribersFeature $subscribersFeature
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
    $this->subscribersFeature = $subscribersFeature;
  }

  public function init($shouldDisplay) {
    $shouldDisplay = $shouldDisplay && $this->checkRequirements();
    if ($shouldDisplay) {
      $this->display();
    }
  }

  private function checkRequirements(): bool {
    $sendingMethod = $this->settings->get('mta.method', false);
    $isPhpMailSendingMethod = $sendingMethod === Mailer::METHOD_PHPMAIL;

    $functionName = 'mail';
    $isMailFunctionDisabled = $this->isFunctionDisabled($functionName);

    return $isPhpMailSendingMethod && $isMailFunctionDisabled;
  }

  private function isFunctionDisabled(string $function): bool {
    $result = function_exists($function) && is_callable($function, false);
    return !$result;
  }

  private function display() {
    $header = $this->getHeader();

    $body = $this->getBody();

    $button = $this->getConnectMailPoetButton();

    $message = $header . $body . $button;

     Notice::displayWarning($message, '', self::OPTION_NAME, false);
  }

  private function getHeader(): string {
    return '<h4>' . __('Get ready to send your first campaign.', 'mailpoet') . '</h4>';
  }

  private function getBody(): string {
    $bodyText = __('Connect your website with MailPoet, and start sending for free. Reach inboxes, not spam boxes. [link]Why am I seeing this?[/link]', 'mailpoet');

    $bodyWithReplacedLink = Helpers::replaceLinkTags($bodyText, 'https://kb.mailpoet.com/article/396-disabled-mail-function', [
      'target' => '_blank',
    ]);

    return '<p>' . $bodyWithReplacedLink . '</p>';
  }

  private function getConnectMailPoetButton(): string {
    $subscribersCount = $this->subscribersFeature->getSubscribersCount();
    $buttonLink = "https://account.mailpoet.com/?s={$subscribersCount}&utm_source=mailpoet&utm_medium=plugin&utm_campaign=disabled_mail_function";
    $link = $this->wp->escAttr($buttonLink);
    return '<p><a target="_blank" href="' . $link . '" class="button button-primary">' . __('Connect MailPoet', 'mailpoet') . '</a></p>';
  }
}
