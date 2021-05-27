<?php

namespace MailPoet\Subscription;

use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\InvalidStateException;
use MailPoet\Models\CustomField;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\Unsubscribes;
use MailPoet\Subscribers\LinkTokens;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Util\Url as UrlHelper;

class Manage {

  /** @var UrlHelper */
  private $urlHelper;

  /** @var FieldNameObfuscator */
  private $fieldNameObfuscator;

  /** @var LinkTokens */
  private $linkTokens;

  /** @var SettingsController */
  private $settings;

  /** @var Unsubscribes */
  private $unsubscribesTracker;

  /** @var NewSubscriberNotificationMailer */
  private $newSubscriberNotificationMailer;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function __construct(
    UrlHelper $urlHelper,
    FieldNameObfuscator $fieldNameObfuscator,
    LinkTokens $linkTokens,
    Unsubscribes $unsubscribesTracker,
    SettingsController $settings,
    NewSubscriberNotificationMailer $newSubscriberNotificationMailer,
    WelcomeScheduler $welcomeScheduler,
    SubscribersRepository $subscribersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository
  ) {
    $this->urlHelper = $urlHelper;
    $this->fieldNameObfuscator = $fieldNameObfuscator;
    $this->unsubscribesTracker = $unsubscribesTracker;
    $this->linkTokens = $linkTokens;
    $this->settings = $settings;
    $this->newSubscriberNotificationMailer = $newSubscriberNotificationMailer;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
  }

  public function onSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    $token = (isset($_POST['token']) ? $_POST['token'] : null);

    if ($action !== 'mailpoet_subscription_update' || empty($_POST['data'])) {
      $this->urlHelper->redirectBack();
    }
    $subscriberData = $_POST['data'];
    $subscriberData = $this->fieldNameObfuscator->deobfuscateFormPayload($subscriberData);

    $result = [];
    if (!empty($subscriberData['email'])) {
      $subscriber = $this->subscribersRepository->findOneBy(['email' => $subscriberData['email']]);

      if (
        ($subscriberData['status'] === SubscriberEntity::STATUS_UNSUBSCRIBED)
        && ($subscriber instanceof SubscriberEntity)
        && ($subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED)
      ) {
        $this->unsubscribesTracker->track(
          (int)$subscriber->getId(),
          StatisticsUnsubscribeEntity::SOURCE_MANAGE
        );
      }

      if ($subscriber && $this->linkTokens->verifyToken($subscriber, $token)) {
        $subscriberModel = Subscriber::findOne($subscriber->getId());
        if (!$subscriberModel instanceof Subscriber) {
          throw new InvalidStateException();
        }
        if ($subscriberData['email'] !== Pages::DEMO_EMAIL) {
          $this->updateSubscriptions($subscriberModel, $subscriberData);
          unset($subscriberData['segments']);
          $subscriberModel = Subscriber::createOrUpdate($this->filterOutEmptyMandatoryFields($subscriberData));
          $subscriberModel->getErrors();
        }
      }
      $result = ['success' => true];
    }

    $this->urlHelper->redirectBack($result);
  }

  private function updateSubscriptions(Subscriber $subscriber, array $subscriberData) {
    $segmentsIds = [];
    if (isset($subscriberData['segments']) && is_array($subscriberData['segments'])) {
      $segmentsIds = $subscriberData['segments'];
    }
    $subscriber->withSubscriptions();
    $allowedSegments = $this->settings->get('subscription.segments', false);

    // Unsubscribe from all other segments already subscribed to
    // but don't change disallowed segments
    foreach ($subscriber->subscriptions as $subscription) {
      $segmentId = $subscription['segment_id'];
      if ($allowedSegments && !in_array($segmentId, $allowedSegments)) {
        continue;
      }
      if (!in_array($segmentId, $segmentsIds)) {
        SubscriberSegment::createOrUpdate([
          'subscriber_id' => $subscriber->id,
          'segment_id' => $segmentId,
          'status' => Subscriber::STATUS_UNSUBSCRIBED,
        ]);
      }
    }

    // Store new segments for notifications
    $subscriberSegments = $this->subscriberSegmentRepository->findBy([
      'status' => SubscriberEntity::STATUS_SUBSCRIBED,
      'subscriber' => $subscriber->id,
    ]);
    $currentSegmentIds = array_filter(array_map(function (SubscriberSegmentEntity $subscriberSegment): ?string {
      $segment = $subscriberSegment->getSegment();
      return $segment ? (string)$segment->getId() : null;
    }, $subscriberSegments));
    $newSegmentIds = array_diff($segmentsIds, $currentSegmentIds);

    // Allow subscribing only to allowed segments
    if ($allowedSegments) {
      $segmentsIds = array_intersect($segmentsIds, $allowedSegments);
    }
    foreach ($segmentsIds as $segmentId) {
      SubscriberSegment::createOrUpdate([
        'subscriber_id' => $subscriber->id,
        'segment_id' => $segmentId,
        'status' => Subscriber::STATUS_SUBSCRIBED,
      ]);
    }

    if ($subscriber->status === SubscriberEntity::STATUS_SUBSCRIBED && $newSegmentIds) {
      $this->newSubscriberNotificationMailer->send($subscriber, Segment::whereIn('id', $newSegmentIds)->findMany());
      $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
        $subscriber->id,
        $newSegmentIds
      );
    }
  }

  private function filterOutEmptyMandatoryFields(array $subscriberData) {
    $mandatory = $this->getMandatory();
    foreach ($mandatory as $name) {
      if (strlen(trim($subscriberData[$name])) === 0) {
        unset($subscriberData[$name]);
      }
    }
    return $subscriberData;
  }

  private function getMandatory() {
    $mandatory = [];
    $requiredCustomFields = CustomField::findMany();
    foreach ($requiredCustomFields as $customField) {
      if (is_serialized($customField->params)) {
        $params = unserialize($customField->params);
      } else {
        $params = $customField->params;
      }
      if (
        is_array($params)
        && isset($params['required'])
        && $params['required']
      ) {
        $mandatory[] = 'cf_' . $customField->id;
      }
    }
    return $mandatory;
  }
}
