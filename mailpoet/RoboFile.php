<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// phpcs:disable PSR1.Classes.ClassDeclaration
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
use MailPoetVendor\Twig\Loader\FilesystemLoader as TwigFileSystem;
use Robo\Symfony\ConsoleIO;

class RoboFile extends \Robo\Tasks {
  const ZIP_BUILD_PATH = __DIR__ . '/mailpoet.zip';

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
      ->exec('cd .. && pnpm install --frozen-lockfile --prefer-offline')
      ->addCode([$this, 'cleanupCachedFiles'])
      ->run();
  }

  public function installPhp() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./tools/vendor/composer.phar install')
      ->addCode([$this, 'cleanupCachedFiles'])
      ->run();
  }

  public function installJs() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('cd .. && pnpm install --frozen-lockfile --prefer-offline')
      ->run();
  }

  public function cleanupCachedFiles() {
    $this->say('Cleaning up generated folder.');
    $this->_exec('rm -rf ' . __DIR__ . '/generated/*');
    $this->say('Cleaning up PHPStan cache.');
    $this->_exec('rm -rf ' . __DIR__ . '/temp/*');
    $this->say('Cleaning up old testing plugins.');
    $this->_exec('rm -rf ' . __DIR__ . '/tests/plugins/*');
  }

  public function update() {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('./tools/vendor/composer.phar update')
      ->exec('pnpm update')
      ->run();
  }

  public function updateJsPackages($opts = ['ticket' => '', 'latest' => true, 'checks' => false, 'dedupe' => false]) {
    $ticket = $opts['ticket'];
    $latest = $opts['latest'];
    $runChecks = $opts['checks'];
    $runDedupe = $opts['dedupe'];

    if (empty($ticket)) {
      $this->say('Please specify a ticket with --ticket=<ticket>');
      exit(1);
    }

    $outdatedPackagesOutput = $this->taskExec('pnpm outdated --no-table')
      ->printOutput(false)
      ->run()
      ->getMessage();
    $lines = explode("\n", $outdatedPackagesOutput);

    // The package names themselves are every third line
    $outdatedPackages = array_filter($lines, function($key) {
      return $key % 3 === 0;
    }, ARRAY_FILTER_USE_KEY);

    $excludePackages = [
      'react-router-dom', // MAILPOET-3911
      'codemirror', // MAILPOET-5483
      'babel-loader', // MAILPOET-5491
      'stylelint', // MAILPOET-5462
      'backbone', // Will remove with new email editor
      'backbone.marionette', // Will remove with new email editor
      'history',
      'fork-ts-checker-webpack-plugin',
    ];

    $majorVersionChanges = [];

    foreach ($outdatedPackages as $packageName) {
      // @wordpress packages should be handled all together
      if (strpos($packageName, '@wordpress') !== false) {
        continue;
      }

      // @types should be kept in sync with the major version of the dependency and will be easiest to update after
      if (strpos($packageName, '@types') !== false) {
        continue;
      }

      $packageName = str_replace(' (dev)', '', $packageName);

      if (in_array($packageName, $excludePackages)) {
        $this->say("Skipping $packageName");
        continue;
      }

      $this->say("Updating $packageName");

      $oldVersion = $this->getCurrentJsPackageVersion($packageName);
      $oldMajorVersion = explode('.', $oldVersion)[0];

      if ($latest) {
        $this->taskExec("pnpm up -L $packageName")->run();
      } else {
        $this->taskExec("pnpm up $packageName")->run();
      }

      if ($this->taskExec("pnpm list $packageName")->run()->wasSuccessful()) {
        $newVersion = $this->getCurrentJsPackageVersion($packageName);
        if ($newVersion === $oldVersion) {
          continue;
        }

        if ($runChecks) {
          $collection = $this->collectionBuilder();
          $collection
            ->addCode([$this, 'installJs'])
            ->addCode([$this, 'compileJs'])
            ->addCode([$this, 'compileCss'])
            ->addCode([$this, 'qaFrontendAssets'])
            ->run();
        }

        $newMajorVersion = explode('.', $newVersion)[0];

        $this->taskExecStack()
          ->exec("git add ./package.json")
          ->exec('git add ../pnpm-lock.yaml')
          ->exec("git commit --no-verify -m \"Update $packageName from $oldVersion to $newVersion\" -m \"\" -m \"$ticket\"")
          ->run();
        if ($oldMajorVersion !== $newMajorVersion) {
          $majorVersionChanges[] = $packageName;
        }
      } else {
        $this->say("Update of $packageName failed. Exiting.");
        exit(1);
      }
    }

    if ($runDedupe) {
      $this->taskExecStack()
        ->exec('pnpm dedupe')
        ->exec('git add ../pnpm-lock.yaml')
        ->exec("git commit --no-verify -m \"Update lock file after pnpm dedupe\" -m \"\" -m \"$ticket\"")
        ->run();
    }

    if (count($majorVersionChanges) > 0) {
      $this->say(sprintf("The following packages changed major version: %s. Check to see if any of them need to have their @types updated.", implode(', ', $majorVersionChanges)));
    }
  }

  public function watchCss() {
    $cssFiles = $this->rsearch('assets/css/src/', ['scss']);
    $this->taskWatch()
      ->monitor($cssFiles, function($changedFile) {
        $file = $changedFile->getResource()->getResource();
        $this->taskExecStack()
          ->stopOnFail()
          ->exec('pnpm run scss')
          ->exec('pnpm run autoprefixer')
          ->run();
      })
      ->run();
  }

  public function watchJs() {
    $this->_exec('./node_modules/webpack/bin/webpack.js --watch');
  }

  public function compileAll($opts = ['env' => null, 'skip-tests' => false, 'only-tests' => false]) {
    $collection = $this->collectionBuilder();
    $collection->addCode(function() use ($opts) {
      return call_user_func([$this, 'compileJs'], $opts);
    });
    $collection->addCode(function() use ($opts) {
      return call_user_func([$this, 'compileCss'], $opts);
    });
    return $collection->run();
  }

  public function compileJs($opts = ['env' => null, 'skip-tests' => false, 'only-tests' => false]) {
    if (!is_dir('assets/dist/js')) {
      mkdir('assets/dist/js', 0777, true);
    }
    if (!$opts['only-tests']) {
      $this->_exec('rm -rf ' . __DIR__ . '/assets/dist/js/*');
    }
    $env = ($opts['env']) ?
      sprintf('./node_modules/.bin/cross-env NODE_ENV="%s"', $opts['env']) :
      null;
    return $this->_exec($env . ' ./node_modules/webpack/bin/webpack.js --env BUILD_TESTS=' . ($opts['skip-tests'] ? 'skip' : 'build') . '--env BUILD_ONLY_TESTS=' . ($opts['only-tests'] ? 'true' : 'false'));
  }

  public function compileCss($opts = ['env' => null]) {
    if (!is_dir('assets/dist/css')) {
      mkdir('assets/dist/css', 0777, true);
    }
    // Clean up folder from previous files
    array_map('unlink', glob("assets/dist/css/*.*"));

    $compilationResult = $this->taskExecStack()
      ->exec('pnpm run stylelint-check -- "assets/css/src/**/*.scss"')
      ->exec('pnpm run scss')
      ->exec('pnpm run autoprefixer')
      ->run();

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

  public function translationsBuild() {
    $exclude = implode(',', [
      '.mp_svn',
      'assets/css',
      'assets/img',
      'assets/js',
      'generated',
      'lang',
      'lib-3rd-party',
      'mailpoet-premium',
      'node_modules',
      'plugin_repository',
      'prefixer',
      'tasks',
      'temp',
      'tests',
      'tools',
      'vendor',
      'vendor-prefixed',
      'RoboFile.php',
    ]);

    $headers = escapeshellarg(
      json_encode([
        'Report-Msgid-Bugs-To' => 'http://support.mailpoet.com/',
        'Last-Translator' => 'MailPoet i18n (https://www.transifex.com/organization/wysija)',
        'Language-Team' => 'MailPoet i18n <https://www.transifex.com/organization/wysija>',
        'Plural-Forms' => 'nplurals=2; plural=(n != 1);',
      ])
    );

    $this->collectionBuilder()
      ->taskExec('mkdir -p ' . __DIR__ . '/lang')

      // HTML, HBS
      ->taskExec("php -d memory_limit=-1 tasks/makepot/makepot-views.php . > lang/mailpoet.pot")

      // PHP, JS/TS
      ->taskExec("php -d memory_limit=-1 vendor/wp-cli/wp-cli/php/boot-fs.php i18n make-pot --merge --slug=mailpoet --domain=mailpoet --exclude=$exclude --headers=$headers . lang/mailpoet.pot")

      ->run();
  }

  public function translationsGetPotFileFromBuild() {
    $potFilePathInsideZip = 'mailpoet/lang/mailpoet.pot';
    $potFilePath = 'lang/mailpoet.pot';

    if (!is_file(self::ZIP_BUILD_PATH)) {
      $this->yell('mailpoet.zip file is missing. You must first download it using `./do release:download-zip`.', 40, 'red');
      exit(1);
    }

    if (!file_exists(__DIR__ . '/lang')) {
      $this->taskExec('mkdir -p ' . __DIR__ . '/lang')->run();
    }

    $zip = new ZipArchive();

    if ($zip->open(self::ZIP_BUILD_PATH) === true) {
      $potFileContent = $zip->getFromName($potFilePathInsideZip);
      if ($potFileContent) {
        file_put_contents($potFilePath, $potFileContent);
        $this->say('mailpoet.pot extracted from the zip file to ' . $potFilePath);
      } else {
        $this->yell('Unable to find mailpoet.pot inside the zip file.', 40, 'red');
        exit(1);
      }
    } else {
      $this->yell('Unable to open the zip file.', 40, 'red');
      exit(1);
    }
  }

  public function translationsPush() {
    $tokenEnvName = 'WP_TRANSIFEX_API_TOKEN';
    $token = getenv($tokenEnvName);
    if (!$token) {
      throw new \Exception("Please provide '$tokenEnvName' environment variable");
    }
    return $this->collectionBuilder()
      ->taskExec('php ' . __DIR__ . '/tools/transifex.php push -s')
      ->env('TX_TOKEN', $token)
      ->run();
  }

  public function testUnit(array $opts = ['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
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

  public function testIntegration(array $opts = ['file' => null, 'group' => null, 'skip-group' => null, 'xml' => false, 'multisite' => false, 'debug' => false, 'skip-deps' => false, 'skip-plugins' => false, 'disable-hpos' => false, 'enable-hpos-sync' => false, 'enable-hpos' => false, 'stop-on-fail' => false, 'wordpress-version' => null]) {
    return $this->runTestsInContainer(array_merge($opts, ['test_type' => 'integration']));
  }

  public function testMultisiteIntegration($opts = ['file' => null, 'group' => null, 'skip-group' => null, 'xml' => false, 'multisite' => true, 'skip-deps' => false, 'skip-plugins' => false, 'disable-hpos' => false, 'enable-hpos-sync' => false, 'enable-hpos' => false]) {
    return $this->runTestsInContainer(array_merge($opts, ['test_type' => 'integration']));
  }

  public function testWooIntegration(array $opts = ['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false, 'disable-hpos' => false, 'enable-hpos-sync' => false, 'enable-hpos' => false]) {
    return $this->runTestsInContainer(array_merge($opts, ['test_type' => 'integration', 'group' => 'woo', 'skip-deps' => true, 'skip-plugins' => false]));
  }

  public function testBaseIntegration(array $opts = ['file' => null, 'xml' => false, 'multisite' => false, 'debug' => false]) {
    return $this->runTestsInContainer(array_merge($opts, ['test_type' => 'integration', 'skip-group' => 'woo', 'skip-deps' => true, 'skip-plugins' => true]));
  }

  public function testNewsletterEditor($xmlOutputFile = null) {
    $command = join(' ', [
      './node_modules/.bin/mocha',
      '-r tests/javascript-newsletter-editor/mocha-test-helper.js',
      '-r tests/javascript-newsletter-editor/mocha-chai.mjs',
      'tests/javascript-newsletter-editor/testBundles/**/*.js',
      '--exit',
    ]);

    if (!empty($xmlOutputFile)) {
      $command .= sprintf(
        ' --reporter xunit --reporter-options output="%s"',
        $xmlOutputFile
      );
    }

    return $this->taskExec($command)
      ->run();
  }

  public function testJavascript($xmlOutputFile = null) {
    $command = './node_modules/.bin/mocha --recursive --require tests/javascript/mocha-env.mjs  tests/javascript --extension spec.ts';

    if (!empty($xmlOutputFile)) {
      $command .= sprintf(
        ' --reporter xunit --reporter-options output="%s"',
        $xmlOutputFile
      );
    }

    return $this->_exec($command);
  }

  public function testDebugUnit($opts = ['file' => null, 'xml' => false, 'debug' => true]) {
    return $this->testUnit($opts);
  }

  public function testDebugIntegration($opts = ['file' => null, 'xml' => false, 'debug' => true]) {
    return $this->testIntegration($opts);
  }

  public function testAcceptance($opts = ['file' => null, 'skip-deps' => false, 'group' => null, 'timeout' => null, 'disable-hpos' => false, 'enable-hpos-sync' => false, 'enable-hpos' => false, 'wordpress-version' => null]) {
    return $this->runTestsInContainer($opts);
  }

  public function testPerformance($path = null, $opts = ['url' => null, 'us' => null, 'pw' => null, 'head' => false, 'scenario' => null]) {
    $dir = __DIR__;
    if ((getenv('K6_CLOUD_TOKEN')) === false) {
      return $this->collectionBuilder()
      ->addCode([$this, 'testPerformanceSetup'])
      ->taskExec("php $dir/tools/k6.php")
      ->arg('run')
      ->option('env', 'K6_BROWSER_ENABLED=1')
      ->option('env', 'URL=' . $opts['url'])
      ->option('env', 'US=' . $opts['us'])
      ->option('env', 'PW=' . $opts['pw'])
      ->option('env', 'K6_BROWSER_HEADLESS=' . ($opts['head'] ? 'false' : 'true'))
      ->option('env', 'K6_BROWSER_TIMEOUT=120s')
      ->option('env', 'SCENARIO=' . $opts['scenario'])
      ->arg($path ?? "$dir/tests/performance/scenarios.js")
      ->dir($dir)->run();
    } else {
      return $this->collectionBuilder()
      ->addCode([$this, 'testPerformanceSetup'])
      ->taskExec("php $dir/tools/k6.php")
      ->arg('run')
      ->option('env', 'K6_BROWSER_ENABLED=1')
      ->option('env', 'URL=' . $opts['url'])
      ->option('env', 'US=' . $opts['us'])
      ->option('env', 'PW=' . $opts['pw'])
      ->option('env', 'HEADLESS=' . ($opts['head'] ? 'false' : 'true'))
      ->option('env', 'SCENARIO=' . $opts['scenario'])
      ->option('env', 'K6_CLOUD_TOKEN=' . getenv('K6_CLOUD_TOKEN'))
      ->option('env', 'K6_CLOUD_ID=' . getenv('K6_CLOUD_ID'))
      ->option('env', 'K6_PROJECT_NAME=' . $opts['scenario'])
      ->option('out', 'cloud')
      ->arg($path ?? "$dir/tests/performance/scenarios.js")
      ->dir($dir)->run();
    }
  }

  public function testPerformanceSetup() {
    // get data file URL
    $url = getenv('WP_TEST_PERFORMANCE_DATA_URL');
    if (!$url) {
      $this->yell("Please set 'WP_TEST_PERFORMANCE_DATA_URL'. You'll find it in the secret store.", 40, 'red');
      exit(1);
    }

    // download data
    $dataFile = __DIR__ . '/tests/performance/_data/data.sql';
    if (!file_exists($dataFile)) {
      $this->say('Downloading data file...');
      if (!is_dir(dirname($dataFile))) {
        mkdir(dirname($dataFile), 0777, true);
      }
      $source = gzopen($url, 'rb');
      $destination = fopen($dataFile, 'wb');
      while (!gzeof($source)) {
        fwrite($destination, gzread($source, 4096));
      }
      fclose($destination);
      gzclose($source);
      $this->say("Data file downloaded to: $dataFile");
    } else {
      $this->say("Data file already exists: $dataFile");
    }

    // import data & run WordPress setup
    $this->say('Importing data and running a WordPress setup...');
    $this->taskExec('COMPOSE_HTTP_TIMEOUT=200 docker-compose run --rm -it setup')
      ->dir(__DIR__ . '/tests/performance')
      ->run();
    $this->say('Data imported, WordPress set up.');
  }

  public function testPerformanceClean() {
    $this->taskExec('COMPOSE_HTTP_TIMEOUT=200 docker-compose down --remove-orphans -v')
      ->dir(__DIR__ . '/tests/performance')
      ->run();
  }

  public function testAcceptanceMultisite($opts = ['file' => null, 'skip-deps' => false, 'group' => null, 'timeout' => null, 'disable-hpos' => false, 'enable-hpos-sync' => false, 'enable-hpos' => false]) {
    return $this->runTestsInContainer(array_merge($opts, ['multisite' => true]));
  }

  /**
   * Deletes docker stuff related to tests including docker images.
   */
  public function deleteDocker() {
    return $this->taskExec(
      'docker-compose down -v --remove-orphans --rmi all'
    )->dir(__DIR__ . '/../tests_env/docker')->run();
  }

  /**
   * Deletes docker containers and volumes used in tests
   */
  public function resetTestDocker() {
    return $this
      ->taskExec(
        'docker-compose down -v --remove-orphans'
      )->dir(__DIR__ . '/../tests_env/docker')
      ->addCode([$this, 'cleanupCachedFiles'])
      ->run();
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

  /**
   * Creates a new migration file. Use `migrations:new db` for a db level migration or `migrations:new app` for app level migration.
   * @param $level string - db or app
   */
  public function migrationsNew($level) {
    $generator = new \MailPoet\Migrator\Repository();
    $level = strtolower($level);
    $result = $generator->create($level);
    $path = realpath($result['path']);
    $this->output->writeln('MAILPOET DATABASE MIGRATIONS');
    $this->output->writeln("============================\n");
    $this->output->writeln("New migration created ✔\n");
    $this->output->writeln("  Name:  {$result['name']}");
    $this->output->writeln("  Path:  $path");
  }

  public function migrationsStatus() {
    return $this->taskExec('vendor/bin/wp mailpoet:migrations:status');
  }

  public function migrationsRun() {
    return $this->taskExec('vendor/bin/wp mailpoet:migrations:run');
  }

  public function qa() {
    $collection = $this->collectionBuilder();
    $collection->addCode([$this, 'qaPhp']);
    $collection->addCode([$this, 'qaFrontendAssets']);
    return $collection->run();
  }

  public function qaPrettierCheck() {
    return $this->taskExec('npx prettier --check .')->dir(dirname(__DIR__));
  }

  public function qaPrettierWrite() {
    return $this->taskExec('npx prettier --write .')->dir(dirname(__DIR__));
  }

  public function qaPhp() {
    $collection = $this->collectionBuilder();
    $collection->addCode([$this, 'qaLint']);
    $collection->addCode(function() {
      return $this->qaCodeSniffer([]);
    });
    $collection->addCode(function() {
      return $this->qaMinimalPluginStandard([]);
    });
    return $collection->run();
  }

  public function qaPhpMaxWPOrg() {
    $collection = $this->collectionBuilder();
    $collection->addCode([$this, 'qaLintBuild']);
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

  public function qaLintBuild() {
    $task = './tasks/code_sniffer/vendor/bin/parallel-lint';
    $filesToCheckString = implode(' ', [
      'lib/',
      'lib-3rd-party/',
      'vendor/composer',
      'vendor/mtdowling',
      'vendor-prefixed/',
      'vendor-prefixed/soundasleep',
      'mailpoet.php',
    ]);
    // The list of files and folders to exclude is coming from build.sh
    $filesToExcludeString = '--exclude ' . implode(' --exclude ', [
      'vendor-prefixed/symfony/dependency-injection/Compiler',
      'vendor-prefixed/symfony/dependency-injection/Config',
      'vendor-prefixed/symfony/dependency-injection/Dumper',
      'vendor-prefixed/symfony/dependency-injection/Loader',
      'vendor-prefixed/symfony/dependency-injection/LazyProxy',
      'vendor-prefixed/symfony/dependency-injection/Extension',
      'vendor-prefixed/cerdic/css-tidy/COPYING',
      'vendor-prefixed/cerdic/css-tidy/NEWS',
      'vendor-prefixed/cerdic/css-tidy/testing',
      'vendor/mtdowling/cron-expression/tests',
      'vendor/phpmailer/phpmailer/test',
      'vendor-prefixed/psr/log/Psr/Log/Test',
      'vendor-prefixed/sabberworm/php-css-parser/tests',
      'vendor-prefixed/soundasleep/html2text/tests',
      'vendor-prefixed/swiftmailer/swiftmailer/tests',
      'vendor-prefixed/symfony/service-contracts/Tests',
      'vendor-prefixed/symfony/translation/Tests',
      'vendor-prefixed/symfony/translation-contracts/Tests',
      'vendor-prefixed/cerdic/css-tidy/css_optimiser.php',
      'vendor-prefixed/gregwar/captcha/demo',
    ]);

    return $this
      ->taskExec($task)
      ->rawArg(implode(' ', [$filesToExcludeString, $filesToCheckString]))
      ->run();
  }

  public function qaLintJavascript() {
    return $this->_exec('pnpm run check-types && pnpm run lint');
  }

  public function qaLintCss() {
    return $this->_exec('pnpm run stylelint-check -- "assets/css/src/**/*.scss"');
  }

  public function qaCodeSniffer(array $filesToCheck, $opts = ['severity' => 'all']) {
    $severityFlag = $opts['severity'] === 'all' ? '-w' : '-n';
    $task = implode(' ', [
      'php -d memory_limit=-1',
      './tasks/code_sniffer/vendor/bin/phpcs',
      "--parallel=" . $this->getParallelism(),
      '--extensions=php',
      $severityFlag,
      '--standard=tasks/code_sniffer/MailPoet/free-ruleset.xml',
      '-s',
    ]);

    $ignorePaths = [
      '.mp_svn',
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
      'tools/wpscan-semgrep-rules',
      'temp',
      'tests/_data',
      'tests/_output',
      'tests/_support/_generated',
      'vendor',
      'vendor-prefixed',
      'views',
    ];

    // the "--ignore" arg takes a list of regexes, we need to anchor and escape them
    $ignorePatterns = array_map(function (string $path): string {
      return '^' . preg_quote(__DIR__ . DIRECTORY_SEPARATOR . $path);
    }, $ignorePaths);

    $stringFilesToCheck = !empty($filesToCheck) ? implode(' ', $filesToCheck) : '.';

    return $this->taskExec($task)
      ->arg('--ignore=' . implode(',', $ignorePatterns))
      ->rawArg($stringFilesToCheck)
      ->run();
  }

  public function qaMinimalPluginStandard(array $filesToCheck, $opts = ['severity' => 'all']) {
    $severityFlag = $opts['severity'] === 'all' ? '-w' : '-n';

    $task = implode(' ', [
      'php -d memory_limit=-1',
      './tasks/code_sniffer/vendor/bin/phpcs',
      '--parallel=' . $this->getParallelism(),
      '--extensions=php',
      $severityFlag,
      '--standard=tasks/code_sniffer/vendor/wporg/plugin-directory/MinimalPluginStandard',
      '-s',
    ]);

    $ignorePaths = [
      '.mp_svn',
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
      'tools/wpscan-semgrep-rules',
      'temp',
      'tests/_data',
      'tests/_output',
      'tests/_support/_generated',
      'vendor',
      'vendor-prefixed',
      'views',
    ];

    // the "--ignore" arg takes a list of regexes, we need to anchor and escape them
    $ignorePatterns = array_map(function (string $path): string {
      return '^' . preg_quote(__DIR__ . DIRECTORY_SEPARATOR . $path);
    }, $ignorePaths);

    $stringFilesToCheck = !empty($filesToCheck) ? implode(' ', $filesToCheck) : '.';

    return $this
      ->taskExec($task)
      ->arg('--ignore=' . implode(',', $ignorePatterns))
      ->rawArg($stringFilesToCheck)
      ->run();
  }

  public function qaFixFile($filePath) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    if ($extension === 'php') {
      // fix PHPCS rules
      return $this->collectionBuilder()
        ->taskExec(
          './tasks/code_sniffer/vendor/bin/phpcbf ' .
            '--standard=./tasks/code_sniffer/MailPoet ' .
            '--runtime-set testVersion 7.4-8.2 ' .
            $filePath . ' -n'
        )
        ->run();
    }
    if (in_array($extension, ['js', 'jsx', 'ts', 'tsx'], true)) {
      // fix ESLint rules
      return $this->collectionBuilder()
        ->taskExec("pnpm eslint --max-warnings 0 --fix $filePath")
        ->run();
    }
  }

  public function qaPhpstan(array $opts = ['php-version' => null]) {
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
      ->taskExec($task)
      ->rawArg(
        implode(' ', [
          "$dir/lib",
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

  public function qaSemgrep() {
    return $this->_exec('./tools/semgrep.sh lib/ lib-3rd-party/');
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
        $this->releaseRerunCircleWorkflow(\MailPoetTasks\Release\CircleCiController::PROJECT_PREMIUM);
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
    // checkout trunk and pull from remote
    $this->taskGitStack()
      ->stopOnFail()
      ->checkout('trunk')
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
      ->addCode(function () use ($version) {
        return $this->releaseVerifyDownloadedZip($version);
      })
      ->addCode(function () {
        return $this->translationsGetPotFileFromBuild();
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
      ->addCode(function () {
        return $this->releaseMergePullRequest(\MailPoetTasks\Release\GitHubController::RELEASE_SOURCE_BRANCH);
      })
      ->addCode(function () {
        return $this->releaseDeleteDownloadedZip();
      })
      ->run();
  }

  public function releaseMergePullRequest(string $branch) {
    try {
      $this->createGitHubController()
        ->mergePullRequest(\MailPoetTasks\Release\CircleCiController::PROJECT_MAILPOET, $branch);
    } catch (\Exception $e) {
      $this->yell($e->getMessage(), 40, 'red');
      exit(1);
    }
    $this->say("Pull request for branch: '{$branch}' was successfully merged");
  }

  /**
   * This command displays how many pull request each person did recently
   */
  public function displayReviewers(ConsoleIO $io) {
    $io->progressStart(2);
    $freePluginGithubController = $this->createGitHubController();
    $logins = $freePluginGithubController->calculateReviewers();

    $io->progressAdvance();
    $shopGithubController = $this->createGitHubController(\MailPoetTasks\Release\GitHubController::PROJECT_SHOP);
    $loginsShop = $shopGithubController->calculateReviewers();
    $io->progressFinish();

    $printReviewers = function ($logins, $header) use ($io) {
      $io->title($header);
      $outputList = [];
      foreach ($logins as $login => $num) {
        $outputList[] = [$login => $num];
      }
      $io->definitionList(...$outputList);
    };

    arsort($logins);
    $printReviewers($logins, 'Free plugin');

    arsort($loginsShop);
    $printReviewers($loginsShop, 'Shop');

    foreach ($loginsShop as $loginShop => $num) {
      if (!isset($logins[$loginShop])) {
        $logins[$loginShop] = 0;
      }
      $logins[$loginShop] += $num;
    }
    arsort($logins);
    $printReviewers($logins, 'Full');
  }

  public function displayCreatedPullRequests(ConsoleIO $io, int $months = 6) {
    $projects = [
      \MailPoetTasks\Release\GitHubController::PROJECT_SHOP,
      \MailPoetTasks\Release\GitHubController::PROJECT_MAILPOET,
      \MailPoetTasks\Release\GitHubController::PROJECT_PREMIUM,
    ];
    $io->progressStart(count($projects));
    $counts = [];
    foreach ($projects as $project) {
      $githubController = $this->createGitHubController($project);
      $countsProject = $githubController->calculatePRcounts($months);

      foreach ($countsProject as $login => $num) {
        if (!isset($counts[$login])) {
          $counts[$login] = 0;
        }
        $counts[$login] += $num;
      }
      $io->progressAdvance();
    }
    $io->progressFinish();

    arsort($counts);
    $io->title('Pull Request counts');
    $outputList = [];
    foreach ($counts as $login => $num) {
      $outputList[] = [
        $login,
        $num,
        round($num / $months, 2),
      ];
    }
    $io->table(['Login', 'Count', 'Per month'], $outputList);
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
      [$version, $output] = $this->getReleaseVersionController()
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

  public function releaseVerifyDownloadedZip($version) {
    $this->say('Verifying ZIP file');
    $zip = new ZipArchive();

    $versionFound = false;
    if ($zip->open(self::ZIP_BUILD_PATH) === true) {
      $fileContent = $zip->getFromName('mailpoet/readme.txt');
      if ($fileContent !== false) {
        $versionFound = strpos($fileContent, 'Stable tag: ' . $version);
      }
      $zip->close();
    } else {
      $this->yell('ZIP file could not be opened!', 40, 'red');
      exit(1);
    }

    if (!$versionFound) {
      $this->yell('ZIP file does not contain required version: "' . $version . '" in readme.txt! ', 40, 'red');
      exit(1);
    }
    $this->say('ZIP file contains required version: "' . $version . '" in readme.txt.');
  }

  public function releaseDownloadZip() {
    $circleciController = $this->createCircleCiController();
    $path = $circleciController->downloadLatestBuild(self::ZIP_BUILD_PATH);
    $this->say('Release ZIP downloaded to: ' . $path);
    $this->say(sprintf('Release ZIP file size: %.2F MB', filesize($path) / pow(1024, 2)));
  }

  public function releaseDeleteDownloadedZip() {
    $this->say('Delete downloaded ZIP: ' . self::ZIP_BUILD_PATH);
    $this->taskExec('rm -f ' . self::ZIP_BUILD_PATH)->run();
    $this->say('ZIP file was deleted');
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

  public function releaseRerunCircleWorkflow(string $project = null) {
    $circleciController = $this->createCircleCiController();
    $result = $circleciController->rerunLatestWorkflow($project);
    // Sometimes can be useful to know which Circle project workflow was restarted
    $project = $project ? " for the project '{$project}'" : '';
    if (!$result) {
      $this->yell("Circle Workflow{$project} was not restarted", 40, 'red');
    } else {
      $this->say("Circle Workflow{$project} was started from the beginning");
    }
  }

  public function downloadWooCommerceMembershipsZip($tag = null) {
    if (!getenv('WP_GITHUB_USERNAME') && !getenv('WP_GITHUB_TOKEN')) {
      $this->yell("Skipping download of WooCommerce Memberships", 40, 'red');
      exit(0); // Exit with 0 since it is a valid state for some environments
    }
    $this->createGithubClient('woocommerce/woocommerce-memberships')
      ->downloadReleaseZip('woocommerce-memberships.zip', __DIR__ . '/tests/plugins/', $tag);
  }

  public function downloadWooCommerceSubscriptionsZip($tag = null) {
    if (!getenv('WP_GITHUB_USERNAME') && !getenv('WP_GITHUB_TOKEN')) {
      $this->yell("Skipping download of WooCommerce Subscriptions", 40, 'red');
      exit(0); // Exit with 0 since it is a valid state for some environments
    }
    $this->createGithubClient('woocommerce/woocommerce-subscriptions')
      ->downloadReleaseZip('woocommerce-subscriptions.zip', __DIR__ . '/tests/plugins/', $tag);
  }

  public function downloadAutomateWooZip($tag = null) {
    if (!getenv('WP_GITHUB_USERNAME') && !getenv('WP_GITHUB_TOKEN')) {
      $this->yell("Skipping download of Automate Woo", 40, 'red');
      exit(0); // Exit with 0 since it is a valid state for some environments
    }
    $this->createGithubClient('woocommerce/automatewoo')
      ->downloadReleaseZip('automatewoo.zip', __DIR__ . '/tests/plugins/', $tag);
  }

  public function downloadWooCommerceZip($tag = null) {
    $this->createWpOrgDownloader('woocommerce')
    ->downloadPluginZip('woocommerce.zip', __DIR__ . '/tests/plugins/', $tag);
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

  public function automationAddStep() {

    require_once __DIR__ . '/tasks/automation/AddStep.php';
    $yes = ['y', 'yes', 'Y', 'Yes', 'YES'];
    $type = in_array($this->ask("Is this step a trigger? [y]"), $yes) ? 'trigger' : 'action';
    $isPremium = in_array($this->ask("Is this $type a premium feature? [y]"), $yes);
    $vendor = $this->ask("Who is the vendor of the $type? (default: mailpoet)") ?? 'mailpoet';
    do {
      $id = $this->ask("What is the id of the $type?");
    } while (!$id);
    do {
      $name = $this->ask("What is the name of the $type?");
    } while (!$name);
    do {
      $description = $this->ask("Describe the $type?");
    } while (!$description);
    do {
      $keywords = array_map(
        function(string $keyword): string {
          return trim($keyword);
        },
        explode(',', $this->ask("Add some keywords (commaseparated)"))
      );
    } while (!$keywords);
    $subtitle = 'Trigger';
    if ($type === 'action') {
      do {
        $subtitle = $this->ask("What is the subtitle?");
      } while (!$subtitle);
    }
    $premiumNotice = '';
    if ($isPremium) {
      do {
        $premiumNotice = $this->ask("What is the text message for the premium modal?");
      } while (!$premiumNotice);
    }


    $generator = new \MailPoetTasks\Automation\AddStep(
      $type,
      $isPremium,
      $vendor,
      $id,
      $name,
      $description,
      $subtitle,
      $keywords,
      $premiumNotice
    );
    $generator->create();
    $this->yell(ucfirst("$type created."));

  }

  public function twigGenerateCache() {

    $templatePath = __DIR__ . '/views/'; // \MailPoet\Config\Env::$viewsPath . '/'
    $renderer = new \MailPoet\Config\Renderer(
      false,
      __DIR__ . '/generated/twig',
      new TwigFileSystem($templatePath)
    );
    $twig = $renderer->getTwig();
    foreach ($this->rsearch($templatePath, ['html', 'hbs', 'txt']) as $template) {
      $path = substr($template, strlen($templatePath));
      $twig->load($path);
    }
  }

  public function emailCreateTemplates() {
    return $this->taskExec('vendor/bin/wp mailpoet:email-editor:create-templates');
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

  protected function createGitHubController($project = \MailPoetTasks\Release\GitHubController::PROJECT_MAILPOET) {
    $help = "Use your GitHub username and a token from https://github.com/settings/tokens with 'repo' scopes.";
    return new \MailPoetTasks\Release\GitHubController(
      $this->getEnv('WP_GITHUB_USERNAME', $help),
      $this->getEnv('WP_GITHUB_TOKEN', $help),
      $project
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

  private function createWpOrgDownloader($pluginSlug) {
    require_once __DIR__ . '/tasks/WPOrgPluginDownloader.php';
    return new \MailPoetTasks\WPOrgPluginDownloader($pluginSlug);
  }

  private function createDoctrineEntityManager() {
    define('ABSPATH', getenv('WP_ROOT') . '/');
    if (\MailPoet\Config\Env::$dbPrefix === null) {
      /**
       * Ensure some prefix is set
       */
      \MailPoet\Config\Env::$dbPrefix = '';
    }
    $annotationReaderProvider = new \MailPoet\Doctrine\Annotations\AnnotationReaderProvider();
    $configuration = (new \MailPoet\Doctrine\ConfigurationFactory($annotationReaderProvider, true))->createConfiguration();
    $platformClass = \MailPoet\Doctrine\ConnectionFactory::PLATFORM_CLASS;
    return \MailPoetVendor\Doctrine\ORM\EntityManager::create([
      'driver' => \MailPoet\Doctrine\ConnectionFactory::DRIVER,
      'platform' => new $platformClass,
    ], $configuration);
  }

  private function runTestsInContainer(array $opts) {
    $testType = $opts['test_type'] ?? 'acceptance';
    $this->doctrineGenerateCache();
    return $this->taskExec(
      'COMPOSE_HTTP_TIMEOUT=200 docker-compose run ' .
      (isset($opts['wordpress-version']) && $opts['wordpress-version'] ? '-e WORDPRESS_VERSION=' . $opts['wordpress-version'] . ' ' : '') .
      (isset($opts['skip-deps']) && $opts['skip-deps'] ? '-e SKIP_DEPS=1 ' : '') .
      (isset($opts['disable-hpos']) && $opts['disable-hpos'] ? '-e DISABLE_HPOS=1 ' : '') .
      (isset($opts['enable-hpos-sync']) && $opts['enable-hpos-sync'] ? '-e ENABLE_HPOS_SYNC=1 ' : '') .
      (isset($opts['enable-hpos']) && $opts['enable-hpos'] ? '-e ENABLE_HPOS=1 ' : '') .
      (isset($opts['skip-plugins']) && $opts['skip-plugins'] ? '-e SKIP_PLUGINS=1 ' : '') .
      (isset($opts['timeout']) && $opts['timeout'] ? '-e WAIT_TIMEOUT=' . (int)$opts['timeout'] . ' ' : '') .
      (isset($opts['multisite']) && $opts['multisite'] ? '-e MULTISITE=1 ' : '-e MULTISITE=0 ') .
      "codeception_{$testType} --steps --debug -vvv " .
      (isset($opts['xml']) && $opts['xml'] ? '--xml ' : '') .
      (isset($opts['group']) && $opts['group'] ? '--group ' . $opts['group'] . ' ' : '') .
      (isset($opts['skip-group']) && $opts['skip-group'] ? '--skip-group ' . $opts['skip-group'] . ' ' : '') .
      (isset($opts['stop-on-fail']) && $opts['stop-on-fail'] ? '-f ' : '') .
      (isset($opts['file']) && $opts['file'] ? $opts['file'] : '')
    )->dir(__DIR__ . '/../tests_env/docker')->run();
  }

  private function getParallelism(int $multiplier = 1, int $min = 4, int $max = 32): int {
    $path = __DIR__ . '/../.circleci/nproc.js';
    $nproc = (int)$this->taskExec("node $path")->printOutput(false)->run()->stopOnFail()->getMessage();
    return max(min($nproc * $multiplier, $max), $min);
  }

  private function getCurrentJsPackageVersion(string $packageName) {
    $versionInfo = $this->taskExec("pnpm list $packageName")->printOutput(false)->run()->getMessage();
    $lines = explode("\n", $versionInfo);
    $lastLine = end($lines);
    return explode(' ', $lastLine)[1];
  }
}
