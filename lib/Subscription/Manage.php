<?php

namespace MailPoet\Subscription;

use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
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
          $subscriberData = $this->addHiddenSegments($subscriber, $subscriberData);
          $subscriber = Subscriber::createOrUpdate($this->filterOutEmptyMandatoryFields($subscriberData));
          $subscriber->getErrors();
        }
      }
    }

    $this->urlHelper->redirectBack();
  }

  private function addHiddenSegments(Subscriber $subscriber, array $data) {
    $shownSegments = $this->settings->get('subscription.segments');
    if (empty($shownSegments)) {
      return $data;
    }
    if (empty($data['segments'])) $data['segments'] = [];
    $subscriber->withSubscriptions();
    foreach ($subscriber->subscriptions as $subscription) {
      if ($subscription['status'] === Subscriber::STATUS_SUBSCRIBED && !in_array($subscription['segment_id'], $shownSegments)) {
        $data['segments'][] = $subscription['segment_id'];
      }
    }
    return $data;
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
