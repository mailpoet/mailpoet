<?php
namespace MailPoet\Subscription;
use MailPoet\Models\Subscriber;
use MailPoet\Util\Url;

class Manage {

  static function onSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    $token = (isset($_POST['token']) ? $_POST['token'] : null);

    if($action !== 'mailpoet_subscription_update' || empty($_POST['data'])) {
      Url::redirectBack();
    }
    $subscriber_data = $_POST['data'];

    if(!empty($subscriber_data['email']) &&
      Subscriber::verifyToken($subscriber_data['email'], $token)
    ) {
      if($subscriber_data['email'] !== Pages::DEMO_EMAIL) {
        $subscriber = Subscriber::createOrUpdate($subscriber_data);
        $errors = $subscriber->getErrors();
      }
    }

    // TODO: success/error messages (not present in MP2)
    Url::redirectBack();
  }
}