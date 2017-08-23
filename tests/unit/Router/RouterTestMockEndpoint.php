<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;

class RouterTestMockEndpoint {
  const ACTION_TEST = 'test';
  public $allowed_actions = array(
    self::ACTION_TEST
  );
  public $data;
  public $permissions = array(
    'global' => AccessControl::NO_ACCESS_RESTRICTION
  );

  function __construct($data) {
    $this->data = $data;
  }

  function test() {
    return $this->data;
  }
}