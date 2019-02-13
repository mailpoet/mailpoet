<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;

class SubscriberPersonalDataEraser {

  function erase($email) {
    if (empty($email)) {
      return array(
        'items_removed' => false,
        'items_retained' => false,
        'messages' => array(),
        'done' => true,
      );
    }

    $subscriber = Subscriber::findOne(trim($email));
    $item_removed = false;
    $items_retained = true;
    if ($subscriber) {
      $this->eraseCustomFields($subscriber->id());
      $this->anonymizeSubscriberData($subscriber);
      $item_removed = true;
      $items_retained = false;
    }

    return array(
      'items_removed' => $item_removed,
      'items_retained' => $items_retained,
      'messages' => array(),
      'done' => true,
    );
  }

  private function eraseCustomFields($subscriber_id) {
    $custom_fields = SubscriberCustomField::where('subscriber_id', $subscriber_id)->findMany();
    foreach ($custom_fields as $custom_field) {
      $custom_field->value = '';
      $custom_field->save();
    }
  }

  private function anonymizeSubscriberData($subscriber) {
    $subscriber->email = sprintf('deleted-%s@site.invalid', uniqid('', true));
    $subscriber->first_name = 'Anonymous';
    $subscriber->last_name = 'Anonymous';
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->subscribed_ip = '0.0.0.0';
    $subscriber->confirmed_ip = '0.0.0.0';
    $subscriber->save();
  }

}
