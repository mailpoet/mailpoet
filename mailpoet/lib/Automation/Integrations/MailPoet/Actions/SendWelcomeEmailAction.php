<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\NotFoundException;
use MailPoet\Segments\SubscribersFinder;

class SendWelcomeEmailAction implements Action {
  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscribersFinder */
  private $subscribersFinder;

  public function __construct(
    WelcomeScheduler $welcomeScheduler,
    NewslettersRepository $newslettersRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    SubscribersFinder $subscribersFinder
  ) {
    $this->welcomeScheduler = $welcomeScheduler;
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->subscribersFinder = $subscribersFinder;
  }

  public function getKey(): string {
    return 'mailpoet:send-welcome-email';
  }

  public function getName(): string {
    return __('Send Welcome Email', 'mailpoet');
  }

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void {
    $subscriberSubject = $workflowRun->getSubjects()['mailpoet:subscriber'] ?? null;
    if (!$subscriberSubject instanceof SubscriberSubject) {
      throw InvalidStateException::create()->withMessage(__('No mailpoet:subscriber subject provided.', 'mailpoet'));
    }

    $segmentSubject = $workflowRun->getSubjects()['mailpoet:segment'] ?? null;
    if (!$segmentSubject instanceof SegmentSubject) {
      throw InvalidStateException::create()->withMessage(__('No mailpoet:segment subject provided.', 'mailpoet'));
    }

    $segment = $segmentSubject->getSegment();
    $subscriber = $subscriberSubject->getSubscriber();

    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create()->withMessage(__(sprintf("Cannot send a welcome email to a subscriber with a global subscription status of '%s'.", $subscriber->getStatus()), 'mailpoet'));
    }

    $isSubscribed = $this->subscribersFinder->findSubscribersInSegments([$subscriber->getId()], [$segment->getId()]) !== [];
    if (!$isSubscribed) {
      throw InvalidStateException::create()->withMessage(__(sprintf("Subscriber ID '%s' is not subscribed to segment ID '%s'.", $subscriber->getId(), $segment->getId()), 'mailpoet'));
    }

    $welcomeEmailId = (int)$step->getArgs()['welcomeEmailId'];
    $newsletter = $this->newslettersRepository->findOneById($welcomeEmailId);
    if ($newsletter === null) {
      throw NotFoundException::create()->withMessage(__(sprintf("Welcome Email with ID '%s' not found.", $welcomeEmailId), 'mailpoet'));
    }

    // This check also occurs in createWelcomeNotificationSendingTask, in which case the method returns null, but
    // that's not the only thing that causes a return value of null, so let's check here so we can craft a more
    // meaningful exception.
    $previouslyScheduledNotification = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($newsletter, (int)$subscriber->getId());
    if (!empty($previouslyScheduledNotification)) {
      throw InvalidStateException::create()->withMessage(__(sprintf("Newsletter with ID '%s' was previously scheduled for subscriber with ID '%s'.", $newsletter->getId(), $subscriber->getId())));
    }

    $sendingTask = $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriber->getId());
    if ($sendingTask === null) {
      // TODO: What exactly does this represent? I think it means the welcome email was configured to be triggered by a segment that has since been deleted. But in the case of this automation it seems like we shouldn't care about how the welcome email is configured. Do we need to be able to create welcome emails that have no explicit trigger segment? Basically something that says "This welcome email can only be triggered via an automation workflow"?
      throw InvalidStateException::create()->withMessage("TBD");
    }

    $errors = $sendingTask->getErrors();
    if ($errors) {
      throw InvalidStateException::create()
        ->withMessage(__('There was an error saving the sending task.', 'mailpoet'))
        ->withErrors($errors);
    }
  }
}
