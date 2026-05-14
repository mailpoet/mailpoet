<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscription;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberSaveController;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Url as UrlHelper;
use MailPoetVendor\Doctrine\DBAL\ArrayParameterType;

class Manage {

  /** @var UrlHelper */
  private $urlHelper;

  /** @var FieldNameObfuscator */
  private $fieldNameObfuscator;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var Unsubscribes */
  private $unsubscribesTracker;

  /** @var NewSubscriberNotificationMailer */
  private $newSubscriberNotificationMailer;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SubscriberSaveController */
  private $subscriberSaveController;

  public function __construct(
    UrlHelper $urlHelper,
    FieldNameObfuscator $fieldNameObfuscator,
    LinkTokens $linkTokens,
    Unsubscribes $unsubscribesTracker,
    NewSubscriberNotificationMailer $newSubscriberNotificationMailer,
    WelcomeScheduler $welcomeScheduler,
    CustomFieldsRepository $customFieldsRepository,
    SegmentsRepository $segmentsRepository,
    SubscribersRepository $subscribersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    SubscriberSaveController $subscriberSaveController
  ) {
    $this->urlHelper = $urlHelper;
    $this->fieldNameObfuscator = $fieldNameObfuscator;
    $this->unsubscribesTracker = $unsubscribesTracker;
    $this->linkTokens = $linkTokens;
    $this->newSubscriberNotificationMailer = $newSubscriberNotificationMailer;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->segmentsRepository = $segmentsRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->subscriberSaveController = $subscriberSaveController;
  }

  public function onSave() {
    $action = (isset($_POST['action']) && is_string($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : '');
    $token = (isset($_POST['token']) && is_string($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '');

    if ($action !== 'mailpoet_subscription_update' || empty($_POST['data'])) {
      $this->urlHelper->redirectBack();
    }

    // custom recursive sanitization via sanitizeFormValue()
    //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $subscriberData = $this->sanitizeFormValue(wp_unslash((array)$_POST['data']));
    $subscriberData = $this->fieldNameObfuscator->deobfuscateFormPayload($subscriberData);

    $result = [];
    if (!empty($subscriberData['email'])) {
      $subscriber = $this->subscribersRepository->findOneBy(['email' => $subscriberData['email']]);

      if ($subscriber && $this->linkTokens->verifyToken($subscriber, $token)) {
        if ($subscriberData['email'] !== Pages::DEMO_EMAIL) {
          $shouldTrackUnsubscribe = (
            ($subscriberData['status'] ?? '') === SubscriberEntity::STATUS_UNSUBSCRIBED
            && $subscriber instanceof SubscriberEntity
            && $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED
          );
          $subscriber = $this->subscriberSaveController->createOrUpdate($subscriberData, $subscriber);
          if ($shouldTrackUnsubscribe) {
            $this->unsubscribesTracker->track(
              (int)$subscriber->getId(),
              StatisticsUnsubscribeEntity::SOURCE_MANAGE
            );
          }
          $this->subscriberSaveController->updateCustomFields($this->filterOutEmptyMandatoryFields($subscriberData), $subscriber);
          $this->updateSubscriptions($subscriber, $subscriberData);
        }
      }
      $result = ['success' => true];
    }

    $this->urlHelper->redirectBack($result);
  }

  private function updateSubscriptions(SubscriberEntity $subscriber, array $subscriberData): void {
    if (array_key_exists('segment_choices', $subscriberData)) {
      $this->updateSubscriptionsFromSegmentChoices($subscriber, $subscriberData['segment_choices']);
      return;
    }

    $segmentsIds = $this->getLegacySegmentIds($subscriberData);
    $segments = $this->getVisibleDefaultManageSegmentsByIds($segmentsIds);
    $segmentsIds = array_map('intval', array_keys($segments));

    // Unsubscribe from all other segments already subscribed to
    // but don't change disallowed segments
    foreach ($subscriber->getSubscriberSegments() as $subscriberSegment) {
      $segment = $subscriberSegment->getSegment();
      if (!$segment) {
        continue;
      }

      if (!$this->isVisibleDefaultManageSegment($segment)) {
        continue;
      }
      if (!in_array((int)$segment->getId(), $segmentsIds, true)) {
        $this->subscriberSegmentRepository->createOrUpdate(
          $subscriber,
          $segment,
          SubscriberEntity::STATUS_UNSUBSCRIBED
        );
      }
    }

    $currentSegmentIds = $this->getCurrentSubscribedSegmentIds($subscriber);
    $newSegmentIds = array_diff($segmentsIds, $currentSegmentIds);

    foreach ($segmentsIds as $segmentId) {
      $this->subscriberSegmentRepository->createOrUpdate(
        $subscriber,
        $segments[$segmentId],
        SubscriberEntity::STATUS_SUBSCRIBED
      );
    }

    $this->sendNotificationsForNewSegments($subscriber, $newSegmentIds);
  }

  /**
   * @param mixed $segmentChoices
   */
  private function updateSubscriptionsFromSegmentChoices(SubscriberEntity $subscriber, $segmentChoices): void {
    $choices = $this->getSegmentChoices($segmentChoices);
    $segments = $this->getVisibleDefaultManageSegmentsByIds(array_keys($choices));
    $subscribeIds = [];
    $unsubscribeIds = [];

    foreach ($choices as $segmentId => $choice) {
      if (!isset($segments[$segmentId])) {
        continue;
      }
      if ($choice === 'subscribed') {
        $subscribeIds[] = $segmentId;
      } elseif ($choice === 'unsubscribed') {
        $unsubscribeIds[] = $segmentId;
      }
    }

    $currentSegmentIds = $this->getCurrentSubscribedSegmentIds($subscriber);

    foreach ($unsubscribeIds as $segmentId) {
      $this->subscriberSegmentRepository->createOrUpdate(
        $subscriber,
        $segments[$segmentId],
        SubscriberEntity::STATUS_UNSUBSCRIBED
      );
    }

    foreach ($subscribeIds as $segmentId) {
      $this->subscriberSegmentRepository->createOrUpdate(
        $subscriber,
        $segments[$segmentId],
        SubscriberEntity::STATUS_SUBSCRIBED
      );
    }

    $this->sendNotificationsForNewSegments($subscriber, array_diff($subscribeIds, $currentSegmentIds));
  }

  /**
   * @param mixed $value
   * @return mixed
   */
  private function sanitizeFormValue($value) {
    if (is_array($value)) {
      $sanitized = [];
      foreach ($value as $key => $item) {
        $sanitized[sanitize_text_field((string)$key)] = $this->sanitizeFormValue($item);
      }
      return $sanitized;
    }
    return sanitize_text_field(is_scalar($value) ? (string)$value : '');
  }

  /**
   * @return int[]
   */
  private function getLegacySegmentIds(array $subscriberData): array {
    if (!isset($subscriberData['segments']) || !is_array($subscriberData['segments'])) {
      return [];
    }

    $segmentIds = [];
    foreach ($subscriberData['segments'] as $segmentId) {
      if (!is_scalar($segmentId) || (int)$segmentId <= 0) {
        continue;
      }
      $segmentIds[] = (int)$segmentId;
    }
    return array_values(array_unique($segmentIds));
  }

  /**
   * @param mixed $segmentChoices
   * @return array<int, string>
   */
  private function getSegmentChoices($segmentChoices): array {
    if (!is_array($segmentChoices)) {
      return [];
    }

    $choices = [];
    foreach ($segmentChoices as $segmentId => $choice) {
      if ((int)$segmentId <= 0 || !is_string($choice)) {
        continue;
      }
      if (!in_array($choice, ['subscribed', 'unsubscribed'], true)) {
        continue;
      }
      $choices[(int)$segmentId] = $choice;
    }
    return $choices;
  }

  /**
   * @param int[] $segmentIds
   * @return array<int, SegmentEntity>
   */
  private function getVisibleDefaultManageSegmentsByIds(array $segmentIds): array {
    $segmentIds = array_values(array_unique(array_filter(array_map('intval', $segmentIds))));
    if (!$segmentIds) {
      return [];
    }

    $segments = $this->segmentsRepository->createQueryBuilder('s')
      ->where('s.id IN (:ids)')
      ->andWhere('s.type = :type')
      ->andWhere('s.deletedAt IS NULL')
      ->andWhere('s.displayInManageSubscriptionPage = :displayInManageSubscriptionPage')
      ->setParameter('ids', $segmentIds, ArrayParameterType::INTEGER)
      ->setParameter('type', SegmentEntity::TYPE_DEFAULT)
      ->setParameter('displayInManageSubscriptionPage', true)
      ->getQuery()
      ->getResult();

    $segmentsMap = [];
    foreach ($segments as $segment) {
      if ($segment instanceof SegmentEntity && $segment->getId()) {
        $segmentsMap[(int)$segment->getId()] = $segment;
      }
    }
    return $segmentsMap;
  }

  private function isVisibleDefaultManageSegment(SegmentEntity $segment): bool {
    return (
      $segment->getType() === SegmentEntity::TYPE_DEFAULT
      && $segment->getDeletedAt() === null
      && $segment->getDisplayInManageSubscriptionPage()
    );
  }

  /**
   * @return int[]
   */
  private function getCurrentSubscribedSegmentIds(SubscriberEntity $subscriber): array {
    $subscriberSegments = $this->subscriberSegmentRepository->findBy([
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'subscriber' => $subscriber,
    ]);
    return array_values(array_filter(array_map(function (SubscriberSegmentEntity $subscriberSegment): ?int {
      $segment = $subscriberSegment->getSegment();
      return $segment ? (int)$segment->getId() : null;
    }, $subscriberSegments)));
  }

  /**
   * @param int[] $newSegmentIds
   */
  private function sendNotificationsForNewSegments(SubscriberEntity $subscriber, array $newSegmentIds): void {
    $newSegmentIds = array_values(array_unique(array_map('intval', $newSegmentIds)));
    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED || !$newSegmentIds) {
      return;
    }

    $newSegments = $this->segmentsRepository->findByIds($newSegmentIds);
    $this->newSubscriberNotificationMailer->send($subscriber, $newSegments);
    $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
      $subscriber->getId(),
      $newSegmentIds
    );
  }

  private function filterOutEmptyMandatoryFields(array $subscriberData): array {
    $mandatory = $this->getMandatory();
    foreach ($mandatory as $name) {
      if (!isset($subscriberData[$name])) {
        continue;
      }
      if (is_array($subscriberData[$name]) && count(array_filter($subscriberData[$name])) === 0) {
        unset($subscriberData[$name]);
      }
      if (is_string($subscriberData[$name]) && strlen(trim($subscriberData[$name])) === 0) {
        unset($subscriberData[$name]);
      }
    }
    return $subscriberData;
  }

  /**
   * @return string[]
   */
  private function getMandatory(): array {
    $mandatory = [];
    $requiredCustomFields = $this->customFieldsRepository->findAllActive();
    foreach ($requiredCustomFields as $customField) {
      $params = $customField->getParams();
      if (
        is_array($params)
        && isset($params['required'])
        && $params['required']
      ) {
        $mandatory[] = 'cf_' . $customField->getId();
      }
    }
    return $mandatory;
  }
}
