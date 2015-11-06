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

  protected function rsearch($folder, $extensions = array()) {
    $dir = new RecursiveDirectoryIterator($folder);
    $iterator = new RecursiveIteratorIterator($dir);

    $pattern = '/^.+\.('.join($extensions, '|').')$/i';

    $files = new RegexIterator(
      $iterator,
      $pattern,
      RecursiveRegexIterator::GET_MATCH
    );

    $list = array();
    foreach($files as $file) {
      $list[] = $file[0];
    }

    return $list;
  }

  function watch() {
    $css_files = $this->rsearch('assets/css/src/', array('styl'));
    $js_files = $this->rsearch('assets/js/src/', array('js', 'jsx'));

    $this->taskWatch()
      ->monitor($js_files, function() {
        $this->compileJs();
      })
      ->monitor($css_files, function() {
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
    $css_files = array(
      'assets/css/src/admin.styl',
      'assets/css/src/newsletter_editor/newsletter_editor.styl',
      'assets/css/src/public.styl',
      'assets/css/src/rtl.styl'
    );

    $this->_exec(join(' ', array(
      './node_modules/stylus/bin/stylus',
      '--include ./node_modules',
      '--include-css',
      '-u nib',
      join(' ', $css_files),
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
    $this->_exec('vendor/bin/codecept run unit -f '.(($file) ? $file : ''));
  }

  function testJavascript() {
    $this->compileJs();

    $this->_exec(join(' ', array(
      './node_modules/mocha/bin/mocha',
      '-r tests/javascript/mochaTestHelper.js',
      'tests/javascript/testBundles/**/*.js'
    )));
  }

  function testDebug() {
    $this->_exec('vendor/bin/codecept build');
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept run unit --debug');
  }

  function testFailed() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $this->_exec('vendor/bin/codecept run -g failed');
  }

  protected function loadEnv() {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
  }
}
