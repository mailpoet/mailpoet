<?php

namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class SendingServiceKeyCheck extends KeyCheckWorker {
  const TASK_TYPE = 'sending_service_key_check';

  /** @var SettingsController */
  private $settings;

  function __construct(SettingsController $settings) {
    $this->settings = $settings;
    parent::__construct();
  }

  function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  function checkKey() {
    $mss_key = $this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME)['mailpoet_api_key'];
    $result = $this->bridge->checkMSSKey($mss_key);
    $this->bridge->storeMSSKeyAndState($mss_key, $result);
    $this->bridge->updateSubscriberCount($result);
    return $result;
  }
}
