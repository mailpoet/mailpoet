<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Control;

use MailPoet\Automation\Engine\Control\StepHandler;
use MailPoet\Automation\Engine\Control\StepRunner;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;

class StepHandlerTest extends \MailPoetTest {

  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationRunLogStorage */
  private $automationRunLogStorage;

  /** @var StepHandler */
  private $testee;

  /** @var array<string, StepRunner> */
  private $originalRunners = [];

  public function _before() {
    $this->testee = $this->diContainer->get(StepHandler::class);
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);
    $this->originalRunners = $this->testee->getStepRunners();
  }

  public function testItDoesOnlyProcessActiveAndDeactivatingAutomations() {
    $automation = $this->createAutomation();
    $this->assertInstanceOf(Automation::class, $automation);
    $steps = $automation->getSteps();
    $automationRun = $this->tester->createAutomationRun($automation);
    $this->assertInstanceOf(AutomationRun::class, $automationRun);

    $currentStep = current($steps);
    $this->assertInstanceOf(Step::class, $currentStep);
    $runner = $this->createMock(StepRunner::class);

    $runner->expects(self::exactly(2))->method('run'); // The run method will be called twice: Once for the active automation and once for the deactivating automation.

    $this->testee->addStepRunner($currentStep->getType(), $runner);
    $this->assertSame(Automation::STATUS_ACTIVE, $automation->getStatus());
    $this->testee->handle(['automation_run_id' => $automationRun->getId(), 'step_id' => $currentStep->getId()]);
    // no exception thrown.
    $newAutomationRun = $this->automationRunStorage->getAutomationRun($automationRun->getId());
    $this->assertInstanceOf(AutomationRun::class, $newAutomationRun);
    $this->assertSame(AutomationRun::STATUS_RUNNING, $newAutomationRun->getStatus());

    $automation->setStatus(Automation::STATUS_DEACTIVATING);
    $this->automationStorage->updateAutomation($automation);
    $this->testee->handle(['automation_run_id' => $automationRun->getId(), 'step_id' => $currentStep->getId()]);
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
        $this->testee->handle(['automation_run_id' => $automationRun->getId(), 'step_id' => $currentStep->getId()]);
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
    $runner = $this->createMock(StepRunner::class);
    $this->testee->addStepRunner($lastStep->getType(), $runner);

    $this->testee->handle(['automation_run_id' => $automationRun1->getId(), 'step_id' => $lastStep->getId()]);
    /** @var Automation $updatedAutomation */
    $updatedAutomation = $this->automationStorage->getAutomation($automation->getId());
    /** @var AutomationRun $updatedautomationRun */
    $updatedautomationRun = $this->automationRunStorage->getAutomationRun($automationRun1->getId());
    $this->assertSame(Automation::STATUS_DEACTIVATING, $updatedAutomation->getStatus());
    $this->assertSame(AutomationRun::STATUS_COMPLETE, $updatedautomationRun->getStatus());

    $this->testee->handle(['automation_run_id' => $automationRun2->getId(), 'step_id' => $lastStep->getId()]);
    /** @var Automation $updatedAutomation */
    $updatedAutomation = $this->automationStorage->getAutomation($automation->getId());
    /** @var AutomationRun $updatedautomationRun */
    $updatedautomationRun = $this->automationRunStorage->getAutomationRun($automationRun1->getId());
    $this->assertSame(Automation::STATUS_DRAFT, $updatedAutomation->getStatus());
    $this->assertSame(AutomationRun::STATUS_COMPLETE, $updatedautomationRun->getStatus());
  }

  private function createAutomation(): ?Automation {
    $trigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $delay = $this->diContainer->get(DelayAction::class);
    return $this->tester->createAutomation(
      'test',
      new Step('someone-subscribes', Step::TYPE_TRIGGER, $trigger->getKey(), [], [new NextStep('a')]),
      new Step('delay', Step::TYPE_ACTION, $delay->getKey(), [], [])
    );
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
    $this->automationRunLogStorage->truncate();
    $this->testee->setStepRunners($this->originalRunners);
  }
}
