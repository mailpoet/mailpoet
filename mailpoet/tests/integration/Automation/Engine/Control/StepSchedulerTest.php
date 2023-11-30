<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use ActionScheduler_Action;
use ActionScheduler_SimpleSchedule;
use ActionScheduler_Store;
use DateTimeImmutable;
use MailPoet\Automation\Engine\Control\StepScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoetTest;

class StepSchedulerTest extends MailPoetTest {
  public function _before() {
    $this->cleanup();
  }

  public function _after() {
    $this->cleanup();
  }

  public function testItSchedulesProgress(): void {
    $scheduler = $this->diContainer->get(StepScheduler::class);

    $args = $this->getStepRunArgs();
    $runId = $args->getAutomationRun()->getId();
    $now = time();
    $scheduler->scheduleProgress($args, $now + 1000);

    $actions = array_values($this->getScheduledActions());
    $this->assertCount(1, $actions);
    $this->assertInstanceOf(ActionScheduler_Action::class, $actions[0]);
    $this->assertSame('mailpoet-automation', $actions[0]->get_group());
    $this->assertSame('mailpoet/automation/step', $actions[0]->get_hook());
    $this->assertSame([['automation_run_id' => $runId, 'step_id' => 'a1', 'run_number' => 2]], $actions[0]->get_args());
    $this->assertInstanceOf(ActionScheduler_SimpleSchedule::class, $actions[0]->get_schedule());
    $this->assertEquals(new DateTimeImmutable('@' . ($now + 1000)), $actions[0]->get_schedule()->get_date());
  }

  public function testItSchedulesNextStep(): void {
    $scheduler = $this->diContainer->get(StepScheduler::class);

    $args = $this->getStepRunArgs();
    $runId = $args->getAutomationRun()->getId();
    $now = time();
    $scheduler->scheduleNextStep($args, $now + 1000);

    $actions = array_values($this->getScheduledActions());
    $this->assertCount(1, $actions);
    $this->assertInstanceOf(ActionScheduler_Action::class, $actions[0]);
    $this->assertSame('mailpoet-automation', $actions[0]->get_group());
    $this->assertSame('mailpoet/automation/step', $actions[0]->get_hook());
    $this->assertSame([['automation_run_id' => $runId, 'step_id' => 'a2', 'run_number' => 1]], $actions[0]->get_args());
    $this->assertInstanceOf(ActionScheduler_SimpleSchedule::class, $actions[0]->get_schedule());
    $this->assertEquals(new DateTimeImmutable('@' . ($now + 1000)), $actions[0]->get_schedule()->get_date());
  }

  public function testHasScheduledProgress(): void {
    $scheduler = $this->diContainer->get(StepScheduler::class);

    $args = $this->getStepRunArgs();
    $runId = $args->getAutomationRun()->getId();
    $this->assertFalse($scheduler->hasScheduledProgress($args));

    // next step
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a2', 'run_number' => 1], time() + 1000);
    $this->assertFalse($scheduler->hasScheduledProgress($args));

    // progress
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a1', 'run_number' => 2], time() + 1000);
    $this->assertTrue($scheduler->hasScheduledProgress($args));
  }

  public function testHasScheduledNextStep(): void {
    $scheduler = $this->diContainer->get(StepScheduler::class);

    $args = $this->getStepRunArgs();
    $runId = $args->getAutomationRun()->getId();
    $this->assertFalse($scheduler->hasScheduledNextStep($args));

    // progress
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a1', 'run_number' => 2], time() + 1000);
    $this->assertFalse($scheduler->hasScheduledNextStep($args));

    // next step
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a2', 'run_number' => 1], time() + 1000);
    $this->assertTrue($scheduler->hasScheduledNextStep($args));
  }

  public function testHasScheduledNextStepBackCompatibility(): void {
    $scheduler = $this->diContainer->get(StepScheduler::class);

    $args = $this->getStepRunArgs();
    $runId = $args->getAutomationRun()->getId();
    $this->assertFalse($scheduler->hasScheduledNextStep($args));

    // progress
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a1', 'run_number' => 2], time() + 1000);
    $this->assertFalse($scheduler->hasScheduledNextStep($args));

    // next step (BC for steps without "run_number")
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a2'], time() + 1000);
    $this->assertTrue($scheduler->hasScheduledNextStep($args));
  }

  public function hasScheduledStep(): void {
    $scheduler = $this->diContainer->get(StepScheduler::class);

    // progress
    $args = $this->getStepRunArgs();
    $runId = $args->getAutomationRun()->getId();
    $this->assertFalse($scheduler->hasScheduledStep($args));
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a1', 'run_number' => 2], time() + 1000);
    $this->assertTrue($scheduler->hasScheduledStep($args));

    // next step
    $this->cleanup();
    $args = $this->getStepRunArgs();
    $this->assertFalse($scheduler->hasScheduledStep($args));
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a2', 'run_number' => 1], time() + 1000);
    $scheduler->scheduleNextStep($args);
    $this->assertTrue($scheduler->hasScheduledStep($args));

    // next step (BC for steps without "run_number")
    $this->cleanup();
    $args = $this->getStepRunArgs();
    $this->assertFalse($scheduler->hasScheduledStep($args));
    $this->scheduleAction(['automation_run_id' => $runId, 'step_id' => 'a2'], time() + 1000);
    $scheduler->scheduleNextStep($args);
    $this->assertTrue($scheduler->hasScheduledStep($args));
  }

  private function getStepRunArgs(): StepRunArgs {
    $automation = $this->tester->createAutomation(
      'Test automation',
      new Step('t', Step::TYPE_TRIGGER, 'test:trigger', [], [new NextStep('a1')]),
      new Step('a1', Step::TYPE_ACTION, 'test:action', [], [new NextStep('a2')]),
      new Step('a2', Step::TYPE_ACTION, 'test:action', [], [])
    );
    $this->assertInstanceOf(Automation::class, $automation);
    $run = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $run);
    return new StepRunArgs($automation, $run, $automation->getSteps()['a1'], [], 1);
  }

  private function getScheduledActions(): array {
    $actions = as_get_scheduled_actions([
      'group' => 'mailpoet-automation',
      'status' => [ActionScheduler_Store::STATUS_PENDING, ActionScheduler_Store::STATUS_RUNNING],
    ]);
    return $actions;
  }

  private function scheduleAction(array $data, int $timestamp): void {
    as_schedule_single_action($timestamp, 'mailpoet/automation/step', [$data], 'mailpoet-automation');
  }

  private function cleanup(): void {
    $this->diContainer->get(AutomationStorage::class)->truncate();
    $this->diContainer->get(AutomationRunStorage::class)->truncate();

    global $wpdb;
    $actionsTable = $wpdb->prefix . 'actionscheduler_actions';
    $wpdb->query('TRUNCATE ' . $actionsTable);
    $claimsTable = $wpdb->prefix . 'actionscheduler_claims';
    $wpdb->query('TRUNCATE ' . $claimsTable);
  }
}
