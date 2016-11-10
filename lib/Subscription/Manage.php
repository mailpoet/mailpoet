<?php
namespace MailPoet\Subscription;
use \MailPoet\Models\Subscriber;
use \MailPoet\Util\Url;

class Manage {

  static function onSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    $token = (isset($_POST['token']) ? $_POST['token'] : null);

    if($action !== 'mailpoet_subscription_update') {
      Url::redirectBack();
    }

    $reserved_keywords = array('action', 'token', 'mailpoet_redirect');
    $subscriber_data = array_diff_key(
      $_POST,
      array_flip($reserved_keywords)
    );

    if(
      isset($subscriber_data['email'])
      &&
      Subscriber::verifyToken($subscriber_data['email'], $token)
    ) {
      if($subscriber_data['email'] !== Pages::DEMO_EMAIL) {
        $subscriber = Subscriber::createOrUpdate($subscriber_data);
        $errors = $subscriber->getErrors();
      }
    }

    // TBD: success/error messages (not present in MP2)
    Url::redirectBack();
  }
}