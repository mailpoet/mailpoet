<?php

namespace MailPoet\Mailer;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Models\Subscriber;

class MetaInfo {
  public function getSendingTestMetaInfo() {
    return $this->makeMetaInfo('sending_test', 'unknown', 'administrator');
  }

  public function getPreviewMetaInfo() {
    return $this->makeMetaInfo('preview', 'unknown', 'administrator');
  }

  public function getStatsNotificationMetaInfo() {
    return $this->makeMetaInfo('email_stats_notification', 'unknown', 'administrator');
  }

  public function getWordPressTransactionalMetaInfo(SubscriberEntity $subscriber = null) {
    return $this->makeMetaInfo(
      'transactional',
      $subscriber ? $subscriber->getStatus() : 'unknown',
      $subscriber ? $subscriber->getSource() : 'unknown'
    );
  }

  public function getConfirmationMetaInfo(SubscriberEntity $subscriber) {
    return $this->makeMetaInfo('confirmation', $subscriber->getStatus(), $subscriber->getSource());
  }

  public function getNewSubscriberNotificationMetaInfo() {
    return $this->makeMetaInfo('new_subscriber_notification', 'unknown', 'administrator');
  }

  public function getNewsletterMetaInfo($newsletter, Subscriber $subscriber) {
    $type = $newsletter->type ?? 'unknown';
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

  private function makeMetaInfo($emailType, $subscriberStatus, $subscriberSource) {
    return [
      'email_type' => $emailType,
      'subscriber_status' => $subscriberStatus,
      'subscriber_source' => $subscriberSource ?: 'unknown',
    ];
  }
}
