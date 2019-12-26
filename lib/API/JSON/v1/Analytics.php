<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\Analytics\Reporter;
use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;

class Analytics extends APIEndpoint {

  /** @var Reporter */
  private $reporter;

  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  public function __construct(Reporter $reporter) {
    $this->reporter = $reporter;
  }

  public function getTrackingData() {
      return $this->successResponse($this->reporter->getTrackingData());
  }
}
