<?php

namespace MailPoet\Router\Endpoints;

class RouterTestMockEndpoint {
  const ACTION_TEST = 'test';
  public $allowed_actions = array(
    self::ACTION_TEST
  );
  public $data;

  function __construct($data) {
    $this->data = $data;
  }

  function test() {
    return $this->data;
  }
}