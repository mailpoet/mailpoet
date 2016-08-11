<?php
namespace MailPoet\API\Endpoints;
use \MailPoet\API\Endpoint as APIEndpoint;
use \MailPoet\API\Error as APIError;

use MailPoet\Cron\CronHelper;
use MailPoet\Models\Setting;

if(!defined('ABSPATH')) exit;

class Cron extends APIEndpoint {
  function getStatus() {
    $daemon = Setting::getValue(CronHelper::DAEMON_SETTING, false);

    if($daemon === false) {
      return $this->errorResponse(array(
        APIError::NOT_FOUND => __('Cron daemon is not running.')
      ));
    } else {
      return $this->successResponse($daemon);
    }
  }
}