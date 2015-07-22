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
    $command = array(
      './node_modules/stylus/bin/stylus -u',
      'nib -w assets/css/src/admin.styl',
      '-o assets/css/'
    );
    $this->_exec(join(' ', $command));
  }

  function testUnit() {
    $this->_exec('vendor/bin/codecept run unit');
  }

  function testAcceptance() {
    $this->loadEnv();
    $this
      ->taskExec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run acceptance');
  }

  function testAll() {
    $this->loadEnv();
    $this
      ->taskexec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run');
  }

  function loadEnv() {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();

    $this
      ->taskWriteToFile('tests/acceptance.suite.yml')
      ->textFromFile('tests/acceptance.suite.src')
      ->run();

    $this
      ->taskReplaceInFile('tests/acceptance.suite.yml')
      ->regex("/url.*/")
      ->to('url: ' . "'" . getenv('WP_TEST_URL'). "'")
      ->run();
  }
}
