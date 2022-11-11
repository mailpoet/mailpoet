<?php

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;

class AutomationStorageTest extends \MailPoetTest
{

  /** @var AutomationStorage */
  private $testee;

  public function _before() {
    $this->testee = $this->diContainer->get(AutomationStorage::class);
  }

  public function testItLoadsLatestVersion() {
    $automation = $this->createEmptyAutomation();

    $step1 = new Step('id', Step::TYPE_ACTION, 'key', [], []);
    $automation->setSteps(['id' => $step1]);
    $this->testee->updateAutomation($automation);
    $updatedAutomation = $this->testee->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $this->assertTrue($automation->getVersionId() < $updatedAutomation->getVersionId());
    $this->assertEquals(1, count($updatedAutomation->getSteps()));

    $step2 = new Step('id-2', Step::TYPE_ACTION, 'key', [], []);
    $automation->setSteps(['id' => $step1, 'id-2' => $step2]);
    $this->testee->updateAutomation($automation);
    $latestAutomation = $this->testee->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $latestAutomation);
    $this->assertTrue($updatedAutomation->getVersionId() < $latestAutomation->getVersionId());
    $this->assertEquals(2, count($latestAutomation->getSteps()));
  }

  public function testItLoadsCorrectVersion() {
    $automation = $this->createEmptyAutomation();

    $step1 = new Step('id', Step::TYPE_ACTION, 'key', [], []);
    $automation->setSteps(['id' => $step1]);
    $this->testee->updateAutomation($automation);
    $updatedAutomation = $this->testee->getAutomation($automation->getId());
    $this->assertInstanceOf(Automation::class, $updatedAutomation);
    $this->assertTrue($automation->getVersionId() < $updatedAutomation->getVersionId());
    $this->assertEquals(1, count($updatedAutomation->getSteps()));

    $step2 = new Step('id-2', Step::TYPE_ACTION, 'key', [], []);
    $automation->setSteps(['id' => $step1, 'id-2' => $step2]);
    $this->testee->updateAutomation($automation);
    $correctAutomation = $this->testee->getAutomation($automation->getId(), $updatedAutomation->getVersionId());
    $this->assertInstanceOf(Automation::class, $correctAutomation);
    $this->assertTrue($updatedAutomation->getVersionId() === $correctAutomation->getVersionId());
    $this->assertEquals($updatedAutomation->getSteps(), $correctAutomation->getSteps());
  }

  public function testItLoadsOnlyActiveAutomationsByTrigger() {
    $automation = $this->createEmptyAutomation();
    $subscriberTrigger = $this->diContainer->get(SomeoneSubscribesTrigger::class);
    $trigger = new Step('id', Step::TYPE_TRIGGER, $subscriberTrigger->getKey(), [], []);
    $automation->setSteps(['id' => $trigger]);
    $automation->setStatus(Automation::STATUS_DRAFT);
    $this->testee->updateAutomation($automation);
    $this->assertEmpty($this->testee->getActiveAutomationsByTrigger($subscriberTrigger));
    $automation->setStatus(Automation::STATUS_ACTIVE);
    $this->testee->updateAutomation($automation);
    $this->assertCount(1, $this->testee->getActiveAutomationsByTrigger($subscriberTrigger));
    $automation->setStatus(Automation::STATUS_DRAFT);
    $this->testee->updateAutomation($automation);
    $this->assertEmpty($this->testee->getActiveAutomationsByTrigger($subscriberTrigger));
  }

  public function testItCanDeleteAAutomation() {
    $automationToDelete = $this->createEmptyAutomation();
    $automationToKeep = $this->createEmptyAutomation();
    expect($this->testee->getAutomations())->count(2);
    $this->testee->deleteAutomation($automationToDelete);
    expect($this->testee->getAutomations())->count(1);
    expect($this->testee->getAutomation($automationToDelete->getId()))->null();
    $automationToKeepFromDatabase = $this->testee->getAutomation($automationToKeep->getId());
    $this->assertInstanceOf(Automation::class, $automationToKeepFromDatabase);
    expect($automationToKeepFromDatabase->getVersionId())->notNull();
  }

  public function testItCanDeleteAutomationsRelatedData() {
    $automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $automationRunLogStorage = $this->diContainer->get(AutomationRunLogStorage::class);
    $automations = [
      'toDelete' => $this->createEmptyAutomation(),
      'toKeep' => $this->createEmptyAutomation()
    ];
    $runs = [
      'toDelete' => [],
      'toKeep' => []
    ];
    $runLogs = [
      'toDelete' => [],
      'toKeep' => []
    ];
    foreach ($automations as $type => $automation) {
      for ($i = 0; $i < 2; $i++) {
        $automationRun = new AutomationRun($automation->getId(), $automation->getVersionId(), 'trigger-key', []);
        $runId = $automationRunStorage->createAutomationRun($automationRun);
        $runs[$type][] = $runId;
        for ($i = 0; $i < 2; $i++) {
          $log = new AutomationRunLog($runId, "step-{$i}");
          $logId = $automationRunLogStorage->createAutomationRunLog($log);
          $runLogs[$type][] = $logId;
        }
      }
    }
    $this->testee->deleteAutomation($automations['toDelete']);
    foreach ($runs['toDelete'] as $runId) {
      expect($automationRunStorage->getAutomationRun($runId))->null();
    }
    foreach ($runs['toKeep'] as $runId) {
      expect($automationRunStorage->getAutomationRun($runId))->notNull();
    }
    foreach ($runLogs['toDelete'] as $runLogId) {
      expect($automationRunLogStorage->getAutomationRunLog($runLogId))->null();
    }
    foreach ($runLogs['toKeep'] as $runLogId) {
      expect($automationRunLogStorage->getAutomationRunLog($runLogId))->notNull();
    }
  }

  private function createEmptyAutomation(string $name="test"): Automation {
    $automation = new Automation($name, [], new \WP_User());
    $automationId = $this->testee->createAutomation($automation);
    $automation = $this->testee->getAutomation($automationId);
    if (! $automation) {
      throw new \RuntimeException("Automation not stored.");
    }
    return $automation;
  }

  public function _after() {
    $this->diContainer->get(AutomationStorage::class)->truncate();
    $this->diContainer->get(AutomationRunStorage::class)->truncate();
    $this->diContainer->get(AutomationRunLogStorage::class)->truncate();
  }
}
