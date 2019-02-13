<?php
namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;

if (!defined('ABSPATH')) exit;

class SendingServiceKeyCheck extends KeyCheckWorker {
  const TASK_TYPE = 'sending_service_key_check';

  function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  function checkKey() {
    $mailer_config = Mailer::getMailerConfig();
    $mss_key = $mailer_config['mailpoet_api_key'];
    $result = $this->bridge->checkMSSKey($mss_key);
    $this->bridge->storeMSSKeyAndState($mss_key, $result);
    $this->bridge->updateSubscriberCount($result);
    return $result;
  }
}
