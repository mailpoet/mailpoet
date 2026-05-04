<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Exceptions\UnexpectedValueException;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneUnsubscribesTrigger;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\WP\Functions as WPFunctions;

class SomeoneUnsubscribesTriggerTest extends \MailPoetTest {
  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItFiresWhenSubscriberIsUnsubscribed(): void {
    $subscriber = (new SubscriberFactory())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    $wpMock = $this->createMock(WPFunctions::class);
    $testee = new SomeoneUnsubscribesTrigger($wpMock, $this->subscribersRepository);

    $wpMock->expects($this->once())->method('doAction')
      ->willReturnCallback(function ($hook, $trigger, array $subjects) use ($testee, $subscriber) {
        $this->assertSame(Hooks::TRIGGER, $hook);
        $this->assertSame($testee, $trigger);
        /** @var Subject[] $subjects */
        $this->assertCount(1, $subjects);
        $this->assertSame(SubscriberSubject::KEY, $subjects[0]->getKey());
        $this->assertSame($subscriber->getId(), $subjects[0]->getArgs()['subscriber_id']);
      });

    $testee->handleStatusChange((int)$subscriber->getId());
  }

  public function testItDoesNotFireWhenSubscriberIsNotUnsubscribed(): void {
    $subscriber = (new SubscriberFactory())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();

    $wpMock = $this->createMock(WPFunctions::class);
    $testee = new SomeoneUnsubscribesTrigger($wpMock, $this->subscribersRepository);

    $wpMock->expects($this->never())->method('doAction');

    $testee->handleStatusChange((int)$subscriber->getId());
  }

  public function testItDoesNotFireWhenSubscriberDoesNotExist(): void {
    $wpMock = $this->createMock(WPFunctions::class);
    $testee = new SomeoneUnsubscribesTrigger($wpMock, $this->subscribersRepository);

    $wpMock->expects($this->never())->method('doAction');

    $testee->handleStatusChange(99999999);
  }

  public function testItPassesAutomationValidationOnDraft(): void {
    $testee = $this->diContainer->get(SomeoneUnsubscribesTrigger::class);
    $automation = $this->buildAutomation([
      $this->triggerStep(),
      $this->sendEmailStep(),
    ], Automation::STATUS_DRAFT);

    $this->expectNotToPerformAssertions();
    $testee->validateAutomation($automation);
  }

  public function testItPassesAutomationValidationWhenNoSendEmailStep(): void {
    $testee = $this->diContainer->get(SomeoneUnsubscribesTrigger::class);
    $automation = $this->buildAutomation([
      $this->triggerStep(),
      new Step('delay-id', Step::TYPE_ACTION, 'core:delay', [], []),
    ], Automation::STATUS_ACTIVE);

    $this->expectNotToPerformAssertions();
    $testee->validateAutomation($automation);
  }

  public function testItFailsAutomationValidationWhenActivatingWithSendEmail(): void {
    $testee = $this->diContainer->get(SomeoneUnsubscribesTrigger::class);
    $automation = $this->buildAutomation([
      $this->triggerStep(),
      $this->sendEmailStep(),
    ], Automation::STATUS_ACTIVE);

    $this->expectException(UnexpectedValueException::class);
    $testee->validateAutomation($automation);
  }

  /** @param Step[] $steps */
  private function buildAutomation(array $steps, string $status): Automation {
    $stepMap = [];
    foreach ($steps as $step) {
      $stepMap[$step->getId()] = $step;
    }
    $automation = new Automation('automation', $stepMap, new \WP_User());
    $automation->setStatus($status);
    return $automation;
  }

  private function triggerStep(): Step {
    return new Step('trigger-id', Step::TYPE_TRIGGER, SomeoneUnsubscribesTrigger::KEY, [], []);
  }

  private function sendEmailStep(): Step {
    return new Step('send-id', Step::TYPE_ACTION, SendEmailAction::KEY, [], []);
  }
}
