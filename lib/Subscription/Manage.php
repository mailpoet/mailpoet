<?php

namespace MailPoet\Subscription;

use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Url as UrlHelper;

class Manage {

  /** @var UrlHelper */
  private $url_helper;

  /** @var FieldNameObfuscator */
  private $field_name_obfuscator;

  function __construct(UrlHelper $url_helper, FieldNameObfuscator $field_name_obfuscator) {
    $this->url_helper = $url_helper;
    $this->field_name_obfuscator = $field_name_obfuscator;
  }

  function onSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    $token = (isset($_POST['token']) ? $_POST['token'] : null);

    if ($action !== 'mailpoet_subscription_update' || empty($_POST['data'])) {
      $this->url_helper->redirectBack();
    }
    $subscriber_data = $_POST['data'];
    $subscriber_data = $this->field_name_obfuscator->deobfuscateFormPayload($subscriber_data);

    if (!empty($subscriber_data['email']) && Subscriber::verifyToken($subscriber_data['email'], $token)) {
      if ($subscriber_data['email'] !== Pages::DEMO_EMAIL) {
        $subscriber = Subscriber::createOrUpdate($this->filterOutEmptyMandatoryFields($subscriber_data));
        $subscriber->getErrors();
      }
    }

    $this->url_helper->redirectBack();
  }

  private function filterOutEmptyMandatoryFields(array $subscriber_data) {
    $mandatory = $this->getMandatory();
    foreach ($mandatory as $name) {
      if (strlen(trim($subscriber_data[$name])) === 0) {
        unset($subscriber_data[$name]);
      }
    }
    return $subscriber_data;
  }

  private function getMandatory() {
    $mandatory = [];
    $required_custom_fields = CustomField::findMany();
    foreach ($required_custom_fields as $custom_field) {
      if (is_serialized($custom_field->params)) {
        $params = unserialize($custom_field->params);
      } else {
        $params = $custom_field->params;
      }
      if (
        is_array($params)
        && isset($params['required'])
        && $params['required']
      ) {
        $mandatory[] = 'cf_' . $custom_field->id;
      }
    }
    return $mandatory;
  }
}
