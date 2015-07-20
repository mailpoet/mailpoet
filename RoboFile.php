<?php
class RoboFile extends \Robo\Tasks {
  function install() {
    $this->_exec('./composer.phar install');
    $this->_exec('npm install');
  }

  function update() {
    $this->_exec('./composer.phar update');
    $this->_exec('npm update');
  }

  function testUnit() {
    $this->_exec('vendor/bin/codecept run unit');
  }

  function testAcceptanceConfig() {
    // create config file from sample unless a config file alread exists
    return $this->_copy(
      'tests/acceptance.suite.yml.sample',
      'tests/acceptance.suite.yml',
      true
    );
  }

  function testAcceptance() {
    if($this->testAcceptanceConfig()) {
      $this
        ->taskExec('phantomjs --webdriver=4444')
        ->background()
        ->run();
      sleep(2);
      $this->_exec('vendor/bin/codecept run acceptance');
    }
  }

  function testAll() {
    $this
      ->taskexec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run');
  }

  function watch() {
    $this->_exec('./node_modules/stylus/bin/stylus -u nib -w assets/css/src/admin.styl -o assets/css/');
  }
}
