<?php
class RoboFile extends \Robo\Tasks {
  function update() {
    $this->_exec('./composer.phar update');
  }

  function testUnit() {
    $this->_exec('vendor/bin/codecept run unit');
  }

  function testAcceptance() {
    $this
      ->taskExec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run acceptance');
  }

  function testAll() {
    $this
      ->taskExec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run');
  }
}
