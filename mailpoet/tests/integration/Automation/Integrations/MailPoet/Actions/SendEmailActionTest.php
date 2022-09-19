<?php declare(strict_types=1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Exception;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class SendEmailActionTest extends \MailPoetTest {

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SendEmailAction */
  private $action;

  /** @var SubscriberSubject */
  private $subscriberSubject;

  /** @var SegmentSubject */
  private $segmentSubject;

  /** @var Step */
  private $step;

  /** @var Workflow */
  private $workflow;

  /** @var NewsletterEntity */
  private $email;

  public function _before() {
    parent::_before();
    $this->cleanup();

    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->action = $this->diContainer->get(SendEmailAction::class);
    $this->subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
    $this->segmentSubject = $this->diContainer->get(SegmentSubject::class);

    $this->email = (new Newsletter())->withAutomationType()->create();
    $this->step = new Step('step-id', Step::TYPE_ACTION, 'step-key',['email_id' => $this->email->getId()], []);
    $this->workflow = new Workflow('test-workflow', [], new \WP_User());
  }

  public function testItKnowsWhenItHasAllRequiredSubjects() {
    expect($this->action->isValid([], $this->step, $this->workflow))->false();
    expect($this->action->isValid($this->getSubjects(), $this->step, $this->workflow))->true();
  }

  public function testItRequiresASubscriberSubject() {
    expect($this->action->isValid([$this->segmentSubject], $this->step, $this->workflow))->false();
  }

  public function testItRequiresASegmentSubject() {
    expect($this->action->isValid([$this->subscriberSubject], $this->step, $this->workflow))->false();
  }

  public function testItIsNotValidIfStepHasNoEmail(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', [], []);
    expect($this->action->isValid($this->getSubjects(), $step, $this->workflow))->false();
  }

  public function testItRequiresAutomationEmailType(): void {
    $newsletter = (new Newsletter())->withPostNotificationsType()->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $newsletter->getId()], []);
    expect($this->action->isValid($this->getSubjects(), $step, $this->workflow))->false();
  }

  public function testHappyPath() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = $this->getSubjectData($subscriber, $segment);
    $email = (new Newsletter())->withAutomationType()->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
    $workflow = new Workflow('some-workflow', [$step], new \WP_User());
    $run = new WorkflowRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $this->action->run(new StepRunArgs($workflow, $run, $step, $this->getSubjectEntries($subjects)));

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(1);
  }

  public function testItDoesNotScheduleDuplicates(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = $this->getSubjectData($subscriber, $segment);
    $email = (new Newsletter())->withAutomationType()->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
    $workflow = new Workflow('some-workflow', [$step], new \WP_User());
    $run = new WorkflowRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);
    $action->run(new StepRunArgs($workflow, $run, $step, $this->getSubjectEntries($subjects)));

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(1);

    try {
      $action->run(new StepRunArgs($workflow, $run, $step, $this->getSubjectEntries($subjects)));
    } catch (InvalidStateException $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(1);
  }

  public function testNothingScheduledIfSegmentDeleted(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = $this->getSubjectData($subscriber, $segment);
    $email = (new Newsletter())->withAutomationType()->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
    $workflow = new Workflow('some-workflow', [$step], new \WP_User());
    $run = new WorkflowRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $this->segmentsRepository->bulkDelete([$segment->getId()]);
    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);

    try {
      $action->run(new StepRunArgs($workflow, $run, $step, $this->getSubjectEntries($subjects)));
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testNothingScheduledIfSubscriberDeleted(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = $this->getSubjectData($subscriber, $segment);
    $email = (new Newsletter())->withAutomationType()->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
    $workflow = new Workflow('some-workflow', [$step], new \WP_User());
    $run = new WorkflowRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);

    try {
      $action->run(new StepRunArgs($workflow, $run, $step, $this->getSubjectEntries($subjects)));
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
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
      $subjects = $this->getSubjectData($subscriber, $segment);
      $email = (new Newsletter())->withAutomationType()->create();

      $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
      $workflow = new Workflow('some-workflow', [$step], new \WP_User());
      $run = new WorkflowRun(1, 1, 'trigger-key', $subjects);

      $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
      expect($scheduled)->count(0);

      $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
      $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);

      try {
        $action->run(new StepRunArgs($workflow, $run, $step, $this->getSubjectEntries($subjects)));
      } catch (Exception $exception) {
        // The exception itself isn't as important as the outcome
      }

      $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
      expect($scheduled)->count(0);
    }
  }

  public function testNothingScheduledIfSubscriberNotSubscribedToSegment(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->create();
    $subjects = $this->getSubjectData($subscriber, $segment);
    $email = (new Newsletter())->withAutomationType()->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
    $workflow = new Workflow('some-workflow', [$step], new \WP_User());
    $run = new WorkflowRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(0);

    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);

    try {
      $action->run(new StepRunArgs($workflow, $run, $step, $this->getSubjectEntries($subjects)));
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  private function getSubjects(): array {
    return [
      $this->segmentSubject,
      $this->subscriberSubject,
    ];
  }

  private function getSubjectData(SubscriberEntity $subscriber, SegmentEntity $segment): array {
    return [
      new Subject('mailpoet:segment', ['segment_id' => $segment->getId()]),
      new Subject('mailpoet:subscriber', ['subscriber_id'=> $subscriber->getId()]),
    ];
  }

  private function getSubjectEntries(array $subjects): array {
    $segmentData = array_filter($subjects, function (Subject $subject) {
      return $subject->getKey() === 'mailpoet:segment';
    });
    $subscriberData = array_filter($subjects, function (Subject $subject) {
      return $subject->getKey() === 'mailpoet:subscriber';
    });
    return [
      new SubjectEntry($this->segmentSubject, reset($segmentData)),
      new SubjectEntry($this->subscriberSubject, reset($subscriberData)),
    ];
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }

  private function cleanup() {
    $this->truncateEntity(NewsletterEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(ScheduledTaskEntity::class);
    $this->truncateEntity(ScheduledTaskSubscriberEntity::class);
    $this->truncateEntity(SendingQueueEntity::class);
  }
}
