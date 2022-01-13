<?php

namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Cron\CronWorkerScheduler;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;

class PremiumKeyCheck extends KeyCheckWorker {
  const TASK_TYPE = 'premium_key_check';

  /** @var SettingsController */
  private $settings;

  public function __construct(
    SettingsController $settings,
    CronWorkerScheduler $cronWorkerScheduler
  ) {
    $this->settings = $settings;
    parent::__construct($cronWorkerScheduler);
  }

  public function checkProcessingRequirements() {
    return Bridge::isPremiumKeySpecified();
  }

  public function checkKey() {
    $premiumKey = $this->settings->get(Bridge::PREMIUM_KEY_SETTING_NAME);
    $result = $this->bridge->checkPremiumKey($premiumKey);
    $this->bridge->storePremiumKeyAndState($premiumKey, $result);
    return $result;
  }
}
