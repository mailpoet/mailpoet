<?php
namespace MailPoet\Mailer;

class MetaInfo {
  function getSendingTestMetaInfo() {
    return $this->makeMetaInfo('sending_test', 'unknown', 'administrator');
  }

  private function makeMetaInfo($email_type,  $subscriber_status, $subscriber_source) {
    return [
      'email_type' => $email_type,
      'subscriber_status' => $subscriber_status,
      'subscriber_source' => $subscriber_source,
    ];
  }
}
