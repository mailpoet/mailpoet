<?php

// phpcs:ignore PSR1.Classes.ClassDeclaration
class RoboFile extends \Robo\Tasks {
  const ZIP_BUILD_PATH = __DIR__ . '/mailpoet.zip';

  use \Codeception\Task\SplitTestsByGroups;

  public function __construct() {

    // disable xdebug to avoid slowing down command execution
    $xdebug_handler = new \Composer\XdebugHandler\XdebugHandler('mailpoet');
    $xdebug_handler->setPersistent();
    $xdebug_handler->check();

    $dotenv = Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
  }

  function install() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./tools/vendor/composer.phar install')
      ->exec('npm ci --prefer-offline')
      ->run();
  }

  function update() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./tools/vendor/composer.phar update')
      ->exec('npm update')
      ->run();
  }

  function watch() {
    $this->say('Warning: this lints and compiles all files, not just the changed one. Use separate tasks watch:js and watch:css for faster and more efficient watching.');
    $css_files = $this->rsearch('assets/css/src/', ['scss']);
    $js_files = $this->rsearch('assets/js/src/', ['js', 'jsx']);

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
    $css_files = $this->rsearch('assets/css/src/', ['scss']);
    $this->taskWatch()
      ->monitor($css_files, function($changedFile) {
        $file = $changedFile->getResource()->getResource();
        $this->taskExecStack()
          ->stopOnFail()
          ->exec('npm run stylelint -- "' . $file . '"')
          ->exec('npm run scss')
          ->exec('npm run autoprefixer')
          ->run();
      })
      ->run();
  }

  function watchJs() {
    $this->_exec('./node_modules/webpack/bin/webpack.js --watch');
  }

  function compileAll($opts = ['env' => null]) {
    $collection = $this->collectionBuilder();
    $collection->addCode(function() use ($opts) {
      return call_user_func([$this, 'compileJs'], $opts);
    });
    $collection->addCode(function() use ($opts) {
      return call_user_func([$this, 'compileCss'], $opts);
    });
    return $collection->run();
  }

  function compileJs($opts = ['env' => null]) {
    if (!is_dir('assets/dist/js')) {
      mkdir('assets/dist/js', 0777, true);
    }
    $env = ($opts['env']) ?
      sprintf('./node_modules/cross-env/dist/bin/cross-env.js NODE_ENV="%s"', $opts['env']) :
      null;
    return $this->_exec($env . ' ./node_modules/webpack/bin/webpack.js --bail');
  }

  function compileCss($opts = ['env' => null]) {
    if (!is_dir('assets/dist/css')) {
      mkdir('assets/dist/css', 0777, true);
    }
    // Clean up folder from previous files
    array_map('unlink', glob("assets/dist/css/*.*"));

    $this->_exec('npm run stylelint -- "assets/css/src/components/**/*.scss"');
    $this->_exec('npm run scss');
    $compilation_result = $this->_exec('npm run autoprefixer');

    // Create manifest file
    $manifest = [];
    foreach (glob('assets/dist/css/*.css') as $style) {
      // Hash and rename styles if production environment
      if ($opts['env'] === 'production') {
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

  function translationsInit() {
    // Define WP_TRANSIFEX_API_TOKEN env. variable
    return $this->_exec('./tasks/transifex_init.sh');
  }

  function translationsBuild() {
    return $this->_exec('./node_modules/.bin/grunt makepot' .
      ' --gruntfile=' . __DIR__ . '/tasks/makepot/makepot.js' .
      ' --base_path=' . __DIR__
    );
  }

  function translationsPack() {
    return $this->collectionBuilder()
      ->addCode([$this, 'translationsInit'])
      ->taskExec('./tasks/pack_translations.sh')
      ->run();
  }

  function translationsPush() {
    return $this->collectionBuilder()
      ->addCode([$this, 'translationsInit'])
      ->taskExec('tx push -s')
      ->run();
  }

  function testUnit(array $opts=['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
    $command = 'vendor/bin/codecept run unit';

    if ($opts['file']) {
      $command .= ' -f ' . $opts['file'];
    }

    if ($opts['xml']) {
      $command .= ' --xml';
    }

    if ($opts['debug']) {
      $command .= ' --debug';
    }

    return $this->_exec($command);
  }

  function testIntegration(array $opts=['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
    $command = 'vendor/bin/codecept run integration';

    if ($opts['multisite']) {
      $command = 'MULTISITE=true ' . $command;
    }

    if ($opts['file']) {
      $command .= ' -f ' . $opts['file'];
    }

    if ($opts['xml']) {
      $command .= ' --xml';
    }

    if ($opts['debug']) {
      $command .= ' --debug';
    }

    return $this->_exec($command);
  }

  function testMultisiteIntegration($opts=['file' => null, 'xml' => false, 'multisite' => true]) {
    return $this->testIntegration($opts);
  }

  function testCoverage($opts=['file' => null, 'xml' => false]) {
    $command = join(' ', [
      'vendor/bin/codecept run -s acceptance',
      (($opts['file']) ? $opts['file'] : ''),
      '--coverage',
      ($opts['xml']) ? '--coverage-xml' : '--coverage-html',
    ]);

    if ($opts['xml']) {
      $command .= ' --xml';
    }

    return $this->execWithXDebug($command);
  }

  function testJavascript($xml_output_file = null) {
    $this->compileJs();

    $command = join(' ', [
      './node_modules/.bin/mocha',
      '-r tests/javascript/mochaTestHelper.js',
      'tests/javascript/testBundles/**/*.js',
    ]);

    if (!empty($xml_output_file)) {
      $command .= sprintf(
        ' --reporter xunit --reporter-options output="%s"',
        $xml_output_file
      );
    }

    return $this->_exec($command);
  }

  function securityComposer() {
    return $this->collectionBuilder()
      ->taskExec('vendor/bin/security-checker security:check --format=simple')
      ->taskExec('vendor/bin/security-checker security:check --format=simple prefixer/composer.lock')
      ->run();
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
    $this->_exec('vendor/bin/codecept build');
    return $this->_exec('vendor/bin/codecept run unit -g failed');
  }

  function testFailedIntegration() {
    $this->_exec('vendor/bin/codecept build');
    return $this->_exec('vendor/bin/codecept run integration -g failed');
  }

  function containerDump() {
    define('ABSPATH', getenv('WP_ROOT') . '/');
    if (!file_exists(ABSPATH . 'wp-config.php')) {
      $this->yell('WP_ROOT env variable does not contain valid path to wordpress root.', 40, 'red');
      exit(1);
    }

    $configurator = new \MailPoet\DI\ContainerConfigurator();
    $dump_file = __DIR__ . '/generated/' . $configurator->getDumpClassname() . '.php';
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
        'namespace' => $configurator->getDumpNamespace(),
      ])
    );
  }

  function doctrineGenerateMetadata() {
    $metadata_dir = \MailPoet\Doctrine\ConfigurationFactory::METADATA_DIR;
    $this->_exec("rm -rf $metadata_dir");

    $entity_manager = $this->createDoctrineEntityManager();
    $entity_manager->getMetadataFactory()->getAllMetadata();
    $this->say("Doctrine metadata generated to: $metadata_dir");
  }

  function doctrineGenerateProxies() {
    $proxy_dir = \MailPoet\Doctrine\ConfigurationFactory::PROXY_DIR;
    $this->_exec("rm -rf $proxy_dir");

    // set ArrayCache for metadata to avoid reading & writing them on filesystem as a side effect
    $entity_manager = $this->createDoctrineEntityManager();
    $entity_manager->getMetadataFactory()->setCacheDriver(new \MailPoetVendor\Doctrine\Common\Cache\ArrayCache());
    $entity_manager->getProxyFactory()->generateProxyClasses(
      $entity_manager->getMetadataFactory()->getAllMetadata()
    );
    $this->say("Doctrine proxies generated to: $proxy_dir");
  }

  function qa() {
    $collection = $this->collectionBuilder();
    $collection->addCode([$this, 'qaLint']);
    $collection->addCode(function() {
      return $this->qaCodeSniffer('all');
    });
    $collection->addCode([$this, 'qaLintJavascript']);
    $collection->addCode([$this, 'qaLintCss']);
    return $collection->run();
  }

  function qaLint() {
    return $this->_exec('./tasks/code_sniffer/vendor/bin/parallel-lint lib/ tests/ mailpoet.php');
  }

  function qaLintJavascript() {
    return $this->_exec('npm run lint');
  }

  function qaLintCss() {
    return $this->_exec('npm run stylelint -- "assets/css/src/components/**/*.scss"');
  }

  function qaCodeSniffer($severity='errors') {
    $severityFlag = $severity === 'all' ? '-w' : '-n';
    $task = implode(' ', [
      './tasks/code_sniffer/vendor/bin/phpcs',
      '--extensions=php',
      $severityFlag,
      '--standard=tasks/code_sniffer/MailPoet',
    ]);

    return $this->collectionBuilder()

      // PHP >= 5.6 for lib & tests
      ->taskExec($task)
      ->rawArg('--runtime-set testVersion 5.6-7.3')
      ->arg('--ignore=' . implode(',', [
          'lib/Config/PopulatorData/Templates',
          'lib/Util/CSS.php',
          'lib/Util/Sudzy',
          'lib/Util/pQuery',
          'lib/Util/XLSXWriter.php',
          'tests/_data',
          'tests/_output',
          'tests/_support',
          'tests/Actions.php',
          'tests/integration/_bootstrap.php',
          'tests/integration/_fixtures.php',
          'tests/unit/_bootstrap.php',
        ])
      )
      ->args([
        'lib',
        'tests',
      ])

      // PHP >= 5.6 in plugin root directory
      ->taskExec($task)
      ->rawArg('--runtime-set testVersion 5.6-7.3')
      ->rawArg('-l .')

      // PHP >= 7.2 for dev tools, etc.
      ->taskExec($task)
      ->rawArg('--runtime-set testVersion 7.2-7.3')
      ->arg('--ignore=' . implode(',', [
          'prefixer/build',
          'prefixer/vendor',
          'tasks/code_sniffer/vendor',
          'tasks/makepot',
          'tools/vendor',
        ])
      )
      ->args([
        '.circleci',
        'prefixer',
        'tasks',
        'tools',
      ])
      ->run();
  }

  function qaFixFile($filePath) {
    if (substr($filePath, -4) === '.php') {
      // fix PHPCS rules
      return $this->collectionBuilder()
        ->taskExec(
          './tasks/code_sniffer/vendor/bin/phpcbf ' .
            '--standard=./tasks/code_sniffer/MailPoet ' .
            '--runtime-set testVersion 5.6-7.3 ' .
            $filePath . ' -n'
        )
        ->run();
    }
    if (substr($filePath, -4) === '.jsx') {
      // fix ESLint using ES6 rules
      return $this->collectionBuilder()
        ->taskExec(
          'npx eslint -c .eslintrc.es6.json ' .
            '--max-warnings 0 ' .
            '--fix ' .
            $filePath
        )
        ->run();
    }
    if (substr($filePath, -8) === '.spec.js') {
      // fix ESLint using tests rules
      return $this->collectionBuilder()
        ->taskExec(
          'npx eslint -c .eslintrc.tests.json ' .
            '--max-warnings 0 ' .
            '--fix ' .
            $filePath
        )
        ->run();
    }
    if (substr($filePath, -3) === '.js') {
      // fix ESLint using ES5 rules
      return $this->collectionBuilder()
        ->taskExec(
          'npx eslint -c .eslintrc.es5.json ' .
            '--max-warnings 0 ' .
            '--fix ' .
            $filePath
        )
        ->run();
    }
  }

  function qaPhpstan() {
    // PHPStan must be run out of main plugin directory to avoid its autoloading
    // from vendor/autoload.php where some dev dependencies cause conflicts.
    $dir = __DIR__;
    return $this->collectionBuilder()
      ->taskExec('rm -rf ' . __DIR__ . '/vendor/goaop')
      ->taskExec('rm -rf ' . __DIR__ . '/vendor/nikic')
      ->taskExec('cd ' . __DIR__ . ' && ./tools/vendor/composer.phar dump-autoload')
      ->taskExec(
        'WP_ROOT="' . getenv('WP_ROOT') . '" ' .
        'php -d memory_limit=2G ' .
        "$dir/tools/vendor/phpstan.phar analyse " .
        "--configuration $dir/tasks/phpstan/phpstan.neon " .
        '--level 5 ' .
        "$dir/lib"
      )
      ->dir(__DIR__ . '/tasks/phpstan')
      ->taskExec('cd ' . __DIR__ . ' && ./tools/vendor/composer.phar install')
      ->run();
  }

  function svnCheckout() {
    $svn_dir = ".mp_svn";

    $collection = $this->collectionBuilder();

    // Clean up the SVN dir for faster shallow checkout
    if (file_exists($svn_dir)) {
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
      ->exec("svn st | grep ^! | awk '$awkCmd' | xargs $xargsFlag svn rm --keep-local")
      ->exec('svn add --force * --auto-props --parents --depth infinity -q')
      ->exec('svn commit -m "Push Templates for test"')
      ->run();
  }

  function svnPublish() {
    $svn_dir = ".mp_svn";
    $plugin_version = $this->getPluginVersion('mailpoet.php');
    $plugin_dist_name = 'mailpoet';
    $plugin_dist_file = $plugin_dist_name . '.zip';

    if (!$plugin_version) {
      throw new \Exception('Could not parse plugin version, check the plugin header');
    }
    $this->say('Publishing version: ' . $plugin_version);

    // Sanity checks
    if (!is_readable($plugin_dist_file)) {
      $this->say("Failed to access " . $plugin_dist_file);
      return;
    } elseif (!file_exists($svn_dir . "/.svn/")) {
      $this->say("$svn_dir/.svn/ dir not found, is it a SVN repository?");
      return;
    } elseif (file_exists($svn_dir . "/tags/" . $plugin_version)) {
      $this->say("A SVN tag already exists: " . $plugin_version);
      return;
    }

    $collection = $this->collectionBuilder();

    // Clean up tmp dirs if the previous run was halted
    if (file_exists("$svn_dir/trunk_new") || file_exists("$svn_dir/trunk_old")) {
      $collection->taskFileSystemStack()
        ->stopOnFail()
        ->remove(["$svn_dir/trunk_new", "$svn_dir/trunk_old"]);
    }

    // Extract the distributable zip to tmp trunk dir
    $collection->taskExtract($plugin_dist_file)
      ->to("$svn_dir/trunk_new")
      ->preserveTopDirectory(false);

    // Rename current trunk
    if (file_exists("$svn_dir/trunk")) {
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
    if (file_exists("$svn_dir/assets")) {
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
      ->exec("svn st | grep ^! | awk '$awkCmd' | xargs $xargsFlag svn rm --keep-local")
      // Recursively add files to SVN that haven't been added yet
      ->exec("svn add --force * --auto-props --parents --depth infinity -q");

    $result = $collection->run();

    if ($result->wasSuccessful()) {
      $repo_url = "https://plugins.svn.wordpress.org/$plugin_dist_name";
      $release_cmd = "svn ci -m \"Release $plugin_version\"";
      $tag_cmd = "svn copy $repo_url/trunk $repo_url/tags/$plugin_version -m \"Tag $plugin_version\"";
      $svn_login = getenv('WP_SVN_USERNAME');
      $svn_password = getenv('WP_SVN_PASSWORD');
      if ($svn_login && $svn_password) {
        $release_cmd .= " --username $svn_login --password $svn_password";
        $tag_cmd .= " --username $svn_login --password $svn_password";
      } else {
        $release_cmd .= ' --force-interactive';
        $tag_cmd .= ' --force-interactive';
      }
      $result = $this->taskExecStack()
        ->stopOnFail()
        ->dir($svn_dir)
        ->exec($release_cmd)
        ->exec($tag_cmd)
        ->run();
    }

    return $result;
  }

  public function testAcceptanceGroupTests() {
    return $this->taskSplitTestFilesByGroups(5)
      ->projectRoot('.')
      ->testsFrom('tests/acceptance')
      ->groupsTo('tests/acceptance/_groups/group_')
      ->run();
  }

  public function releasePrepare($version = null) {
    $version = $this->releaseVersionAssign($version, ['return' => true]);

    return $this->collectionBuilder()
      ->addCode(function () use ($version) {
        return $this->releaseCheckIssues($version);
      })
      ->addCode(function () {
        $this->releasePrepareGit();
      })
      ->addCode(function () use ($version) {
        return $this->releaseVersionWrite($version);
      })
      ->addCode(function () use ($version) {
        return $this->releaseChangelogWrite($version);
      })
      ->addCode(function () use ($version) {
        $this->releaseCreatePullRequest($version);
      })
      ->run();
  }

  public function releaseCheckIssues($version = null) {
    $jira = $this->createJiraController();
    $version = $jira->getVersion($this->releaseVersionGetNext($version));
    $issues = $jira->getIssuesDataForVersion($version);
    $pull_requests_id = \MailPoetTasks\Release\JiraController::PULL_REQUESTS_ID;
    foreach ($issues as $issue) {
      if (strpos($issue['fields'][$pull_requests_id], 'state=OPEN') !== false) {
        $key = $issue['key'];
        $this->yell("Some pull request associated to task {$key} is not merged yet!", 40, 'red');
        exit(1);
      }
    }
  }

  public function releasePrepareGit() {
    // make sure working directory is clean
    $git_status = $this->taskGitStack()
      ->printOutput(false)
      ->exec('git status --porcelain')
      ->run();
    if (strlen(trim($git_status->getMessage())) > 0) {
      $this->yell('Please make sure your working directory is clean before running release.', 40, 'red');
      exit(1);
    }
    // checkout master and pull from remote
    $this->taskGitStack()
      ->stopOnFail()
      ->checkout('master')
      ->pull()
      ->run();
    // make sure release branch doesn't exist on github
    $release_branch_status = $this->taskGitStack()
      ->printOutput(false)
      ->exec('git ls-remote --heads git@github.com:mailpoet/mailpoet.git release')
      ->run();
    if (strlen(trim($release_branch_status->getMessage())) > 0) {
      $this->yell('Delete old release branch before running release.', 40, 'red');
      exit(1);
    }
    // check if local branch with name "release" exists
    $git_status = $this->taskGitStack()
      ->printOutput(false)
      ->exec('git rev-parse --verify release')
      ->run();
    if ($git_status->wasSuccessful()) {
      // delete local "release" branch
      $this->taskGitStack()
        ->printOutput(false)
        ->exec('git branch -D release')
        ->run();
    }
    // create a new "release" branch and switch to it.
    $this->taskGitStack()
      ->printOutput(false)
      ->exec('git checkout -b release')
      ->run();
  }

  public function releaseCreatePullRequest($version) {
    $this->taskGitStack()
      ->stopOnFail()
      ->add('-A')
      ->commit('Release ' . $version)
      ->exec('git push --set-upstream git@github.com:mailpoet/mailpoet.git release')
      ->run();
    $this->createGitHubController()
      ->createReleasePullRequest($version);
  }

  public function releasePublish($version = null) {
    $version = $this->releaseVersionGetNext($version);
    return $this->collectionBuilder()
      ->addCode(function () use ($version) {
        return $this->releaseCheckPullRequest($version);
      })
      ->addCode(function () {
        return $this->releaseDownloadZip();
      })
      ->addCode(function () {
        return $this->translationsBuild();
      })
      ->addCode(function () {
        return $this->translationsPush();
      })
      ->addCode(function () {
        return $this->svnCheckout();
      })
      ->addCode(function () {
        return $this->svnPublish();
      })
      ->addCode(function () use ($version) {
        return $this->releasePublishGithub($version);
      })
      ->addCode(function () use ($version) {
        return $this->releasePublishJira($version);
      })
      ->addCode(function () use ($version) {
        return $this->releasePublishSlack($version);
      })
      ->run();
  }

  public function releaseCheckPullRequest($version) {
    $this->createGitHubController()
      ->checkReleasePullRequestPassed($version);
  }

  public function releaseVersionGetNext($version = null) {
    if (!$version) {
      $version = $this->getReleaseVersionController()
        ->determineNextVersion();
    }
    $this->validateVersion($version);
    return $version;
  }

  public function releaseVersionAssign($version = null, $opts = []) {
    $version = $this->releaseVersionGetNext($version);
    try {
      list($version, $output) = $this->getReleaseVersionController()
        ->assignVersionToCompletedTickets($version);
    } catch (\Exception $e) {
      $this->yell($e->getMessage(), 40, 'red');
      exit(1);
    }
    $this->say($output);
    if (!empty($opts['return'])) {
      return $version;
    }
  }

  public function releaseVersionWrite($version) {
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

  function releaseChangelogGet($version = null) {
    $outputs = $this->getChangelogController()->get($version);
    $this->say("Changelog \n{$outputs[0]} \n{$outputs[1]}\n");
    $this->say("IMPORTANT NOTES \n" . ($outputs[2] ?: 'none'));
  }

  function releaseChangelogWrite($version = null) {
    $this->say("Updating changelog");
    $outputs = $this->getChangelogController()->update($version);
    $this->say("Changelog \n{$outputs[0]} \n{$outputs[1]}\n\n");
    $this->say("IMPORTANT NOTES \n" . ($outputs[2] ?: 'none'));
  }

  public function releaseDownloadZip() {
    $circleci_controller = $this->createCircleCiController();
    $path = $circleci_controller->downloadLatestBuild(self::ZIP_BUILD_PATH);
    $this->say('Release ZIP downloaded to: ' . $path);
    $this->say(sprintf('Release ZIP file size: %.2F MB', filesize($path) / pow(1024, 2)));
  }

  public function releasePublishGithub($version = null) {
    $jira_controller = $this->createJiraController();
    $version = $jira_controller->getVersion($version);
    $changelog = $this->getChangelogController()->get($version['name']);

    $github_controller = $this->createGitHubController();
    $github_controller->publishRelease($version['name'], $changelog[1], self::ZIP_BUILD_PATH);
    $this->say("Release '$version[name]' was published to GitHub.");
  }

  public function releasePublishJira($version = null) {
    $version = $this->releaseVersionGetNext($version);
    $jira_controller = $this->createJiraController();
    $jira_version = $jira_controller->releaseVersion($version);
    $this->say("JIRA version '$jira_version[name]' was released.");
  }

  public function releasePublishSlack($version = null) {
    $jira_controller = $this->createJiraController();
    $version = $jira_controller->getVersion($version);
    $changelog = $this->getChangelogController()->get($version['name']);

    $slack_notifier = $this->createSlackNotifier();
    $slack_notifier->notify($version['name'], $changelog[1], $version['id']);
    $this->say("Release '$version[name]' info was published on Slack.");
  }

  public function generateData($generator_name) {
    require_once __DIR__ . '/tests/DataGenerator/_bootstrap.php';
    $generator = new \MailPoet\Test\DataGenerator\DataGenerator(new \Codeception\Lib\Console\Output([]));
    $generator->run($generator_name);
  }

  protected function rsearch($folder, $extensions = []) {
    $dir = new RecursiveDirectoryIterator($folder);
    $iterator = new RecursiveIteratorIterator($dir);

    $pattern = '/^.+\.(' . join($extensions, '|') . ')$/i';

    $files = new RegexIterator(
      $iterator,
      $pattern,
      RecursiveRegexIterator::GET_MATCH
    );

    $list = [];
    foreach ($files as $file) {
      $list[] = $file[0];
    }

    return $list;
  }

  protected function getPluginVersion($file) {
    $data = file_get_contents($file);
    preg_match('/^[ \t*]*Version:(.*)$/mi', $data, $m);
    return !empty($m[1]) ? trim($m[1]) : false;
  }

  protected function validateVersion($version) {
    if (!\MailPoetTasks\Release\VersionHelper::validateVersion($version)) {
      $this->yell('Incorrect version format', 40, 'red');
      exit(1);
    }
  }

  protected function getChangelogController() {
    return new \MailPoetTasks\Release\ChangelogController(
      $this->createJiraController(),
      __DIR__ . '/readme.txt'
    );
  }

  protected function getReleaseVersionController() {
    return new \MailPoetTasks\Release\ReleaseVersionController(
      $this->createJiraController(),
      \MailPoetTasks\Release\JiraController::PROJECT_MAILPOET
    );
  }

  protected function createJiraController() {
    $help = 'Use your JIRA username and a token from https://id.atlassian.com/manage/api-tokens.';
    return new \MailPoetTasks\Release\JiraController(
      $this->getEnv('WP_JIRA_TOKEN', $help),
      $this->getEnv('WP_JIRA_USER', $help),
      \MailPoetTasks\Release\JiraController::PROJECT_MAILPOET
    );
  }

  protected function createCircleCiController() {
    $help = "Use 'mailpoet' username and a token from https://circleci.com/gh/mailpoet/mailpoet/edit#api.";
    return new \MailPoetTasks\Release\CircleCiController(
      $this->getEnv('WP_CIRCLECI_USERNAME', $help),
      $this->getEnv('WP_CIRCLECI_TOKEN', $help),
      \MailPoetTasks\Release\CircleCiController::PROJECT_MAILPOET,
      $this->createGitHubController()
    );
  }

  protected function createGitHubController() {
    $help = "Use your GitHub username and a token from https://github.com/settings/tokens with 'repo' scopes.";
    return new \MailPoetTasks\Release\GitHubController(
      $this->getEnv('WP_GITHUB_USERNAME', $help),
      $this->getEnv('WP_GITHUB_TOKEN', $help),
      \MailPoetTasks\Release\GitHubController::PROJECT_MAILPOET
    );
  }

  protected function createSlackNotifier() {
    $help = 'Use Webhook URL from https://mailpoet.slack.com/services/BHRB9AHSQ.';
    return new \MailPoetTasks\Release\SlackNotifier(
      $this->getEnv('WP_SLACK_WEBHOOK_URL', $help),
      \MailPoetTasks\Release\SlackNotifier::PROJECT_MAILPOET
    );
  }

  protected function getEnv($name, $help = null) {
    $env = getenv($name);
    if ($env === false || $env === '') {
      $this->yell("Environment variable '$name' was not set.", 40, 'red');
      if ($help !== null) {
        $this->say('');
        $this->say($help);
      }
      exit(1);
    }
    return $env;
  }

  private function execWithXDebug($command) {
    $php_config = new \Composer\XdebugHandler\PhpConfig();
    $php_config->useOriginal();

    // exec command in subprocess with original settings
    passthru($command, $exitCode);

    $php_config->usePersistent();
    return $exitCode;
  }

  private function createDoctrineEntityManager() {
    define('ABSPATH', getenv('WP_ROOT') . '/');
    if (\MailPoet\Config\Env::$db_prefix === null) {
      \MailPoet\Config\Env::$db_prefix = ''; // ensure some prefix is set
    }
    $configuration = (new \MailPoet\Doctrine\ConfigurationFactory(true))->createConfiguration();
    $platform_class = \MailPoet\Doctrine\ConnectionFactory::PLATFORM_CLASS;
    return \MailPoetVendor\Doctrine\ORM\EntityManager::create([
      'driver' => \MailPoet\Doctrine\ConnectionFactory::DRIVER,
      'platform' => new $platform_class,
    ], $configuration);
  }
}
