<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;

class SendWelcomeEmailAction implements Action {
  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscriberSegmentRepository */
  private $subscribersSegmentRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    WelcomeScheduler $welcomeScheduler,
    NewslettersRepository $newslettersRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->welcomeScheduler = $welcomeScheduler;
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->subscribersSegmentRepository = $subscriberSegmentRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function getKey(): string {
    return 'mailpoet:send-welcome-email';
  }

  public function getName(): string {
    return __('Send Welcome Email', 'mailpoet');
  }

  public function hasRequiredSubjects(array $subjects): bool {
    $segmentSubject = $subjects['mailpoet:segment'] ?? null;
    $subscriberSubject = $subjects['mailpoet:subscriber'] ?? null;

    return $segmentSubject instanceof SegmentSubject && $subscriberSubject instanceof SubscriberSubject;
  }

  public function getWelcomeEmailForStep(Step $step): NewsletterEntity {
    if (!isset($step->getArgs()['welcomeEmailId'])) {
      throw InvalidStateException::create();
    }
    $welcomeEmailId = $step->getArgs()['welcomeEmailId'];
    $newsletter = $this->newslettersRepository->findOneById($welcomeEmailId);
    if ($newsletter === null) {
      throw InvalidStateException::create()->withMessage(sprintf("Welcome Email with ID '%s' not found.", $welcomeEmailId));
    }
    $type = $newsletter->getType();
    if ($type !== NewsletterEntity::TYPE_WELCOME) {
      throw InvalidStateException::create()->withMessage(sprintf("Newsletter must be a Welcome Email. Actual type for newsletter ID '%s' was '%s'.", $welcomeEmailId, $type));
    }

    return $newsletter;
  }

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void {
    $newsletter = $this->getWelcomeEmailForStep($step);
    $subscriberSubject = $workflowRun->getSubjects()['mailpoet:subscriber'] ?? null;
    if (!$subscriberSubject instanceof SubscriberSubject) {
      throw InvalidStateException::create()->withMessage('A mailpoet:subscriber subject is required.');
    }

    $subscriberId = $subscriberSubject->getFields()['id']->getValue();
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);

    if (!$subscriber instanceof SubscriberEntity) {
      throw InvalidStateException::create()->withMessage('Could not retrieve subscriber from the subscriber subject.');
    }

    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create()->withMessage(sprintf("Cannot schedule a newsletter for subscriber ID '%s' because their status is '%s'.", $subscriber->getId(), $subscriber->getStatus()));
    }

    $segmentSubject = $workflowRun->getSubjects()['mailpoet:segment'] ?? null;
    if (!$segmentSubject instanceof SegmentSubject) {
      throw InvalidStateException::create()->withMessage('A mailpoet:segment subject is required.');
    }

    $segmentId = $segmentSubject->getFields()['id']->getValue();
    $subscriberSegment = $this->subscribersSegmentRepository->findOneBy([
      'subscriber' => $subscriber,
      'segment' => $segmentId,
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ]);

    if ($subscriberSegment === null) {
      throw InvalidStateException::create()->withMessage(sprintf("Subscriber ID '%s' is not subscribed to segment ID '%s'.", $subscriber->getId(), $segmentId));
    }

    $previouslyScheduledNotification = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($newsletter, (int)$subscriber->getId());
    if (!empty($previouslyScheduledNotification)) {
      throw InvalidStateException::create()->withMessage(sprintf("Subscriber ID '%s' was already scheduled to receive newsletter ID '%s'.", $subscriber->getId(), $newsletter->getId()));
    }

    $sendingTask = $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriber->getId());
    if ($sendingTask === null) {
      throw InvalidStateException::create()->withMessage('Could not create sending task.');
    }

    $errors = $sendingTask->getErrors();
    if ($errors) {
      throw InvalidStateException::create()
        ->withMessage('There was an error saving the sending task.')
        ->withErrors($errors);
    }
  }
}
