<?php declare(strict_types = 1);

require_once __DIR__ . '/vendor/autoload.php';

function mp_renderer_get_content_renderer(): \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ContentRenderer {
  $themeController = new \MailPoet\EmailEditor\Engine\ThemeController();
  $settingsController = new \MailPoet\EmailEditor\Engine\SettingsController($themeController);
  $cssInliner = new \MailPoetVendor\CSS();
  $blocksRegistry = new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\BlocksRegistry($settingsController);

  $processManager = new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ProcessManager(
    new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\CleanupPreprocessor(),
    new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\TopLevelPreprocessor(),
    new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\BlocksWidthPreprocessor(),
    new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\TypographyPreprocessor($settingsController),
    new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\SpacingPreprocessor(),
    new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\HighlightingPostprocessor(),
    new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\VariablesPostprocessor($themeController)
  );
  return new \MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\ContentRenderer(
    $cssInliner,
    $processManager,
    $blocksRegistry,
    $settingsController,
    $themeController
  );
}

function mp_renderer_init() {
  $coreIntegration = new \MailPoet\EmailEditor\Integrations\Core\Initializer();
  $coreIntegration->initialize();
}
