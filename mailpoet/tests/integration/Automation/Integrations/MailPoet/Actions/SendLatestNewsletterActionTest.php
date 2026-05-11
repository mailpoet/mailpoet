<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Control\StepRunControllerFactory;
use MailPoet\Automation\Engine\Control\StepRunLoggerFactory;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Data\SubjectEntry;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendLatestNewsletterAction;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Scheduler\LatestNewsletterScheduler;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;
use Throwable;

class SendLatestNewsletterActionTest extends \MailPoetTest {
  private SendLatestNewsletterAction $action;

  private Automation $automation;

  private LatestNewsletterScheduler $latestNewsletterScheduler;

  private SegmentSubject $segmentSubject;

  private SubscriberSubject $subscriberSubject;

  public function _before() {
    parent::_before();
    $this->action = $this->diContainer->get(SendLatestNewsletterAction::class);
    $this->automation = new Automation('test-automation', [], new \WP_User());
    $this->latestNewsletterScheduler = $this->diContainer->get(LatestNewsletterScheduler::class);
    $this->segmentSubject = $this->diContainer->get(SegmentSubject::class);
    $this->subscriberSubject = $this->diContainer->get(SubscriberSubject::class);
  }

  public function testItReturnsRequiredSubjects(): void {
    $this->assertSame(['mailpoet:subscriber', 'mailpoet:segment'], $this->action->getSubjectKeys());
  }

  public function testItAllowsEmptyArgs(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, SendLatestNewsletterAction::KEY, [], []);

    $this->action->validate(new StepValidationArgs($this->automation, $step, [
      $this->diContainer->get(SubscriberSubject::class),
      $this->diContainer->get(SegmentSubject::class),
    ]));

    $this->assertSame([], $step->getArgs());
  }

  public function testItRejectsNonEmptyArgsWithGeneralError(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, SendLatestNewsletterAction::KEY, ['email_id' => 1], []);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($this->automation, $step, [
        $this->diContainer->get(SubscriberSubject::class),
        $this->diContainer->get(SegmentSubject::class),
      ]));
    } catch (ValidationException $error) {
      $this->assertArrayHasKey('general', $error->getErrors());
    }

    $this->assertNotNull($error);
  }

  public function testItRequiresSegmentSubject(): void {
    $step = new Step('step-id', Step::TYPE_ACTION, SendLatestNewsletterAction::KEY, [], []);

    $error = null;
    try {
      $this->action->validate(new StepValidationArgs($this->automation, $step, [
        $this->diContainer->get(SubscriberSubject::class),
      ]));
    } catch (ValidationException $error) {
      $this->assertArrayHasKey('general', $error->getErrors());
    }

    $this->assertNotNull($error);
  }

  public function testItSchedulesLatestNewsletterOnFirstRun(): void {
    [$subscriber, $segment, $sourceNewsletter] = $this->createSubscriberAndSourceNewsletter();
    [$automation, $run, $step, $subjects] = $this->createAutomationRunContext($subscriber, $segment);
    [$args, $controller] = $this->createStepRun($automation, $run, $step, $subjects, 1);

    $this->action->run($args, $controller);

    $taskSubscriber = $this->latestNewsletterScheduler->getScheduledTaskSubscriber($sourceNewsletter, $subscriber, $run);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    $task = $taskSubscriber->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    $this->assertSame(ScheduledTaskEntity::STATUS_SCHEDULED, $task->getStatus());
    $this->assertSame([
      'outcome' => 'scheduled',
      'newsletter_id' => $sourceNewsletter->getId(),
    ], $controller->getRunLog()->getLog()->getData());
  }

  public function testItPausesReplayTaskWhenPollingTimesOut(): void {
    [$subscriber, $segment, $sourceNewsletter] = $this->createSubscriberAndSourceNewsletter();
    [$automation, $run, $step, $subjects] = $this->createAutomationRunContext($subscriber, $segment);
    [$args, $controller] = $this->createStepRun($automation, $run, $step, $subjects, 1);
    $this->action->run($args, $controller);

    $taskSubscriber = $this->latestNewsletterScheduler->getScheduledTaskSubscriber($sourceNewsletter, $subscriber, $run);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    $task = $taskSubscriber->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);
    [$timeoutArgs, $timeoutController] = $this->createStepRun($automation, $run, $step, $subjects, 8);

    $this->assertThrowsExceptionWithMessage(
      'Email sending process timed out.',
      function() use ($timeoutArgs, $timeoutController) {
        $this->action->run($timeoutArgs, $timeoutController);
      }
    );
    $this->entityManager->refresh($task);
    $this->entityManager->refresh($taskSubscriber);

    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, $taskSubscriber->getProcessed());
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED, $taskSubscriber->getFailed());
    $this->assertSame('Email sending process timed out.', $taskSubscriber->getError());
  }

  public function testItPausesReplayTaskWhenSubscriberBecomesIneligible(): void {
    [$subscriber, $segment, $sourceNewsletter] = $this->createSubscriberAndSourceNewsletter();
    [$automation, $run, $step, $subjects] = $this->createAutomationRunContext($subscriber, $segment);
    [$args, $controller] = $this->createStepRun($automation, $run, $step, $subjects, 1);
    $this->action->run($args, $controller);

    $taskSubscriber = $this->latestNewsletterScheduler->getScheduledTaskSubscriber($sourceNewsletter, $subscriber, $run);
    $this->assertInstanceOf(ScheduledTaskSubscriberEntity::class, $taskSubscriber);
    $task = $taskSubscriber->getTask();
    $this->assertInstanceOf(ScheduledTaskEntity::class, $task);

    $subscriber->setStatus(SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->entityManager->flush();

    [$pollArgs, $pollController] = $this->createStepRun($automation, $run, $step, $subjects, 2);
    $this->action->run($pollArgs, $pollController);
    $this->entityManager->refresh($task);
    $this->entityManager->refresh($taskSubscriber);

    $this->assertSame(ScheduledTaskEntity::STATUS_PAUSED, $task->getStatus());
    $this->assertSame(ScheduledTaskSubscriberEntity::STATUS_PROCESSED, $taskSubscriber->getProcessed());
    $this->assertSame(ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED, $taskSubscriber->getFailed());
    $this->assertSame('Subscriber is no longer eligible for this email.', $taskSubscriber->getError());
    $this->assertSame('skipped-ineligible-subscriber', $pollController->getRunLog()->getLog()->getData()['outcome']);
  }

  /** @return array{0: SubscriberEntity, 1: SegmentEntity, 2: NewsletterEntity} */
  private function createSubscriberAndSourceNewsletter(): array {
    $segment = (new Segment())->create();
    $subscriber = (new Subscriber())
      ->withStatus(SubscriberEntity::STATUS_SUBSCRIBED)
      ->withSegments([$segment])
      ->create();
    $sourceNewsletter = (new Newsletter())
      ->withSentStatus()
      ->withSegments([$segment])
      ->withSendingQueue(['processed_at' => Carbon::parse('2026-01-02 10:00:00')])
      ->create();

    return [$subscriber, $segment, $sourceNewsletter];
  }

  /**
   * @return array{0: Automation, 1: AutomationRun, 2: Step, 3: array{'mailpoet:segment': Subject, 'mailpoet:subscriber': Subject}}
   */
  private function createAutomationRunContext(SubscriberEntity $subscriber, SegmentEntity $segment): array {
    $step = new Step('step-id', Step::TYPE_ACTION, SendLatestNewsletterAction::KEY, [], []);
    $automation = new Automation('test-automation', [$step->getId() => $step], new \WP_User(), 10);
    $subjects = [
      'mailpoet:segment' => new Subject('mailpoet:segment', ['segment_id' => $segment->getId()]),
      'mailpoet:subscriber' => new Subject('mailpoet:subscriber', ['subscriber_id' => $subscriber->getId()]),
    ];
    $run = new AutomationRun(10, 1, 'mailpoet:someone-subscribes', $subjects, 123);

    return [$automation, $run, $step, $subjects];
  }

  /**
   * @param array{'mailpoet:segment': Subject, 'mailpoet:subscriber': Subject} $subjects
   * @return array{0: StepRunArgs, 1: StepRunController}
   */
  private function createStepRun(Automation $automation, AutomationRun $run, Step $step, array $subjects, int $runNumber): array {
    $args = new StepRunArgs($automation, $run, $step, $this->getSubjectEntries($subjects), $runNumber);
    $logger = $this->diContainer->get(StepRunLoggerFactory::class)->createLogger($run->getId(), $step->getId(), $step->getType(), $runNumber);
    $controller = $this->diContainer->get(StepRunControllerFactory::class)->createController($args, $logger);

    return [$args, $controller];
  }

  /** @param array{'mailpoet:segment': Subject, 'mailpoet:subscriber': Subject} $subjects */
  private function getSubjectEntries(array $subjects): array {
    return [
      new SubjectEntry($this->segmentSubject, $subjects['mailpoet:segment']),
      new SubjectEntry($this->subscriberSubject, $subjects['mailpoet:subscriber']),
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
}
