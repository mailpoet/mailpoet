<?php

namespace MailPoet\Test\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendWelcomeEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Exception;
use MailPoet\Models\Subscriber as SubscriberModel;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;

class SendWelcomeEmailActionTest extends \MailPoetTest {

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function _before() {
    parent::_before();
    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
  }

  public function testSendingTaskQueuedForHappyPath() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $run = new WorkflowRun(1, 'some-trigger', [
      'mailpoet:subscriber' => $this->getSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getSegmentSubject($segment),
    ]);

    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);

    /** @var SendWelcomeEmailAction $action */
    $action = $this->diContainer->get(SendWelcomeEmailAction::class);
    $action->run($workflow, $run, $step);
    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(1);
  }

  public function testNoSendingTaskQueuedForGloballyUnconfirmedSubscriber() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNCONFIRMED)
      ->withSegments([$segment])
      ->create();

    $run = new WorkflowRun(1, 'some-trigger', [
      'mailpoet:subscriber' => $this->getSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getSegmentSubject($segment),
    ]);

    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);

    /** @var SendWelcomeEmailAction $action */
    $action = $this->diContainer->get(SendWelcomeEmailAction::class);

    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $e) {
      // We don't care about the exception, just the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testNoSendingTaskQueuedIfSubscriberNoLongerSubscribedToSegment() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $run = new WorkflowRun(1, 'some-trigger', [
      'mailpoet:subscriber' => $this->getSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getSegmentSubject($segment),
    ]);

    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);

    /** @var SendWelcomeEmailAction $action */
    $action = $this->diContainer->get(SendWelcomeEmailAction::class);

    $subscriberModel = SubscriberModel::findOne($subscriber->getId());
    SubscriberSegment::unsubscribeFromSegments($subscriberModel, [$segment->getId()]);

    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $e) {
      // We don't care about the exception, just the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testItDoesNotScheduleAnythingIfSubscriberHasBeenDeleted() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $run = new WorkflowRun(1, 'some-trigger', [
      'mailpoet:subscriber' => $this->getSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getSegmentSubject($segment),
    ]);

    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);

    /** @var SendWelcomeEmailAction $action */
    $action = $this->diContainer->get(SendWelcomeEmailAction::class);

    ContainerWrapper::getInstance()->get(SubscribersRepository::class)->bulkDelete([$subscriber->getId()]);

    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $e) {
      // We don't care about the exception, just the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testItDoesNotScheduleAnythingIfSegmentHasBeenDeleted() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $run = new WorkflowRun(1, 'some-trigger', [
      'mailpoet:subscriber' => $this->getSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getSegmentSubject($segment),
    ]);

    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);

    /** @var SendWelcomeEmailAction $action */
    $action = $this->diContainer->get(SendWelcomeEmailAction::class);

    ContainerWrapper::getInstance()->get(SegmentsRepository::class)->bulkDelete([$segment->getId()]);
    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $e) {
      // We don't care about the exception, just the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(0);
  }

  public function testItDoesNotScheduleADuplicateIfRunAgain() {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();

    $run = new WorkflowRun(1, 'some-trigger', [
      'mailpoet:subscriber' => $this->getSubscriberSubject($subscriber),
      'mailpoet:segment' => $this->getSegmentSubject($segment),
    ]);

    $welcomeEmail = (new Newsletter())->withWelcomeTypeForSegment($segment->getId())->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', null, ['welcomeEmailId' => $welcomeEmail->getId()]);
    $workflow = new Workflow('some-workflow', [$step]);

    /** @var SendWelcomeEmailAction $action */
    $action = $this->diContainer->get(SendWelcomeEmailAction::class);
    $action->run($workflow, $run, $step);
    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(1);
    try {
      $action->run($workflow, $run, $step);
    } catch (Exception $e) {
      // We don't care about the exception, just the outcome
    }
    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($welcomeEmail, (int)$subscriber->getId());
    expect($scheduled)->count(1);
  }

  private function getSubscriberSubject(SubscriberEntity $subscriber): SubscriberSubject {
    $subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
    $subscriberSubject->load(['subscriber_id' => $subscriber->getId()]);

    return $subscriberSubject;
  }

  private function getSegmentSubject(SegmentEntity $segment): SegmentSubject {
    /** @var SegmentSubject $segmentSubject */
    $segmentSubject = $this->diContainer->get(SegmentSubject::class);
    $segmentSubject->load(['segment_id' => $segment->getId()]);

    return $segmentSubject;
  }
}
