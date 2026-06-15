<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use Codeception\Stub;
use MailPoet\Cron\CronHelper;
use MailPoet\Router\Endpoints\CronDaemon;
use MailPoet\Settings\SettingsController;

class WooSystemInfoTest extends \MailPoetUnitTest {
  public function testItDoesNotThrowWhenCronUrlCannotBeGenerated(): void {
    $systemInfo = $this->createWooSystemInfoThrowing(new \Exception('Site URL is unreachable.'));

    verify($systemInfo->toArray())->equals([
      'sending_method' => 'MailPoet',
      'transactional_emails' => 'Current sending method',
      'task_scheduler_method' => 'WordPress',
      'cron_ping_url' => 'Can‘t generate cron URL. (Site URL is unreachable.)',
    ]);
  }

  public function testItDoesNotThrowWhenCronUrlGenerationCausesAnError(): void {
    $systemInfo = $this->createWooSystemInfoThrowing(new \TypeError('Cron URL type error.'));

    verify($systemInfo->toArray())->equals([
      'sending_method' => 'MailPoet',
      'transactional_emails' => 'Current sending method',
      'task_scheduler_method' => 'WordPress',
      'cron_ping_url' => 'Can‘t generate cron URL. (Cron URL type error.)',
    ]);
  }

  private function createWooSystemInfoThrowing(\Throwable $throwable): WooSystemInfo {
    return new WooSystemInfo(
      Stub::make(CronHelper::class, [
        'getCronUrl' => function($action) use ($throwable) {
          verify($action)->equals(CronDaemon::ACTION_PING);
          throw $throwable;
        },
      ]),
      Stub::make(SettingsController::class, [
        'get' => function($setting) {
          $settings = [
            'mta.method' => 'MailPoet',
            'send_transactional_emails' => true,
            'cron_trigger.method' => 'WordPress',
          ];
          return $settings[$setting];
        },
      ])
    );
  }
}
