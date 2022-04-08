<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions\SendWelcomeEmail;

use MailPoet\Automation\Engine\Workflows\ActionInterface;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Segments\SubscribersFinder;

class Action implements ActionInterface {
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

  public function validate(Workflow $workflow, Step $step, array $subjects = []): ValidationResult {
    $result = new ValidationResult();
    if (!isset($step->getArgs()['welcomeEmailId'])) {
      $result->addError('welcomeEmailIdRequired', 'Step arguments did not include a welcomeEmailId.');
    } else {
      $welcomeEmailId = (int)$step->getArgs()['welcomeEmailId'];
      $newsletter = $this->newslettersRepository->findOneById($welcomeEmailId);
      if ($newsletter === null) {
        $result->addError('welcomeEmailNotFound', sprintf("Welcome Email with ID '%s' not found.", $welcomeEmailId));
      } else {
        $type = $newsletter->getType();
        if ($type !== NewsletterEntity::TYPE_WELCOME) {
          $result->addError('newsletterMustBeWelcomeType', sprintf("Newsletter must be a Welcome Email. Actual type for newsletter ID '%s' was '%s'.", $welcomeEmailId, $type));
        } else {
          $result->setNewsletter($newsletter);
        }
      }
    }

    $segmentSubject = $subjects['mailpoet:segment'] ?? null;
    if (!$segmentSubject instanceof SegmentSubject) {
      $result->addError('segmentSubjectRequired', "A 'mailpoet:segment' subject is required.");
    } else {
      $result->setSegmentSubject($segmentSubject);
    }

    $subscriberSubject = $subjects['mailpoet:subscriber'] ?? null;
    if (!$subscriberSubject instanceof SubscriberSubject) {
      $result->addError('subscriberSubjectRequired', "A 'mailpoet:subscriber' subject is required.");
    } else {
      $result->setSubscriberSubject($subscriberSubject);
    }

    return $result;
  }

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void {
    $validationResult = $this->validate($workflow, $step, $workflowRun->getSubjects());

    if (!$validationResult->isValid()) {
      throw InvalidStateException::create()->withErrors($validationResult->getErrors());
    }

    $newsletter = $validationResult->getNewsletter();
    $subscriber = $validationResult->getSubscriberSubject()->getSubscriber();
    if (!$subscriber instanceof SubscriberEntity) {
      throw InvalidStateException::create();
    }

    $segment = $validationResult->getSegmentSubject()->getSegment();
    if (!$segment instanceof SegmentEntity) {
      throw InvalidStateException::create();
    }

    // Maybe unnecessary since findSubscribersInSegments is status-aware
    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create();
    }

    $isSubscribedToSegment = $this->subscribersFinder->findSubscribersInSegments([$subscriber->getId()], [$segment->getId()]) !== [];
    if (!$isSubscribedToSegment) {
      throw InvalidStateException::create();
    }

    $previouslyScheduledNotification = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($newsletter, (int)$subscriber->getId());
    if (!empty($previouslyScheduledNotification)) {
      throw InvalidStateException::create();
    }

    $sendingTask = $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriber->getId());
    if ($sendingTask === null) {
      throw InvalidStateException::create();
    }

    $errors = $sendingTask->getErrors();
    if ($errors) {
      throw InvalidStateException::create()
        ->withMessage(__('There was an error saving the sending task.', 'mailpoet'))
        ->withErrors($errors);
    }
  }
}
