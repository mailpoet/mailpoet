<?php declare(strict_types = 1);

namespace MailPoet\Mailer;

use MailPoet\Cron\Workers\BulkConfirmationEmailResend;
use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Cron\Workers\StatsNotifications\AutomatedEmails as StatsNotificationsAutomatedEmails;
use MailPoet\Cron\Workers\StatsNotifications\Worker as StatsNotificationsWorker;
use MailPoet\Cron\Workers\SubscriberLimitNotificationWorker;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Decides whether the queue and cron may send email on this site. Staging, development and local
 * sites wait unless sending is allowed. Direct sends (sign-up confirmations, test emails) are not
 * affected.
 */
class SendingEnvironment {
  public const SETTING_ALLOW_SENDING = 'allow_sending_in_non_production';
  public const SETTING_ALLOW_SENDING_SITE_URL = 'allow_sending_in_non_production_site_url';
  public const FILTER_ALLOW_SENDING = 'mailpoet_allow_sending_in_non_production';

  /**
   * Task types of the workers that send email from the queue or cron. The scheduler turns scheduled
   * newsletters into sending tasks and has no task type of its own, so it is listed by its worker name.
   */
  public const GATED_TASK_TYPES = [
    'schedule',
    SendingQueue::TASK_TYPE,
    StatsNotificationsWorker::TASK_TYPE,
    StatsNotificationsAutomatedEmails::TASK_TYPE,
    BulkConfirmationEmailResend::TASK_TYPE,
    SubscriberLimitNotificationWorker::TASK_TYPE,
  ];

  private SettingsController $settings;

  private WPFunctions $wp;

  public function __construct(
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->settings = $settings;
    $this->wp = $wp;
  }

  public function getEnvironmentType(): string {
    return $this->wp->wpGetEnvironmentType();
  }

  public function isProduction(): bool {
    return $this->getEnvironmentType() === 'production';
  }

  public function isSendingAllowed(): bool {
    if ($this->isProduction()) {
      return true;
    }
    $allowedBySetting = $this->isOverrideStoredForThisSite();

    /**
     * Lets a developer allow or block queued sending on a non-production site.
     * Return true (for example with __return_true) to allow sending.
     *
     * @param bool $allowed Whether the "Allow sending" setting is on.
     */
    return (bool)$this->wp->applyFilters(self::FILTER_ALLOW_SENDING, $allowedBySetting);
  }

  public function isTaskTypeGated(string $type): bool {
    return in_array($type, self::GATED_TASK_TYPES, true) && !$this->isSendingAllowed();
  }

  /**
   * The override only matters on non-production sites. Drop it so a site moved to production and
   * later back to staging does not keep sending. Call it from admin web requests only, because the
   * environment type can differ between web and CLI requests.
   */
  public function clearOverrideOnProduction(): void {
    if ($this->isProduction() && $this->settings->get(self::SETTING_ALLOW_SENDING)) {
      $this->settings->delete(self::SETTING_ALLOW_SENDING);
      $this->settings->delete(self::SETTING_ALLOW_SENDING_SITE_URL);
    }
  }

  public function allowSending(): void {
    $this->settings->set(self::SETTING_ALLOW_SENDING, '1');
    $this->settings->set(self::SETTING_ALLOW_SENDING_SITE_URL, $this->getNormalizedSiteUrl());
  }

  /**
   * The setting lives in the database, so a copy of the site carries it along. It only counts on the
   * site address it was turned on for.
   */
  public function isOverrideStoredForThisSite(): bool {
    return $this->settings->get(self::SETTING_ALLOW_SENDING) === '1'
      && $this->settings->get(self::SETTING_ALLOW_SENDING_SITE_URL) === $this->getNormalizedSiteUrl();
  }

  /**
   * Call after the setting was saved: remember the address when it is on, forget it when it is off.
   */
  public function syncSiteUrlWithSetting(): void {
    if ($this->settings->get(self::SETTING_ALLOW_SENDING) === '1') {
      $this->settings->set(self::SETTING_ALLOW_SENDING_SITE_URL, $this->getNormalizedSiteUrl());
    } else {
      $this->settings->delete(self::SETTING_ALLOW_SENDING_SITE_URL);
    }
  }

  private function getNormalizedSiteUrl(): string {
    $parts = explode('://', strtolower(trim((string)$this->wp->homeUrl())), 2);
    return rtrim($parts[count($parts) - 1], '/');
  }
}
