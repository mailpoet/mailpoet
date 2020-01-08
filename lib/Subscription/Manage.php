<?php

namespace MailPoet\Subscription;

use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\LinkTokens;
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

  public function __construct(UrlHelper $urlHelper, FieldNameObfuscator $fieldNameObfuscator, LinkTokens $linkTokens, SettingsController $settings) {
    $this->urlHelper = $urlHelper;
    $this->fieldNameObfuscator = $fieldNameObfuscator;
    $this->linkTokens = $linkTokens;
    $this->settings = $settings;
  }

  public function onSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    $token = (isset($_POST['token']) ? $_POST['token'] : null);

    if ($action !== 'mailpoet_subscription_update' || empty($_POST['data'])) {
      $this->urlHelper->redirectBack();
    }
    $subscriberData = $_POST['data'];
    $subscriberData = $this->fieldNameObfuscator->deobfuscateFormPayload($subscriberData);

    if (!empty($subscriberData['email'])) {
      $subscriber = Subscriber::where('email', $subscriberData['email'])->findOne();
      if ($subscriber && $this->linkTokens->verifyToken($subscriber, $token)) {
        if ($subscriberData['email'] !== Pages::DEMO_EMAIL) {
          if (!empty($subscriberData['segments'])) {
            $this->updateSubscriptions($subscriber, $subscriberData['segments']);
            unset($subscriberData['segments']);
          }
          $subscriber = Subscriber::createOrUpdate($this->filterOutEmptyMandatoryFields($subscriberData));
          $subscriber->getErrors();
        }
      }
    }

    $this->urlHelper->redirectBack();
  }

  private function updateSubscriptions(Subscriber $subscriber, array $segmentsIds) {
    $subscriber->withSubscriptions();
    $allowedSegments = $this->settings->get('subscription.segments', false);
    $subscriptionsSegmentsIds = [];
    // Unsubscribe from missing segments
    foreach ($subscriber->subscriptions as $subscription) {
      $segmentId = $subscription['segment_id'];
      $subscriptionsSegmentsIds[] = $segmentId;
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
    // subscribe to new segments
    foreach ($segmentsIds as $segmentId) {
      if (!in_array($segmentId, $subscriptionsSegmentsIds)) {
        SubscriberSegment::createOrUpdate([
          'subscriber_id' => $subscriber->id,
          'segment_id' => $segmentId,
          'status' => Subscriber::STATUS_SUBSCRIBED,
        ]);
      }
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
