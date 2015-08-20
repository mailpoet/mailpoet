<?php

class RoboFile extends \Robo\Tasks {

  private $css_files = array(
    'assets/css/src/admin.styl',
    'assets/css/src/public.styl',
    'assets/css/src/rtl.styl'
  );

  private $js_files = array(
    'assets/js/src/*.js',
    'assets/js/src/*.jsx',
    'assets/js/src/**/*.js',
    'assets/js/src/**/*.jsx'
  );

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
    $js_files = array();
    array_map(function($path) use(&$js_files) {
      $js_files = array_merge($js_files, glob($path));
    }, $this->js_files);

    $this->taskWatch()
      ->monitor($js_files, function() {
        $this->compileJs();
      })
      ->monitor($this->css_files, function() {
        $this->compileCss();
      })
      ->run();
  }

  function compileAll() {
    $this->compileJs();
    $this->compileCss();
  }

  function compileJs() {
    $this->_exec('./node_modules/webpack/bin/webpack.js');
  }

  function compileCss() {
    $this->_exec(join(' ', array(
      './node_modules/stylus/bin/stylus',
      '--include ./node_modules',
      '--include-css',
      '-u nib',
      join(' ', $this->css_files),
      '-o assets/css/'
    )));
  }

  function makepot() {
    $this->_exec('grunt makepot'.
      ' --gruntfile '.__DIR__.'/tasks/makepot/makepot.js'.
      ' --base_path '.__DIR__
    );
  }

  function pushpot() {
    $this->_exec('grunt pushpot'.
      ' --gruntfile '.__DIR__.'/tasks/makepot/makepot.js'.
      ' --base_path '.__DIR__
    );
  }

  function testUnit($file = null) {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $this->_exec('vendor/bin/codecept run unit '.(($file) ? $file : ''));
  }

  function testAcceptance($file = null) {
    $this->loadEnv();
    $this->compileAll();
    $this->_exec('vendor/bin/codecept build');
    $this
      ->taskExec('phantomjs --webdriver=4444')
      ->background()
      ->run();
    sleep(2);
    $this->_exec('vendor/bin/codecept run acceptance '.(($file) ? $file : ''));
  }

  function testJavascript() {
    $this->compileJs();

    $this->_exec(join(' ', array(
      './node_modules/mocha/bin/mocha',
      '-r tests/javascript/mochaTestHelper.js',
      'tests/javascript/testBundles/**/*.js'
    )));
  }

  function testAll() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $this
      ->taskexec('phantomjs --webdriver=4444')
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
