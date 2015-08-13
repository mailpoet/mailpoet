<?php


class RoboFile extends \Robo\Tasks {
  function install() {
    $this->_exec('./composer.phar install');
    $this->_exec('npm install');
  }

  function update() {
    $this->say(getenv('WP_TEST_URL'));
    $this->_exec('./composer.phar update');
    $this->_exec('npm update');
  }

  function watch() {
    $files = array(
      // global admin styles
      'assets/css/src/admin.styl',
      // rtl specific styles
      'assets/css/src/rtl.styl'
    );

    $command = array(
      './node_modules/stylus/bin/stylus -u',
      ' nib -w' . join(' ', $files) . ' -o assets/css/'
    );
    $this->_exec(join(' ', $command));
  }

  function makepot() {
    $this->_exec('grunt makepot' . ' --gruntfile '
      . __DIR__ . '/tasks/makepot/makepot.js'
      . ' --base_path ' . __DIR__);
  }

  function pushpot() {
    $this->_exec('grunt pushpot' . ' --gruntfile '
      . __DIR__ . '/tasks/makepot/makepot.js'
      . ' --base_path ' . __DIR__);
  }

  function testUnit() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $this->_exec('vendor/bin/codecept run unit');
  }

  function testUnitSingle($unit = null) {
    if (!$unit) {
      throw new Exception("Your need to specify what you want to test (e.g.: test:unit-single models/SubscriberCest)");
    }
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $this->_exec('vendor/bin/codecept run unit ' . $unit);
  }

  function testAcceptance() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $this->taskExec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run acceptance');
  }

  function testAll() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $this->taskexec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run');
  }

  function testDebug() {
    $this->_exec('vendor/bin/codecept build');
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept run unit --debug');
  }

  protected function loadEnv() {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();

    $this->taskWriteToFile('tests/acceptance.suite.yml')
      ->textFromFile('tests/acceptance.suite.src')
      ->run();

    $this->taskReplaceInFile('tests/acceptance.suite.yml')
      ->regex("/url.*/")
      ->to('url: ' . "'" . getenv('WP_TEST_URL') . "'")
      ->run();
  }
}
