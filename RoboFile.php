<?php

class RoboFile extends \Robo\Tasks {
  function install() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./composer.phar install')
      ->exec('npm install')
      ->run();
  }

  function update() {
    $this->say(getenv('WP_TEST_URL'));

    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./composer.phar update')
      ->exec('npm update')
      ->run();
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

  function compileAll($opts = ['env' => null]) {
    $collection = $this->collectionBuilder();
    $collection->addCode(function() use ($opts) {
      return call_user_func(array($this, 'compileJs'), $opts);
    });
    $collection->addCode(function() use ($opts) {
      return call_user_func(array($this, 'compileCss'), $opts);
    });
    return $collection->run();
  }

  function compileJs($opts = ['env' => null]) {
    $env = ($opts['env']) ?
      sprintf('./node_modules/cross-env/dist/bin/cross-env.js NODE_ENV="%s"', $opts['env']) :
      null;
    return $this->_exec($env . ' ./node_modules/webpack/bin/webpack.js --bail');
  }

  function compileCss($opts = ['env' => null]) {
    // Clean up folder from previous files
    array_map('unlink', glob("assets/css/*.*"));

    $css_files = array(
      'assets/css/src/admin.styl',
      'assets/css/src/newsletter_editor/newsletter_editor.styl',
      'assets/css/src/public.styl',
      'assets/css/src/rtl.styl',
      'assets/css/src/importExport.styl'
    );

    $compilation_result = $this->_exec(join(' ', array(
      './node_modules/stylus/bin/stylus',
      '--include ./node_modules',
      '--include-css',
      '-u nib',
      join(' ', $css_files),
      '-o assets/css/'
    )));

    // Create manifest file
    $manifest = [];
    foreach(glob('assets/css/*.css') as $style) {
      // Hash and rename styles if production environment
      if($opts['env'] === 'production') {
        $hashed_style = sprintf(
          '%s.%s.css',
          pathinfo($style)['filename'],
          substr(md5_file($style), 0, 8)
        );
        $manifest[basename($style)] = $hashed_style;
        rename($style, str_replace(basename($style), $hashed_style, $style));
      } else {
        $manifest[basename($style)] = basename($style);
      }
    }
    file_put_contents('assets/css/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    return $compilation_result;
  }

  function makepot() {
    return $this->_exec('./node_modules/.bin/grunt makepot'.
      ' --gruntfile='.__DIR__.'/tasks/makepot/makepot.js'.
      ' --base_path='.__DIR__
    );
  }

  function pushpot() {
    return $this->collectionBuilder()
      ->addCode(array($this, 'txinit'))
      ->taskExec('tx push -s')
      ->run();
  }

  function packtranslations() {
    return $this->collectionBuilder()
      ->addCode(array($this, 'txinit'))
      ->taskExec('./tasks/pack_translations.sh')
      ->run();
  }

  function txinit() {
    // Define WP_TRANSIFEX_API_TOKEN env. variable
    $this->loadEnv();
    return $this->_exec('./tasks/transifex_init.sh');
  }

  function testUnit($opts=['file' => null, 'xml' => false]) {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build -c codeception.unit.yml');

    $command = 'vendor/bin/codecept run unit -c codeception.unit.yml -f '.(($opts['file']) ? $opts['file'] : '');

    if($opts['xml']) {
      $command .= ' --xml';
    }
    return $this->_exec($command);
  }

  function testCoverage($opts=['file' => null, 'xml' => false]) {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build -c codeception.unit.yml');
    $command = join(' ', array(
      'vendor/bin/codecept run unit -c codeception.unit.yml ',
      (($opts['file']) ? $opts['file'] : ''),
      '--coverage',
      ($opts['xml']) ? '--coverage-xml' : '--coverage-html'
    ));

    if($opts['xml']) {
      $command .= ' --xml';
    }
    return $this->_exec($command);
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

    return $this->_exec($command);
  }

  function testDebug($opts=['file' => null, 'xml' => false]) {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build -c codeception.unit.yml');

    $command = 'vendor/bin/codecept run unit -c codeception.unit.yml --debug -f '.(($opts['file']) ? $opts['file'] : '');

    if($opts['xml']) {
      $command .= ' --xml';
    }
    return $this->_exec($command);
  }

  function testAcceptance() {
    return $this->_exec('COMPOSE_HTTP_TIMEOUT=200 docker-compose run codeception --steps --debug -vvv');
  }

  function testFailed() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build -c codeception.unit.yml');
    return $this->_exec('vendor/bin/codecept run -c codeception.unit.yml -g failed');
  }

  function qa() {
    $collection = $this->collectionBuilder();
    $collection->addCode(array($this, 'qaLint'));
    $collection->addCode(function() {
      return $this->qaCodeSniffer('all');
    });
    $collection->addCode(array($this, 'qaLintJavascript'));
    return $collection->run();
  }

  function qaLint() {
    return $this->_exec('./tasks/php_lint.sh lib/ tests/ mailpoet.php');
  }

  function qaLintJavascript() {
    return $this->_exec('npm run lint');
  }

  function qaCodeSniffer($severity='errors') {
    if ($severity === 'all') {
      $severityFlag = '-w';
    } else {
      $severityFlag = '-n';
    }
    return $this->collectionBuilder()
      ->taskExec(
        './vendor/bin/phpcs '.
        '--standard=./tasks/code_sniffer/MailPoet '.
        '--runtime-set testVersion 5.3-7.0 '.
        '--ignore=./lib/Util/Sudzy/*,./lib/Util/CSS.php,./lib/Util/XLSXWriter.php,'.
        './lib/Util/pQuery/*,./lib/Config/PopulatorData/Templates/* '.
        'lib/ '.
        $severityFlag
      )
      ->taskExec(
        './vendor/bin/phpcs '.
        '--standard=./tasks/code_sniffer/MailPoet '.
        '--runtime-set testVersion 5.4-7.0 '.
        '--ignore=./tests/unit/_bootstrap.php '.
        'tests/unit/ '.
        $severityFlag
      )
      ->run();
  }

  function svnCheckout() {
    $svn_dir = ".mp_svn";

    $collection = $this->collectionBuilder();

    // Clean up the SVN dir for faster shallow checkout
    if(file_exists($svn_dir)) {
      $collection->taskExecStack()
        ->exec('rm -rf ' . $svn_dir);
    }

    $collection->taskFileSystemStack()
        ->mkdir($svn_dir);

    return $collection->taskExecStack()
      ->stopOnFail()
      ->dir($svn_dir)
      ->exec('svn co https://plugins.svn.wordpress.org/mailpoet/ -N .')
      ->exec('svn up trunk')
      ->exec('svn up assets')
      ->run();
  }

  function svnPublish($opts = ['force' => false]) {
    $this->loadWPFunctions();

    $svn_dir = ".mp_svn";
    $plugin_data = get_plugin_data('mailpoet.php', false, false);
    $plugin_version = $plugin_data['Version'];
    $plugin_dist_name = sanitize_title_with_dashes($plugin_data['Name']);
    $plugin_dist_name = explode('-', $plugin_dist_name);
    $plugin_dist_name = $plugin_dist_name[0];
    $plugin_dist_file = $plugin_dist_name . '.zip';
    $this->say('Publishing version: ' . $plugin_version);

    // Sanity checks
    if(!is_readable($plugin_dist_file)) {
      $this->say("Failed to access " . $plugin_dist_file);
      return;
    } elseif(!file_exists($svn_dir . "/.svn/")) {
      $this->say("$svn_dir/.svn/ dir not found, is it a SVN repository?");
      return;
    } elseif(file_exists($svn_dir . "/tags/" . $plugin_version)) {
      $this->say("A SVN tag already exists: " . $plugin_version);
      return;
    }

    $collection = $this->collectionBuilder();

    // Clean up tmp dirs if the previous run was halted
    if(file_exists("$svn_dir/trunk_new") || file_exists("$svn_dir/trunk_old")) {
      $collection->taskFileSystemStack()
        ->stopOnFail()
        ->remove(array("$svn_dir/trunk_new", "$svn_dir/trunk_old"));
    }

    // Extract the distributable zip to tmp trunk dir
    $collection->taskExtract($plugin_dist_file)
      ->to("$svn_dir/trunk_new")
      ->preserveTopDirectory(false);

    // Rename current trunk
    if(file_exists("$svn_dir/trunk")) {
      $collection->taskFileSystemStack()
        ->rename("$svn_dir/trunk", "$svn_dir/trunk_old");
    }

    // Replace old trunk with a new one
    $collection->taskFileSystemStack()
      ->stopOnFail()
      ->rename("$svn_dir/trunk_new", "$svn_dir/trunk")
      ->remove("$svn_dir/trunk_old");

    // Add new repository assets
    $collection->taskFileSystemStack()
      ->mirror('./plugin_repository/assets', "$svn_dir/assets_new");

    // Rename current assets folder
    if(file_exists("$svn_dir/assets")) {
      $collection->taskFileSystemStack()
        ->rename("$svn_dir/assets", "$svn_dir/assets_old");
    }

    // Replace old assets with new ones
    $collection->taskFileSystemStack()
      ->stopOnFail()
      ->rename("$svn_dir/assets_new", "$svn_dir/assets")
      ->remove("$svn_dir/assets_old");

    // Windows compatibility
    $awkCmd = '{print " --force \""$2"\""}';
    // Mac OS X compatibility
    $xargsFlag = (stripos(PHP_OS, 'Darwin') !== false) ? '' : '-r';

    $collection->taskExecStack()
      ->stopOnFail()
      // Set SVN repo as working directory
      ->dir($svn_dir)
      // Remove files from SVN repo that have already been removed locally
      ->exec("svn st | grep ^! | awk '$awkCmd' | xargs $xargsFlag svn rm")
      // Recursively add files to SVN that haven't been added yet
      ->exec("svn add --force * --auto-props --parents --depth infinity -q");

    $result = $collection->run();

    if($result->wasSuccessful()) {
      // Run or suggest release command depending on a flag
      $repo_url = "https://plugins.svn.wordpress.org/$plugin_dist_name";
      $release_cmd = "svn ci -m \"Release $plugin_version\"";
      $tag_cmd = "svn copy $repo_url/trunk $repo_url/tags/$plugin_version -m \"Tag $plugin_version\"";
      if(!empty($opts['force'])) {
        $svn_login = getenv('WP_SVN_USERNAME');
        $svn_password = getenv('WP_SVN_PASSWORD');
        if ($svn_login && $svn_password) {
          $release_cmd .= " --username $svn_login --password $svn_password";
        } else {
          $release_cmd .= ' --force-interactive';
        }
        $result = $this->taskExecStack()
          ->stopOnFail()
          ->dir($svn_dir)
          ->exec($release_cmd)
          ->exec($tag_cmd)
          ->run();
      } else {
        $this->yell(
          "Go to '$svn_dir' and run '$release_cmd' to publish the release"
        );
        $this->yell(
          "Run '$tag_cmd' to tag the release"
        );
      }
    }

    return $result;
  }

  public function publish($opts = ['force' => false]) {
    return $this->collectionBuilder()
      ->addCode(array($this, 'pushpot'))
      ->addCode(function () use ($opts) {
        return $this->svnPublish($opts);
      })
      ->run();
  }

  protected function loadEnv() {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
  }

  protected function loadWPFunctions() {
    $this->loadEnv();
    define('ABSPATH', getenv('WP_TEST_PATH') . '/');
    define('WPINC', 'wp-includes');
    require_once(ABSPATH . WPINC . '/functions.php');
    require_once(ABSPATH . WPINC . '/formatting.php');
    require_once(ABSPATH . WPINC . '/plugin.php');
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
  }
}
