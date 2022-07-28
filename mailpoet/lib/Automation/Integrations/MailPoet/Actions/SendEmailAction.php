<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\Workflow;
use MailPoet\Automation\Engine\Data\WorkflowRun;
use MailPoet\Automation\Engine\Workflows\Action;
use MailPoet\Automation\Engine\Workflows\Subject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\AutomationEmailScheduler;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use Throwable;

class SendEmailAction implements Action {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var AutomationEmailScheduler */
  private $automationEmailScheduler;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    ScheduledTasksRepository $scheduledTasksRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    AutomationEmailScheduler $automationEmailScheduler
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->automationEmailScheduler = $automationEmailScheduler;
  }

  public function getKey(): string {
    return 'mailpoet:send-email';
  }

  public function getName(): string {
    return __('Send email', 'mailpoet');
  }

  public function isValid(array $subjects, Step $step, Workflow $workflow): bool {
    try {
      $this->getEmailForStep($step);
    } catch (InvalidStateException $exception) {
      return false;
    }

    $segmentSubjects = array_filter($subjects, function (Subject $subject) {
      return $subject->getKey() === SegmentSubject::KEY;
    });
    $subscriberSubjects = array_filter($subjects, function (Subject $subject) {
      return $subject->getKey() === SubscriberSubject::KEY;
    });

    return count($segmentSubjects) === 1 && count($subscriberSubjects) === 1;
  }

  public function run(Workflow $workflow, WorkflowRun $workflowRun, Step $step): void {
    $newsletter = $this->getEmailForStep($step);
    $subscriberSubject = $workflowRun->requireSingleSubject(SubscriberSubject::class);
    $subscriber = $subscriberSubject->getSubscriber();

    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      throw InvalidStateException::create()->withMessage(sprintf("Cannot schedule a newsletter for subscriber ID '%s' because their status is '%s'.", $subscriber->getId(), $subscriber->getStatus()));
    }

    $segmentSubject = $workflowRun->requireSingleSubject(SegmentSubject::class);
    $segmentId = $segmentSubject->getSegment()->getId();
    $subscriberSegment = $this->subscriberSegmentRepository->findOneBy([
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

    try {
      $this->automationEmailScheduler->createSendingTask($newsletter, $subscriber);
    } catch (Throwable $e) {
      throw InvalidStateException::create()->withMessage('Could not create sending task.');
    }
  }

  public function saveEmailSettings(Step $step): void {
    if ($step->getKey() !== $this->getKey()) {
      return;
    }

    $args = $step->getArgs();
    if (!isset($args['email_id'])) {
      return;
    }

    $email = $this->getEmailForStep($step);
    $email->setSubject($args['subject'] ?? '');
    $email->setPreheader($args['preheader'] ?? '');
    $email->setSenderName($args['from_name'] ?? '');
    $email->setSenderAddress($args['email'] ?? '');
    $this->newslettersRepository->flush();
  }

  private function getEmailForStep(Step $step): NewsletterEntity {
    $emailId = $step->getArgs()['email_id'] ?? null;
    if (!$emailId) {
      throw InvalidStateException::create();
    }

    $email = $this->newslettersRepository->findOneBy([
      'id' => $emailId,
      'type' => NewsletterEntity::TYPE_AUTOMATION,
    ]);
    if (!$email) {
      throw InvalidStateException::create()->withMessage(sprintf("Automation email with ID '%s' not found.", $emailId));
    }
    return $email;
  }
}
