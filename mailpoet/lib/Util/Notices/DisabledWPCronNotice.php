<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util\Notices;

use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class DisabledWPCronNotice {

  const DISMISS_NOTICE_TIMEOUT_SECONDS = YEAR_IN_SECONDS;
  const OPTION_NAME = 'dismissed-wp-cron-disabled-notice';

  /** @var WPFunctions */
  private $wp;

  /** @var CronHelper */
  private $cronHelper;

  /** @var SettingsController */
  private $settings;

  public function __construct(
    WPFunctions $wp,
    CronHelper $cronHelper,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->cronHelper = $cronHelper;
    $this->settings = $settings;
  }

  public function init($shouldDisplay) {
    if (!$shouldDisplay) {
      return null;
    }
    $isDismissed = $this->wp->getTransient(self::OPTION_NAME);
    $currentMethod = $this->settings->get(CronTrigger::SETTING_CURRENT_METHOD);
    $isWPCronMethodActive = $currentMethod === CronTrigger::METHOD_ACTION_SCHEDULER;
    $isCronFunctional = $this->isCronFunctional();
    if (!$isDismissed && $isWPCronMethodActive && $this->isWPCronDisabled() && !$isCronFunctional) {
      return $this->display();
    }
  }

  public function isWPCronDisabled() {
    return defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
  }

  public function isCronFunctional(): bool {
    // If a cron run was started/completed less than an hour ago, we consider it functional.
    $lastRunThreshold = time() - HOUR_IN_SECONDS;
    return ($this->cronHelper->getDaemon()['run_started_at'] ?? 0) > $lastRunThreshold
      || ($this->cronHelper->getDaemon()['run_completed_at'] ?? 0) > $lastRunThreshold;
  }

  public function display() {
    $errorString = __('WordPress built-in cron is disabled with the DISABLE_WP_CRON constant on your website, this prevents MailPoet sending from working. Please enable WordPress built-in cron or choose a different cron method in MailPoet Settings.', 'mailpoet');

    $buttonString = __('[link]Go to Settings[/link]', 'mailpoet');
    $error = $errorString . '<br><br>' . Helpers::replaceLinkTags($buttonString, 'admin.php?page=mailpoet-settings#advanced', [
      'class' => 'button-primary',
    ]);

    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    return Notice::displayError($error, $extraClasses, self::OPTION_NAME, true, false);
  }

  public function disable() {
    $this->wp->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
