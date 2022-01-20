<?php

namespace MailPoet\Util\Notices;

use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class InactiveSubscribersNotice {
  const OPTION_NAME = 'inactive-subscribers-notice';
  const MIN_INACTIVE_SUBSCRIBERS_COUNT = 50;

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
    if (!$shouldDisplay || !$this->settings->get(self::OPTION_NAME, true)) {
      return;
    }

    // don't display notice if user has changed the default inactive time range
    $inactiveDays = (int)$this->settings->get('deactivate_subscriber_after_inactive_days');
    if ($inactiveDays !== SettingsController::DEFAULT_DEACTIVATE_SUBSCRIBER_AFTER_INACTIVE_DAYS) {
      return;
    }

    $inactiveSubscribersCount = Subscriber::getInactiveSubscribersCount();
    if ($inactiveSubscribersCount < self::MIN_INACTIVE_SUBSCRIBERS_COUNT) {
      return;
    }
    return $this->display($inactiveSubscribersCount);
  }

  public function disable() {
    $this->settings->set(self::OPTION_NAME, false);
  }

  private function display($inactiveSubscribersCount) {
    $goToSettingsString = __('Go to the Advanced Settings', 'mailpoet');

    $notice = sprintf(
      __('Good news! MailPoet won’t send emails to your %s inactive subscribers. This is a standard practice to maintain good deliverability and open rates. But if you want to disable it, you can do so in settings. [link]Read more.[/link]', 'mailpoet'),
      $this->wp->numberFormatI18n($inactiveSubscribersCount)
    );
    $notice = Helpers::replaceLinkTags($notice, 'https://kb.mailpoet.com/article/264-inactive-subscribers', [
      'target' => '_blank',
      'data-beacon-article' => '5cbf19622c7d3a026fd3efe1',
    ]);
    $notice = "<p>$notice</p>";
    $notice .= '<p><a href="admin.php?page=mailpoet-settings#advanced" class="button button-primary">' . $goToSettingsString . '</a></p>';

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    Notice::displaySuccess($notice, $extraClasses, self::OPTION_NAME, false);
    return $notice;
  }
}
