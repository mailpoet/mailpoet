<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use Exception;
use MailPoet\Automation\Engine\Control\StepRunLogger;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Utils\Json;
use MailPoet\Automation\Engine\WordPress;
use MailPoetTest;
use MailPoetVendor\Monolog\DateTimeImmutable;

class StepRunLoggerTest extends MailPoetTest {
  /** @var AutomationRunLogStorage */
  private $storage;

  /** @var Hooks */
  private $hooks;

  public function _before() {
    parent::_before();
    $this->storage = $this->diContainer->get(AutomationRunLogStorage::class);
    $this->hooks = $this->diContainer->get(Hooks::class);
  }

  public function testItLogsStart(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $logger->logStart();
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0]);
  }

  public function testItLogsStepData(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $logger->logStart();
    $logger->logStepData(new Step('step-id', 'action', 'step-key', [], []));
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0], ['step_key' => 'step-key']);
  }

  public function testItLogsSuccess(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $logger->logStart();
    $logger->logSuccess();
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0], ['status' => 'complete']);
  }

  public function testItLogsProgress(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $logger->logStart();
    $logger->logProgress();
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0], ['status' => 'running']);
  }

  public function testItLogsFailure(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $error = new Exception('test error');
    $logger->logStart();
    $logger->logFailure($error);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0], ['status' => 'failed', 'error' => $error]);
  }

  public function testItLogsRunNumber(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $logger->logStart();
    $logger->logProgress();
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0], ['run_number' => 1]);

    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 2);
    $logger->logStart();
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0], ['status' => 'running', 'run_number' => 2]);
  }

  public function testItTriggersAfterRunHook(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $runs = 0;
    $lastLog = null;

    $wp = $this->diContainer->get(WordPress::class);
    $wp->addAction(Hooks::AUTOMATION_STEP_LOG_AFTER_RUN, function (AutomationRunLog $log) use (&$runs, &$lastLog) {
      $log->setData('test', 'value');
      $runs += 1;
      $lastLog = $log;
    });

    $logger->logStart();
    $logger->logStepData(new Step('step-id', 'action', 'step-key', [], []));
    $logger->logProgress();
    $this->assertSame(0, $runs);

    $logger->logSuccess();

    $this->assertSame(1, $runs); // @phpstan-ignore-line - PHPStan thinks $runs === 0 from the previous assert
    $this->assertNotNull($lastLog);
    $this->assertLogData($lastLog, ['step_key' => 'step-key', 'status' => 'complete', 'data' => '{"test":"value"}']);

    $error = new Exception('test error');
    $logger->logFailure($error);

    $this->assertSame(2, $runs);
    $this->assertNotNull($lastLog);
    $this->assertLogData($lastLog, ['step_key' => 'step-key', 'status' => 'failed', 'data' => '{"test":"value"}', 'error' => $error]);
  }

  public function testItCatchesAfterRunHookErrors(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1, false);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $runs = 0;
    $wp = $this->diContainer->get(WordPress::class);
    $wp->addAction(Hooks::AUTOMATION_STEP_LOG_AFTER_RUN, function (AutomationRunLog $log) use (&$runs) {
      $runs += 1;
      throw new Exception('test error');
    });

    $logger->logStart();
    $logger->logStepData(new Step('step-id', 'action', 'step-key', [], []));
    $logger->logProgress();
    $this->assertSame(0, $runs);

    $logger->logSuccess();

    $this->assertSame(1, $runs); // @phpstan-ignore-line - PHPStan thinks $runs === 0 from the previous assert

    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(1, $logs);
    $this->assertLogData($logs[0], ['step_key' => 'step-key', 'status' => 'complete']);
  }

  public function testItRethrowsAfterRunHookErrorsInDebugMode(): void {
    $logger = new StepRunLogger($this->storage, $this->hooks, 1, 'step-id', AutomationRunLog::TYPE_ACTION, 1, true);
    $logs = $this->storage->getLogsForAutomationRun(1);
    $this->assertCount(0, $logs);

    $runs = 0;
    $wp = $this->diContainer->get(WordPress::class);
    $wp->addAction(Hooks::AUTOMATION_STEP_LOG_AFTER_RUN, function (AutomationRunLog $log) use (&$runs) {
      $runs += 1;
      throw new Exception('test error');
    });

    $logger->logStart();
    $logger->logStepData(new Step('step-id', 'action', 'step-key', [], []));
    $logger->logProgress();
    $this->assertSame(0, $runs);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('test error');
    $logger->logSuccess();
  }

  private function assertLogData(AutomationRunLog $log, array $data = []): void {
    $error = isset($data['error']) ? [
      'message' => $data['error']->getMessage(),
      'errorClass' => get_class($data['error']),
      'code' => $data['error']->getCode(),
      'trace' => Json::decode(Json::encode($data['error']->getTrace())), // normalize objects to arrays
    ] : null;

    $expected = [
      'id' => $data['id'] ?? $log->getId(),
      'automation_run_id' => $data['automation_run_id'] ?? 1,
      'step_id' => $data['step_id'] ?? 'step-id',
      'step_type' => $data['step_type'] ?? 'action',
      'step_key' => $data['step_key'] ?? 'unknown',
      'status' => $data['status'] ?? 'running',
      'started_at' => $data['started_at'] ?? $log->getStartedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $data['updated_at'] ?? $log->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'run_number' => $data['run_number'] ?? 1,
      'data' => $data['data'] ?? '{}',
      'error' => $error ? Json::encode($error) : null,
    ];
    $this->assertSame($expected, $log->toArray());
  }
}
