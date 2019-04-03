<?php

namespace MailPoet\Subscription;

use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Models\CustomField;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Url;

class Manage {

  static function onSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    $token = (isset($_POST['token']) ? $_POST['token'] : null);

    if ($action !== 'mailpoet_subscription_update' || empty($_POST['data'])) {
      Url::redirectBack();
    }
    $subscriber_data = $_POST['data'];
    $obfuscator = new FieldNameObfuscator();
    $subscriber_data = $obfuscator->deobfuscateFormPayload($subscriber_data);

    if (!empty($subscriber_data['email']) && Subscriber::verifyToken($subscriber_data['email'], $token)) {
      if ($subscriber_data['email'] !== Pages::DEMO_EMAIL) {
        $subscriber = Subscriber::createOrUpdate(static::filterOutEmptyMandatoryFields($subscriber_data));
        $subscriber->getErrors();
      }
    }

    Url::redirectBack();
  }

  private static function filterOutEmptyMandatoryFields(array $subscriber_data) {
    $mandatory = self::getMandatory();
    foreach ($mandatory as $name) {
      if (strlen(trim($subscriber_data[$name])) === 0) {
        unset($subscriber_data[$name]);
      }
    }
    return $subscriber_data;
  }

  private static function getMandatory() {
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
