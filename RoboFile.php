<?php

// phpcs:ignore PSR1.Classes.ClassDeclaration
class RoboFile extends \Robo\Tasks {
  const ZIP_BUILD_PATH = __DIR__ . '/mailpoet.zip';

  use \Codeception\Task\SplitTestsByGroups;

  public function __construct() {

    // disable xdebug to avoid slowing down command execution
    $xdebugHandler = new \Composer\XdebugHandler\XdebugHandler('mailpoet');
    $xdebugHandler->setPersistent();
    $xdebugHandler->check();

    $dotenv = Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
  }

  public function install() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./tools/vendor/composer.phar install')
      ->exec('npm ci --prefer-offline')
      ->run();
  }

  public function update() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./tools/vendor/composer.phar update')
      ->exec('npm update')
      ->run();
  }

  public function watch() {
    $this->say('Warning: this lints and compiles all files, not just the changed one. Use separate tasks watch:js and watch:css for faster and more efficient watching.');
    $cssFiles = $this->rsearch('assets/css/src/', ['scss']);
    $jsFiles = $this->rsearch('assets/js/src/', ['js', 'jsx']);

    $this->taskWatch()
      ->monitor($jsFiles, function() {
        $this->compileJs();
      })
      ->monitor($cssFiles, function() {
        $this->compileCss();
      })
      ->run();
  }

  public function watchCss() {
    $cssFiles = $this->rsearch('assets/css/src/', ['scss']);
    $this->taskWatch()
      ->monitor($cssFiles, function($changedFile) {
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

  public function watchJs() {
    $this->_exec('./node_modules/webpack/bin/webpack.js --watch');
  }

  public function compileAll($opts = ['env' => null]) {
    $collection = $this->collectionBuilder();
    $collection->addCode(function() use ($opts) {
      return call_user_func([$this, 'compileJs'], $opts);
    });
    $collection->addCode(function() use ($opts) {
      return call_user_func([$this, 'compileCss'], $opts);
    });
    return $collection->run();
  }

  public function compileJs($opts = ['env' => null]) {
    if (!is_dir('assets/dist/js')) {
      mkdir('assets/dist/js', 0777, true);
    }
    $env = ($opts['env']) ?
      sprintf('./node_modules/cross-env/dist/bin/cross-env.js NODE_ENV="%s"', $opts['env']) :
      null;
    return $this->_exec($env . ' ./node_modules/webpack/bin/webpack.js --bail');
  }

  public function compileCss($opts = ['env' => null]) {
    if (!is_dir('assets/dist/css')) {
      mkdir('assets/dist/css', 0777, true);
    }
    // Clean up folder from previous files
    array_map('unlink', glob("assets/dist/css/*.*"));

    $this->_exec('npm run stylelint -- "assets/css/src/**/*.scss"');
    $this->_exec('npm run scss');
    $compilationResult = $this->_exec('npm run autoprefixer');

    // Create manifest file
    $manifest = [];
    foreach (glob('assets/dist/css/*.css') as $style) {
      // Hash and rename styles if production environment
      if ($opts['env'] === 'production') {
        $hashedStyle = sprintf(
          '%s.%s.css',
          pathinfo($style)['filename'],
          substr(md5_file($style), 0, 8)
        );
        $manifest[basename($style)] = $hashedStyle;
        rename($style, str_replace(basename($style), $hashedStyle, $style));
      } else {
        $manifest[basename($style)] = basename($style);
      }
    }
    file_put_contents('assets/dist/css/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    return $compilationResult;
  }

  public function translationsInit() {
    // Define WP_TRANSIFEX_API_TOKEN env. variable
    return $this->_exec('./tasks/transifex_init.sh');
  }

  public function translationsBuild() {
    return $this->_exec('./node_modules/.bin/grunt makepot' .
      ' --gruntfile=' . __DIR__ . '/tasks/makepot/makepot.js' .
      ' --base_path=' . __DIR__
    );
  }

  public function translationsPack() {
    return $this->collectionBuilder()
      ->addCode([$this, 'translationsInit'])
      ->taskExec('./tasks/pack_translations.sh')
      ->run();
  }

  public function translationsPush() {
    return $this->collectionBuilder()
      ->addCode([$this, 'translationsInit'])
      ->taskExec('tx push -s')
      ->run();
  }

  public function testUnit(array $opts=['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
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

  public function testIntegration(array $opts=['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
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

  public function testMultisiteIntegration($opts=['file' => null, 'xml' => false, 'multisite' => true]) {
    return $this->testIntegration($opts);
  }

  public function testCoverage($opts=['file' => null, 'xml' => false]) {
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

  public function testNewsletterEditor($xmlOutputFile = null) {
    $this->compileJs();

    $command = join(' ', [
      './node_modules/.bin/mocha',
      '-r tests/javascript_newsletter_editor/mochaTestHelper.js',
      'tests/javascript_newsletter_editor/testBundles/**/*.js',
    ]);

    if (!empty($xmlOutputFile)) {
      $command .= sprintf(
        ' --reporter xunit --reporter-options output="%s"',
        $xmlOutputFile
      );
    }

    return $this->_exec($command);
  }

  public function testJavascript($xmlOutputFile = null) {
    $command = './node_modules/.bin/mocha --require tests/javascript/babel_register.js  tests/javascript/**/*.spec.js';

    if (!empty($xmlOutputFile)) {
      $command .= sprintf(
        ' --reporter xunit --reporter-options output="%s"',
        $xmlOutputFile
      );
    }

    return $this->_exec($command);
  }

  public function securityComposer() {
    return $this->collectionBuilder()
      ->taskExec('vendor/bin/security-checker security:check --format=simple')
      ->taskExec('vendor/bin/security-checker security:check --format=simple prefixer/composer.lock')
      ->run();
  }

  public function testDebugUnit($opts=['file' => null, 'xml' => false, 'debug' => true]) {
    return $this->testUnit($opts);
  }

  public function testDebugIntegration($opts=['file' => null, 'xml' => false, 'debug' => true]) {
    return $this->testIntegration($opts);
  }

  public function testAcceptance($opts=['file' => null, 'skip-deps' => false, 'timeout' => null]) {
    return $this->taskExec(
      'COMPOSE_HTTP_TIMEOUT=200 docker-compose run ' .
      ($opts['skip-deps'] ? '-e SKIP_DEPS=1 ' : '') .
      ($opts['timeout'] ? '-e WAIT_TIMEOUT=' . (int)$opts['timeout'] . ' ' : '') .
      'codeception --steps --debug -vvv ' .
      '-f ' . ($opts['file'] ? $opts['file'] : '')
    )->dir(__DIR__ . '/tests/docker')->run();
  }

  public function testAcceptanceMultisite($opts=['file' => null, 'skip-deps' => false, 'timeout' => null]) {
    return $this->taskExec(
      'COMPOSE_HTTP_TIMEOUT=200 docker-compose run ' .
      ($opts['skip-deps'] ? '-e SKIP_DEPS=1 ' : '') .
      ($opts['timeout'] ? '-e WAIT_TIMEOUT=' . (int)$opts['timeout'] . ' ' : '') .
      '-e MULTISITE=1 ' .
      'codeception --steps --debug -vvv ' .
      '-f ' . ($opts['file'] ? $opts['file'] : '')
    )->dir(__DIR__ . '/tests/docker')->run();
  }

  public function deleteDocker() {
    return $this->taskExec(
      'docker-compose down -v --remove-orphans --rmi all'
    )->dir(__DIR__ . '/tests/docker')->run();
  }

  public function testFailedUnit() {
    $this->_exec('vendor/bin/codecept build');
    return $this->_exec('vendor/bin/codecept run unit -g failed');
  }

  public function testFailedIntegration() {
    $this->_exec('vendor/bin/codecept build');
    return $this->_exec('vendor/bin/codecept run integration -g failed');
  }

  public function containerDump() {
    define('ABSPATH', getenv('WP_ROOT') . '/');
    if (!file_exists(ABSPATH . 'wp-config.php')) {
      $this->yell('WP_ROOT env variable does not contain valid path to wordpress root.', 40, 'red');
      exit(1);
    }

    $configurator = new \MailPoet\DI\ContainerConfigurator();
    $dumpFile = __DIR__ . '/generated/' . $configurator->getDumpClassname() . '.php';
    $this->say('Deleting DI Container');
    $this->_exec("rm -f $dumpFile");
    $this->say('Generating DI container cache');
    $containerFactory = new \MailPoet\DI\ContainerFactory($configurator);
    $container = $containerFactory->getConfiguredContainer();
    $container->compile();
    $dumper = new \MailPoetVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper($container);
    file_put_contents(
      $dumpFile,
      $dumper->dump([
        'class' => $configurator->getDumpClassname(),
        'namespace' => $configurator->getDumpNamespace(),
      ])
    );
  }

  public function doctrineGenerateMetadata() {
    $doctrineMetadataDir = \MailPoet\Doctrine\ConfigurationFactory::METADATA_DIR;
    $validatorMetadataDir = \MailPoet\Doctrine\Validator\ValidatorFactory::METADATA_DIR;
    $this->_exec("rm -rf $doctrineMetadataDir");
    $this->_exec("rm -rf $validatorMetadataDir");

    $entityManager = $this->createDoctrineEntityManager();
    $doctrineMetadata = $entityManager->getMetadataFactory()->getAllMetadata();

    $annotationReaderProvider = new \MailPoet\Doctrine\Annotations\AnnotationReaderProvider();
    $validatorFactory = new \MailPoet\Doctrine\Validator\ValidatorFactory($annotationReaderProvider);
    $validator = $validatorFactory->createValidator();

    foreach ($doctrineMetadata as $metadata) {
      $validator->getMetadataFor($metadata->getName());
    }

    $this->say("Doctrine metadata generated to: $doctrineMetadataDir");
    $this->say("Validator metadata generated to: $validatorMetadataDir");
  }

  public function doctrineGenerateProxies() {
    $proxyDir = \MailPoet\Doctrine\ConfigurationFactory::PROXY_DIR;
    $this->_exec("rm -rf $proxyDir");

    // set ArrayCache for metadata to avoid reading & writing them on filesystem as a side effect
    $entityManager = $this->createDoctrineEntityManager();
    $entityManager->getMetadataFactory()->setCacheDriver(new \MailPoetVendor\Doctrine\Common\Cache\ArrayCache());
    $entityManager->getProxyFactory()->generateProxyClasses(
      $entityManager->getMetadataFactory()->getAllMetadata()
    );
    $this->say("Doctrine proxies generated to: $proxyDir");
  }

  public function qa() {
    $collection = $this->collectionBuilder();
    $collection->addCode([$this, 'qaPhp']);
    $collection->addCode([$this, 'qaFrontendAssets']);
    return $collection->run();
  }

  public function qaPhp() {
    $collection = $this->collectionBuilder();
    $collection->addCode([$this, 'qaLint']);
    $collection->addCode(function() {
      return $this->qaCodeSniffer('all');
    });
    return $collection->run();
  }

  public function qaFrontendAssets() {
    $collection = $this->collectionBuilder();
    $collection->addCode([$this, 'qaLintJavascript']);
    $collection->addCode([$this, 'qaLintCss']);
    return $collection->run();
  }

  public function qaLint() {
    return $this->_exec('./tasks/code_sniffer/vendor/bin/parallel-lint lib/ tests/ mailpoet.php');
  }

  public function qaLintJavascript() {
    return $this->_exec('npm run check-types && npm run lint');
  }

  public function qaLintCss() {
    return $this->_exec('npm run stylelint-check -- "assets/css/src/**/*.scss"');
  }

  public function qaCodeSniffer($severity='errors') {
    $severityFlag = $severity === 'all' ? '-w' : '-n';
    $task = implode(' ', [
      './tasks/code_sniffer/vendor/bin/phpcs',
      '--extensions=php',
      $severityFlag,
      '--standard=tasks/code_sniffer/MailPoet',
    ]);

    return $this->collectionBuilder()

      // PHP >= 7.1 for lib & tests
      ->taskExec($task)
      ->rawArg('--runtime-set testVersion 7.1-7.4')
      ->arg('--ignore=' . implode(',', [
          'lib/Config/PopulatorData/Templates',
          'tests/_data',
          'tests/_output',
          'tests/_support/_generated',
        ])
      )
      ->args([
        'lib',
        'tests',
      ])

      // PHP >= 7.1 in plugin root directory
      ->taskExec($task)
      ->rawArg('--runtime-set testVersion 7.1-7.4')
      ->rawArg('-l .')

      // PHP >= 7.2 for dev tools, etc.
      ->taskExec($task)
      ->rawArg('--runtime-set testVersion 7.2-7.4')
      ->arg('--ignore=' . implode(',', [
          'prefixer/build',
          'prefixer/vendor',
          'tasks/code_sniffer/vendor',
          'tasks/phpstan/vendor',
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

  public function qaFixFile($filePath) {
    if (substr($filePath, -4) === '.php') {
      // fix PHPCS rules
      return $this->collectionBuilder()
        ->taskExec(
          './tasks/code_sniffer/vendor/bin/phpcbf ' .
            '--standard=./tasks/code_sniffer/MailPoet ' .
            '--runtime-set testVersion 7.1-7.4 ' .
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
    if (substr($filePath, -4) === '.tsx' || substr($filePath, -3) === '.ts') {
      // fix ESLint using TS rules
      return $this->collectionBuilder()
        ->taskExec(
          'npx eslint -c .eslintrc.ts.json ' .
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
          'npx eslint -c .eslintrc.tests_newsletter_editor.json ' .
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

  public function qaPhpstan() {
    $dir = __DIR__;
    $task = implode(' ', [
      'WP_ROOT="' . getenv('WP_ROOT') . '"',
      'php -d memory_limit=-1',
      "$dir/tasks/phpstan/vendor/bin/phpstan analyse ",
    ]);

    // PHPStan must be run out of main plugin directory to avoid its autoloading
    // from vendor/autoload.php where some dev dependencies cause conflicts.
    return $this->collectionBuilder()
      // temp dir
      ->taskExec('mkdir -p ' . __DIR__ . '/temp')
      ->taskExec('rm -rf ' . __DIR__ . '/temp/phpstan')
      // Generate config with correct path to WP source
      ->taskExec("cp -rf $dir/tasks/phpstan/phpstan-wp-source.neon $dir/tasks/phpstan/_phpstan-wp-source.neon")
      ->taskExec("sed -i 's+WP_ROOT+" . getenv('WP_ROOT') . "+g' $dir/tasks/phpstan/_phpstan-wp-source.neon")
      // lib
      ->taskExec($task)
      ->arg("$dir/lib")
      ->dir(__DIR__ . '/tasks/phpstan')

      // tests
      ->taskExec($task)
      ->rawArg('--configuration=phpstan-tests.neon')
      ->rawArg(
        implode(' ', [
          "$dir/tests/_support",
          "$dir/tests/DataFactories",
          "$dir/tests/acceptance",
          "$dir/tests/integration",
          "$dir/tests/unit",
        ])
      )
      ->dir(__DIR__ . '/tasks/phpstan')

      ->run();
  }

  public function storybookBuild() {
    return $this->_exec('npm run build-storybook');
  }

  public function storybookWatch() {
    return $this->_exec('npm run storybook');
  }

  public function svnCheckout() {
    $svnDir = ".mp_svn";

    $collection = $this->collectionBuilder();

    // Clean up the SVN dir for faster shallow checkout
    if (file_exists($svnDir)) {
      $collection->taskExecStack()
        ->exec('rm -rf ' . $svnDir);
    }

    $collection->taskFileSystemStack()
        ->mkdir($svnDir);

    return $collection->taskExecStack()
      ->stopOnFail()
      ->dir($svnDir)
      ->exec('svn co https://plugins.svn.wordpress.org/mailpoet/ -N .')
      ->exec('svn up trunk')
      ->exec('svn up assets')
      ->run();
  }

  public function svnPushTemplates() {
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

  public function svnPublish() {
    $svnDir = ".mp_svn";
    $pluginVersion = $this->getPluginVersion('mailpoet.php');
    $pluginDistName = 'mailpoet';
    $pluginDistFile = $pluginDistName . '.zip';

    if (!$pluginVersion) {
      throw new \Exception('Could not parse plugin version, check the plugin header');
    }
    $this->say('Publishing version: ' . $pluginVersion);

    // Sanity checks
    if (!is_readable($pluginDistFile)) {
      $this->say("Failed to access " . $pluginDistFile);
      return;
    } elseif (!file_exists($svnDir . "/.svn/")) {
      $this->say("$svnDir/.svn/ dir not found, is it a SVN repository?");
      return;
    } elseif (file_exists($svnDir . "/tags/" . $pluginVersion)) {
      $this->say("A SVN tag already exists: " . $pluginVersion);
      return;
    }

    $collection = $this->collectionBuilder();

    // Clean up tmp dirs if the previous run was halted
    if (file_exists("$svnDir/trunk_new") || file_exists("$svnDir/trunk_old")) {
      $collection->taskFileSystemStack()
        ->stopOnFail()
        ->remove(["$svnDir/trunk_new", "$svnDir/trunk_old"]);
    }

    // Extract the distributable zip to tmp trunk dir
    $collection->taskExtract($pluginDistFile)
      ->to("$svnDir/trunk_new")
      ->preserveTopDirectory(false);

    // Rename current trunk
    if (file_exists("$svnDir/trunk")) {
      $collection->taskFileSystemStack()
        ->rename("$svnDir/trunk", "$svnDir/trunk_old");
    }

    // Replace old trunk with a new one
    $collection->taskFileSystemStack()
      ->stopOnFail()
      ->rename("$svnDir/trunk_new", "$svnDir/trunk")
      ->remove("$svnDir/trunk_old");

    // Add new repository assets
    $collection->taskFileSystemStack()
      ->mirror('./plugin_repository/assets', "$svnDir/assets_new");

    // Rename current assets folder
    if (file_exists("$svnDir/assets")) {
      $collection->taskFileSystemStack()
        ->rename("$svnDir/assets", "$svnDir/assets_old");
    }

    // Replace old assets with new ones
    $collection->taskFileSystemStack()
      ->stopOnFail()
      ->rename("$svnDir/assets_new", "$svnDir/assets")
      ->remove("$svnDir/assets_old");

    // Windows compatibility
    $awkCmd = '{print " --force \""$2"\""}';
    // Mac OS X compatibility
    $xargsFlag = (stripos(PHP_OS, 'Darwin') !== false) ? '' : '-r';

    $collection->taskExecStack()
      ->stopOnFail()
      // Set SVN repo as working directory
      ->dir($svnDir)
      // Remove files from SVN repo that have already been removed locally
      ->exec("svn st | grep ^! | awk '$awkCmd' | xargs $xargsFlag svn rm --keep-local")
      // Recursively add files to SVN that haven't been added yet
      ->exec("svn add --force * --auto-props --parents --depth infinity -q");

    $result = $collection->run();

    if ($result->wasSuccessful()) {
      $repoUrl = "https://plugins.svn.wordpress.org/$pluginDistName";
      $releaseCmd = "svn ci -m \"Release $pluginVersion\"";
      $tagCmd = "svn copy $repoUrl/trunk $repoUrl/tags/$pluginVersion -m \"Tag $pluginVersion\"";
      $svnLogin = getenv('WP_SVN_USERNAME');
      $svnPassword = getenv('WP_SVN_PASSWORD');
      if ($svnLogin && $svnPassword) {
        $releaseCmd .= " --username $svnLogin --password \"$svnPassword\"";
        $tagCmd .= " --username $svnLogin --password \"$svnPassword\"";
      } else {
        $releaseCmd .= ' --force-interactive';
        $tagCmd .= ' --force-interactive';
      }
      $result = $this->taskExecStack()
        ->stopOnFail()
        ->dir($svnDir)
        ->exec($releaseCmd)
        ->exec($tagCmd)
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
    $pullRequestsId = \MailPoetTasks\Release\JiraController::PULL_REQUESTS_ID;
    foreach ($issues as $issue) {
      if (strpos($issue['fields'][$pullRequestsId], 'state=OPEN') !== false) {
        $key = $issue['key'];
        $this->yell("Some pull request associated to task {$key} is not merged yet!", 40, 'red');
        exit(1);
      }
    }
  }

  public function releasePrepareGit() {
    // make sure working directory is clean
    $gitStatus = $this->taskGitStack()
      ->printOutput(false)
      ->exec('git status --porcelain')
      ->run();
    if (strlen(trim($gitStatus->getMessage())) > 0) {
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
    $releaseBranchStatus = $this->taskGitStack()
      ->printOutput(false)
      ->exec('git ls-remote --heads git@github.com:mailpoet/mailpoet.git release')
      ->run();
    if (strlen(trim($releaseBranchStatus->getMessage())) > 0) {
      $this->yell('Delete old release branch before running release.', 40, 'red');
      exit(1);
    }
    // check if local branch with name "release" exists
    $gitStatus = $this->taskGitStack()
      ->printOutput(false)
      ->exec('git rev-parse --verify release')
      ->run();
    if ($gitStatus->wasSuccessful()) {
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
    $version = $this->releaseVersionGetPrepared($version);
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

  public function releaseVersionGetPrepared($version = null) {
    if (!$version) {
      $version = $this->getReleaseVersionController()
        ->getPreparedVersion();
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

  public function releaseChangelogGet($version = null) {
    $outputs = $this->getChangelogController()->get($version);
    $this->say("Changelog \n{$outputs[0]} \n{$outputs[1]}\n");
    $this->say("IMPORTANT NOTES \n" . ($outputs[2] ?: 'none'));
  }

  public function releaseChangelogWrite($version = null) {
    $this->say("Updating changelog");
    $outputs = $this->getChangelogController()->update($version);
    $this->say("Changelog \n{$outputs[0]} \n{$outputs[1]}\n\n");
    $this->say("IMPORTANT NOTES \n" . ($outputs[2] ?: 'none'));
  }

  public function releaseDownloadZip() {
    $circleciController = $this->createCircleCiController();
    $path = $circleciController->downloadLatestBuild(self::ZIP_BUILD_PATH);
    $this->say('Release ZIP downloaded to: ' . $path);
    $this->say(sprintf('Release ZIP file size: %.2F MB', filesize($path) / pow(1024, 2)));
  }

  public function releasePublishGithub($version = null) {
    $jiraController = $this->createJiraController();
    $version = $jiraController->getVersion($version);
    $changelog = $this->getChangelogController()->get($version['name']);

    $githubController = $this->createGitHubController();
    $githubController->publishRelease($version['name'], $changelog[1], self::ZIP_BUILD_PATH);
    $this->say("Release '$version[name]' was published to GitHub.");
  }

  public function releasePublishJira($version = null) {
    $version = $this->releaseVersionGetPrepared($version);
    $jiraController = $this->createJiraController();
    $jiraVersion = $jiraController->releaseVersion($version);
    $this->say("JIRA version '$jiraVersion[name]' was released.");
  }

  public function releasePublishSlack($version = null) {
    $jiraController = $this->createJiraController();
    $version = $jiraController->getVersion($version);
    $changelog = $this->getChangelogController()->get($version['name']);

    $slackNotifier = $this->createSlackNotifier();
    $slackNotifier->notify($version['name'], $changelog[1], $version['id']);
    $this->say("Release '$version[name]' info was published on Slack.");
  }

  public function generateData($generatorName = null) {
    require_once __DIR__ . '/tests/DataGenerator/_bootstrap.php';
    $generator = new \MailPoet\Test\DataGenerator\DataGenerator(new \Codeception\Lib\Console\Output([]));
    $generator->run($generatorName);
  }

  protected function rsearch($folder, $extensions = []) {
    $dir = new RecursiveDirectoryIterator($folder);
    $iterator = new RecursiveIteratorIterator($dir);

    $pattern = '/^.+\.(' . join('|', $extensions) . ')$/i';

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
      $this->createGitHubController(),
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
    $phpConfig = new \Composer\XdebugHandler\PhpConfig();
    $phpConfig->useOriginal();

    // exec command in subprocess with original settings
    passthru($command, $exitCode);

    $phpConfig->usePersistent();
    return $exitCode;
  }

  private function createDoctrineEntityManager() {
    define('ABSPATH', getenv('WP_ROOT') . '/');
    if (\MailPoet\Config\Env::$dbPrefix === null) {
      \MailPoet\Config\Env::$dbPrefix = ''; // ensure some prefix is set
    }
    $annotationReaderProvider = new \MailPoet\Doctrine\Annotations\AnnotationReaderProvider();
    $configuration = (new \MailPoet\Doctrine\ConfigurationFactory(true, $annotationReaderProvider))->createConfiguration();
    $platformClass = \MailPoet\Doctrine\ConnectionFactory::PLATFORM_CLASS;
    return \MailPoetVendor\Doctrine\ORM\EntityManager::create([
      'driver' => \MailPoet\Doctrine\ConnectionFactory::DRIVER,
      'platform' => new $platformClass,
    ], $configuration);
  }
}
