<?php declare(strict_types = 1);

namespace MailPoet\Form;

use MailPoet\Config\Renderer as TemplateRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class AssetsControllerTest extends \MailPoetUnitTest {
  /** @var WPFunctions & MockObject */
  private $wp;

  /** @var TemplateRenderer & MockObject */
  private $renderer;

  /** @var SettingsController & MockObject */
  private $settings;

  /** @var AssetsController */
  private $controller;

  public function _before() {
    parent::_before();
    $this->wp = $this->createMock(WPFunctions::class);
    $this->renderer = $this->createMock(TemplateRenderer::class);
    $this->renderer->method('getJsAsset')->willReturnArgument(0);
    $this->renderer->method('getCssAsset')->willReturnArgument(0);
    $this->settings = $this->createMock(SettingsController::class);
    $this->settings->method('get')->willReturn([]);
    $this->controller = new AssetsController($this->wp, $this->renderer, $this->settings);
  }

  public function testPublicScriptUsesMinJsNameSoOptimizersSkipReminifyingIt() {
    $this->wp->expects($this->once())->method('wpEnqueueScript')->with(
      'mailpoet_public',
      $this->stringEndsWith('/dist/js/public.min.js')
    );
    $this->controller->setupFrontEndDependencies();
  }
}
