<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\AutomationRunLog;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunLogStorage;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger;
use MailPoet\Automation\Integrations\MailPoet\Triggers\UserRegistrationTrigger;

class AutomationStorageTest extends \MailPoetTest {


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

  public function testItLoadsCorrectVersions() {

    $automation1 = $this->createEmptyAutomation('automation-1');
    $step1 = new Step('id', Step::TYPE_ACTION, 'key', [], []);
    $automation1->setSteps(['id' => $step1]);
    $this->testee->updateAutomation($automation1);
    $step2 = new Step('step-2', Step::TYPE_ACTION, 'key', [], []);
    $automation1->setSteps(['step-2' => $step2]);
    $this->testee->updateAutomation($automation1);

    $automation2 = $this->createEmptyAutomation('automation-2');
    $step4 = new Step('step-3', Step::TYPE_ACTION, 'key', [], []);
    $automation2->setSteps(['step-3' => $step4]);
    $this->testee->updateAutomation($automation2);

    $versions = $this->testee->getAutomationVersionDates($automation1->getId());
    $this->assertCount(3, $versions);
    $versionIds = array_map(function($version) {
      return $version['id'];
    }, $versions);
    // remove first version id
    array_shift($versionIds);
    $automations1 = $this->testee->getAutomationWithDifferentVersions($versionIds);
    $this->assertCount(count($versionIds), $automations1);
    foreach ($automations1 as $automation) {
      $this->assertInstanceOf(Automation::class, $automation);
      $this->assertEquals($automation1->getId(), $automation->getId());
    }
    $loadedVersionIds = array_map(function($automation) {
      return $automation->getVersionId();
    }, $automations1);

    $this->assertEquals($versionIds, $loadedVersionIds);
  }

  public function testItLoadsVersionDates() {
    $automation1 = $this->createEmptyAutomation('automation-1');

    $step1 = new Step('id', Step::TYPE_ACTION, 'key', [], []);
    $automation1->setSteps(['id' => $step1]);
    $this->testee->updateAutomation($automation1);

    $automation2 = $this->createEmptyAutomation('automation-2');
    $step2 = new Step('id-2', Step::TYPE_ACTION, 'key', [], []);
    $automation2->setSteps(['id' => $step2]);
    $this->testee->updateAutomation($automation2);

    $versionDates1 = $this->testee->getAutomationVersionDates($automation1->getId());
    $this->assertCount(2, $versionDates1);
    foreach ($versionDates1 as $versionDate) {
      $this->assertInstanceOf(\DateTimeImmutable::class, $versionDate['created_at']);
      $versionedAutomation = $this->testee->getAutomation($automation1->getId(), $versionDate['id']);
      $this->assertInstanceOf(Automation::class, $versionedAutomation);
      $this->assertEquals($versionDate['id'], $versionedAutomation->getVersionId());
      $this->assertEquals($automation1->getId(), $versionedAutomation->getId());
    }

    $versionDates2 = $this->testee->getAutomationVersionDates($automation2->getId());
    $this->assertCount(2, $versionDates2);
    foreach ($versionDates2 as $versionDate) {
      $this->assertInstanceOf(\DateTimeImmutable::class, $versionDate['created_at']);
      $versionedAutomation = $this->testee->getAutomation($automation2->getId(), $versionDate['id']);
      $this->assertInstanceOf(Automation::class, $versionedAutomation);
      $this->assertEquals($versionDate['id'], $versionedAutomation->getVersionId());
      $this->assertEquals($automation2->getId(), $versionedAutomation->getId());
    }
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

  public function testItCanDeleteAnAutomation() {
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
      'toKeep' => $this->createEmptyAutomation(),
    ];
    $runs = [
      'toDelete' => [],
      'toKeep' => [],
    ];
    $runLogs = [
      'toDelete' => [],
      'toKeep' => [],
    ];
    foreach ($automations as $type => $automation) {
      for ($runI = 0; $runI < 2; $runI++) {
        $automationRun = new AutomationRun($automation->getId(), $automation->getVersionId(), 'trigger-key', []);
        $runId = $automationRunStorage->createAutomationRun($automationRun);
        $runs[$type][] = $runId;
        for ($logI = 0; $logI < 2; $logI++) {
          $log = new AutomationRunLog($runId, "step-{$logI}", AutomationRunLog::TYPE_ACTION);
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

  public function testItCanGetCountOfActiveAutomationsByTriggersAndActionKeys(): void {
    $automation = $this->createEmptyAutomation();
    expect($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
    $triggerStep = new Step('id', Step::TYPE_TRIGGER, SomeoneSubscribesTrigger::KEY, [], []);
    $emailActionStep = new Step('id-2', Step::TYPE_ACTION, SendEmailAction::KEY, [], []);
    $automation->setSteps(['id' => $triggerStep, 'id-2' => $emailActionStep]);
    $automation->setStatus(Automation::STATUS_ACTIVE);
    $this->testee->updateAutomation($automation);
    // Correct trigger and action
    expect($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(1);
    // Incorrect trigger
    expect($this->testee->getCountOfActiveByTriggerKeysAndAction([UserRegistrationTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
    // Incorrect action
    expect($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], 'mailpoet:send-emai'))
      ->equals(0);
    // New version without any send email step
    $automation->setSteps(['id' => $triggerStep]);
    $this->testee->updateAutomation($automation);
    expect($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
    // Draft automation
    $automation->setSteps(['id' => $triggerStep, 'id-2' => $emailActionStep]);
    $automation->setStatus(Automation::STATUS_DRAFT);
    $this->testee->updateAutomation($automation);
    expect($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
  }

  private function createEmptyAutomation(string $name = "test"): Automation {
    $automation = new Automation($name, [], new \WP_User());
    $automationId = $this->testee->createAutomation($automation);
    $automation = $this->testee->getAutomation($automationId);
    if (!$automation) {
      throw new \RuntimeException("Automation not stored.");
    }
    return $automation;
  }
}
