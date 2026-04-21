<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class SendingQueueBodyCleanupNotice {
  const OPTION_NAME = 'mailpoet_display_sending_queue_body_cleanup_notice';

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

  public function enable(): void {
    $this->settings->set(self::OPTION_NAME, true);
  }

  public function disable(): void {
    $this->settings->set(self::OPTION_NAME, false);
  }

  public function init(bool $shouldDisplay): ?string {
    if ($shouldDisplay && $this->settings->get(self::OPTION_NAME, false)) {
      return $this->display();
    }
    return null;
  }

  private function display(): string {
    $settingsUrl = $this->wp->adminUrl('admin.php?page=mailpoet-settings#/advanced');
    $message = Helpers::replaceLinkTags(
      __('MailPoet now automatically purges rendered email bodies from completed sends older than 30 days to reduce database size. The "View in browser" link for older emails will re-render from the original template. You can change this in [link]Settings → Advanced[/link].', 'mailpoet'),
      $settingsUrl
    );

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';
    \MailPoet\WP\Notice::displayInfo($message, $extraClasses, self::OPTION_NAME);
    return $message;
  }
}
