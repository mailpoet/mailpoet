<?php

namespace MailPoet\Models;

class ScheduledTaskStub extends ScheduledTask {
  private $store;

  public function __get($name) {
    return $this->store[$name];
  }

  public function __set($name, $value) {
    $this->store[$name] = $value;
  }

  public function save() {
  }
}
