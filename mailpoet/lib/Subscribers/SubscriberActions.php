<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailResolver;
use MailPoet\Util\Helpers;
use MailPoetVendor\Carbon\Carbon;

class SubscriberActions {
  /**
   * Key in the subscriber's unconfirmed data holding IDs of lists the subscriber
   * left earlier and asked to join again. They are added after confirmation.
   */
  public const SEGMENTS_TO_CONFIRM = 'segments_to_confirm';

  /** @var SettingsController */
  private $settings;

  /** @var NewSubscriberNotificationMailer */
  private $newSubscriberNotificationMailer;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var SubscriberSaveController */
  private $subscriberSaveController;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var ConfirmationEmailResolver */
  private $confirmationEmailResolver;

  public function __construct(
    SettingsController $settings,
    NewSubscriberNotificationMailer $newSubscriberNotificationMailer,
    ConfirmationEmailMailer $confirmationEmailMailer,
    WelcomeScheduler $welcomeScheduler,
    SegmentsRepository $segmentsRepository,
    SubscriberSaveController $subscriberSaveController,
    SubscribersRepository $subscribersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    ConfirmationEmailResolver $confirmationEmailResolver
  ) {
    $this->settings = $settings;
    $this->newSubscriberNotificationMailer = $newSubscriberNotificationMailer;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->subscriberSaveController = $subscriberSaveController;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->confirmationEmailResolver = $confirmationEmailResolver;
  }

  /**
   * Returns SubscriberEntity and associative array with some metadata related to the subscription (e.g. ['confirmationEmailResult' => $exception])
   * @param array $subscriberData Subscriber data (email, first_name, last_name, etc.)
   * @param array $segmentIds IDs of segments to subscribe to
   * @return array{0: SubscriberEntity, 1: array{confirmationEmailResult: bool|\Exception, error?: string}}
   */
  public function subscribe($subscriberData = [], $segmentIds = []): array {
    // filter out keys from the subscriber_data array
    // that should not be editable when subscribing
    $subscriberData = $this->subscriberSaveController->filterOutReservedColumns($subscriberData);
    unset($subscriberData[self::SEGMENTS_TO_CONFIRM]);

    $signupConfirmationEnabled = (bool)$this->settings->get(
      'signup_confirmation.enabled'
    );

    $subscriberData['subscribed_ip'] = Helpers::getIP();

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $subscriberData['email']]);
    $previousStatus = $subscriber instanceof SubscriberEntity ? $subscriber->getStatus() : null;
    $wasAlreadySubscribed = $previousStatus === SubscriberEntity::STATUS_SUBSCRIBED;
    $isUnconfirmedResubscription = (
      $subscriber instanceof SubscriberEntity
      && $subscriber->getStatus() === SubscriberEntity::STATUS_UNCONFIRMED
    );
    $segmentIdsToConfirm = [];
    if (!$subscriber && !isset($subscriberData['source'])) {
      $subscriberData['source'] = Source::FORM;
    }

    if (!$subscriber || !$signupConfirmationEnabled) {
      // create new subscriber or update if no confirmation is required
      $subscriber = $this->subscriberSaveController->createOrUpdate($subscriberData, $subscriber);
      // custom fields should use the same approach as the subscriber main data that means to wait on confirmation
      $this->subscriberSaveController->updateCustomFields($subscriberData, $subscriber);
    } else if ($wasAlreadySubscribed) {
      // New lists are added right away, lists the subscriber left earlier only after confirmation
      $segmentIdsToConfirm = $this->getLeftSegmentIds($subscriber, $segmentIds);
      $previousSegmentIdsToConfirm = $this->getSegmentIdsToConfirm($subscriber);
      if ($segmentIdsToConfirm && !$previousSegmentIdsToConfirm) {
        // Emails sent for the original sign-up don't count towards the limit for this confirmation
        $subscriber->setConfirmationsCount(0);
      }
      $pendingSegmentIds = $this->getLeftSegmentIds($subscriber, array_merge($segmentIdsToConfirm, $previousSegmentIdsToConfirm));
      $subscriber->setUnconfirmedData(
        $pendingSegmentIds ? (string)json_encode([self::SEGMENTS_TO_CONFIRM => $pendingSegmentIds]) : null
      );
    } else {
      // store subscriber data to be updated after confirmation
      $unconfirmedData = $this->subscriberSaveController->filterOutReservedColumns($subscriberData);
      $unconfirmedData = json_encode($unconfirmedData);
      $subscriber->setUnconfirmedData($unconfirmedData ?: null);
    }

    // restore trashed subscriber
    if ($subscriber->getDeletedAt()) {
      $subscriber->setDeletedAt(null);
    }

    // set status depending on signup confirmation setting
    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      if ($signupConfirmationEnabled === true) {
        $subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
        if ($previousStatus !== SubscriberEntity::STATUS_UNCONFIRMED) {
          $subscriber->setConfirmationsCount(0);
          $subscriber->setLastConfirmationEmailSentAt(null);
          $subscriber->setLastSubscribedAt(Carbon::now()->millisecond(0));
        }
      } else {
        $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
      }
    }

    $this->subscribersRepository->flush();

    $metaData = ['confirmationEmailResult' => false];
    // link subscriber to segments
    $segments = $this->segmentsRepository->findByIds($segmentIds);
    $this->subscriberSegmentRepository->subscribeToSegments(
      $subscriber,
      array_filter($segments, function (SegmentEntity $segment) use ($segmentIdsToConfirm): bool {
        return !in_array((int)$segment->getId(), $segmentIdsToConfirm, true);
      })
    );

    // resolve confirmation settings from segment-level overrides
    [$confirmationEmailId, $confirmationPageId] = $this->confirmationEmailResolver->resolveFromSegments($segments);

    try {
      if (
        $signupConfirmationEnabled
        && ($isUnconfirmedResubscription || $segmentIdsToConfirm)
        && $subscriber->getConfirmationsCount() >= ConfirmationEmailMailer::MAX_CONFIRMATION_EMAILS
      ) {
        $metaData['error'] = $this->getMaxConfirmationEmailsReachedError();
      } else if (!$wasAlreadySubscribed || $segmentIdsToConfirm) {
        $metaData['confirmationEmailResult'] = $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriber, $confirmationEmailId, $confirmationPageId, true);
      }
    } catch (\Exception $e) {
      $metaData['confirmationEmailResult'] = $e;
    }

    // We want to send the notification on subscribe only when signupConfirmation is disabled
    if ($signupConfirmationEnabled === false && $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED) {
      $this->newSubscriberNotificationMailer->send($subscriber, $this->segmentsRepository->findByIds($segmentIds));

      $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
        $subscriber->getId(),
        $segmentIds
      );
    }

    return [$subscriber, $metaData];
  }

  /**
   * @param array<int|string> $segmentIds
   * @return int[] IDs from $segmentIds of lists the subscriber unsubscribed from
   */
  private function getLeftSegmentIds(SubscriberEntity $subscriber, array $segmentIds): array {
    $segmentIds = array_map('intval', $segmentIds);
    $leftSegmentIds = [];
    foreach ($subscriber->getSubscriberSegments() as $subscriberSegment) {
      $segment = $subscriberSegment->getSegment();
      if (
        $segment instanceof SegmentEntity
        && $subscriberSegment->getStatus() === SubscriberEntity::STATUS_UNSUBSCRIBED
        && in_array((int)$segment->getId(), $segmentIds, true)
      ) {
        $leftSegmentIds[] = (int)$segment->getId();
      }
    }
    return $leftSegmentIds;
  }

  /**
   * @return int[]
   */
  private function getSegmentIdsToConfirm(SubscriberEntity $subscriber): array {
    $unconfirmedData = json_decode((string)$subscriber->getUnconfirmedData(), true);
    return is_array($unconfirmedData) ? self::readSegmentIdsToConfirm($unconfirmedData) : [];
  }

  /**
   * @param array<mixed> $unconfirmedData Decoded unconfirmed data of a subscriber
   * @return int[]
   */
  public static function readSegmentIdsToConfirm(array $unconfirmedData): array {
    $segmentIds = $unconfirmedData[self::SEGMENTS_TO_CONFIRM] ?? [];
    if (!is_array($segmentIds)) {
      return [];
    }
    return array_map(function ($segmentId): int {
      return is_numeric($segmentId) ? (int)$segmentId : 0;
    }, $segmentIds);
  }

  private function getMaxConfirmationEmailsReachedError(): string {
    return __('We\'ve already sent you a confirmation email. Please check your inbox or spam folder.', 'mailpoet');
  }
}
