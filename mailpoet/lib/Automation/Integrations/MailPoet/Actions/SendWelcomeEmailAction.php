<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Step;
use MailPoet\Automation\Engine\Workflows\Workflow;
use MailPoet\Automation\Engine\Workflows\WorkflowRun;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\NotFoundException;
use MailPoet\Subscribers\SubscribersRepository;

class SendWelcomeEmailAction implements Action {
  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    WelcomeScheduler $welcomeScheduler,
    NewslettersRepository $newslettersRepository,
    ScheduledTasksRepository $scheduledTasksRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
  }

  public function getKey(): string {
    return 'mailpoet:sendWelcomeEmail'; // TODO: casing for multi word keys? send-welcome-email? send_welcome_email?
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

    $globalSubscriberStatus = $subscriberSubject->getSubscriberStatusField()->getFactory()();
    if ($globalSubscriberStatus !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create()->withMessage(__(sprintf("Cannot send a welcome email to a subscriber with a global subscription status of '%s'.", $globalSubscriberStatus), 'mailpoet'));
    }

    $segmentName = $segmentSubject->getNameField()->getFactory()();
    $idField = $subscriberSubject->getSubscriberIdField();
    $subscriberId = $idField->getFactory()();
    if ($subscriberId === null) {
      throw NotFoundException::create()->withMessage(__(sprintf("Subscriber with ID '%s' not found.", $subscriberId), 'mailpoet'));
    }
    
    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    // This is for PHPStan, which doesn't understand that retrieving the ID above means the subscriber entity exists
    if (!$subscriber instanceof SubscriberEntity) {
      throw NotFoundException::create()->withMessage(__(sprintf("Subscriber with ID '%s' not found.", $subscriberId), 'mailpoet'));
    }

    // TODO: Are these necessarily "subscribed" status? Maybe there's a better way to check this
    $subscriberSegments = $subscriber->getSegments();
    $subscribedSegmentNames = array_map(function(SegmentEntity $segment) {
      return $segment->getName();
    }, $subscriberSegments);
    
    if (!in_array($segmentName, $subscribedSegmentNames)) {
      throw InvalidStateException::create()->withMessage(__(sprintf("Subscriber with ID '%s' is not subscribed to segment '%s'.", $subscriberId, $segmentName), 'mailpoet'));
    }
    
    $welcomeEmailId = (int)$step->getArgs()['welcomeEmailId'];
    $newsletter = $this->newslettersRepository->findOneById($welcomeEmailId);
    if ($newsletter === null) {
      throw NotFoundException::create()->withMessage(__(sprintf("Welcome Email with ID '%s' not found.", $welcomeEmailId), 'mailpoet'));
    }

    // This check also occurs in createWelcomeNotificationSendingTask, in which case the method returns null, but
    // that's not the only thing that causes a return value of null, so let's check here so we can craft a more
    // meaningful exception.
    $previouslyScheduledNotification = $this->scheduledTasksRepository->findByNewsletterAndSubscriberId($newsletter, $subscriberId);
    if (!empty($previouslyScheduledNotification)) {
      throw InvalidStateException::create()->withMessage(__(sprintf("Newsletter with ID '%s' was previously scheduled for subscriber with ID '%s'.", $newsletter->getId(), $subscriberId)));
    }

    $sendingTask = $this->welcomeScheduler->createWelcomeNotificationSendingTask($newsletter, $subscriberId);
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
