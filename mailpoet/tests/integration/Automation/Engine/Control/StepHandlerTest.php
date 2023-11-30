<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use DateTimeImmutable;
use Exception;
use MailPoet\Automation\Engine\Control\StepHandler;
use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;

class StepHandlerTest extends \MailPoetTest {
  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  public function _before() {
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);
  }

  public function testItLogsStartAndStepData(): void {
    $step = $this->createMock(Action::class);
    $step->expects(self::once())->method('run')->willReturnCallback(
      function (StepRunArgs $args, StepRunController $controller) {
        $runId = $args->getAutomationRun()->getId();
        $logs = $this->automationRunLogStorage->getLogsForAutomationRun($runId);
        $this->assertCount(1, $logs);
        $this->assertSame($runId, $logs[0]->getAutomationRunId());
        $this->assertSame('a1', $logs[0]->getStepId());
        $this->assertSame(AutomationRunLog::STATUS_RUNNING, $logs[0]->getStatus());
        $this->assertSame('test:action', $logs[0]->getStepKey());
      }
    );

    $registry = $this->createMock(Registry::class);
    $registry->expects(self::once())->method('getStep')->willReturn($step);

    // run step
    $stepHandler = $this->getServiceWithOverrides(StepHandler::class, ['registry' => $registry]);
    $automation = $this->createAutomation();
    $this->assertInstanceOf(Automation::class, $automation);
    $run = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $run);
    $stepHandler->handle(['automation_run_id' => $run->getId(), 'step_id' => 'a1', 'run_number' => 1]);
  }

  public function testItLogsSuccess(): void {
    $step = $this->createMock(Action::class);
    $step->expects(self::once())->method('run');

    $registry = $this->createMock(Registry::class);
    $registry->expects(self::once())->method('getStep')->willReturn($step);

    $stepHandler = $this->getServiceWithOverrides(StepHandler::class, ['registry' => $registry]);
    $automation = $this->createAutomation();
    $this->assertInstanceOf(Automation::class, $automation);
    $run = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $run);

    // create start log and modify "updated_at" to an older date
    $oldDate = new DateTimeImmutable('2000-01-01 00:00:00');
    $log = new AutomationRunLog($run->getId(), 'a1', AutomationRunLog::TYPE_ACTION);
    $log->setUpdatedAt($oldDate);
    $logId = $this->automationRunLogStorage->createAutomationRunLog($log);
    $log = $this->automationRunLogStorage->getAutomationRunLog($logId);
    $this->assertInstanceOf(AutomationRunLog::class, $log);
    $this->assertEquals($oldDate, $log->getUpdatedAt());

    // run step
    $stepHandler->handle(['automation_run_id' => $run->getId(), 'step_id' => 'a1', 'run_number' => 1]);
    $logs = $this->automationRunLogStorage->getLogsForAutomationRun($run->getId());
    $this->assertCount(1, $logs);
    $this->assertSame(AutomationRunLog::STATUS_COMPLETE, $logs[0]->getStatus());
    $this->assertGreaterThan($oldDate, $logs[0]->getUpdatedAt());
  }

  public function testItLogsFailure(): void {
    $step = $this->createMock(Action::class);
    $step->expects(self::once())->method('run')->willReturnCallback(
      function () {
        throw new Exception('test error');
      }
    );

    $registry = $this->createMock(Registry::class);
    $registry->expects(self::once())->method('getStep')->willReturn($step);

    $stepHandler = $this->getServiceWithOverrides(StepHandler::class, ['registry' => $registry]);
    $automation = $this->createAutomation();
    $this->assertInstanceOf(Automation::class, $automation);
    $run = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $run);

    // create start log and modify "updated_at" to an older date
    $oldDate = new DateTimeImmutable('2000-01-01 00:00:00');
    $log = new AutomationRunLog($run->getId(), 'a1', AutomationRunLog::TYPE_ACTION);
    $log->setUpdatedAt($oldDate);
    $logId = $this->automationRunLogStorage->createAutomationRunLog($log);
    $log = $this->automationRunLogStorage->getAutomationRunLog($logId);
    $this->assertInstanceOf(AutomationRunLog::class, $log);
    $this->assertEquals($oldDate, $log->getUpdatedAt());

    // run step
    $exception = null;
    try {
      $stepHandler->handle(['automation_run_id' => $run->getId(), 'step_id' => 'a1', 'run_number' => 1]);
    } catch (Exception $e) {
      $exception = $e;
    }
    $this->assertInstanceOf(Exception::class, $exception);
    $this->assertSame('test error', $exception->getMessage());

    $logs = $this->automationRunLogStorage->getLogsForAutomationRun($run->getId());
    $this->assertCount(1, $logs);
    $this->assertSame(AutomationRunLog::STATUS_FAILED, $logs[0]->getStatus());
    $this->assertGreaterThan($oldDate, $logs[0]->getUpdatedAt());
  }

  public function testItDoesOnlyProcessActiveAndDeactivatingAutomations() {
    // The run method will be called twice: Once for the active automation and once for the deactivating automation.
    $step = $this->createMock(Action::class);
    $step->expects(self::exactly(2))->method('run');
    $registry = $this->createMock(Registry::class);
    $registry->expects(self::exactly(2))->method('getStep')->willReturn($step);
    $stepHandler = $this->getServiceWithOverrides(StepHandler::class, [
      'registry' => $registry,
    ]);

    $automation = $this->createAutomation();
    $this->assertInstanceOf(Automation::class, $automation);
    $steps = $automation->getSteps();
    $automationRun = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $automationRun);

    $currentStep = current($steps);
    $this->assertInstanceOf(Step::class, $currentStep);

    $this->assertSame(Automation::STATUS_ACTIVE, $automation->getStatus());
    $stepHandler->handle(['automation_run_id' => $automationRun->getId(), 'step_id' => $currentStep->getId()]);
    // no exception thrown.
    $newAutomationRun = $this->automationRunStorage->getAutomationRun($automationRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $newAutomationRun);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $newAutomationRun->getStatus());

    $automation->setStatus(Automation::STATUS_DEACTIVATING);
    $this->automationStorage->updateAutomation($automation);
    $stepHandler->handle(['automation_run_id' => $automationRun->getId(), 'step_id' => $currentStep->getId()]);
    // no exception thrown.
    $newAutomationRun = $this->automationRunStorage->getAutomationRun($automationRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $newAutomationRun);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $newAutomationRun->getStatus());

    $invalidStati = array_filter(
      Automation::STATUS_ALL,
      function(string $status): bool {
        return !in_array($status, [Automation::STATUS_ACTIVE, Automation::STATUS_DEACTIVATING], true);
      }
    );

    foreach ($invalidStati as $status) {
      $automation->setStatus($status);
      $this->automationStorage->updateAutomation($automation);
      $automationRun = $this->tester->createAutomationRun($automation);
      $this->assertInstanceOf(AutomationRun::class, $automationRun);
      $error = null;
      try {
        $stepHandler->handle(['automation_run_id' => $automationRun->getId(), 'step_id' => $currentStep->getId()]);
      } catch (InvalidStateException $error) {
        $this->assertSame('mailpoet_automation_not_active', $error->getErrorCode(), "Automation with '$status' did not return expected error code.");
      }
      $this->assertInstanceOf(InvalidStateException::class, $error);

      $newAutomationRun = $this->automationRunStorage->getAutomationRun($automationRun->getId());
      $this->assertInstanceOf(AutomationRun::class, $newAutomationRun);

      $this->assertSame(AutomationRun::STATUS_CANCELLED, $newAutomationRun->getStatus());
    }
  }

  public function testAnDeactivatingAutomationBecomesDraftAfterLastRunIsExecuted() {
    $step = $this->createMock(Action::class);
    $step->expects(self::exactly(2))->method('run');
    $registry = $this->createMock(Registry::class);
    $registry->expects(self::exactly(2))->method('getStep')->willReturn($step);
    $stepHandler = $this->getServiceWithOverrides(StepHandler::class, [
      'registry' => $registry,
    ]);

    $automation = $this->createAutomation();
    $this->assertInstanceOf(Automation::class, $automation);
    $automationRun1 = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $automationRun1);
    $automationRun2 = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $automationRun2);
    $automation->setStatus(Automation::STATUS_DEACTIVATING);
    $this->automationStorage->updateAutomation($automation);

    $steps = $automation->getSteps();
    $lastStep = end($steps);
    $this->assertInstanceOf(Step::class, $lastStep);

    $stepHandler->handle(['automation_run_id' => $automationRun1->getId(), 'step_id' => $lastStep->getId()]);
    /** @var Automation $updatedAutomation */
    $updatedAutomation = $this->automationStorage->getAutomation($automation->getId());
    /** @var AutomationRun $updatedautomationRun */
    $updatedautomationRun = $this->automationRunStorage->getAutomationRun($automationRun1->getId());
    $this->assertSame(Automation::STATUS_DEACTIVATING, $updatedAutomation->getStatus());
    $this->assertSame(AutomationRun::STATUS_COMPLETE, $updatedautomationRun->getStatus());

    $stepHandler->handle(['automation_run_id' => $automationRun2->getId(), 'step_id' => $lastStep->getId()]);
    /** @var Automation $updatedAutomation */
    $updatedAutomation = $this->automationStorage->getAutomation($automation->getId());
    /** @var AutomationRun $updatedautomationRun */
    $updatedautomationRun = $this->automationRunStorage->getAutomationRun($automationRun1->getId());
    $this->assertSame(Automation::STATUS_DRAFT, $updatedAutomation->getStatus());
    $this->assertSame(AutomationRun::STATUS_COMPLETE, $updatedautomationRun->getStatus());
  }

  private function createAutomation(): ?Automation {
    return $this->tester->createAutomation(
      'Test automation',
      new Step('t', Step::TYPE_TRIGGER, 'test:trigger', [], [new NextStep('a1')]),
      new Step('a1', Step::TYPE_ACTION, 'test:action', [], [new NextStep('a2')]),
      new Step('a2', Step::TYPE_ACTION, 'test:action', [], [])
    );
  }
}
