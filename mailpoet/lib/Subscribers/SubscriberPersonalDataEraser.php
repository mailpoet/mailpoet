<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;

class SubscriberPersonalDataEraser {
  public function erase($email) {
    if (empty($email)) {
      return [
        'items_removed' => false,
        'items_retained' => false,
        'messages' => [],
        'done' => true,
      ];
    }
    $subscriber = Subscriber::findOne(trim($email));
    $itemRemoved = false;
    $itemsRetained = true;
    if ($subscriber) {
      $this->eraseCustomFields($subscriber->id());
      $this->anonymizeSubscriberData($subscriber);
      $itemRemoved = true;
      $itemsRetained = false;
    }

    return [
      'items_removed' => $itemRemoved,
      'items_retained' => $itemsRetained,
      'messages' => [],
      'done' => true,
    ];
  }

  private function eraseCustomFields($subscriberId) {
    $customFields = SubscriberCustomField::where('subscriber_id', $subscriberId)->findMany();
    foreach ($customFields as $customField) {
      $customField->value = '';
      $customField->save();
    }
  }

  private function anonymizeSubscriberData($subscriber) {
    $subscriber->email = sprintf('deleted-%s@site.invalid', bin2hex(random_bytes(12))); // phpcs:ignore
    $subscriber->firstName = 'Anonymous';
    $subscriber->lastName = 'Anonymous';
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->subscribedIp = '0.0.0.0';
    $subscriber->confirmedIp = '0.0.0.0';
    $subscriber->save();
  }
}
