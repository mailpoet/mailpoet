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
    verify($this->testee->getAutomations())->arrayCount(2);
    $this->testee->deleteAutomation($automationToDelete);
    verify($this->testee->getAutomations())->arrayCount(1);
    verify($this->testee->getAutomation($automationToDelete->getId()))->null();
    $automationToKeepFromDatabase = $this->testee->getAutomation($automationToKeep->getId());
    $this->assertInstanceOf(Automation::class, $automationToKeepFromDatabase);
    verify($automationToKeepFromDatabase->getVersionId())->notNull();
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
      verify($automationRunStorage->getAutomationRun($runId))->null();
    }
    foreach ($runs['toKeep'] as $runId) {
      verify($automationRunStorage->getAutomationRun($runId))->notNull();
    }
    foreach ($runLogs['toDelete'] as $runLogId) {
      verify($automationRunLogStorage->getAutomationRunLog($runLogId))->null();
    }
    foreach ($runLogs['toKeep'] as $runLogId) {
      verify($automationRunLogStorage->getAutomationRunLog($runLogId))->notNull();
    }
  }

  public function testItCanGetCountOfActiveAutomationsByTriggersAndActionKeys(): void {
    $automation = $this->createEmptyAutomation();
    verify($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
    $triggerStep = new Step('id', Step::TYPE_TRIGGER, SomeoneSubscribesTrigger::KEY, [], []);
    $emailActionStep = new Step('id-2', Step::TYPE_ACTION, SendEmailAction::KEY, [], []);
    $automation->setSteps(['id' => $triggerStep, 'id-2' => $emailActionStep]);
    $automation->setStatus(Automation::STATUS_ACTIVE);
    $this->testee->updateAutomation($automation);
    // Correct trigger and action
    verify($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(1);
    // Incorrect trigger
    verify($this->testee->getCountOfActiveByTriggerKeysAndAction([UserRegistrationTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
    // Incorrect action
    verify($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], 'mailpoet:send-emai'))
      ->equals(0);
    // New version without any send email step
    $automation->setSteps(['id' => $triggerStep]);
    $this->testee->updateAutomation($automation);
    verify($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
    // Draft automation
    $automation->setSteps(['id' => $triggerStep, 'id-2' => $emailActionStep]);
    $automation->setStatus(Automation::STATUS_DRAFT);
    $this->testee->updateAutomation($automation);
    verify($this->testee->getCountOfActiveByTriggerKeysAndAction([SomeoneSubscribesTrigger::KEY], SendEmailAction::KEY))
      ->equals(0);
  }

  public function testItCanFilterAutomationsByStatus(): void {
    $activeAutomation = $this->createEmptyAutomation('active-automation');
    $activeAutomation->setStatus(Automation::STATUS_ACTIVE);
    $this->testee->updateAutomation($activeAutomation);

    $draftAutomation = $this->createEmptyAutomation('draft-automation');
    $draftAutomation->setStatus(Automation::STATUS_DRAFT);
    $this->testee->updateAutomation($draftAutomation);

    // Test without filter
    $allAutomations = $this->testee->getAutomations();
    verify($allAutomations)->arrayCount(2);

    // Test with active filter
    $activeAutomations = $this->testee->getAutomations([Automation::STATUS_ACTIVE]);
    verify($activeAutomations)->arrayCount(1);
    verify($activeAutomations[0]->getStatus())->equals(Automation::STATUS_ACTIVE);

    // Test with draft filter
    $draftAutomations = $this->testee->getAutomations([Automation::STATUS_DRAFT]);
    verify($draftAutomations)->arrayCount(1);
    verify($draftAutomations[0]->getStatus())->equals(Automation::STATUS_DRAFT);

    // Test with multiple status filter
    $filteredAutomations = $this->testee->getAutomations([Automation::STATUS_ACTIVE, Automation::STATUS_DRAFT]);
    verify($filteredAutomations)->arrayCount(2);
  }

  public function testItCanSearchAutomationsByName(): void {
    $automation1 = $this->createEmptyAutomation('Welcome Email');
    $automation2 = $this->createEmptyAutomation('Purchase Follow-up');
    $automation3 = $this->createEmptyAutomation('Welcome SMS');

    // Test search for "Welcome"
    $welcomeAutomations = $this->testee->getAutomations(null, null, null, null, null, 'Welcome');
    verify($welcomeAutomations)->arrayCount(2);

    // Test search for "Email"
    $emailAutomations = $this->testee->getAutomations(null, null, null, null, null, 'Email');
    verify($emailAutomations)->arrayCount(1);
    verify($emailAutomations[0]->getName())->equals('Welcome Email');

    // Test search for non-existent term
    $noResults = $this->testee->getAutomations(null, null, null, null, null, 'NonExistent');
    verify($noResults)->arrayCount(0);

    // Test empty search
    $emptySearch = $this->testee->getAutomations(null, null, null, null, null, '');
    verify($emptySearch)->arrayCount(3);
  }

  public function testItCanOrderAutomationsByDifferentColumns(): void {
    $automation1 = $this->createEmptyAutomation('Alpha');
    $automation2 = $this->createEmptyAutomation('Beta');
    $automation3 = $this->createEmptyAutomation('Gamma');

    // Test order by name ASC
    $ascAutomations = $this->testee->getAutomations(null, 'name', 'ASC');
    verify($ascAutomations[0]->getName())->equals('Alpha');
    verify($ascAutomations[1]->getName())->equals('Beta');
    verify($ascAutomations[2]->getName())->equals('Gamma');

    // Test order by name DESC
    $descAutomations = $this->testee->getAutomations(null, 'name', 'DESC');
    verify($descAutomations[0]->getName())->equals('Gamma');
    verify($descAutomations[1]->getName())->equals('Beta');
    verify($descAutomations[2]->getName())->equals('Alpha');

    // Test order by id (default DESC)
    $idAutomations = $this->testee->getAutomations(null, 'id', 'DESC');
    verify($idAutomations[0]->getId())->equals($automation3->getId());
    verify($idAutomations[2]->getId())->equals($automation1->getId());
  }

  public function testItCanPaginateAutomations(): void {
    // Create 5 automations
    for ($i = 1; $i <= 5; $i++) {
      $this->createEmptyAutomation("Automation $i");
    }

    // Test first page with 2 per page
    $page1 = $this->testee->getAutomations(null, null, null, 1, 2);
    verify($page1)->arrayCount(2);

    // Test second page with 2 per page
    $page2 = $this->testee->getAutomations(null, null, null, 2, 2);
    verify($page2)->arrayCount(2);

    // Test third page with 2 per page
    $page3 = $this->testee->getAutomations(null, null, null, 3, 2);
    verify($page3)->arrayCount(1);

    // Verify different results
    verify($page1[0]->getId())->notEquals($page2[0]->getId());
  }

  public function testItCanCountAutomationsWithFilters(): void {
    $activeAutomation = $this->createEmptyAutomation('Active Test');
    $activeAutomation->setStatus(Automation::STATUS_ACTIVE);
    $this->testee->updateAutomation($activeAutomation);

    $draftAutomation = $this->createEmptyAutomation('Draft Test');
    $draftAutomation->setStatus(Automation::STATUS_DRAFT);
    $this->testee->updateAutomation($draftAutomation);

    // Test total count
    $totalCount = $this->testee->getAutomationCount();
    verify($totalCount)->equals(2);

    // Test count with status filter
    $activeCount = $this->testee->getAutomationCount([Automation::STATUS_ACTIVE]);
    verify($activeCount)->equals(1);

    $draftCount = $this->testee->getAutomationCount([Automation::STATUS_DRAFT]);
    verify($draftCount)->equals(1);

    // Test count with search filter
    $searchCount = $this->testee->getAutomationCount(null, 'Test');
    verify($searchCount)->equals(2);

    $specificSearchCount = $this->testee->getAutomationCount(null, 'Active');
    verify($specificSearchCount)->equals(1);

    // Test count with combined filters
    $combinedCount = $this->testee->getAutomationCount([Automation::STATUS_ACTIVE], 'Active');
    verify($combinedCount)->equals(1);

    $noResultsCount = $this->testee->getAutomationCount([Automation::STATUS_DRAFT], 'Active');
    verify($noResultsCount)->equals(0);
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
