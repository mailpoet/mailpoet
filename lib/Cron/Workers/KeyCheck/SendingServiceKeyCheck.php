<?php

namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class SendingServiceKeyCheck extends KeyCheckWorker {
  const TASK_TYPE = 'sending_service_key_check';

  /** @var SettingsController */
  private $settings;

  public function __construct(SettingsController $settings) {
    $this->settings = $settings;
    parent::__construct();
  }

  public function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  public function checkKey() {
    $mssKey = $this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME)['mailpoet_api_key'];
    $result = $this->bridge->checkMSSKey($mssKey);
    $this->bridge->storeMSSKeyAndState($mssKey, $result);
    $this->bridge->updateSubscriberCount($result);
    return $result;
  }
}
