<?php declare(strict_types = 1);

use Codeception\Stub;
use MailPoet\EmailEditor\Container;
use MailPoet\EmailEditor\Engine\Email_Api_Controller;
use MailPoet\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Engine\Patterns\Patterns;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Blocks_Registry;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Highlighting_Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Blocks_Width_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Cleanup_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Typography_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Process_Manager;
use MailPoet\EmailEditor\Engine\Renderer\Renderer;
use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Engine\Templates\Template_Preview;
use MailPoet\EmailEditor\Engine\Templates\Templates;
use MailPoet\EmailEditor\Engine\Templates\Utils;
use MailPoet\EmailEditor\Engine\Theme_Controller;
use MailPoet\EmailEditor\Integrations\Core\Initializer;
use MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypesController;
use MailPoet\EmailEditor\Utils\Cdn_Asset_Url;

if ((boolean)getenv('MULTISITE') === true) {
  // REQUEST_URI needs to be set for WP to load the proper subsite where MailPoet is activated
  $_SERVER['REQUEST_URI'] = '/' . getenv('WP_TEST_MULTISITE_SLUG');
  $wpLoadFile = getenv('WP_ROOT_MULTISITE') . '/wp-load.php';
} else {
  $wpLoadFile = getenv('WP_ROOT') . '/wp-load.php';
}

/**
 * Setting env from .evn file
 * Note that the following are override in the docker-compose file
 * WP_ROOT, WP_ROOT_MULTISITE, WP_TEST_MULTISITE_SLUG
 */
$console = new \Codeception\Lib\Console\Output([]);
$console->writeln('Loading WP core... (' . $wpLoadFile . ')');
require_once($wpLoadFile);

/**
 * @property IntegrationTester $tester
 */
abstract class MailPoetTest extends \Codeception\TestCase\Test { // phpcs:ignore

  public Container $diContainer;

  protected $backupGlobals = false;
  protected $backupStaticAttributes = false;
  protected $runTestInSeparateProcess = false;
  protected $preserveGlobalState = false;

  public function setUp(): void {
    $this->initContainer();
    parent::setUp();
  }

  protected function checkValidHTML(string $html): void {
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);

    // Check for errors during parsing
    $errors = libxml_get_errors();
    libxml_clear_errors();

    $this->assertEmpty($errors, 'HTML is not valid: ' . $html);
  }

  public function getServiceWithOverrides(string $id, array $overrides) {
    $instance = $this->diContainer->get($id);
    return Stub::copy($instance, $overrides);
  }

  protected function initContainer(): void {
    $container = new Container();
    // Start: MailPoet plugin dependencies
    $container->set(Initializer::class, function() {
      return new Initializer();
    });
    $container->set(Cdn_Asset_Url::class, function() {
      return new Cdn_Asset_Url('http://localhost');
    });
    $container->set(Email_Api_Controller::class, function() {
      return new Email_Api_Controller();
    });
    $container->set(BlockTypesController::class, function() {
      return $this->createMock(BlockTypesController::class);
    });
    // End: MailPoet plugin dependencies
    $container->set(Utils::class, function() {
      return new Utils();
    });
    $container->set(Theme_Controller::class, function() {
      return new Theme_Controller();
    });
    $container->set(Settings_Controller::class, function ($container) {
      return new Settings_Controller($container->get(Theme_Controller::class));
    });
    $container->set(Settings_Controller::class, function ($container) {
      return new Settings_Controller($container->get(Theme_Controller::class));
    });
    $container->set(Templates::class, function ($container) {
      return new Templates($container->get(Utils::class));
    });
    $container->set(Template_Preview::class, function ($container) {
      return new Template_Preview(
        $container->get(Theme_Controller::class),
        $container->get(Templates::class),
      );
    });
    $container->set(Patterns::class, function ($container) {
      return new Patterns(
        $container->get(Cdn_Asset_Url::class),
      );
    });
    $container->set(Cleanup_Preprocessor::class, function () {
      return new Cleanup_Preprocessor();
    });
    $container->set(Blocks_Width_Preprocessor::class, function () {
      return new Blocks_Width_Preprocessor();
    });
    $container->set(Typography_Preprocessor::class, function ($container) {
      return new Typography_Preprocessor($container->get(Settings_Controller::class));
    });
    $container->set(Spacing_Preprocessor::class, function () {
      return new Spacing_Preprocessor();
    });
    $container->set(Highlighting_Postprocessor::class, function () {
      return new Highlighting_Postprocessor();
    });
    $container->set(Variables_Postprocessor::class, function ($container) {
      return new Variables_Postprocessor($container->get(Theme_Controller::class));
    });
    $container->set(Process_Manager::class, function ($container) {
      return new Process_Manager(
        $container->get(Cleanup_Preprocessor::class),
        $container->get(Blocks_Width_Preprocessor::class),
        $container->get(Typography_Preprocessor::class),
        $container->get(Spacing_Preprocessor::class),
        $container->get(Highlighting_Postprocessor::class),
        $container->get(Variables_Postprocessor::class),
      );
    });
    $container->set(Blocks_Registry::class, function() {
      return new Blocks_Registry();
    });
    $container->set(Content_Renderer::class, function ($container) {
      return new Content_Renderer(
        $container->get(Process_Manager::class),
        $container->get(Blocks_Registry::class),
        $container->get(Settings_Controller::class),
        $container->get(Theme_Controller::class),
      );
    });
    $container->set(Renderer::class, function ($container) {
      return new Renderer(
        $container->get(Content_Renderer::class),
        $container->get(Templates::class),
        $container->get(Theme_Controller::class),
      );
    });
    $container->set(Email_Editor::class, function ($container) {
      return new Email_Editor(
        $container->get(Email_Api_Controller::class),
        $container->get(Templates::class),
        $container->get(Template_Preview::class),
        $container->get(Patterns::class),
        $container->get(Settings_Controller::class),
      );
    });

    $this->diContainer = $container;
  }
}
