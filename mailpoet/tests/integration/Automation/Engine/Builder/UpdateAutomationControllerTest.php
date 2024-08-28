<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use ActionScheduler_Store;
use MailPoet\Automation\Engine\Builder\UpdateAutomationController;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoetTest;

class UpdateAutomationControllerTest extends MailPoetTest {
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
