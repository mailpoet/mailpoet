<?php
namespace MailPoet\Export;

class Export {
  public function __construct($data) {
    $this->profilerStart = microtime(true);
  }

  function process() {
  }

  function timeExecution() {
    $profilerEnd = microtime(true);
    return ($profilerEnd - $this->profilerStart) / 60;
  }
}