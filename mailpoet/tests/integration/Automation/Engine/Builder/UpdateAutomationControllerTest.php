<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use ActionScheduler_Store;
use MailPoet\Automation\Engine\Builder\UpdateAutomationController;
use MailPoet\Automation\Engine\Builder\UpdateStepsController;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Engine\Validation\AutomationValidator;
use MailPoetTest;

class UpdateAutomationControllerTest extends MailPoetTest {
  public function testItUpdatesActiveAutomationWithRunningRuns(): void {
    $automation = $this->tester->createAutomation(
      'Active automation',
      new Step('t', Step::TYPE_TRIGGER, 'test:trigger', [], [new NextStep('a1')]),
      new Step('a1', Step::TYPE_ACTION, 'test:action', ['note' => 'before'], [])
    );
    $this->assertSame(Automation::STATUS_ACTIVE, $automation->getStatus());

    $run = $this->tester->createAutomationRun($automation);
    $this->diContainer->get(AutomationRunStorage::class)
      ->updateStatus($run->getId(), AutomationRun::STATUS_RUNNING);

    $controller = $this->diContainer->get(UpdateAutomationController::class);
    $controller->updateAutomation(
      $automation->getId(),
      [
        'steps' => [
          'root' => ['id' => 'root', 'type' => Step::TYPE_ROOT, 'key' => 'root', 'args' => [], 'next_steps' => [['id' => 't']]],
          't' => ['id' => 't', 'type' => Step::TYPE_TRIGGER, 'key' => 'test:trigger', 'args' => [], 'next_steps' => [['id' => 'a1']]],
          'a1' => ['id' => 'a1', 'type' => Step::TYPE_ACTION, 'key' => 'test:action', 'args' => ['note' => 'after'], 'next_steps' => []],
        ],
      ]
    );

    $updated = $this->diContainer->get(AutomationStorage::class)->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updated);
    $this->assertSame(Automation::STATUS_ACTIVE, $updated->getStatus());
    $updatedStep = $updated->getStep('a1');
    $this->assertInstanceOf(Step::class, $updatedStep);
    $this->assertSame('after', $updatedStep->getArgs()['note']);

    // existing run is preserved on its original version
    $this->assertSame(AutomationRun::STATUS_RUNNING, $this->getAutomationRun($run->getId())->getStatus());
    $this->assertNotSame($updated->getVersionId(), $run->getVersionId());
  }

  public function testItCancelsRunningRunsWhenCancelFlagIsSet(): void {
    $automation = $this->tester->createAutomation(
      'Active automation',
      new Step('t', Step::TYPE_TRIGGER, 'test:trigger', [], [new NextStep('a1')]),
      new Step('a1', Step::TYPE_ACTION, 'test:action', ['note' => 'before'], [])
    );

    $run = $this->tester->createAutomationRun($automation);
    $runStorage = $this->diContainer->get(AutomationRunStorage::class);
    $runStorage->updateStatus($run->getId(), AutomationRun::STATUS_RUNNING);

    $this->scheduleAction(time() + 100, ['automation_run_id' => $run->getId(), 'step_id' => 'a1', 'run_number' => 1]);

    $controller = $this->diContainer->get(UpdateAutomationController::class);
    $controller->updateAutomation(
      $automation->getId(),
      [
        'cancel_running_runs' => true,
        'steps' => [
          'root' => ['id' => 'root', 'type' => Step::TYPE_ROOT, 'key' => 'root', 'args' => [], 'next_steps' => [['id' => 't']]],
          't' => ['id' => 't', 'type' => Step::TYPE_TRIGGER, 'key' => 'test:trigger', 'args' => [], 'next_steps' => [['id' => 'a1']]],
          'a1' => ['id' => 'a1', 'type' => Step::TYPE_ACTION, 'key' => 'test:action', 'args' => ['note' => 'after'], 'next_steps' => []],
        ],
      ]
    );

    $updated = $this->diContainer->get(AutomationStorage::class)->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updated);
    $this->assertSame(Automation::STATUS_ACTIVE, $updated->getStatus());
    $this->assertSame(AutomationRun::STATUS_CANCELLED, $this->getAutomationRun($run->getId())->getStatus());
    $this->assertCount(0, $this->getActions(['status' => ActionScheduler_Store::STATUS_PENDING]));
  }

  public function testItDoesNotCancelRunningRunsWhenSaveFails(): void {
    $automation = $this->tester->createAutomation(
      'Active automation',
      new Step('t', Step::TYPE_TRIGGER, 'test:trigger', [], [new NextStep('a1')]),
      new Step('a1', Step::TYPE_ACTION, 'test:action', ['note' => 'before'], [])
    );

    $run = $this->tester->createAutomationRun($automation);
    $runStorage = $this->diContainer->get(AutomationRunStorage::class);
    $runStorage->updateStatus($run->getId(), AutomationRun::STATUS_RUNNING);

    $this->scheduleAction(time() + 100, ['automation_run_id' => $run->getId(), 'step_id' => 'a1', 'run_number' => 1]);

    $throwOnSave = function(): void {
      throw new \RuntimeException('Save failed');
    };
    add_action(Hooks::AUTOMATION_BEFORE_SAVE, $throwOnSave);

    try {
      $controller = $this->diContainer->get(UpdateAutomationController::class);
      $controller->updateAutomation(
        $automation->getId(),
        [
          'cancel_running_runs' => true,
          'steps' => [
            'root' => ['id' => 'root', 'type' => Step::TYPE_ROOT, 'key' => 'root', 'args' => [], 'next_steps' => [['id' => 't']]],
            't' => ['id' => 't', 'type' => Step::TYPE_TRIGGER, 'key' => 'test:trigger', 'args' => [], 'next_steps' => [['id' => 'a1']]],
            'a1' => ['id' => 'a1', 'type' => Step::TYPE_ACTION, 'key' => 'test:action', 'args' => ['note' => 'after'], 'next_steps' => []],
          ],
        ]
      );
      $this->fail('Expected save to fail.');
    } catch (\RuntimeException $e) {
      $this->assertSame('Save failed', $e->getMessage());
    } finally {
      remove_action(Hooks::AUTOMATION_BEFORE_SAVE, $throwOnSave);
    }

    $updated = $this->diContainer->get(AutomationStorage::class)->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updated);
    $updatedStep = $updated->getStep('a1');
    $this->assertInstanceOf(Step::class, $updatedStep);
    $this->assertSame('before', $updatedStep->getArgs()['note']);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $this->getAutomationRun($run->getId())->getStatus());
    $this->assertCount(1, $this->getActions(['status' => ActionScheduler_Store::STATUS_PENDING]));
  }

  /**
   * @group automation-trigger-invariants
   */
  public function testItAllowsTriggerChangeWhenNoRunIsRecorded(): void {
    $automation = $this->createRootOnlyAutomation();
    $controller = $this->getEditableStructureUpdateAutomationController();

    $updated = $controller->updateAutomation(
      $automation->getId(),
      [
        'status' => Automation::STATUS_ACTIVE,
        'steps' => [
          'root' => ['id' => 'root', 'type' => Step::TYPE_ROOT, 'key' => 'core:root', 'args' => [], 'next_steps' => [['id' => 't']]],
          't' => ['id' => 't', 'type' => Step::TYPE_TRIGGER, 'key' => 'mailpoet:someone-subscribes', 'args' => [], 'next_steps' => [['id' => 'a1']]],
          'a1' => ['id' => 'a1', 'type' => Step::TYPE_ACTION, 'key' => 'core:delay', 'args' => ['delay' => 1, 'delay_type' => 'MINUTES'], 'next_steps' => []],
        ],
      ]
    );

    $this->assertSame(Automation::STATUS_ACTIVE, $updated->getStatus());
    $this->assertInstanceOf(Step::class, $updated->getTrigger('mailpoet:someone-subscribes'));
  }

  /**
   * @group automation-trigger-invariants
   */
  public function testItRejectsTriggerKeyChangeWhenRunIsRecorded(): void {
    $automation = $this->tester->createAutomation(
      'Trigger lock automation',
      new Step('t', Step::TYPE_TRIGGER, 'test:trigger', [], [new NextStep('a1')]),
      new Step('a1', Step::TYPE_ACTION, 'test:action', [], [])
    );
    $this->tester->createAutomationRun($automation);

    $controller = $this->diContainer->get(UpdateAutomationController::class);

    try {
      $controller->updateAutomation(
        $automation->getId(),
        [
          'steps' => [
            'root' => ['id' => 'root', 'type' => Step::TYPE_ROOT, 'key' => 'root', 'args' => [], 'next_steps' => [['id' => 't']]],
            't' => ['id' => 't', 'type' => Step::TYPE_TRIGGER, 'key' => 'test:other-trigger', 'args' => [], 'next_steps' => [['id' => 'a1']]],
            'a1' => ['id' => 'a1', 'type' => Step::TYPE_ACTION, 'key' => 'test:action', 'args' => [], 'next_steps' => []],
          ],
        ]
      );
      $this->fail('Expected trigger modification to fail.');
    } catch (UnexpectedValueException $e) {
      $this->assertSame('mailpoet_automation_trigger_modification_not_supported', $e->getErrorCode());
    }
  }

  public function testItUnschedulesTasksWhenSwitchedToDraft(): void {
    $automation = $this->tester->createAutomation(
      'Test automation',
      new Step('t', Step::TYPE_TRIGGER, 'test:trigger', [], [new NextStep('a1')]),
      new Step('a1', Step::TYPE_ACTION, 'test:action', [], [new NextStep('a2')]),
      new Step('a2', Step::TYPE_ACTION, 'test:action', [], [])
    );

    $run1 = $this->tester->createAutomationRun($automation);
    $run2 = $this->tester->createAutomationRun($automation);
    $run3 = $this->tester->createAutomationRun($automation);
    $run4 = $this->tester->createAutomationRun($automation);

    $runStorage = $this->diContainer->get(AutomationRunStorage::class);
    $runStorage->updateStatus($run1->getId(), AutomationRun::STATUS_RUNNING);
    $runStorage->updateStatus($run2->getId(), AutomationRun::STATUS_COMPLETE);
    $runStorage->updateStatus($run3->getId(), AutomationRun::STATUS_FAILED);
    $runStorage->updateStatus($run4->getId(), AutomationRun::STATUS_CANCELLED);

    $this->scheduleAction(time() + 100, ['automation_run_id' => $run1->getId(), 'step_id' => 'aaa', 'run_number' => 1]);
    $this->scheduleAction(time() + 200, ['automation_run_id' => $run1->getId(), 'step_id' => 'bbb', 'run_number' => 1]);
    $this->scheduleAction(time() + 300, ['automation_run_id' => $run1->getId(), 'step_id' => 'bbb', 'run_number' => 2]);

    $this->assertCount(3, $this->getActions());
    $this->assertCount(3, $this->getActions(['status' => ActionScheduler_Store::STATUS_PENDING]));

    // switch to draft
    $controller = $this->diContainer->get(UpdateAutomationController::class);
    $controller->updateAutomation($automation->getId(), [
      'status' => Automation::STATUS_DRAFT,
    ]);

    $automationStorage = $this->diContainer->get(AutomationStorage::class);
    $updatedAutomation = $automationStorage->getAutomation($automation->getId());

    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $this->assertSame(Automation::STATUS_DRAFT, $updatedAutomation->getStatus());
    $this->assertSame(AutomationRun::STATUS_CANCELLED, $this->getAutomationRun($run1->getId())->getStatus());
    $this->assertSame(AutomationRun::STATUS_COMPLETE, $this->getAutomationRun($run2->getId())->getStatus());
    $this->assertSame(AutomationRun::STATUS_FAILED, $this->getAutomationRun($run3->getId())->getStatus());
    $this->assertSame(AutomationRun::STATUS_CANCELLED, $this->getAutomationRun($run4->getId())->getStatus());

    $this->assertCount(3, $this->getActions());
    $this->assertCount(3, $this->getActions(['status' => ActionScheduler_Store::STATUS_CANCELED]));
    $this->assertCount(0, $this->getActions(['status' => ActionScheduler_Store::STATUS_PENDING]));

    // resume automation and check that no run status was changed
    $controller->updateAutomation($automation->getId(), [
      'status' => Automation::STATUS_ACTIVE,
    ]);

    $automationStorage = $this->diContainer->get(AutomationStorage::class);
    $updatedAutomation = $automationStorage->getAutomation($automation->getId());

    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $this->assertSame(Automation::STATUS_ACTIVE, $updatedAutomation->getStatus());
    $this->assertSame(AutomationRun::STATUS_CANCELLED, $this->getAutomationRun($run1->getId())->getStatus());
    $this->assertSame(AutomationRun::STATUS_COMPLETE, $this->getAutomationRun($run2->getId())->getStatus());
    $this->assertSame(AutomationRun::STATUS_FAILED, $this->getAutomationRun($run3->getId())->getStatus());
    $this->assertSame(AutomationRun::STATUS_CANCELLED, $this->getAutomationRun($run4->getId())->getStatus());

    $this->assertCount(3, $this->getActions());
    $this->assertCount(3, $this->getActions(['status' => ActionScheduler_Store::STATUS_CANCELED]));
    $this->assertCount(0, $this->getActions(['status' => ActionScheduler_Store::STATUS_PENDING]));
  }

  private function getAutomationRun(int $id): AutomationRun {
    $runStorage = $this->diContainer->get(AutomationRunStorage::class);
    $run = $runStorage->getAutomationRun($id);
    $this->assertInstanceOf(AutomationRun::class, $run);
    return $run;
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

  private function createRootOnlyAutomation(): Automation {
    $automationStorage = $this->diContainer->get(AutomationStorage::class);
    $automation = new Automation(
      'New automation',
      [
        'root' => new Step('root', Step::TYPE_ROOT, 'core:root', [], []),
      ],
      wp_get_current_user()
    );
    $automation = $automationStorage->getAutomation($automationStorage->createAutomation($automation));
    $this->assertInstanceOf(Automation::class, $automation);
    return $automation;
  }

  private function getEditableStructureUpdateAutomationController(): UpdateAutomationController {
    return new class(
      $this->diContainer->get(Hooks::class),
      $this->diContainer->get(AutomationStorage::class),
      $this->diContainer->get(AutomationValidator::class),
      $this->diContainer->get(AutomationRunStorage::class),
      $this->diContainer->get(ActionScheduler::class),
      $this->diContainer->get(UpdateStepsController::class)
    ) extends UpdateAutomationController {
      protected function validateAutomationSteps(Automation $automation, array $steps): void {
      }
    };
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

    global $wpdb;
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $wpdb->prefix . 'actionscheduler_actions'));
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $wpdb->prefix . 'actionscheduler_claims'));
  }
}
