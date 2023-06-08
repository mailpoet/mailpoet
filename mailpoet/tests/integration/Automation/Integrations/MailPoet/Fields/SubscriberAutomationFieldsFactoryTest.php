<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\MailPoet\Fields;

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
    $automation->setStatus($status);
    $this->diContainer->get(AutomationStorage::class)->updateAutomation($automation);
    return $automation;
  }

  private function createAutomationRun(Automation $automation, string $status, array $subjects): AutomationRun {
    $run = $this->tester->createAutomationRun($automation, $subjects);
    $this->diContainer->get(AutomationRunStorage::class)->updateStatus($run->getId(), $status);
    return $run;
  }
}
