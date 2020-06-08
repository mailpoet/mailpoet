<?php

namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Config\ServicesChecker;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoetVendor\Carbon\Carbon;

class SendingServiceKeyCheck extends KeyCheckWorker {
  const TASK_TYPE = 'sending_service_key_check';

  /** @var SettingsController */
  private $settings;

  /** @var ServicesChecker */
  private $servicesChecker;

  public function __construct(SettingsController $settings, ServicesChecker $servicesChecker) {
    $this->settings = $settings;
    $this->servicesChecker = $servicesChecker;
    parent::__construct();
  }

  public function checkProcessingRequirements() {
    return Bridge::isMPSendingServiceEnabled();
  }

  /**
   * @return \DateTimeInterface|Carbon
   */
  public function getNextRunDate() {
    // when key pending approval, check key sate every hour
    if ($this->servicesChecker->isMailPoetAPIKeyPendingApproval()) {
      $date = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
      return $date->addHour();
    }
    return parent::getNextRunDate();
  }

  public function checkKey() {
    $mssKey = $this->settings->get(Mailer::MAILER_CONFIG_SETTING_NAME)['mailpoet_api_key'];
    $result = $this->bridge->checkMSSKey($mssKey);
    $this->bridge->storeMSSKeyAndState($mssKey, $result);
    $this->bridge->updateSubscriberCount($result);
    return $result;
  }
}
