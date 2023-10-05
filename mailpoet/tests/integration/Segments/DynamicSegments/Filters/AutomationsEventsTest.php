<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Filters\AutomationsEvents;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class AutomationsEventsTest extends \MailPoetTest {

  /** @var AutomationsEvents */
  private $filter;

  /** @var AutomationRunStorage */
  private $automationRunStorage;

  /** @var SegmentEntity */
  private $triggerSegment;

  public function _before(): void {
    $this->filter = $this->diContainer->get(AutomationsEvents::class);
    $this->automationRunStorage = $this->diContainer->get(AutomationRunStorage::class);
    $this->triggerSegment = (new Segment())->withType(SegmentEntity::TYPE_DEFAULT)->withName('test segment')->create();
  }

  public function testEnteredWorksWithAnyOperator(): void {
    $s1 = (new Subscriber())->withEmail('1@e.com')->create();
    $s2 = (new Subscriber())->withEmail('2@e.com')->create();
    $s3 = (new Subscriber())->withEmail('3@e.com')->create();
    $s4 = (new Subscriber())->withEmail('4@e.com')->create();
    $s5 = (new Subscriber())->withEmail('5@e.com')->create();
    $s6 = (new Subscriber())->withEmail('6@e.com')->create();
    $s7 = (new Subscriber())->withEmail('7@e.com')->create();
    $s8 = (new Subscriber())->withEmail('8@e.com')->create();

    $automation1 = $this->createAutomation();
    $automation2 = $this->createAutomation();

    $this->addSubscriberToAutomation($s1, $automation1, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s2, $automation1, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s3, $automation1, AutomationRun::STATUS_CANCELLED);
    $this->addSubscriberToAutomation($s4, $automation1, AutomationRun::STATUS_FAILED);

    $this->addSubscriberToAutomation($s5, $automation2, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s6, $automation2, AutomationRun::STATUS_COMPLETE);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'any', [$automation1->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'any', [$automation2->getId()], ['5@e.com', '6@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'any', [$automation1->getId(), $automation2->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com', '5@e.com', '6@e.com']);

    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'any', [$automation1->getId()], ['2@e.com', '3@e.com', '4@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'any', [$automation2->getId()], ['6@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'any', [$automation1->getId(), $automation2->getId()], ['2@e.com', '3@e.com', '4@e.com', '6@e.com']);
  }

  public function testStatusesWorkAsExpectedForAllOperator(): void {
    $s1 = (new Subscriber())->withEmail('1@e.com')->create();
    $s2 = (new Subscriber())->withEmail('2@e.com')->create();
    $s3 = (new Subscriber())->withEmail('3@e.com')->create();
    $s4 = (new Subscriber())->withEmail('4@e.com')->create();
    $s5 = (new Subscriber())->withEmail('5@e.com')->create();
    $s6 = (new Subscriber())->withEmail('6@e.com')->create();
    $s7 = (new Subscriber())->withEmail('7@e.com')->create();
    $s8 = (new Subscriber())->withEmail('8@e.com')->create();
    $s9 = (new Subscriber())->withEmail('9@e.com')->create();
    $s10 = (new Subscriber())->withEmail('10@e.com')->create();
    $s11 = (new Subscriber())->withEmail('11@e.com')->create();
    $s12 = (new Subscriber())->withEmail('12@e.com')->create();

    $automation1 = $this->createAutomation();
    $automation2 = $this->createAutomation();
    $automation3 = $this->createAutomation();

    $this->addSubscriberToAutomation($s1, $automation1, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s2, $automation1, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s3, $automation1, AutomationRun::STATUS_CANCELLED);
    $this->addSubscriberToAutomation($s4, $automation1, AutomationRun::STATUS_FAILED);

    $this->addSubscriberToAutomation($s5, $automation2, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s6, $automation2, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s7, $automation2, AutomationRun::STATUS_CANCELLED);
    $this->addSubscriberToAutomation($s8, $automation2, AutomationRun::STATUS_FAILED);

    $this->addSubscriberToAutomation($s9, $automation3, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s10, $automation3, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s11, $automation3, AutomationRun::STATUS_CANCELLED);
    $this->addSubscriberToAutomation($s12, $automation3, AutomationRun::STATUS_FAILED);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation1->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation2->getId()], ['5@e.com', '6@e.com', '7@e.com', '8@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation3->getId()], ['9@e.com', '10@e.com', '11@e.com', '12@e.com']);

    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'all', [$automation1->getId()], ['2@e.com', '3@e.com', '4@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'all', [$automation2->getId()], ['6@e.com', '7@e.com', '8@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'all', [$automation3->getId()], ['10@e.com', '11@e.com', '12@e.com']);
  }

  public function testCombinationsWorkAsExpectedWithAllOperator(): void {
    $automation1 = $this->createAutomation();
    $automation2 = $this->createAutomation();
    $automation3 = $this->createAutomation();
    $automation4 = $this->createAutomation();

    $s1 = (new Subscriber())->withEmail('1@e.com')->create();
    $this->addSubscriberToAutomation($s1, $automation1, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s1, $automation2, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s1, $automation3, AutomationRun::STATUS_COMPLETE);

    $s2 = (new Subscriber())->withEmail('2@e.com')->create();
    $this->addSubscriberToAutomation($s2, $automation2, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s2, $automation3, AutomationRun::STATUS_COMPLETE);

    $s3 = (new Subscriber())->withEmail('3@e.com')->create();
    $this->addSubscriberToAutomation($s3, $automation3, AutomationRun::STATUS_COMPLETE);

    $s4 = (new Subscriber())->withEmail('4@e.com')->create();
    $this->addSubscriberToAutomation($s4, $automation1, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s4, $automation3, AutomationRun::STATUS_COMPLETE);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation1->getId(), $automation2->getId(), $automation3->getId()], ['1@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation1->getId(), $automation2->getId(), $automation3->getId(), $automation4->getId()], []);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation2->getId(), $automation3->getId()], ['1@e.com', '2@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation2->getId(), $automation3->getId(), $automation4->getId()], []);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation3->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation3->getId(), $automation4->getId()], []);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation1->getId(), $automation3->getId()], ['1@e.com', '4@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation1->getId(), $automation3->getId(), $automation4->getId()], []);
  }

  public function testItWorksWithNoneOperator(): void {
    $s1 = (new Subscriber())->withEmail('1@e.com')->create();
    $s2 = (new Subscriber())->withEmail('2@e.com')->create();
    $s3 = (new Subscriber())->withEmail('3@e.com')->create();
    $s4 = (new Subscriber())->withEmail('4@e.com')->create();
    $s5 = (new Subscriber())->withEmail('5@e.com')->create();
    $s6 = (new Subscriber())->withEmail('6@e.com')->create();
    $s7 = (new Subscriber())->withEmail('7@e.com')->create();
    $s8 = (new Subscriber())->withEmail('8@e.com')->create();
    $s9 = (new Subscriber())->withEmail('9@e.com')->create();
    $s10 = (new Subscriber())->withEmail('10@e.com')->create();
    $s11 = (new Subscriber())->withEmail('11@e.com')->create();
    $s12 = (new Subscriber())->withEmail('12@e.com')->create();

    $automation1 = $this->createAutomation();
    $automation2 = $this->createAutomation();
    $automation3 = $this->createAutomation();

    $this->addSubscriberToAutomation($s1, $automation1, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s2, $automation1, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s3, $automation1, AutomationRun::STATUS_CANCELLED);
    $this->addSubscriberToAutomation($s4, $automation1, AutomationRun::STATUS_FAILED);

    $this->addSubscriberToAutomation($s5, $automation2, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s6, $automation2, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s7, $automation2, AutomationRun::STATUS_CANCELLED);
    $this->addSubscriberToAutomation($s8, $automation2, AutomationRun::STATUS_FAILED);

    $this->addSubscriberToAutomation($s9, $automation3, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s10, $automation3, AutomationRun::STATUS_COMPLETE);
    $this->addSubscriberToAutomation($s11, $automation3, AutomationRun::STATUS_CANCELLED);
    $this->addSubscriberToAutomation($s12, $automation3, AutomationRun::STATUS_FAILED);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation1->getId()], ['5@e.com', '6@e.com', '7@e.com', '8@e.com', '9@e.com', '10@e.com', '11@e.com', '12@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation2->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com', '9@e.com', '10@e.com', '11@e.com', '12@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation3->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com', '5@e.com', '6@e.com', '7@e.com', '8@e.com']);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation1->getId(), $automation2->getId()], ['9@e.com', '10@e.com', '11@e.com', '12@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation2->getId(), $automation3->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation1->getId(), $automation3->getId()], ['5@e.com', '6@e.com', '7@e.com', '8@e.com']);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation1->getId(), $automation2->getId(), $automation3->getId()], []);

    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation1->getId()], ['1@e.com', '5@e.com', '6@e.com', '7@e.com', '8@e.com', '9@e.com', '10@e.com', '11@e.com', '12@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation2->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com', '5@e.com', '9@e.com', '10@e.com', '11@e.com', '12@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation3->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com', '5@e.com', '6@e.com', '7@e.com', '8@e.com', '9@e.com']);

    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation1->getId(), $automation2->getId()], ['1@e.com', '5@e.com', '9@e.com', '10@e.com', '11@e.com', '12@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation2->getId(), $automation3->getId()], ['1@e.com', '2@e.com', '3@e.com', '4@e.com', '5@e.com', '9@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation1->getId(), $automation3->getId()], ['1@e.com', '5@e.com', '6@e.com', '7@e.com', '8@e.com', '9@e.com']);

    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation1->getId(), $automation2->getId(), $automation3->getId()], ['1@e.com', '5@e.com', '9@e.com']);
  }

  public function testNoneOfReturnsSubscribersNotAssociatedWithAutomations(): void {
    $automation1 = $this->createAutomation();
    $s1 = (new Subscriber())->withEmail('1@e.com')->create();
    $s2 = (new Subscriber())->withEmail('2@e.com')->create();
    $this->addSubscriberToAutomation($s1, $automation1, AutomationRun::STATUS_COMPLETE);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation1->getId()], ['2@e.com']);
  }

  public function testItWorksWithSubscribersWhoHaveMultipleRunsForTheSameAutomation(): void {
    $automation1 = $this->createAutomation();

    $s1 = (new Subscriber())->withEmail('1@e.com')->create();

    $this->addSubscriberToAutomation($s1, $automation1, AutomationRun::STATUS_RUNNING);
    $this->addSubscriberToAutomation($s1, $automation1, AutomationRun::STATUS_COMPLETE);

    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'any', [$automation1->getId()], ['1@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'any', [$automation1->getId()], ['1@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'all', [$automation1->getId()], ['1@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'all', [$automation1->getId()], ['1@e.com']);
    $this->assertFilterReturnsEmails(AutomationsEvents::ENTERED_ACTION, 'none', [$automation1->getId()], []);
    $this->assertFilterReturnsEmails(AutomationsEvents::EXITED_ACTION, 'none', [$automation1->getId()], []);
  }

  public function testItRetrievesLookupData(): void {
    $automation1 = $this->createAutomation('a1');
    $automation2 = $this->createAutomation('a2');

    $data = $this->getSegmentFilterData('enteredAutomation', 'all', [$automation1->getId(), $automation2->getId(), 928374]);
    $this->assertEqualsCanonicalizing([
      'automations' => [
        1 => 'a1',
        2 => 'a2',
      ],
    ], $this->filter->getLookupData($data));
  }

  private function getSegmentFilterData(string $action, string $operator, array $automationIds): DynamicSegmentFilterData {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_AUTOMATIONS, $action, [
      'action' => $action,
      'operator' => $operator,
      'automation_ids' => $automationIds,
    ]);
    return $filterData;
  }

  private function assertFilterReturnsEmails(string $action, string $operator, array $automationIds, array $expectedEmails): void {
    $filterData = $this->getSegmentFilterData($action, $operator, $automationIds);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }

  private function createAutomation(string $name = 'test automation'): Automation {
    return (new AutomationFactory())
      ->withName($name)
      ->withStatusActive()
      ->withSomeoneSubscribesTrigger()
      ->create();
  }

  private function addSubscriberToAutomation(SubscriberEntity $subscriberEntity, Automation $automation, string $status) {
    $subscriberSubject = new Subject('mailpoet:subscriber', ['subscriber_id' => $subscriberEntity->getId()]);
    $segmentSubject = new Subject('mailpoet:segment', ['segment_id' => $this->triggerSegment->getId()]);
    $automationRun = new AutomationRun(
      $automation->getId(),
      $automation->getVersionId(),
      'mailpoet:someone-subscribes',
      [$subscriberSubject, $segmentSubject]
    );
    $id = $this->automationRunStorage->createAutomationRun($automationRun);
    if ($status !== AutomationRun::STATUS_RUNNING) {
      $this->automationRunStorage->updateStatus($id, $status);
    }
  }
}
