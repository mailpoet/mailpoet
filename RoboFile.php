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

  function watchCss() {
    $css_files = $this->rsearch('assets/css/src/', array('styl'));
    $this->taskWatch()
      ->monitor($css_files, function() {
        $this->compileCss();
      })
      ->run();
  }

  function watchJs() {
    $this->_exec('./node_modules/webpack/bin/webpack.js --watch');
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
      'assets/css/src/rtl.styl',
      'assets/css/src/importExport.styl'
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
    $this->_exec('./node_modules/.bin/grunt makepot'.
      ' --gruntfile '.__DIR__.'/tasks/makepot/makepot.js'.
      ' --base_path '.__DIR__
    );
  }

  function pushpot() {
    $this->_exec('./node_modules/.bin/grunt pushpot'.
      ' --gruntfile '.__DIR__.'/tasks/makepot/makepot.js'.
      ' --base_path '.__DIR__
    );
  }

  function testUnit($opts=['file' => null, 'xml' => false]) {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');

    $command = 'vendor/bin/codecept run unit -f '.(($opts['file']) ? $opts['file'] : '');

    if($opts['xml']) {
      $command .= ' --xml';
    }
    $this->_exec($command);
  }

  function testCoverage($opts=['file' => null, 'xml' => false]) {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    $command = join(' ', array(
      'vendor/bin/codecept run',
      (($opts['file']) ? $opts['file'] : ''),
      '--coverage',
      ($opts['xml']) ? '--coverage-xml' : '--coverage-html'
    ));

    if($opts['xml']) {
      $command .= ' --xml';
    }
    $this->_exec($command);
  }

  function testJavascript($xml_output_file = null) {
    $this->compileJs();

    $command = join(' ', array(
      './node_modules/.bin/mocha',
      '-r tests/javascript/mochaTestHelper.js',
      'tests/javascript/testBundles/**/*.js'
    ));

    if(!empty($xml_output_file)) {
      $command .= sprintf(
        ' --reporter xunit --reporter-options output="%s"',
        $xml_output_file
      );
    }

    $this->_exec($command);
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

  function qa() {
    $this->qaLint();
    $this->qaCodeSniffer('all');
  }

  function qaLint() {
    $this->_exec('./tasks/php_lint.sh lib/ tests/ mailpoet.php');
  }

  function qaCodeSniffer($severity='errors') {
    if ($severity === 'all') {
      $severityFlag = '-w';
    } else {
      $severityFlag = '-n';
    }
    $this->_exec(
      './vendor/bin/phpcs '.
      '--standard=./tasks/code_sniffer/MailPoet '.
      '--ignore=./lib/Util/Sudzy/*,./lib/Util/CSS.php,./lib/Util/XLSXWriter.php,'.
      './lib/Config/PopulatorData/Templates/* '.
      'lib/ '.
      $severityFlag
    );
  }

  protected function loadEnv() {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
  }
}
