<?php
namespace MailPoet\Cron\Workers\KeyCheck;

use MailPoet\Models\Setting;
use MailPoet\Services\Bridge;

if(!defined('ABSPATH')) exit;

class PremiumKeyCheck extends KeyCheckWorker {
  const TASK_TYPE = 'premium_key_check';

  function checkProcessingRequirements() {
    return Bridge::isPremiumKeySpecified();
  }

  function checkKey() {
    $premium_key = Setting::getValue(Bridge::PREMIUM_KEY_SETTING_NAME);
    $result = $this->bridge->checkPremiumKey($premium_key);
    return $result;
  }
}
