<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\ActionValidationResult;
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

  public function validate(Workflow $workflow, WorkflowRun $workflowRun, Step $step): ActionValidationResult {
    $result = new ActionValidationResult();

    if (!isset($step->getArgs()['welcomeEmailId'])) {
      $result->addError(InvalidStateException::create()->withMessage('Step arguments did not include a welcomeEmailId.'));
    } else {
      $welcomeEmailId = (int)$step->getArgs()['welcomeEmailId'];
      $newsletter = $this->newslettersRepository->findOneById($welcomeEmailId);
      if ($newsletter === null) {
        $result->addError(NotFoundException::create()->withMessage(__(sprintf("Welcome Email with ID '%s' not found.", $welcomeEmailId), 'mailpoet')));
      } else {
        $type = $newsletter->getType();
        if ($type !== NewsletterEntity::TYPE_WELCOME) {
          $result->addError(InvalidStateException::create()->withMessage("Newsletter must be a Welcome Email but actual type was '$type'."));
        }
      }
    }

    $segmentSubject = $workflowRun->getSubjects()['mailpoet:segment'] ?? null;
    if (!$segmentSubject instanceof SegmentSubject) {
      $result->addError(InvalidStateException::create()->withMessage(__('No mailpoet:segment subject provided.', 'mailpoet')));
    } else {
      $segment = $segmentSubject->getSegment();
    }

    $subscriberSubject = $workflowRun->getSubjects()['mailpoet:subscriber'] ?? null;
    if (!$subscriberSubject instanceof SubscriberSubject) {
      $result->addError(InvalidStateException::create()->withMessage(__('No mailpoet:subscriber subject provided.', 'mailpoet')));
    } else {
      $subscriber = $subscriberSubject->getSubscriber();
      if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
        $result->addError(InvalidStateException::create()->withMessage(__(sprintf("Cannot send a welcome email to a subscriber with a global subscription status of '%s'.", $subscriber->getStatus()), 'mailpoet')));
      }
    }

    if (!isset($subscriber) || !isset($segment)) {
      return $result;
    }

    $isSubscribed = $this->subscribersFinder->findSubscribersInSegments([$subscriber->getId()], [$segment->getId()]) !== [];
    if (!$isSubscribed) {
      $result->addError(InvalidStateException::create()->withMessage(__(sprintf("Subscriber ID '%s' is not subscribed to segment ID '%s'.", $subscriber->getId(), $segment->getId()), 'mailpoet')));
    }

    if (!isset($newsletter)) {
      return $result;
    }

    $previouslyScheduledNotification = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($newsletter, (int)$subscriber->getId());
    if (!empty($previouslyScheduledNotification)) {
      $result->addError(InvalidStateException::create()->withMessage(__(sprintf("Newsletter with ID '%s' was previously scheduled for subscriber with ID '%s'.", $newsletter->getId(), $subscriber->getId()))));
    }

    if (!$result->hasErrors()) {
      $result->setValidatedParam('welcomeEmail', $newsletter);
      $result->setValidatedParam('subscriberId', $subscriber->getId());
    }

    return $result;
  }

  public function run(ActionValidationResult $actionValidationResult): void {
    if ($actionValidationResult->hasErrors()) {
      // throw the exceptions chained together
    }

    $newsletter = $actionValidationResult->getValidatedParam('welcomeEmail');
    $subscriberId = $actionValidationResult->getValidatedParam('subscriberId');


    $sendingTask = $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriberId);
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
