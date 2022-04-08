<?php

namespace MailPoet\Test\Automation\Integrations\MailPoet\Actions\SendWelcomeEmail;

use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendWelcomeEmail\Action;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Exception;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class ActionTest extends \MailPoetTest {

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function _before() {
    parent::_before();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testHappyPath() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = [
      'mailpoet:subscriber' => $this->getLoadedSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getLoadedSegmentSubject($segment),
    ];
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $run = new WorkflowRun(1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $action = ContainerWrapper::getInstance()->get(Action::class);
    $action->run($workflow, $run, $step);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(1);
  }

  public function testItDoesNotScheduleDuplicates() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subscriberSubject = ContainerWrapper::getInstance()->get(SubscriberSubject::class);
    $subscriberSubject->load(['subscriber_id' => $subscriber->getId()]);
    $segmentSubject = ContainerWrapper::getInstance()->get(SegmentSubject::class);
    $segmentSubject->load(['segment_id' => $segment->getId()]);
    $subjects = [
      'mailpoet:subscriber' => $subscriberSubject,
      'mailpoet:segment' => $segmentSubject,
    ];
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $run = new WorkflowRun(1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $action = ContainerWrapper::getInstance()->get(Action::class);
    $action->run($workflow, $run, $step);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(1);

    try {
      $action->run($workflow, $run, $step);
    } catch (InvalidStateException $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(1);
  }

  public function testNothingScheduledIfSegmentDeleted(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = [
      'mailpoet:subscriber' => $this->getLoadedSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getLoadedSegmentSubject($segment),
    ];
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $run = new WorkflowRun(1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $this->segmentsRepository->bulkDelete([$segment->getId()]);
    $action = ContainerWrapper::getInstance()->get(Action::class);

    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testNothingScheduledIfSubscriberDeleted(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = [
      'mailpoet:subscriber' => $this->getLoadedSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getLoadedSegmentSubject($segment),
    ];
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $run = new WorkflowRun(1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
    $action = ContainerWrapper::getInstance()->get(Action::class);

    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testNothingScheduledIfSubscriberIsNotGloballySubscribed(): void {
    $segment = (new Segment())->create();

    $otherStatuses = [
      SubscriberEntity::STATUS_UNCONFIRMED,
      SubscriberEntity::STATUS_INACTIVE,
      SubscriberEntity::STATUS_BOUNCED,
      SubscriberEntity::STATUS_UNSUBSCRIBED,
    ];

    foreach ($otherStatuses as $status) {
      $subscriber = (new Subscriber())
        ->withStatus($status)
        ->withSegments([$segment])
        ->create();
      $subjects = [
        'mailpoet:subscriber' => $this->getLoadedSubscriberSubject($subscriber),
        'mailpoet:segment' => $this->getLoadedSegmentSubject($segment),
      ];
      $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();

      $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
      $workflow = new Workflow('some-workflow', [$step]);
      $run = new WorkflowRun(1, 'trigger-key', $subjects);

      $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
      expect($scheduled)->count(0);

      $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
      $action = ContainerWrapper::getInstance()->get(Action::class);

      try {
        $action->run($workflow, $run, $step);
      } catch (Exception $exception) {
        // The exception itself isn't as important as the outcome
      }

      $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
      expect($scheduled)->count(0);
    }
  }

  public function testNothingScheduledIfSubscriberNotSubscribedToSegment(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $subjects = [
      'mailpoet:subscriber' => $this->getLoadedSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getLoadedSegmentSubject($segment),
    ];
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $run = new WorkflowRun(1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $action = ContainerWrapper::getInstance()->get(Action::class);

    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testIsValidWithValidSubjectsAndStep() {
    $segment = (new Segment())->create();
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $subscriberSubject = ContainerWrapper::getInstance()->get(SubscriberSubject::class);
    $segmentSubject = ContainerWrapper::getInstance()->get(SegmentSubject::class);
    $subjects = [
      'mailpoet:subscriber' => $subscriberSubject,
      'mailpoet:segment' => $segmentSubject,
    ];
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $action = ContainerWrapper::getInstance()->get(Action::class);
    $result = $action->validate($workflow, $step, $subjects);
    expect($result->isValid())->true();
  }

  public function testNotValidWithoutSegmentSubject() {
    $segment = (new Segment())->create();
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $subscriberSubject = ContainerWrapper::getInstance()->get(SubscriberSubject::class);
    $subjects = [
      'mailpoet:subscriber' => $subscriberSubject,
    ];
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $action = ContainerWrapper::getInstance()->get(Action::class);
    $result = $action->validate($workflow, $step, $subjects);
    expect($result->isValid())->false();
    expect($result->getErrors())->count(1);
    expect(array_keys($result->getErrors())[0])->equals('segmentSubjectRequired');
  }

  public function testNotValidWithoutSubscriberSubject() {
    $segment = (new Segment())->create();
    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $segmentSubject = ContainerWrapper::getInstance()->get(SegmentSubject::class);
    $subjects = [
      'mailpoet:segment' => $segmentSubject,
    ];
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $action = ContainerWrapper::getInstance()->get(Action::class);
    $result = $action->validate($workflow, $step, $subjects);
    expect($result->isValid())->false();
    expect($result->getErrors())->count(1);
    expect(array_keys($result->getErrors())[0])->equals('subscriberSubjectRequired');
  }

  public function testNotValidWithoutWelcomeEmailId() {
    $subscriberSubject = ContainerWrapper::getInstance()->get(SubscriberSubject::class);
    $segmentSubject = ContainerWrapper::getInstance()->get(SegmentSubject::class);
    $subjects = [
      'mailpoet:subscriber' => $subscriberSubject,
      'mailpoet:segment' => $segmentSubject,
    ];
    // welcomeEmailId would normally be included here
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, []);
    $workflow = new Workflow('some-workflow', [$step]);
    $action = ContainerWrapper::getInstance()->get(Action::class);
    $result = $action->validate($workflow, $step, $subjects);
    expect($result->isValid())->false();
    expect($result->getErrors())->count(1);
    expect(array_keys($result->getErrors())[0])->equals('welcomeEmailIdRequired');
  }

  public function testNotValidIfWelcomeEmailDoesNotExist() {
    $subscriberSubject = ContainerWrapper::getInstance()->get(SubscriberSubject::class);
    $segmentSubject = ContainerWrapper::getInstance()->get(SegmentSubject::class);
    $subjects = [
      'mailpoet:subscriber' => $subscriberSubject,
      'mailpoet:segment' => $segmentSubject,
    ];
    // welcomeEmailId would normally be included here
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => 0]);
    $workflow = new Workflow('some-workflow', [$step]);
    $action = ContainerWrapper::getInstance()->get(Action::class);
    $result = $action->validate($workflow, $step, $subjects);
    expect($result->isValid())->false();
    expect($result->getErrors())->count(1);
    expect(array_keys($result->getErrors())[0])->equals('welcomeEmailNotFound');
  }

  public function testNotValidIfNewsletterIsNotWelcomeType() {
    $wrongKindOfEmail = (new Newsletter())->withPostNotificationsType()->create();
    $subscriberSubject = ContainerWrapper::getInstance()->get(SubscriberSubject::class);
    $segmentSubject = ContainerWrapper::getInstance()->get(SegmentSubject::class);
    $subjects = [
      'mailpoet:subscriber' => $subscriberSubject,
      'mailpoet:segment' => $segmentSubject,
    ];
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $wrongKindOfEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);
    $action = ContainerWrapper::getInstance()->get(Action::class);
    $result = $action->validate($workflow, $step, $subjects);
    expect($result->isValid())->false();
    expect($result->getErrors())->count(1);
    expect(array_keys($result->getErrors())[0])->equals('newsletterMustBeWelcomeType');
  }

  public function testCanFailValidationForMultipleReasons() {
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, []);
    $workflow = new Workflow('some-workflow', [$step]);
    $action = ContainerWrapper::getInstance()->get(Action::class);
    $result = $action->validate($workflow, $step, []);
    expect($result->isValid())->false();
    expect($result->getErrors())->count(3);
    $errors = $result->getErrors();
    expect($errors)->count(3);
    expect($errors)->hasKey('welcomeEmailIdRequired');
    expect($errors)->hasKey('subscriberSubjectRequired');
    expect($errors)->hasKey('segmentSubjectRequired');
  }

  private function getLoadedSubscriberSubject(SubscriberEntity $subscriber): SubscriberSubject {
    $subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
    $subscriberSubject->load(['subscriber_id' => $subscriber->getId()]);

    return $subscriberSubject;
  }

  private function getLoadedSegmentSubject(SegmentEntity $segment): SegmentSubject {
    /** @var SegmentSubject $segmentSubject */
    $segmentSubject = $this->diContainer->get(SegmentSubject::class);
    $segmentSubject->load(['segment_id' => $segment->getId()]);

    return $segmentSubject;
  }
}
