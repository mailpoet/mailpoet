<?php
namespace MailPoet\Mailer;

use MailPoet\Models\Subscriber;

class MetaInfo {
  function getSendingTestMetaInfo() {
    return $this->makeMetaInfo('sending_test', 'unknown', 'administrator');
  }

  function getPreviewMetaInfo() {
    return $this->makeMetaInfo('preview', 'unknown', 'administrator');
  }

  function getStatsNotificationMetaInfo() {
    return $this->makeMetaInfo('email_stats_notification', 'unknown', 'administrator');
  }

  function getWordPressTransactionalMetaInfo() {
    return $this->makeMetaInfo('transactional', 'unknown', 'administrator');
  }

  function getConfirmationMetaInfo(Subscriber $subscriber) {
    return $this->makeMetaInfo('confirmation', $subscriber->status, $subscriber->source);
  }

  function getNewSubscriberNotificationMetaInfo() {
    return $this->makeMetaInfo('new_subscriber_notification', 'unknown', 'administrator');
  }

  private function makeMetaInfo($email_type,  $subscriber_status, $subscriber_source) {
    return [
      'email_type' => $email_type,
      'subscriber_status' => $subscriber_status,
      'subscriber_source' => $subscriber_source,
    ];
  }
}
