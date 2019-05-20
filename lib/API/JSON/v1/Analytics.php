<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\Analytics\Reporter;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;

if (!defined('ABSPATH')) exit;

class Analytics extends APIEndpoint {

  /** @var Reporter */
  private $reporter;

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  function __construct(Reporter $reporter) {
    $this->reporter = $reporter;
  }

  function getTrackingData() {
      return $this->successResponse($this->reporter->getTrackingData());
  }
}
