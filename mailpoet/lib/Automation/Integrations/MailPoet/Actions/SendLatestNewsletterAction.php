<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Actions;

use MailPoet\Automation\Engine\Control\StepRunController;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Exceptions\NotFoundException;
use MailPoet\Automation\Engine\Integration\Action;
use MailPoet\Automation\Engine\Integration\ValidationException;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SegmentPayload;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SegmentSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\InvalidStateException;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Scheduler\LatestNewsletterScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use Throwable;

class SendLatestNewsletterAction implements Action {
  public const KEY = 'mailpoet:send-latest-newsletter';

  private const POLL_INTERVALS = [
    5 * MINUTE_IN_SECONDS,
    10 * MINUTE_IN_SECONDS,
    45 * MINUTE_IN_SECONDS,
    4 * HOUR_IN_SECONDS,
    19 * HOUR_IN_SECONDS,
    4 * DAY_IN_SECONDS,
    25 * DAY_IN_SECONDS,
  ];

  private const OPTIN_RETRY_INTERVALS = [
    1 * MINUTE_IN_SECONDS,
    5 * MINUTE_IN_SECONDS,
    20 * MINUTE_IN_SECONDS,
    1 * HOUR_IN_SECONDS,
    12 * HOUR_IN_SECONDS,
    1 * DAY_IN_SECONDS,
  ];

  private const WAIT_OPTIN = 'wait_optin';
  private const OPTIN_RETRIES = 'optin_retries';
  private const OUTCOME = 'outcome';
  private const NEWSLETTER_ID = 'newsletter_id';

  private SubscribersRepository $subscribersRepository;

  private SubscriberSegmentRepository $subscriberSegmentRepository;

  private NewslettersRepository $newslettersRepository;

  private SegmentsRepository $segmentsRepository;

  private LatestNewsletterScheduler $latestNewsletterScheduler;

  public function __construct(
    SubscribersRepository $subscribersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    NewslettersRepository $newslettersRepository,
    SegmentsRepository $segmentsRepository,
    LatestNewsletterScheduler $latestNewsletterScheduler
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->latestNewsletterScheduler = $latestNewsletterScheduler;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    // translators: automation action title
    return __('Send latest newsletter', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([])
      ->disableAdditionalProperties()
      ->maxProperties(0);
  }

  public function getSubjectKeys(): array {
    return [
      SubscriberSubject::KEY,
      SegmentSubject::KEY,
    ];
  }

  public function validate(StepValidationArgs $args): void {
    if ($args->getStep()->getArgs() !== []) {
      throw ValidationException::create()
        ->withMessage(__('This action does not accept configuration.', 'mailpoet'))
        ->withError('general', __('Send latest newsletter does not accept any configuration fields.', 'mailpoet'));
    }

    try {
      $args->getSingleSubject(SubscriberSubject::KEY);
      $args->getSingleSubject(SegmentSubject::KEY);
    } catch (NotFoundException $e) {
      throw ValidationException::create()
        ->withMessage(__('This action needs a trigger list.', 'mailpoet'))
        ->withError('general', __('This action needs a trigger list, such as "Someone subscribes".', 'mailpoet'));
    }
  }

  public function run(StepRunArgs $args, StepRunController $controller): void {
    [$subscriber, $segmentId] = $this->getSubscriberAndSegment($args);
    $state = null;

    if ($args->isFirstRun()) {
      if ($this->isSubscriberIneligible($subscriber, $segmentId)) {
        $this->saveOutcome($controller, 'skipped-ineligible-subscriber');
        return;
      }

      if ($subscriber->getStatus() === SubscriberEntity::STATUS_UNCONFIRMED) {
        $controller->getRunLog()->saveLogData([self::WAIT_OPTIN => 1]);
        $this->rerunLater($args->getRunNumber(), $controller, true);
        return;
      }

      if (!$this->scheduleLatestNewsletter($args, $controller, $subscriber, $segmentId)) {
        return;
      }
    } else {
      $state = $this->getRunLogData($controller);
      if (($state[self::WAIT_OPTIN] ?? 0) === 1) {
        if ($subscriber->getStatus() === SubscriberEntity::STATUS_UNCONFIRMED) {
          $this->rerunLater($args->getRunNumber(), $controller, true);
          return;
        }

        if ($this->isSubscriberIneligible($subscriber, $segmentId)) {
          $this->saveOutcome($controller, 'skipped-ineligible-subscriber');
          return;
        }

        $controller->getRunLog()->saveLogData([
          self::WAIT_OPTIN => 0,
          self::OPTIN_RETRIES => $args->getRunNumber(),
        ]);
        if (!$this->scheduleLatestNewsletter($args, $controller, $subscriber, $segmentId)) {
          return;
        }
      }

      $success = $this->checkSendingStatus($args, $controller, $subscriber, $segmentId);
      if ($success) {
        return;
      }
    }

    $runNumber = $args->getRunNumber();
    $state = $state ?? $this->getRunLogData($controller);
    $optinRetryCount = $state[self::OPTIN_RETRIES] ?? 0;
    $runNumber -= $optinRetryCount;
    $this->rerunLater($runNumber, $controller, false);
  }

  private function scheduleLatestNewsletter(StepRunArgs $args, StepRunController $controller, SubscriberEntity $subscriber, int $segmentId): bool {
    try {
      $result = $this->latestNewsletterScheduler->schedule($subscriber, $segmentId, $this->getAutomationMeta($args));
    } catch (Throwable $e) {
      throw InvalidStateException::create()->withMessage(__('Could not create sending task.', 'mailpoet'));
    }

    $newsletter = $result['newsletter'];
    $newsletterId = $newsletter instanceof NewsletterEntity ? $newsletter->getId() : null;
    $this->saveOutcome($controller, $result['outcome'], $newsletterId);

    if ($result['outcome'] === LatestNewsletterScheduler::OUTCOME_SCHEDULED) {
      return true;
    }
    return false;
  }

  private function checkSendingStatus(StepRunArgs $args, StepRunController $controller, SubscriberEntity $subscriber, int $segmentId): bool {
    $state = $this->getRunLogData($controller);
    $newsletterId = (int)($state[self::NEWSLETTER_ID] ?? 0);
    if (!$newsletterId) {
      return true;
    }

    $newsletter = $this->latestNewsletterScheduler->getScheduledTaskSubscriber(
      $this->getNewsletter($newsletterId),
      $subscriber,
      $args->getAutomationRun()
    );
    if (!$newsletter) {
      if ($this->isSubscriberIneligible($subscriber, $segmentId)) {
        $this->saveOutcome($controller, 'skipped-ineligible-subscriber', $newsletterId);
        return true;
      }
      throw InvalidStateException::create()->withMessage(__('Email failed to schedule.', 'mailpoet'));
    }

    if ($newsletter->getFailed() === ScheduledTaskSubscriberEntity::FAIL_STATUS_FAILED) {
      throw InvalidStateException::create()->withMessage(
        // translators: %s is the error message.
        sprintf(__('Email failed to send. Error: %s', 'mailpoet'), $newsletter->getError() ?: 'Unknown error')
      );
    }

    $wasSent = $newsletter->getProcessed() === ScheduledTaskSubscriberEntity::STATUS_PROCESSED;
    if ($wasSent) {
      $this->saveOutcome($controller, 'sent', $newsletterId);
      return true;
    }

    if ($this->isSubscriberIneligible($subscriber, $segmentId)) {
      $this->latestNewsletterScheduler->saveErrorAndPause(
        $newsletter,
        __('Subscriber is no longer eligible for this email.', 'mailpoet')
      );
      $this->saveOutcome($controller, 'skipped-ineligible-subscriber', $newsletterId);
      return true;
    }

    $isLastRun = $args->getRunNumber() >= 1 + count(self::POLL_INTERVALS);
    if (!$isLastRun) {
      $this->saveOutcome($controller, 'polling', $newsletterId);
      return false;
    }

    $error = __('Email sending process timed out.', 'mailpoet');
    $this->latestNewsletterScheduler->saveErrorAndPause($newsletter, $error);
    throw InvalidStateException::create()->withMessage($error);
  }

  private function getNewsletter(int $newsletterId): NewsletterEntity {
    $newsletter = $this->newslettersRepository->findOneById($newsletterId);
    if (!$newsletter instanceof NewsletterEntity) {
      throw InvalidStateException::create()->withMessage(__('Email failed to schedule.', 'mailpoet'));
    }
    return $newsletter;
  }

  /**
   * @return array{0: SubscriberEntity, 1: int}
   */
  private function getSubscriberAndSegment(StepRunArgs $args): array {
    try {
      $subscriberId = $args->getSinglePayloadByClass(SubscriberPayload::class)->getId();
      $segmentId = $args->getSinglePayloadByClass(SegmentPayload::class)->getId();
    } catch (Throwable $e) {
      throw InvalidStateException::create()->withMessage(__('This action needs a trigger list, such as "Someone subscribes".', 'mailpoet'));
    }

    $subscriber = $this->subscribersRepository->findOneById($subscriberId);
    if (!$subscriber) {
      throw InvalidStateException::create()->withMessage(__('Subscriber was not found.', 'mailpoet'));
    }
    if (!$this->segmentsRepository->findOneById($segmentId)) {
      throw InvalidStateException::create()->withMessage(__('Cannot send the email because the list was not found.', 'mailpoet'));
    }

    return [$subscriber, $segmentId];
  }

  private function isSubscriberIneligible(SubscriberEntity $subscriber, int $segmentId): bool {
    if (
      $subscriber->getDeletedAt() !== null
      || in_array($subscriber->getStatus(), [SubscriberEntity::STATUS_BOUNCED, SubscriberEntity::STATUS_UNSUBSCRIBED], true)
    ) {
      return true;
    }

    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      return false;
    }

    return !$this->subscriberSegmentRepository->findOneBy([
      'subscriber' => $subscriber->getId(),
      'segment' => $segmentId,
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
    ]);
  }

  private function rerunLater(int $runNumber, StepRunController $controller, bool $waitingForOptIn): void {
    if ($waitingForOptIn) {
      if ($runNumber > count(self::OPTIN_RETRY_INTERVALS)) {
        $this->saveOutcome($controller, 'skipped-ineligible-subscriber');
        return;
      }
      $controller->scheduleProgress(time() + self::OPTIN_RETRY_INTERVALS[$runNumber - 1]);
      return;
    }

    $nextInterval = self::POLL_INTERVALS[$runNumber - 1] ?? 0;
    $controller->scheduleProgress(time() + $nextInterval);
  }

  private function getRunLogData(StepRunController $controller): array {
    $runLog = $controller->getRunLog()->getLog();
    return $runLog->getData();
  }

  /** @return array{id: mixed, run_id: mixed, step_id: mixed, run_number: mixed} */
  private function getAutomationMeta(StepRunArgs $args): array {
    return [
      'id' => $args->getAutomation()->getId(),
      'run_id' => $args->getAutomationRun()->getId(),
      'step_id' => $args->getStep()->getId(),
      'run_number' => $args->getRunNumber(),
    ];
  }

  private function saveOutcome(StepRunController $controller, string $outcome, ?int $newsletterId = null): void {
    $data = [self::OUTCOME => $outcome];
    if ($newsletterId) {
      $data[self::NEWSLETTER_ID] = $newsletterId;
    }
    $controller->getRunLog()->saveLogData($data);
  }

  public function onDuplicate(Step $step): Step {
    return $step;
  }
}
