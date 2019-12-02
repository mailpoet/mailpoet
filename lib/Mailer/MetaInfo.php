<?php

namespace MailPoet\Mailer;

use MailPoet\Models\Newsletter;
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

  function getNewsletterMetaInfo($newsletter, Subscriber $subscriber) {
    $type = 'unknown';
    switch ($newsletter->type) {
      case Newsletter::TYPE_AUTOMATIC:
        $group = isset($newsletter->options['group']) ? $newsletter->options['group'] : 'unknown';
        $event = isset($newsletter->options['event']) ? $newsletter->options['event'] : 'unknown';
        $type = sprintf('automatic_%s_%s', $group, $event);
        break;
      case Newsletter::TYPE_STANDARD:
        $type = 'newsletter';
        break;
      case Newsletter::TYPE_WELCOME:
        $type = 'welcome';
        break;
      case Newsletter::TYPE_NOTIFICATION:
      case Newsletter::TYPE_NOTIFICATION_HISTORY:
        $type = 'post_notification';
        break;
    }
    return $this->makeMetaInfo($type, $subscriber->status, $subscriber->source);
  }

  private function makeMetaInfo($email_type,  $subscriber_status, $subscriber_source) {
    return [
      'email_type' => $email_type,
      'subscriber_status' => $subscriber_status,
      'subscriber_source' => $subscriber_source ?: 'unknown',
    ];
  }
}
