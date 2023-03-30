<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Storage;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStatisticsStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;

class AutomationStatisticsStorageTest extends \MailPoetTest {


  /** @var AutomationStorage */
  private $automationStorage;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var AutomationStatisticsStorage */
  private $testee;

  /** @var int[] */
  private $automations = [];

  public function _before() {
    $this->automationStorage = $this->diContainer->get(AutomationStorage::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->testee = $this->diContainer->get(AutomationStatisticsStorage::class);

    $this->automations = [
      $this->tester->createAutomation('1')->getId(),
      $this->tester->createAutomation('2')->getId(),
      $this->tester->createAutomation('3')->getId(),
    ];
  }

  /**
   * @dataProvider dataForTestItCalculatesTotalsCorrectly
   */
  public function testItCalculatesTotalsCorrectlyForSingleAutomation(int $automationIndex, int $expectedTotal, int $expectedInProgress, int $expectedExited, int $versionId = null) {
    $automation = $this->automationStorage->getAutomation($this->automations[$automationIndex], $versionId);
    $this->assertInstanceOf(Automation::class, $automation);
    $i = 0;
    while ($i < $expectedInProgress) {
      $this->createRun($automation, AutomationRun::STATUS_RUNNING);
      $i++;
    }
    $i = 0;
    while ($i < $expectedExited) {
      $this->createRun($automation, AutomationRun::STATUS_FAILED);
      $i++;
    }

    $statistics = $this->testee->getAutomationStats($automation->getId(), $versionId);
    $this->assertEquals($expectedInProgress, $statistics->getInProgress());
    $this->assertEquals($expectedTotal, $statistics->getEntered());
    $this->assertEquals($expectedExited, $statistics->getExited());
    $this->assertEquals([
      'automation_id' => $automation->getId(),
      'totals' => [
        'entered' => $expectedTotal,
        'in_progress' => $expectedInProgress,
        'exited' => $expectedExited,
      ],
    ], $statistics->toArray());
  }

  public function dataForTestItCalculatesTotalsCorrectly() {
    return [
      'zero' => [
        1, 0,0,0, null,
      ],
      'two-one-one' => [
        0, 2,1,1, null,
      ],
      'two-two-zero' => [
        2, 2,2,0, null,
      ],
      'two-zero-two' => [
        1, 2,0,2, null,
      ],
    ];
  }

  public function testPluralReturnsSameAsSingular() {
    /** @var \MailPoet\Automation\Engine\Data\AutomationStatistics[] $singleStatistics **/
    $singleStatistics = [
      $this->automations[0] => $this->testee->getAutomationStats($this->automations[0]),
      $this->automations[1] => $this->testee->getAutomationStats($this->automations[1]),
      $this->automations[2] => $this->testee->getAutomationStats($this->automations[2]),
    ];

    $pluralStatistics = $this->testee->getAutomationStatisticsForAutomations(...$this->automationStorage->getAutomations());

    $this->assertEquals(count($singleStatistics), count($pluralStatistics));
    foreach ($singleStatistics as $automationId => $statistic) {
      $this->assertEquals($statistic->getEntered(), $pluralStatistics[$automationId]->getEntered());
      $this->assertEquals($statistic->getInProgress(), $pluralStatistics[$automationId]->getInProgress());
      $this->assertEquals($statistic->getExited(), $pluralStatistics[$automationId]->getExited());
      $this->assertEquals($statistic->getVersionId(), $pluralStatistics[$automationId]->getVersionId());
      $this->assertEquals($statistic->getAutomationId(), $pluralStatistics[$automationId]->getAutomationId());
    }


  }

  public function testItSeparatesAutomationRunsCorrectly() {
    $automation1 = $this->automationStorage->getAutomation($this->automations[0]);
    $this->assertInstanceOf(Automation::class, $automation1);
    $automation2 = $this->automationStorage->getAutomation($this->automations[1]);
    $this->assertInstanceOf(Automation::class, $automation2);
    $automation3 = $this->automationStorage->getAutomation($this->automations[2]);
    $this->assertInstanceOf(Automation::class, $automation3);

    $this->createRun($automation1, AutomationRun::STATUS_COMPLETE);

    $this->createRun($automation2, AutomationRun::STATUS_COMPLETE);
    $this->createRun($automation2, AutomationRun::STATUS_COMPLETE);

    $this->createRun($automation3, AutomationRun::STATUS_COMPLETE);
    $this->createRun($automation3, AutomationRun::STATUS_COMPLETE);
    $this->createRun($automation3, AutomationRun::STATUS_COMPLETE);

    $statistics1 = $this->testee->getAutomationStats($automation1->getId(), $automation1->getVersionId());
    $this->assertEquals(1, $statistics1->getEntered());

    $statistics2 = $this->testee->getAutomationStats($automation2->getId(), $automation2->getVersionId());
    $this->assertEquals(2, $statistics2->getEntered());

    $statistics3 = $this->testee->getAutomationStats($automation3->getId(), $automation3->getVersionId());
    $this->assertEquals(3, $statistics3->getEntered());
  }

  public function testItCanDistinguishBetweenVersions() {
    $oldestAutomation = $this->automationStorage->getAutomation($this->automations[0]);
    $this->assertInstanceOf(Automation::class, $oldestAutomation);
    $oldestAutomation->setName('new-name');
    $this->automationStorage->updateAutomation($oldestAutomation);

    $middleWorkeflow = $this->automationStorage->getAutomation($this->automations[0]);
    $this->assertInstanceOf(Automation::class, $middleWorkeflow);
    $middleWorkeflow->setName('another-name');
    $this->automationStorage->updateAutomation($middleWorkeflow);

    $newestAutomation = $this->automationStorage->getAutomation($this->automations[0]);
    $this->assertInstanceOf(Automation::class, $newestAutomation);
    // 1 Run in the oldest Automation
    $this->createRun($oldestAutomation, AutomationRun::STATUS_CANCELLED);

    // 2 Runs in the middle Automation
    $this->createRun($middleWorkeflow, AutomationRun::STATUS_RUNNING);
    $this->createRun($middleWorkeflow, AutomationRun::STATUS_FAILED);

    // 3 Runs in the newest Automation
    $this->createRun($newestAutomation, AutomationRun::STATUS_RUNNING);
    $this->createRun($newestAutomation, AutomationRun::STATUS_RUNNING);
    $this->createRun($newestAutomation, AutomationRun::STATUS_RUNNING);

    $stats = $this->testee->getAutomationStats($newestAutomation->getId(), null);
    $this->assertEquals(6, $stats->getEntered());

    $stats = $this->testee->getAutomationStats($newestAutomation->getId(), $newestAutomation->getVersionId());
    $this->assertEquals(3, $stats->getEntered());

    $stats = $this->testee->getAutomationStats($newestAutomation->getId(), $middleWorkeflow->getVersionId());
    $this->assertEquals(2, $stats->getEntered());

    $stats = $this->testee->getAutomationStats($newestAutomation->getId(), $oldestAutomation->getVersionId());
    $this->assertEquals(1, $stats->getEntered());
  }

  public function _after() {
    parent::_after();
    $this->automationStorage->truncate();
    $this->automationRunStorage->truncate();
  }

  private function createRun(Automation $automation, string $status) {
    $run = AutomationRun::fromArray([
      'automation_id' => $automation->getId(),
      'version_id' => $automation->getVersionId(),
      'trigger_key' => '',
      'subjects' => [],
      'id' => 0,
      'status' => $status,
      'created_at' => (new \DateTimeImmutable())->format(\DateTimeImmutable::W3C),
      'updated_at' => (new \DateTimeImmutable())->format(\DateTimeImmutable::W3C),
    ]);
    $this->automationRunStorage->createAutomationRun($run);
  }
}
