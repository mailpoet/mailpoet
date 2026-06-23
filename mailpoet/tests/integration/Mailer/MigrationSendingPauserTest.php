<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer;

use MailPoet\Mailer\MailerLog;
use MailPoet\Mailer\MigrationSendingPauser;
use MailPoet\Settings\SettingsController;

/**
 * @phpstan-import-type MailerLogData from MailerLog
 */
class MigrationSendingPauserTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var MigrationSendingPauser */
  private $pauser;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->pauser = $this->diContainer->get(MigrationSendingPauser::class);
    $this->settings->delete(MigrationSendingPauser::BACKUP_SETTING_NAME);
    MailerLog::createMailerLog();
  }

  public function testItBlocksSendingWhilePaused(): void {
    $this->pauser->pause();

    verify(MailerLog::isSendingPaused())->true();

    try {
      MailerLog::enforceExecutionRequirements();
      self::fail('Paused sending exception was not thrown.');
    } catch (\Exception $e) {
      verify($e->getMessage())->equals('Sending has been paused.');
    }
  }

  public function testItResumesActiveSendingWithMailerLogIntact(): void {
    $mailerLog = $this->createMailerLog([
      'sent' => [date('Y-m-d H:i:s', time() - 60) => 7],
      'retry_attempt' => 2,
      'retry_at' => time() + 120,
      'error' => [
        'operation' => 'send',
        'error_code' => 'smtp_temporary_failure',
        'error_message' => 'Temporary SMTP failure',
      ],
      'transactional_email_last_error_at' => time() - 30,
      'transactional_email_error_count' => 1,
    ]);
    MailerLog::updateMailerLog($mailerLog);

    $this->pauser->pause();
    verify(MailerLog::isSendingPaused())->true();

    $this->pauser->resume();

    verify(MailerLog::isSendingPaused())->false();
    verify(MailerLog::getMailerLog())->equals($mailerLog);
    verify($this->settings->hasSavedValue(MigrationSendingPauser::BACKUP_SETTING_NAME))->false();
  }

  public function testItLeavesAlreadyPausedSendingUntouched(): void {
    $mailerLog = $this->createMailerLog([
      'status' => MailerLog::STATUS_PAUSED,
      'sent' => [date('Y-m-d H:i:s', time() - 60) => 3],
      'retry_attempt' => 2,
      'retry_at' => time() + 120,
      'error' => [
        'operation' => 'send',
        'error_message' => 'User-paused sender error',
      ],
    ]);
    MailerLog::updateMailerLog($mailerLog);

    $this->pauser->pause();
    verify($this->settings->hasSavedValue(MigrationSendingPauser::BACKUP_SETTING_NAME))->false();

    $this->pauser->resume();

    verify(MailerLog::isSendingPaused())->true();
    verify(MailerLog::getMailerLog())->equals($mailerLog);
  }

  public function testItDoesNotClobberBackupOnRerun(): void {
    $mailerLog = $this->createMailerLog([
      'sent' => [date('Y-m-d H:i:s', time() - 60) => 11],
      'retry_attempt' => 1,
      'retry_at' => time() + 120,
      'error' => [
        'operation' => 'send',
        'error_message' => 'Original retry state',
      ],
    ]);
    MailerLog::updateMailerLog($mailerLog);

    $this->pauser->pause();
    $changedPausedLog = MailerLog::getMailerLog();
    $changedPausedLog['sent'] = [date('Y-m-d H:i:s', time() - 30) => 99];
    $changedPausedLog['status'] = null;
    MailerLog::updateMailerLog($changedPausedLog);

    $this->pauser->pause();
    verify(MailerLog::isSendingPaused())->true();
    $this->pauser->resume();

    verify(MailerLog::isSendingPaused())->false();
    verify(MailerLog::getMailerLog())->equals($mailerLog);
    verify($this->settings->hasSavedValue(MigrationSendingPauser::BACKUP_SETTING_NAME))->false();
  }

  /**
   * @param array<string, mixed> $overrides
   * @return MailerLogData
   */
  private function createMailerLog(array $overrides = []): array {
    /** @var MailerLogData $mailerLog */
    $mailerLog = array_replace(MailerLog::getMailerLog(), $overrides, [
      'started' => time() - 600,
    ]);
    return $mailerLog;
  }
}
