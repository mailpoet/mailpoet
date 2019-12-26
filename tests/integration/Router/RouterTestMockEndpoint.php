<?php

namespace MailPoet\Router\Endpoints;

use MailPoet\Config\AccessControl;

class RouterTestMockEndpoint {
  const ACTION_TEST = 'test';
  public $allowed_actions = [
    self::ACTION_TEST,
  ];
  public $data;
  public $permissions = [
    'global' => AccessControl::NO_ACCESS_RESTRICTION,
  ];

  public function test($data) {
    return $data;
  }
}
