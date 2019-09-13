<?php
namespace MailPoet\Mailer;

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

  private function makeMetaInfo($email_type,  $subscriber_status, $subscriber_source) {
    return [
      'email_type' => $email_type,
      'subscriber_status' => $subscriber_status,
      'subscriber_source' => $subscriber_source,
    ];
  }
}
