<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use ActionScheduler;
use ActionScheduler_NullSchedule;
use ActionScheduler_Store;
use MailPoet\Automation\Engine\Control\ActionScheduler as AutomationActionScheduler;
use MailPoet\Automation\Engine\Control\AutomationController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Test\DataFactories;
use MailPoetTest;

class AutomationControllerTest extends MailPoetTest {
  public function testItEnqueuesProgress(): void {
    $this->createAutomationWithStepRunAndLog(AutomationRun::STATUS_RUNNING, AutomationRunLog::STATUS_RUNNING);

    $controller = $this->diContainer->get(AutomationController::class);
    $controller->enqueueProgress(1, 'abc');

    $actions = $this->getActions(['status' => [ActionScheduler_Store::STATUS_PENDING]]);
    $this->assertCount(1, $actions);
    $this->assertSame('mailpoet-automation', $actions[0]->get_group());
    $this->assertSame('mailpoet/automation/step', $actions[0]->get_hook());
    $this->assertSame([['automation_run_id' => 1, 'step_id' => 'abc', 'run_number' => 2]], $actions[0]->get_args());
    $this->assertInstanceOf(ActionScheduler_NullSchedule::class, $actions[0]->get_schedule());
  }

  public function testItFailsWhenStepWasNotStarted(): void {
    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage("Automation step with ID 'abc' was not started in automation run with ID '1'.");

    $controller = $this->diContainer->get(AutomationController::class);
    $controller->enqueueProgress(1, 'abc');
  }

  public function testItFailsWhenStepIsComplete(): void {
    $this->createAutomationWithStepRunAndLog(AutomationRun::STATUS_RUNNING, AutomationRunLog::STATUS_COMPLETE);

    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage("Automation step with ID 'abc' is not running in automation run with ID '1'. Status: 'complete'");

    $controller = $this->diContainer->get(AutomationController::class);
    $controller->enqueueProgress(1, 'abc');
  }

  public function testItFailsWhenStepFailed(): void {
    $this->createAutomationWithStepRunAndLog(AutomationRun::STATUS_RUNNING, AutomationRunLog::STATUS_FAILED);

    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage("Automation step with ID 'abc' is not running in automation run with ID '1'. Status: 'failed'");

    $controller = $this->diContainer->get(AutomationController::class);
    $controller->enqueueProgress(1, 'abc');
  }

  public function testItUnschedulesPendingAction(): void {
    $this->createAutomationWithStepRunAndLog(AutomationRun::STATUS_RUNNING, AutomationRunLog::STATUS_RUNNING);

    $data = ['automation_run_id' => 1, 'step_id' => 'abc', 'run_number' => 2];
    $this->scheduleAction(time() + MONTH_IN_SECONDS, $data);
    $this->assertCount(1, $this->getActions());
    $this->assertCount(1, $this->getActions(['status' => [ActionScheduler_Store::STATUS_PENDING]]));

    $controller = $this->getServiceWithOverrides(AutomationController::class, [
      // skip creating new action so we can check if the existing one is unscheduled
      'actionScheduler' => $this->make(AutomationActionScheduler::class, ['enqueue' => 123]),
    ]);
    $controller->enqueueProgress(1, 'abc');

    $this->assertCount(1, $this->getActions());
    $this->assertCount(1, $this->getActions(['status' => [ActionScheduler_Store::STATUS_CANCELED]]));
  }

  public function testItFailsWithExistingAction(): void {
    $this->createAutomationWithStepRunAndLog(AutomationRun::STATUS_RUNNING, AutomationRunLog::STATUS_RUNNING);

    $data = ['automation_run_id' => 1, 'step_id' => 'abc', 'run_number' => 2];
    $actionId = $this->scheduleAction(time() + MONTH_IN_SECONDS, $data);
    ActionScheduler::store()->mark_complete($actionId);
    $this->assertCount(1, $this->getActions());
    $this->assertCount(1, $this->getActions(['status' => [ActionScheduler_Store::STATUS_COMPLETE]]));

    $this->expectException(InvalidStateException::class);
    $this->expectExceptionMessage("Automation run with ID '1' already has a processed action for step with ID 'abc' and run number '2'.");

    $controller = $this->diContainer->get(AutomationController::class);
    $controller->enqueueProgress(1, 'abc');
  }

  private function createAutomationWithStepRunAndLog(string $runStatus, string $logStatus): void {
    $step = new Step('abc', Step::TYPE_ACTION, 'key', [], []);
    $automation = (new DataFactories\Automation())
      ->withStatus(Automation::STATUS_ACTIVE)
      ->withStep($step)
      ->create();

    $run = (new DataFactories\AutomationRun())
      ->withAutomation($automation)
      ->withStatus($runStatus)
      ->create();

    // automation run log (running)
    (new DataFactories\AutomationRunLog($run->getId(), $step))
      ->setStatus($logStatus)
      ->create();
  }

  private function scheduleAction(int $timestamp, array $args): int {
    return as_schedule_single_action($timestamp, Hooks::AUTOMATION_STEP, [$args], 'mailpoet-automation');
  }

  private function getActions(array $args = []): array {
    return array_values(
      as_get_scheduled_actions(
        array_merge(['hook' => Hooks::AUTOMATION_STEP, 'group' => 'mailpoet-automation'], $args)
      )
    );
  }

  public function _before(): void {
    $this->cleanup();
  }

  public function _after(): void {
    $this->cleanup();
  }

  private function cleanup() {
    $this->diContainer->get(AutomationStorage::class)->truncate();
    $this->diContainer->get(AutomationRunStorage::class)->truncate();
    $this->diContainer->get(AutomationRunLogStorage::class)->truncate();

    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $claimsTable = $wpdb->prefix . 'actionscheduler_claims';
    $wpdb->query('TRUNCATE ' . $claimsTable);
  }
}
