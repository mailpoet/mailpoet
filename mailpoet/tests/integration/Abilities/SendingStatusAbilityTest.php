<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;
use MailPoet\Cron\CronHelper;
use MailPoet\Cron\CronTrigger;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\MailerLog;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class SendingStatusAbilityTest extends \MailPoetTest {
  private const ABILITY = 'mailpoet/get-sending-status';

  /** @var SettingsController */
  private $settings;

  public function _before(): void {
    $this->settings = $this->diContainer->get(SettingsController::class);
    AbilitiesTestHelper::register($this->diContainer->get(AbilitiesRegistrar::class));
    wp_set_current_user((int)$this->tester->createWordPressUser('sending-status-admin@example.com', 'administrator'));
  }

  public function _after(): void {
    AbilitiesTestHelper::unregister();
    MailerLog::resetMailerLog();
    parent::_after();
  }

  public function testItReportsPausedSendingWithLastErrorWithoutEmailAddresses(): void {
    $this->settings->set('mta.method', Mailer::METHOD_SMTP);
    $mailerLog = MailerLog::setError(
      MailerLog::getMailerLog(),
      MailerError::OPERATION_SEND,
      'SMTP Error: The following recipients failed: qa-8630@example.com'
    );
    MailerLog::pauseSending($mailerLog);

    $result = $this->executeAbility();

    verify($result['sending_method'])->equals(Mailer::METHOD_SMTP);
    verify($result['mailpoet_sending_service']['enabled'])->false();
    verify($result['sending']['status'])->equals('paused');
    verify($result['sending']['last_error']['operation'])->equals(MailerError::OPERATION_SEND);
    verify($result['sending']['last_error']['message'])->stringContainsString('SMTP Error: The following recipients failed:');
    verify(wp_json_encode($result))->stringNotContainsString('qa-8630@example.com');
  }

  public function testItMasksSubscriberNamesAndAddressesInTheLastError(): void {
    $mailerLog = MailerLog::setError(
      MailerLog::getMailerLog(),
      MailerError::OPERATION_SEND,
      'Unprocessed subscribers: (John Doe <john@example.com>: Mailbox full), (Jane <jane@intranet>: Rejected), (root@localhost: Unknown)'
    );
    MailerLog::pauseSending($mailerLog);

    $message = $this->executeAbility()['sending']['last_error']['message'];

    verify($message)->equals('Unprocessed subscribers: ([subscriber]: Mailbox full), ([subscriber]: Rejected), ([email]: Unknown)');
  }

  public function testItMasksSubscriberNamesWithPunctuationInTheLastError(): void {
    $mailerLog = MailerLog::setError(
      MailerLog::getMailerLog(),
      MailerError::OPERATION_SEND,
      'Error (550): Mailbox rejected. Unprocessed subscribers: (Jean-Luc Picard (Captain) <jl@example.com>: Rejected), (Anna Novak, PhD <anna@example.com>: Bounced), (Petr Svoboda: VIP <petr@example.com>: Full), (=?utf-8?B?SmFuIEx5c8O9?= <jan@example.com>: Full)'
    );
    MailerLog::pauseSending($mailerLog);

    $message = $this->executeAbility()['sending']['last_error']['message'];

    verify($message)->equals('Error (550): Mailbox rejected. Unprocessed subscribers: ([subscriber]: Rejected), ([subscriber]: Bounced), ([subscriber]: Full), ([subscriber]: Full)');
  }

  public function testItMasksSubscriberNamesWithUnpairedBracketsInTheLastError(): void {
    $mailerLog = MailerLog::setError(
      MailerLog::getMailerLog(),
      MailerError::OPERATION_SEND,
      'Error (550): Mailbox rejected. Unprocessed subscribers: (Jan (Honza <jan@example.com>: Full), (Petr) Svoboda <petr@example.com>: Bounced), (Eva (Evi (E <eva@example.com>: Full)'
    );
    MailerLog::pauseSending($mailerLog);

    $message = $this->executeAbility()['sending']['last_error']['message'];

    verify($message)->equals('Error (550): Mailbox rejected. Unprocessed subscribers: ([subscriber]: Full), ([subscriber]: Bounced), ([subscriber]: Full)');
  }

  public function testItReportsAWaitingRetryAfterATemporaryError(): void {
    $mailerLog = MailerLog::setError(MailerLog::getMailerLog(), MailerError::OPERATION_CONNECT, 'Could not connect to SMTP host');
    $mailerLog['retry_attempt'] = 2;
    $mailerLog['retry_at'] = time() + 120;
    MailerLog::updateMailerLog($mailerLog);

    $sending = $this->executeAbility()['sending'];

    verify($sending['status'])->equals('waiting_for_retry');
    verify($sending['retry_attempt'])->equals(2);
    verify($sending['last_error']['operation'])->equals(MailerError::OPERATION_CONNECT);
  }

  public function testItKeepsTheSpaceBeforeAMaskedSubscriber(): void {
    $mailerLog = MailerLog::setError(
      MailerLog::getMailerLog(),
      MailerError::OPERATION_SEND,
      'SMTP Error: The following recipients failed: Anna Novak <anna@example.com>'
    );
    MailerLog::pauseSending($mailerLog);

    $message = $this->executeAbility()['sending']['last_error']['message'];

    verify($message)->equals('SMTP Error: The following recipients failed: [subscriber]');
  }

  public function testItReportsMailPoetSendingServiceRestrictionsAndLimits(): void {
    $this->settings->set('mta.method', Mailer::METHOD_MAILPOET);
    $this->settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_VALID,
      'access_restriction' => Bridge::KEY_ACCESS_EMAIL_VOLUME_LIMIT,
      'data' => ['email_volume_limit' => 5000, 'emails_sent' => 5001],
    ]);

    $result = $this->executeAbility();

    verify($result['mailpoet_sending_service'])->equals([
      'enabled' => true,
      'key_state' => Bridge::KEY_VALID,
      'access_restriction' => Bridge::KEY_ACCESS_EMAIL_VOLUME_LIMIT,
    ]);
    verify($result['email_volume'])->equals(['sent' => 5001, 'limit' => 5000, 'limit_reached' => true]);
    verify($result['sending']['status'])->equals('active');
    verify($result['sending']['last_error'])->null();
  }

  public function testItReportsCronDaemonStateWithoutTheToken(): void {
    $this->settings->set(CronTrigger::SETTING_CURRENT_METHOD, CronTrigger::METHOD_ACTION_SCHEDULER);
    $this->settings->set(CronHelper::DAEMON_SETTING, [
      'token' => 'secret-daemon-token',
      'status' => CronHelper::DAEMON_STATUS_ACTIVE,
      'run_started_at' => 1760000000,
      'run_completed_at' => 1760000060,
      'last_error' => [['worker' => 'SendingQueue', 'message' => 'Failed for owner@example.com']],
      'last_error_date' => 1760000030,
    ]);

    $result = $this->executeAbility();

    verify($result['cron'])->equals([
      'trigger_method' => CronTrigger::METHOD_ACTION_SCHEDULER,
      'daemon_status' => CronHelper::DAEMON_STATUS_ACTIVE,
      'last_run_started_at' => '2025-10-09T08:53:20+00:00',
      'last_run_completed_at' => '2025-10-09T08:54:20+00:00',
      'last_errors' => [['worker' => 'SendingQueue', 'message' => 'Failed for [email]']],
      'last_error_at' => '2025-10-09T08:53:50+00:00',
    ]);
    verify(wp_json_encode($result))->stringNotContainsString('secret-daemon-token');
  }

  public function testItAcceptsAnEmptyInputObject(): void {
    $result = $this->getAbility()->execute([]);

    $this->assertIsArray($result, $result instanceof \WP_Error ? $result->get_error_message() : '');
    verify($result)->arrayHasKey('sending');
  }

  public function testItDeniesUsersWhoCannotManageSettings(): void {
    wp_set_current_user((int)$this->tester->createWordPressUser('sending-status-editor@example.com', 'editor'));

    $result = $this->getAbility()->execute();

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_permissions');
  }

  private function executeAbility(): array {
    $result = $this->getAbility()->execute();
    $this->assertIsArray($result, $result instanceof \WP_Error ? $result->get_error_message() : '');
    return $result;
  }

  private function getAbility(): \WP_Ability {
    $ability = wp_get_ability(self::ABILITY);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    return $ability;
  }
}
