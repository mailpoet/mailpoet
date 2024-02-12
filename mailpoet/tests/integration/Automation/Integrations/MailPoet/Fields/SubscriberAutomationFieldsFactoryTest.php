<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\MailPoet\Fields;

use DateTimeImmutable;
use DateTimeInterface;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Fields\SubscriberAutomationFieldsFactory;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetTest;

class SubscriberAutomationFieldsFactoryTest extends MailPoetTest {
  public function testItCreatesAutomationFields(): void {
    $draft = $this->createAutomation('Draft', Automation::STATUS_DRAFT);
    $active = $this->createAutomation('Active', Automation::STATUS_ACTIVE);
    $deactivating = $this->createAutomation('Deactivating', Automation::STATUS_DEACTIVATING);
    $this->createAutomation('Trash', Automation::STATUS_TRASH); // not included

    $fields = $this->getFieldsMap();

    // check definitions
    $this->assertCount(3, $fields);
    $expected = [
      'mailpoet:subscriber:automations-entered' => 'Automations — entered',
      'mailpoet:subscriber:automations-processing' => 'Automations — processing',
      'mailpoet:subscriber:automations-exited' => 'Automations — exited',
    ];

    foreach ($expected as $key => $name) {
      $field = $fields[$key];
      $this->assertSame($name, $field->getName());
      $this->assertSame('enum_array', $field->getType());
      $this->assertSame(['options' => [
        ['id' => $deactivating->getId(), 'name' => "Deactivating (#{$deactivating->getId()})"],
        ['id' => $active->getId(), 'name' => "Active (#{$active->getId()})"],
        ['id' => $draft->getId(), 'name' => "Draft (#{$draft->getId()})"],
      ]], $field->getArgs());
    }

    // check values
    $subscriber = (new SubscriberFactory())->create();
    $subject = new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]);
    $this->createAutomationRun($draft, AutomationRun::STATUS_COMPLETE, [$subject]);
    $this->createAutomationRun($active, AutomationRun::STATUS_CANCELLED, [$subject]);
    $this->createAutomationRun($active, AutomationRun::STATUS_RUNNING, [$subject]);
    $this->createAutomationRun($active, AutomationRun::STATUS_COMPLETE, [$subject]);
    $this->createAutomationRun($deactivating, AutomationRun::STATUS_RUNNING, [$subject]);

    $payload = new SubscriberPayload($subscriber);
    $entered = $fields['mailpoet:subscriber:automations-entered'];
    $processing = $fields['mailpoet:subscriber:automations-processing'];
    $exited = $fields['mailpoet:subscriber:automations-exited'];

    $this->assertSame([$deactivating->getId(), $active->getId(), $draft->getId()], $entered->getValue($payload));
    $this->assertSame([$deactivating->getId(), $active->getId()], $processing->getValue($payload));
    $this->assertSame([$active->getId(), $draft->getId()], $exited->getValue($payload));
  }

  public function testAutomationFieldsWithInTheLastParameter(): void {
    $draft1 = $this->createAutomation('Draft 1', Automation::STATUS_DRAFT);
    $draft2 = $this->createAutomation('Draft 2', Automation::STATUS_DRAFT);

    $active1 = $this->createAutomation('Active 1', Automation::STATUS_ACTIVE);
    $active2 = $this->createAutomation('Active 2', Automation::STATUS_ACTIVE);
    $active3 = $this->createAutomation('Active 3', Automation::STATUS_ACTIVE);

    $deactivating1 = $this->createAutomation('Deactivating 1', Automation::STATUS_DEACTIVATING);
    $deactivating2 = $this->createAutomation('Deactivating 2', Automation::STATUS_DEACTIVATING);

    $this->createAutomation('Trash', Automation::STATUS_TRASH); // not included

    $fields = $this->getFieldsMap();

    $subscriber = (new SubscriberFactory())->create();
    $subject = new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]);

    $this->createAutomationRun($draft1, AutomationRun::STATUS_COMPLETE, [$subject], new DateTimeImmutable('-1 month'));
    $this->createAutomationRun($draft2, AutomationRun::STATUS_COMPLETE, [$subject], new DateTimeImmutable('-1 week'));

    $this->createAutomationRun($active1, AutomationRun::STATUS_CANCELLED, [$subject], new DateTimeImmutable('-1 month'));
    $this->createAutomationRun($active2, AutomationRun::STATUS_CANCELLED, [$subject], new DateTimeImmutable('-1 week'));
    $this->createAutomationRun($active1, AutomationRun::STATUS_RUNNING, [$subject], new DateTimeImmutable('-1 month'));
    $this->createAutomationRun($active3, AutomationRun::STATUS_RUNNING, [$subject], new DateTimeImmutable('-1 week'));
    $this->createAutomationRun($active2, AutomationRun::STATUS_COMPLETE, [$subject], new DateTimeImmutable('-1 month'));
    $this->createAutomationRun($active3, AutomationRun::STATUS_COMPLETE, [$subject], new DateTimeImmutable('-1 week'));

    $this->createAutomationRun($deactivating1, AutomationRun::STATUS_RUNNING, [$subject], new DateTimeImmutable('-1 month'));
    $this->createAutomationRun($deactivating2, AutomationRun::STATUS_RUNNING, [$subject], new DateTimeImmutable('-1 week'));

    $payload = new SubscriberPayload($subscriber);
    $entered = $fields['mailpoet:subscriber:automations-entered'];

    // all time
    $this->assertSame(
      [$deactivating2->getId(), $deactivating1->getId(), $active3->getId(), $active2->getId(), $active1->getId(), $draft2->getId(), $draft1->getId()],
      $entered->getValue($payload)
    );

    // 3 months
    $this->assertSame(
      [$deactivating2->getId(), $deactivating1->getId(), $active3->getId(), $active2->getId(), $active1->getId(), $draft2->getId(), $draft1->getId()],
      $entered->getValue($payload, ['in_the_last_seconds' => 3 * MONTH_IN_SECONDS])
    );

    // 3 weeks
    $this->assertSame(
      [$deactivating2->getId(), $active3->getId(), $active2->getId(), $draft2->getId()],
      $entered->getValue($payload, ['in_the_last_seconds' => 3 * WEEK_IN_SECONDS])
    );

    // 3 days
    $this->assertSame([], $entered->getValue($payload, ['in_the_last_seconds' => 3 * DAY_IN_SECONDS]));
  }

  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(SubscriberAutomationFieldsFactory::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }

  private function createAutomation(string $name, string $status): Automation {
    $automation = $this->tester->createAutomation($name);
    $this->assertInstanceOf(Automation::class, $automation);
    $automation->setStatus($status);
    $this->diContainer->get(AutomationStorage::class)->updateAutomation($automation);
    return $automation;
  }

  private function createAutomationRun(
    Automation $automation,
    string $status,
    array $subjects,
    DateTimeInterface $createdAt = null
  ): AutomationRun {
    $runStorage = $this->diContainer->get(AutomationRunStorage::class);
    $run = $this->tester->createAutomationRun($automation, $subjects);
    $this->assertInstanceOf(AutomationRun::class, $run);

    global $wpdb;
    $wpdb->update(
      $wpdb->prefix . 'mailpoet_automation_runs',
      array_merge(
        ['status' => $status],
        ...[$createdAt ? ['created_at' => $createdAt->format('Y-m-d H:i:s')] : []],
      ),
      ['id' => $run->getId()]
    );
    $run = $runStorage->getAutomationRun($run->getId());
    $this->assertInstanceOf(AutomationRun::class, $run);
    return $run;
  }
}
