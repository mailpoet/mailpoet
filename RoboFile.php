<?php

class RoboFile extends \Robo\Tasks {

  use \Codeception\Task\SplitTestsByGroups;

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
    if(!is_dir('assets/dist/js')) {
      mkdir('assets/dist/js', 0777, true);
    }
    $env = ($opts['env']) ?
      sprintf('./node_modules/cross-env/dist/bin/cross-env.js NODE_ENV="%s"', $opts['env']) :
      null;
    return $this->_exec($env . ' ./node_modules/webpack/bin/webpack.js --bail');
  }

  function compileCss($opts = ['env' => null]) {
    if(!is_dir('assets/dist/css')) {
      mkdir('assets/dist/css', 0777, true);
    }
    // Clean up folder from previous files
    array_map('unlink', glob("assets/dist/css/*.*"));

    $css_files = array(
      'assets/css/src/admin.styl',
      'assets/css/src/admin-global.styl',
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
      '-o assets/dist/css/'
    )));

    // Create manifest file
    $manifest = [];
    foreach(glob('assets/dist/css/*.css') as $style) {
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
    file_put_contents('assets/dist/css/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
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

  function testUnit(array $opts=['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
    $this->loadEnv();

    $command = 'vendor/bin/codecept run unit';

    if($opts['file']) {
      $command .= ' -f ' . $opts['file'];
    }

    if($opts['xml']) {
      $command .= ' --xml';
    }

    if($opts['debug']) {
      $command .= ' --debug';
    }

    return $this->_exec($command);
  }

  function testIntegration(array $opts=['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
    $this->loadEnv();

    $command = 'vendor/bin/codecept run integration';

    if($opts['multisite']) {
      $command = 'MULTISITE=true ' . $command;
    }

    if($opts['file']) {
      $command .= ' -f ' . $opts['file'];
    }

    if($opts['xml']) {
      $command .= ' --xml';
    }

    if($opts['debug']) {
      $command .= ' --debug';
    }

    return $this->_exec($command);
  }

  function testMultisiteIntegration($opts=['file' => null, 'xml' => false, 'multisite' => true]) {
    return $this->testIntegration($opts);
  }

  function testCoverage($opts=['file' => null, 'xml' => false]) {
    $this->loadEnv();
    $command = join(' ', array(
      'vendor/bin/codecept run -s acceptance',
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

  function securityComposer() {
    return $this->_exec('vendor/bin/security-checker security:check --format=simple');
  }

  function testDebugUnit($opts=['file' => null, 'xml' => false, 'debug' => true]) {
    return $this->testUnit($opts);
  }

  function testDebugIntegration($opts=['file' => null, 'xml' => false, 'debug' => true]) {
    return $this->testIntegration($opts);
  }

  function testAcceptance($opts=['file' => null, 'skip-deps' => false, 'timeout' => null]) {
    return $this->taskExec(
      'COMPOSE_HTTP_TIMEOUT=200 docker-compose run ' .
      ($opts['skip-deps'] ? '-e SKIP_DEPS=1 ' : '') .
      ($opts['timeout'] ? '-e WAIT_TIMEOUT=' . (int)$opts['timeout'] . ' ' : '') .
      'codeception --steps --debug -vvv ' .
      '-f ' . ($opts['file'] ? $opts['file'] : '')
    )->dir(__DIR__ . '/tests/docker')->run();
  }

  function testAcceptanceMultisite($opts=['file' => null, 'skip-deps' => false, 'timeout' => null]) {
    return $this->taskExec(
      'COMPOSE_HTTP_TIMEOUT=200 docker-compose run ' .
      ($opts['skip-deps'] ? '-e SKIP_DEPS=1 ' : '') .
      ($opts['timeout'] ? '-e WAIT_TIMEOUT=' . (int)$opts['timeout'] . ' ' : '') .
      '-e MULTISITE=1 ' .
      'codeception --steps --debug -vvv' .
      '-f ' . ($opts['file'] ? $opts['file'] : '')
    )->dir(__DIR__ . '/tests/docker')->run();
  }

  function deleteDocker() {
    return $this->taskExec(
      'docker-compose down -v --remove-orphans --rmi all'
    )->dir(__DIR__ . '/tests/docker')->run();
  }

  function testFailedUnit() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    return $this->_exec('vendor/bin/codecept run unit -g failed');
  }

  function testFailedIntegration() {
    $this->loadEnv();
    $this->_exec('vendor/bin/codecept build');
    return $this->_exec('vendor/bin/codecept run integration -g failed');
  }

  function containerDump() {
    $this->loadEnv();
    define('ABSPATH', getenv('WP_ROOT') . '/');
    if (!file_exists(ABSPATH . 'wp-config.php')) {
      $this->yell('WP_ROOT env variable does not contain valid path to wordpress root.', 40, 'red');
      exit(1);
    }
    require_once __DIR__ . '/vendor/autoload.php';
    $configurator = new \MailPoet\DI\ContainerConfigurator();
    $dump_file = __DIR__ .  '/generated/' . $configurator->getDumpClassname() . '.php';
    $this->say('Deleting DI Container');
    $this->_exec("rm -f $dump_file");
    $this->say('Generating DI container cache');
    $container_factory = new \MailPoet\DI\ContainerFactory($configurator);
    $container = $container_factory->getConfiguredContainer();
    $container->compile();
    $dumper = new \MailPoetVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
    file_put_contents(
      $dump_file,
      $dumper->dump([
        'class' => $configurator->getDumpClassname(),
        'namespace' => $configurator->getDumpNamespace()
      ])
    );
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
        '--runtime-set testVersion 5.6-7.2 '.
        '--ignore=./lib/Util/Sudzy/*,./lib/Util/CSS.php,./lib/Util/XLSXWriter.php,'.
        './lib/Util/pQuery/*,./lib/Config/PopulatorData/Templates/* '.
        'lib/ '.
        $severityFlag
      )
      ->taskExec(
        './vendor/bin/phpcs '.
        '--standard=./tasks/code_sniffer/MailPoet '.
        '--runtime-set testVersion 5.6-7.2 '.
        '--ignore=./tests/unit/_bootstrap.php,./tests/unit/_fixtures.php,./tests/integration/_bootstrap.php,./tests/integration/_fixtures.php '.
        'tests/unit tests/integration tests/acceptance tests/DataFactories '.
        $severityFlag
      )
      ->run();
  }

  function qaPhpstan() {
    // PHPStan must be run out of main plugin directory to avoid its autoloading
    // from vendor/autoload.php where some dev dependencies cause conflicts.
    $dir = __DIR__;
    $this->loadEnv();
    return $this->collectionBuilder()
      ->taskExec('rm -rf ' . __DIR__ . '/vendor/goaop')
      ->taskExec('rm -rf ' . __DIR__ . '/vendor/nikic')
      ->taskExec('cd ' . __DIR__ . ' && ./composer.phar dump-autoload')
      ->taskExec(
        'WP_ROOT="'.getenv('WP_ROOT').'" '.
        'php -d memory_limit=2G '.
        "$dir/phpstan.phar analyse ".
        "--configuration $dir/tasks/phpstan/phpstan.neon ".
        '--level 1 '.
        "$dir/lib"
      )
      ->dir(__DIR__ . '/tasks/phpstan')
      ->taskExec('cd ' . __DIR__ . ' && ./composer.phar install')
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

  function svnPushTemplates() {
    $collection = $this->collectionBuilder();
    $this->svnCheckout();
    $awkCmd = '{print " --force \""$2"\""}';
    $xargsFlag = (stripos(PHP_OS, 'Darwin') !== false) ? '' : '-r';
    return $collection->taskExecStack()
      ->stopOnFail()
      ->dir('.mp_svn')
      ->exec('cp -R ../plugin_repository/assets/newsletter-templates/* assets/newsletter-templates')
      ->exec("svn st | grep ^! | awk '$awkCmd' | xargs $xargsFlag svn rm")
      ->exec('svn add --force * --auto-props --parents --depth infinity -q')
      ->exec('svn commit -m "Push Templates for test"')
      ->run();
  }

  function svnPublish($opts = ['force' => false]) {
    $this->loadEnv();

    $svn_dir = ".mp_svn";
    $plugin_version = $this->getPluginVersion('mailpoet.php');
    $plugin_dist_name = 'mailpoet';
    $plugin_dist_file = $plugin_dist_name . '.zip';

    if(!$plugin_version) {
      throw new \Exception('Could not parse plugin version, check the plugin header');
    }
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

  function changelogUpdate($opts = ['version-name' => null]) {
    $this->say("Updating changelog");
    $outputs = $this->getChangelogController()->update($opts['version-name']);
    if($opts['quiet']) {
      return;
    }
    $this->say("Changelog \n{$outputs[0]} \n{$outputs[1]}\n\n");
    $this->say("IMPORTANT NOTES \n" . ($outputs[2] ?: 'none'));
  }

  function changelogGet($opts = ['version-name' => null]) {
    $outputs = $this->getChangelogController()->get($opts['version-name']);
    $this->say("Changelog \n{$outputs[0]} \n{$outputs[1]}\n");
    $this->say("IMPORTANT NOTES \n" . ($outputs[2] ?: 'none'));
  }

  protected function loadEnv() {
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
  }

  protected function getPluginVersion($file) {
    $data = file_get_contents($file);
    preg_match('/^[ \t*]*Version:(.*)$/mi', $data, $m);
    return !empty($m[1]) ? trim($m[1]) : false;
  }

  protected function getChangelogController() {
    require_once './tasks/release/ChangelogController.php';
    $this->loadEnv();
    return \MailPoetTasks\Release\ChangelogController::createWithJiraCredentials(
      getenv('WP_JIRA_TOKEN'),
      getenv('WP_JIRA_USER'),
      \MailPoetTasks\Release\Jira::PROJECT_MAILPOET,
      __DIR__ . '/readme.txt'
    );
  }

  protected function getReleaseVersionController($project) {
    require_once './tasks/release/ReleaseVersionController.php';
    $this->loadEnv();
    return \MailPoetTasks\Release\ReleaseVersionController::createWithJiraCredentials(
      getenv('WP_JIRA_TOKEN'),
      getenv('WP_JIRA_USER'),
      $project
    );
  }

  public function testAcceptanceGroupTests() {
    return $this->taskSplitTestFilesByGroups(4)
      ->projectRoot('.')
      ->testsFrom('tests/acceptance')
      ->groupsTo('tests/acceptance/_groups/group_')
      ->run();
  }

  public function writeReleaseVersion($version) {
    $version = trim($version);
    $this->validateVersion($version);

    $this->taskReplaceInFile(__DIR__ . '/readme.txt')
      ->regex('/Stable tag:\s*\d+\.\d+\.\d+/i')
      ->to('Stable tag: ' . $version)
      ->run();

    $this->taskReplaceInFile(__DIR__ . '/mailpoet.php')
      ->regex('/Version:\s*\d+\.\d+\.\d+/i')
      ->to('Version: ' . $version)
      ->run();

    $this->taskReplaceInFile(__DIR__ . '/mailpoet.php')
      ->regex("/['\"]version['\"]\s*=>\s*['\"]\d+\.\d+\.\d+['\"],/i")
      ->to(sprintf("'version' => '%s',", $version))
      ->run();
  }

  public function jiraReleaseVersion($opts = ['free' => null, 'premium' => null]) {
    require_once './tasks/release/Jira.php';
    if (empty($opts['free']) && empty($opts['premium'])) {
      $this->yell('No Free or Premium version specified', 40, 'red');
      exit(1);
    }
    $output = [];
    if (!empty($opts['free'])) {
      $this->validateVersion($opts['free']);
      $output[] = $this->getReleaseVersionController(\MailPoetTasks\Release\Jira::PROJECT_MAILPOET)
        ->assignVersionToCompletedTickets($opts['free']);
    }
    if (!empty($opts['premium'])) {
      $this->validateVersion($opts['premium']);
      $output[] = $this->getReleaseVersionController(\MailPoetTasks\Release\Jira::PROJECT_PREMIUM)
        ->assignVersionToCompletedTickets($opts['premium']);
    }
    if($opts['quiet']) {
      return;
    }
    $this->say(join("\n", $output));
  }

  private function validateVersion($version) {
    if(!preg_match('/\d+\.\d+\.\d+/', $version)) {
      $this->yell('Incorrect version format', 40, 'red');
      exit(1);
    }
  }
}
