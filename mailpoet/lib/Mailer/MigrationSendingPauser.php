<?php declare(strict_types = 1);

namespace MailPoet\Mailer;

use MailPoet\Settings\SettingsController;

/**
 * @phpstan-import-type MailerLogData from MailerLog
 */
class MigrationSendingPauser {
  const BACKUP_SETTING_NAME = 'mta_log_migration_sending_pause_backup';

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SettingsController $settings
  ) {
    $this->settings = $settings;
  }

  public function pause(): void {
    $mailerLog = MailerLog::getMailerLog();
    if (MailerLog::isSendingPaused($mailerLog)) {
      return;
    }

    if (!$this->settings->hasSavedValue(self::BACKUP_SETTING_NAME)) {
      $this->settings->set(self::BACKUP_SETTING_NAME, $mailerLog);
    }

    $mailerLog = MailerLog::setError(
      $mailerLog,
      MailerError::OPERATION_MIGRATION,
      __('MailPoet is updating its database. Email sending is temporarily paused and will resume automatically when the database update finishes.', 'mailpoet')
    );
    MailerLog::pauseSending($mailerLog);
  }

  public function resume(): void {
    if (!$this->settings->hasSavedValue(self::BACKUP_SETTING_NAME)) {
      return;
    }

    /** @var MailerLogData $mailerLog */
    $mailerLog = $this->settings->get(self::BACKUP_SETTING_NAME);
    MailerLog::updateMailerLog($mailerLog);
    $this->settings->delete(self::BACKUP_SETTING_NAME);
  }
}
