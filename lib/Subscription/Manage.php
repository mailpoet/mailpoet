<?php
namespace MailPoet\Subscription;
use \MailPoet\Models\Setting;
use \MailPoet\Models\Subscriber;
use \MailPoet\Models\SubscriberSegment;
use \MailPoet\Util\Url;

class Manage {

  static function onSave() {
    $action = (isset($_POST['action']) ? $_POST['action'] : null);
    if($action !== 'mailpoet_subscription_update') {
      Url::redirectBack();
    }

    $reserved_keywords = array('action', 'mailpoet_redirect');
    $subscriber_data = array_diff_key(
      $_POST,
      array_flip($reserved_keywords)
    );

    if(isset($subscriber_data['email'])) {
      if($subscriber_data['email'] !== Pages::DEMO_EMAIL) {
        $subscriber = Subscriber::createOrUpdate($subscriber_data);
        $errors = $subscriber->getErrors();
      }
    }

    // TBD: success/error messages (not present in MP2)
    Url::redirectBack();
  }
}