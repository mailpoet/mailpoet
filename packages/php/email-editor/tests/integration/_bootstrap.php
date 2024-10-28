<?php declare(strict_types = 1);

use Codeception\Stub;
use MailPoet\EmailEditor\Container;
use MailPoet\EmailEditor\Engine\EmailApiController;
use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\Patterns\Patterns;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlocksRegistry;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ContentRenderer;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\HighlightingPostprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\VariablesPostprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\BlocksWidthPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\CleanupPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\SpacingPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\TypographyPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ProcessManager;
use MailPoet\EmailEditor\Engine\Renderer\Renderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Engine\Templates\TemplatePreview;
use MailPoet\EmailEditor\Engine\Templates\Templates;
use MailPoet\EmailEditor\Engine\Templates\Utils;
use MailPoet\EmailEditor\Engine\ThemeController;
use MailPoet\EmailEditor\Integrations\Core\Initializer;
use MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypesController;
use MailPoet\EmailEditor\Utils\CdnAssetUrl;

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
    $container->set(CdnAssetUrl::class, function() {
      return new CdnAssetUrl('http://localhost');
    });
    $container->set(EmailApiController::class, function() {
      return new EmailApiController();
    });
    $container->set(BlockTypesController::class, function() {
      return $this->createMock(BlockTypesController::class);
    });
    // End: MailPoet plugin dependencies
    $container->set(Utils::class, function() {
      return new Utils();
    });
    $container->set(ThemeController::class, function() {
      return new ThemeController();
    });
    $container->set(SettingsController::class, function ($container) {
      return new SettingsController($container->get(ThemeController::class));
    });
    $container->set(SettingsController::class, function ($container) {
      return new SettingsController($container->get(ThemeController::class));
    });
    $container->set(Templates::class, function ($container) {
      return new Templates($container->get(Utils::class));
    });
    $container->set(TemplatePreview::class, function ($container) {
      return new TemplatePreview(
        $container->get(ThemeController::class),
        $container->get(SettingsController::class),
        $container->get(Templates::class),
      );
    });
    $container->set(Patterns::class, function ($container) {
      return new Patterns(
        $container->get(CdnAssetUrl::class),
      );
    });
    $container->set(CleanupPreprocessor::class, function () {
      return new CleanupPreprocessor();
    });
    $container->set(BlocksWidthPreprocessor::class, function () {
      return new BlocksWidthPreprocessor();
    });
    $container->set(TypographyPreprocessor::class, function ($container) {
      return new TypographyPreprocessor($container->get(SettingsController::class));
    });
    $container->set(SpacingPreprocessor::class, function () {
      return new SpacingPreprocessor();
    });
    $container->set(HighlightingPostprocessor::class, function () {
      return new HighlightingPostprocessor();
    });
    $container->set(VariablesPostprocessor::class, function ($container) {
      return new VariablesPostprocessor($container->get(ThemeController::class));
    });
    $container->set(ProcessManager::class, function ($container) {
      return new ProcessManager(
        $container->get(CleanupPreprocessor::class),
        $container->get(BlocksWidthPreprocessor::class),
        $container->get(TypographyPreprocessor::class),
        $container->get(SpacingPreprocessor::class),
        $container->get(HighlightingPostprocessor::class),
        $container->get(VariablesPostprocessor::class),
      );
    });
    $container->set(BlocksRegistry::class, function() {
      return new BlocksRegistry();
    });
    $container->set(ContentRenderer::class, function ($container) {
      return new ContentRenderer(
        $container->get(ProcessManager::class),
        $container->get(BlocksRegistry::class),
        $container->get(SettingsController::class),
        $container->get(ThemeController::class),
      );
    });
    $container->set(Renderer::class, function ($container) {
      return new Renderer(
        $container->get(ContentRenderer::class),
        $container->get(Templates::class),
        $container->get(ThemeController::class),
      );
    });
    $container->set(EmailEditor::class, function ($container) {
      return new EmailEditor(
        $container->get(EmailApiController::class),
        $container->get(Templates::class),
        $container->get(TemplatePreview::class),
        $container->get(Patterns::class),
        $container->get(SettingsController::class),
      );
    });

    $this->diContainer = $container;
  }
}
