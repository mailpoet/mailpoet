<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Actions;

use Codeception\Stub\Expected;
use MailPoet\Automation\Engine\Builder\UpdateAutomationController;
use MailPoet\Automation\Engine\Control\ActionScheduler;
use MailPoet\Automation\Engine\Control\AutomationController;
use MailPoet\Automation\Engine\Control\StepRunControllerFactory;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Exception;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Test\DataFactories\Automation as AutomationFactory;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\ScheduledTask;
use MailPoet\Test\DataFactories\ScheduledTaskSubscriber;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\SendingQueue;
use MailPoet\Test\DataFactories\Subscriber;
use Throwable;

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

  /** @var Automation */
  private $automation;

  public function _before() {
    parent::_before();
    $this->cleanup();

    $this->scheduledTasksRepository = $this->diContainer->get(ScheduledTasksRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->action = $this->diContainer->get(SendEmailAction::class);
    $this->subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
    $this->segmentSubject = $this->diContainer->get(SegmentSubject::class);

    $this->automation = new Automation('test-automation', [], new \WP_User());
  }

  public function _after() {
    parent::_after();
    $this->cleanup();
  }

  public function testItReturnsRequiredSubjects() {
    $this->assertSame(['mailpoet:subscriber'], $this->action->getSubjectKeys());
  }

  public function testItIsNotValidIfStepHasNoEmail(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', [], []);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($this->automation, $step, []));
    } catch (ValidationException $error) {
      $this->assertSame('Automation email not found.', $error->getErrors()['email_id']);
    }
    $this->assertNotNull($error);
  }

  public function testItRequiresAutomationEmailType(): void {
    $newsletter = (new Newsletter())->withPostNotificationsType()->create();
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $newsletter->getId()], []);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($this->automation, $step, []));
      $this->action->validate(new StepValidationArgs($this->automation, $step, []));
    } catch (ValidationException $error) {
      $this->assertSame("Automation email with ID '{$newsletter->getId()}' not found.", $error->getErrors()['email_id']);
    }
    $this->assertNotNull($error);
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
    $automation = new Automation('some-automation', [$step->getId() => $step], new \WP_User(), 1);
    $run = new AutomationRun(1, 1, 'trigger-key', $subjects, 1);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);

    // first run
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 1);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);
    $this->action->run($args, $controller);

    // scheduled task
    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(1);
    $this->assertSame([
      'automation' => ['id' => 1, 'run_id' => 1, 'step_id' => 'step-id', 'run_number' => 1],
    ], $scheduled[0]->getMeta());

    // scheduled task subscriber
    $this->scheduledTasksRepository->refresh($scheduled[0]);
    $scheduledTaskSubscribers = $scheduled[0]->getSubscribers();
    $this->assertCount(1, $scheduledTaskSubscribers);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $scheduledTaskSubscribers[0]);

    // mark email as sent
    $scheduledTaskSubscribers[0]->setProcessed(ScheduledTaskSubscriberEntity::STATUS_PROCESSED);

    // progress — won't throw an exception when the email was sent (= step will be completed)
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 2);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);
    $this->action->run($args, $controller);
  }

  public function testItChecksThatEmailWasSent(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $subjects = $this->getSubjectData($subscriber, $segment);
    $email = (new Newsletter())->withAutomationType()->create();

    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
    $automation = new Automation('some-automation', [$step->getId() => $step], new \WP_User(), 1);
    $run = new AutomationRun(1, 1, 'trigger-key', [], 1);

    // progress run step args
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 2);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);

    // email was never scheduled
    $this->assertThrowsExceptionWithMessage(
      'Email failed to schedule.',
      function() use ($args, $controller) {
        $this->action->run($args, $controller);
      }
    );

    // create scheduled task & subscriber
    $scheduledTask = (new ScheduledTask())->create('sending', ScheduledTaskEntity::STATUS_SCHEDULED);
    $scheduledTask->setMeta(['automation' => ['run_id' => $run->getId(), 'step_id' => $step->getId(), 'run_number' => 1]]);
    (new SendingQueue())->create($scheduledTask, $email);
    $scheduledTaskSubscriber = (new ScheduledTaskSubscriber())->createFailed($scheduledTask, $subscriber, 'Test error');

    // email failed to send
    $this->assertThrowsExceptionWithMessage(
      'Email failed to send. Error: Test error',
      function() use ($args, $controller) {
        $this->action->run($args, $controller);
      }
    );

    // mark subscriber unprocessed
    $scheduledTaskSubscriber->setProcessed(ScheduledTaskSubscriberEntity::STATUS_UNPROCESSED);
    $scheduledTaskSubscriber->setFailed(ScheduledTaskSubscriberEntity::FAIL_STATUS_OK);
    $scheduledTaskSubscriber->setError(null);

    // email was not sent yet, scheduling next progress (no exception)
    $actionScheduler = $this->diContainer->get(ActionScheduler::class);
    $this->assertCount(0, $actionScheduler->getScheduledActions());
    $this->action->run($args, $controller);
    $actions = array_values($actionScheduler->getScheduledActions());
    $this->assertCount(1, $actions);
    $this->assertSame('mailpoet/automation/step', $actions[0]->get_hook());
    $this->assertSame([['automation_run_id' => $run->getId(), 'step_id' => 'step-id', 'run_number' => 3]], $actions[0]->get_args());

    // email was never sent (7th run is the last check after ~1 month)
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 7);
    $this->assertThrowsExceptionWithMessage(
      'Email sending process timed out.',
      function() use ($args, $controller) {
        $this->action->run($args, $controller);
      }
    );
  }

  public function testEmailSentHandlerEnqueuesProgress(): void {
    $action = $this->getServiceWithOverrides(SendEmailAction::class, [
      'automationController' => $this->make(AutomationController::class, [
        'enqueueProgress' => Expected::once(),
      ]),
    ]);

    $action->handleEmailSent([
      'run_id' => 1,
      'step_id' => 'abc',
    ]);
  }

  public function testEmailSentHandlerValidatesData() {
    $this->assertThrowsExceptionWithMessage(
      'Invalid automation step data. Array expected, got: NULL',
      function() {
        $this->action->handleEmailSent(null);
      }
    );

    $this->assertThrowsExceptionWithMessage(
      "Invalid automation step data. Expected 'run_id' to be an integer, got: string",
      function() {
        $this->action->handleEmailSent(['run_id' => 'abc']);
      }
    );

    $this->assertThrowsExceptionWithMessage(
      "Invalid automation step data. Expected 'step_id' to be a string, got: integer",
      function() {
        $this->action->handleEmailSent(['run_id' => 123]);
      }
    );
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
    $automation = new Automation('some-automation', [$step->getId() => $step], new \WP_User());
    $run = new AutomationRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);

    $this->segmentsRepository->bulkDelete([$segment->getId()]);
    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 1);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);

    try {
      $action->run($args, $controller);
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);
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
    $automation = new Automation('some-automation', [$step->getId() => $step], new \WP_User());
    $run = new AutomationRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);

    $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 1);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);

    try {
      $action->run($args, $controller);
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);
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
      $automation = new Automation('some-automation', [$step->getId() => $step], new \WP_User());
      $run = new AutomationRun(1, 1, 'trigger-key', $subjects);

      $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
      verify($scheduled)->arrayCount(0);

      $this->subscribersRepository->bulkDelete([$subscriber->getId()]);
      $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);
      $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 1);
      $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);

      try {
        $action->run($args, $controller);
      } catch (Exception $exception) {
        // The exception itself isn't as important as the outcome
      }

      $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
      verify($scheduled)->arrayCount(0);
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
    $automation = new Automation('some-automation', [$step->getId() => $step], new \WP_User());
    $run = new AutomationRun(1, 1, 'trigger-key', $subjects);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);

    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 1);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);

    try {
      $action->run($args, $controller);
    } catch (Exception $exception) {
      // The exception itself isn't as important as the outcome
    }

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);
  }

  public function testItSchedulesTransactionalEmailsWhenNotSubscribedToSegment(): void {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $subjects = $this->getSubjectData($subscriber, $segment);
    $email = (new Newsletter())->withAutomationTransactionalType()->create();

    $root = new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('trigger')]);
    $trigger = new Step('trigger-id', Step::TYPE_TRIGGER, 'woocommerce:order-status-changed', [], [new NextStep('step-id')]);
    $step = new Step('step-id', Step::TYPE_ACTION, 'step-key', ['email_id' => $email->getId()], []);
    $automation = new Automation(
      'some-automation',
      [
        'root' => $root,
        $trigger->getId() => $trigger,
        $step->getId() => $step,
      ],
      new \WP_User(),
      1
    );
    $run = new AutomationRun(1, 1, 'trigger-key', $subjects, 1);

    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(0);

    $action = ContainerWrapper::getInstance()->get(SendEmailAction::class);
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), 1);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args);

    $action->run($args, $controller);
    $scheduled = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($email, (int)$subscriber->getId());
    verify($scheduled)->arrayCount(1);
  }

  /**
   * @dataProvider dataForTestItStoresAnTransactionalEmail
   *
   * @param Step[] $steps
   */
  public function testItStoresAnTransactionalEmail(array $steps, string $expectedEmailType): void {


    $newsletter = new NewsletterEntity();
    $newsletter->setType(NewsletterEntity::TYPE_AUTOMATION);
    $newsletter->setSubject('subject');
    $newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $newsletterRepository->persist($newsletter);
    $newsletterRepository->flush();

    foreach ($steps as $key => $step) {
      if ($step->getKey() !== SendEmailAction::KEY) {
        continue;
      }

      $steps[$key] = new Step($step->getId(), $step->getType(), $step->getKey(), ['email_id' => $newsletter->getId()], []);
    }

    $newsletterRepository = $this->diContainer->get(NewslettersRepository::class);
    $newsletterRepository->persist($newsletter);
    $newsletterRepository->flush();
    /** @var UpdateAutomationController $controller */
    $controller = $this->diContainer->get(UpdateAutomationController::class);

    $automation = (new AutomationFactory())->withSteps($steps)->create();
    $data = [
      'steps' => array_map(
        function(Step $step): array {
          return $step->toArray();
        },
        $automation->getSteps()
      ),
    ];

    $controller->updateAutomation(
      $automation->getId(),
      $data
    );

    $newsletter = $newsletterRepository->findOneById($newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->assertEquals($expectedEmailType, $newsletter->getType());
  }

  public function dataForTestItStoresAnTransactionalEmail(): array {

    $root = new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep('trigger')]);
    $trigger = new Step('trigger', Step::TYPE_TRIGGER, 'woocommerce:order-status-changed', [], [new NextStep('emailstep')]);
    $emailStep = new Step('emailstep', Step::TYPE_ACTION, SendEmailAction::KEY, [], []);

    $isTransactional = [
      'steps' => [$root, $trigger, $emailStep],
      'expected_type' => NewsletterEntity::TYPE_AUTOMATION_TRANSACTIONAL,
    ];

    $nonTransactionalTrigger = new Step('trigger', Step::TYPE_TRIGGER, 'some-other-trigger', [], [new NextStep('emailstep')]);

    $isNotTransactionalBecauseOfTrigger = [
      'steps' => [$root, $nonTransactionalTrigger, $emailStep],
      'expected_type' => NewsletterEntity::TYPE_AUTOMATION,
    ];

    $positionTrigger = new Step('trigger', Step::TYPE_TRIGGER, 'woocommerce:order-status-changed', [], [new NextStep('action')]);
    $someAction = new Step('action', Step::TYPE_ACTION, 'some-action', [], [new NextStep('emailstep')]);

    $isNotTransactionalBecauseOfPosition = [
      'steps' => [$root, $positionTrigger, $someAction, $emailStep],
      'expected_type' => NewsletterEntity::TYPE_AUTOMATION,
    ];
    return [
      'is_transactional' => $isTransactional,
      'is_not_transactional_because_of_trigger' => $isNotTransactionalBecauseOfTrigger,
      'is_not_transactional_because_of_position' => $isNotTransactionalBecauseOfPosition,
    ];
  }

  private function getSubjectData(SubscriberEntity $subscriber, SegmentEntity $segment): array {
    return [
      new Subject('mailpoet:segment', ['segment_id' => $segment->getId()]),
      new Subject('mailpoet:subscriber', ['subscriber_id' => $subscriber->getId()]),
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

  private function assertThrowsExceptionWithMessage(string $expectedMessage, callable $callback): void {
    $error = null;
    try {
      $callback();
    } catch (Throwable $e) {
      $error = $e->getMessage();
    }
    $this->assertSame($expectedMessage, $error);
  }

  private function cleanup(): void {
    global $wpdb;
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $wpdb->prefix . 'actionscheduler_actions'));
    $wpdb->query($wpdb->prepare('TRUNCATE %i', $wpdb->prefix . 'actionscheduler_claims'));
  }
}
