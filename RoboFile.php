<?php

// phpcs:ignore PSR1.Classes.ClassDeclaration
class RoboFile extends \Robo\Tasks {
  const ZIP_BUILD_PATH = __DIR__ . '/mailpoet.zip';

  use \MailPoet\Test\SplitTests\SplitTestFilesByGroups;

  public function __construct() {

    // disable xdebug to avoid slowing down command execution
    $xdebugHandler = new \Composer\XdebugHandler\XdebugHandler('mailpoet');
    $xdebugHandler->setPersistent();
    $xdebugHandler->check();

    $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
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

  public function watchCss() {
    $cssFiles = $this->rsearch('assets/css/src/', ['scss']);
    $this->taskWatch()
      ->monitor($cssFiles, function($changedFile) {
        $file = $changedFile->getResource()->getResource();
        $this->taskExecStack()
          ->stopOnFail()
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
    $this->_exec('rm -rf ' . __DIR__ . '/assets/dist/js/*');
    $env = ($opts['env']) ?
      sprintf('./node_modules/.bin/cross-env NODE_ENV="%s"', $opts['env']) :
      null;
    return $this->_exec($env . ' ./node_modules/webpack/bin/webpack.js');
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
    return $this->_exec(
      'php tasks/makepot/grunt-makepot.php wp-plugin . lang/mailpoet.pot mailpoet .mp_svn,assets,lang,node_modules,plugin_repository,tasks,tests,vendor'
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
    if (getenv('MAILPOET_DEV_SITE')) {
      $run = $this->confirm("You are about to run tests on the development site. Your DB data will be erased. \nDo you want to proceed?", false);
      if (!$run) {
        return;
      }
    }
    $command = 'vendor/bin/codecept run integration -vvv';

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
    $command = join(' ', [
      './node_modules/.bin/mocha',
      '-r tests/javascript_newsletter_editor/mochaTestHelper.js',
      'tests/javascript_newsletter_editor/testBundles/**/*.js',
      '--exit',
    ]);

    if (!empty($xmlOutputFile)) {
      $command .= sprintf(
        ' --reporter xunit --reporter-options output="%s"',
        $xmlOutputFile
      );
    }

    return $this->collectionBuilder()
      ->addCode(function () {
        $this->compileJs();
      })
      ->taskExec($command)
      ->run();
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

  public function doctrineGenerateCache() {
    $doctrineMetadataDir = \MailPoet\Doctrine\ConfigurationFactory::METADATA_DIR;
    $validatorMetadataDir = \MailPoet\Doctrine\Validator\ValidatorFactory::METADATA_DIR;
    $proxyDir = \MailPoet\Doctrine\ConfigurationFactory::PROXY_DIR;

    // Cleanup
    $this->_exec("rm -rf $doctrineMetadataDir");
    $this->_exec("rm -rf $validatorMetadataDir");
    $this->_exec("rm -rf $proxyDir");

    // Metadata
    $entityManager = $this->createDoctrineEntityManager();
    $doctrineMetadata = $entityManager->getMetadataFactory()->getAllMetadata();
    $this->say("Doctrine metadata generated to: $doctrineMetadataDir");

    // Proxies
    $entityManager->getProxyFactory()->generateProxyClasses($doctrineMetadata);
    $this->say("Doctrine proxies generated to: $proxyDir");

    // Validator
    $annotationReaderProvider = new \MailPoet\Doctrine\Annotations\AnnotationReaderProvider();
    $validatorFactory = new \MailPoet\Doctrine\Validator\ValidatorFactory($annotationReaderProvider);
    $validator = $validatorFactory->createValidator();
    foreach ($doctrineMetadata as $metadata) {
      $validator->getMetadataFor($metadata->getName());
      require_once $proxyDir . '/__CG__' . str_replace('\\', '', $metadata->getName()) . '.php';
      $validator->getMetadataFor("MailPoetDoctrineProxies\__CG__\\" . $metadata->getName());
    }
    $this->say("Validator metadata generated to: $validatorMetadataDir");
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
      return $this->qaCodeSniffer([]);
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

  public function qaCodeSniffer(array $filesToCheck, $opts = ['severity' => 'all']) {
    $severityFlag = $opts['severity'] === 'all' ? '-w' : '-n';
    $task = implode(' ', [
      './tasks/code_sniffer/vendor/bin/phpcs',
      '--extensions=php',
      $severityFlag,
      '--standard=tasks/code_sniffer/MailPoet',
    ]);
    $foldersToIgnore = [
      'assets',
      'doc',
      'generated',
      'lib/Config/PopulatorData/Templates',
      'lib-3rd-party',
      'node_modules',
      'plugin_repository',
      'prefixer/build',
      'prefixer/vendor',
      'tasks/code_sniffer/vendor',
      'tasks/phpstan/vendor',
      'tasks/makepot',
      'tools/vendor',
      'temp',
      'tests/_data',
      'tests/_output',
      'tests/_support/_generated',
      'vendor',
      'vendor-prefixed',
      'views',
    ];
    $stringFilesToCheck = !empty($filesToCheck) ? implode(' ', $filesToCheck) : '.';

    return $this
      ->taskExec($task)
      ->arg('--ignore=' . implode(',', $foldersToIgnore))
      ->rawArg($stringFilesToCheck)
      ->run();
  }

  public function qaFixFile($filePath) {
    if (substr($filePath, -4) === '.php') {
      // fix PHPCS rules
      return $this->collectionBuilder()
        ->taskExec(
          './tasks/code_sniffer/vendor/bin/phpcbf ' .
            '--standard=./tasks/code_sniffer/MailPoet ' .
            '--runtime-set testVersion 7.2-8.0 ' .
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

  public function qaPhpstan(array $opts=['php-version' => null]) {
    $dir = __DIR__;
    $task = implode(' ', [
      'php -d memory_limit=-1',
      "$dir/tasks/phpstan/vendor/bin/phpstan analyse ",
    ]);

    if ($opts['php-version'] !== null) {
      $task = "ANALYSIS_PHP_VERSION={$opts['php-version']} $task";
    }

    // make sure Codeception support files are present to avoid invalid errors when running PHPStan
    $this->_exec('vendor/bin/codecept build');

    // PHPStan must be run out of main plugin directory to avoid its autoloading
    // from vendor/autoload.php where some dev dependencies cause conflicts.
    return $this->collectionBuilder()
      // temp dir
      ->taskExec('mkdir -p ' . __DIR__ . '/temp')
      ->taskExec('rm -rf ' . __DIR__ . '/temp/phpstan')
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

  public function svnPublish(string $version) {
    $svnDir = ".mp_svn";
    $pluginDistName = 'mailpoet';
    $pluginDistFile = $pluginDistName . '.zip';

    $this->say('Publishing version: ' . $version);

    // Sanity checks
    if (!is_readable($pluginDistFile)) {
      $this->say("Failed to access " . $pluginDistFile);
      return;
    } elseif (!file_exists($svnDir . "/.svn/")) {
      $this->say("$svnDir/.svn/ dir not found, is it a SVN repository?");
      return;
    } elseif (file_exists($svnDir . "/tags/" . $version)) {
      $this->say("A SVN tag already exists: " . $version);
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
      $releaseCmd = "svn ci -m \"Release $version\"";
      $tagCmd = "svn copy $repoUrl/trunk $repoUrl/tags/$version -m \"Tag $version\"";
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
    return $this->taskSplitTestFilesByGroups(16)
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
      ->addCode(function () use ($version) {
        $this->translationsPrepareLanguagePacks($version);
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
      ->exec('git pull --ff-only')
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

  /**
   * This is part of release prepare script. It imports translations from Transifex to the Wordpress.com translations system
   * @param string $version
   */
  public function translationsPrepareLanguagePacks($version) {
    $translations = new \MailPoetTasks\Release\TranslationsController();
    $result = $translations->importTransifex($version);
    if (!$result['success']) {
      $this->yell($result['data'], 40, 'red');
      exit(1);
    }
    $this->say('Translations ' . $result['data']);
  }

  /**
   * This is part of release publish script. It checks if translations are ready at Wordpress.com translations system
   * @param string $version
   */
  public function translationsCheckLanguagePacks($version) {
    $translations = new \MailPoetTasks\Release\TranslationsController();
    $result = $translations->checkIfTranslationsAreReady($version);
    if (!$result['success']) {
      $this->yell('Translations are not ready yet on WordPress.com. ' . $result['data'], 40, 'red');
      exit(1);
    }
    $this->say('Translations check passed');
  }

  public function releasePublish($version = null) {
    $version = $this->releaseVersionGetPrepared($version);
    return $this->collectionBuilder()
      ->addCode(function () use ($version) {
        return $this->releaseCheckPullRequest($version);
      })
      ->addCode(function () use ($version) {
        return $this->translationsCheckLanguagePacks($version);
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
      ->addCode(function () use ($version) {
        return $this->svnPublish($version);
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

  public function downloadWooCommerceSubscriptionsZip($tag = null) {
    if (!getenv('WP_GITHUB_USERNAME') && !getenv('WP_GITHUB_TOKEN')) {
      $this->yell("Skipping download of WooCommerce Subscriptions", 40, 'red');
      exit(0); // Exit with 0 since it is a valid state for some environments
    }
    $this->createGithubClient('woocommerce/woocommerce-subscriptions')
      ->downloadReleaseZip('woocommerce-subscriptions.zip', __DIR__ . '/tests/plugins/', $tag);
  }

  public function downloadWooCommerceZip($tag = null) {
    $this->createGithubClient('woocommerce/woocommerce')
      ->downloadReleaseZip('woocommerce.zip', __DIR__ . '/tests/plugins/', $tag);
  }

  public function generateData($generatorName = null, $threads = 1) {
    require_once __DIR__ . '/tests/DataGenerator/_bootstrap.php';
    $generator = new \MailPoet\Test\DataGenerator\DataGenerator(new \Codeception\Lib\Console\Output([]));
    $generator->runBefore($generatorName);
    if ((int)$threads === 1) {
      $this->generateUnitOfData($generatorName);
    } else {
      $parallelTask = $this->taskParallelExec();
      for ($i = 1; $i <= $threads; $i++) {
        $parallelTask = $parallelTask->process("./do generate:unit-of-data $generatorName");
      }
      $parallelTask->run();
    }
    $generator->runAfter($generatorName);
  }

  /**
   * This is intended only for usage as a child process in parallel execution
   * @param string|null $generatorName
   */
  public function generateUnitOfData($generatorName = null) {
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

  private function createGithubClient($repositoryName) {
    require_once __DIR__ . '/tasks/GithubClient.php';
    return new \MailPoetTasks\GithubClient(
      $repositoryName,
      getenv('WP_GITHUB_USERNAME') ?: null,
      getenv('WP_GITHUB_TOKEN') ?: null
    );
  }

  private function createDoctrineEntityManager() {
    define('ABSPATH', getenv('WP_ROOT') . '/');
    if (\MailPoet\Config\Env::$dbPrefix === null) {
      \MailPoet\Config\Env::$dbPrefix = ''; // ensure some prefix is set
    }
    $annotationReaderProvider = new \MailPoet\Doctrine\Annotations\AnnotationReaderProvider();
    $configuration = (new \MailPoet\Doctrine\ConfigurationFactory($annotationReaderProvider, true))->createConfiguration();
    $platformClass = \MailPoet\Doctrine\ConnectionFactory::PLATFORM_CLASS;
    return \MailPoetVendor\Doctrine\ORM\EntityManager::create([
      'driver' => \MailPoet\Doctrine\ConnectionFactory::DRIVER,
      'platform' => new $platformClass,
    ], $configuration);
  }
}
