<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaFormRenderer;
use MailPoet\Captcha\PageRenderer;
use MailPoet\Form\AssetsController;
use MailPoet\WP\Functions as WPFunctions;

class PageRendererTitleTest extends \MailPoetTest {
  private PageRenderer $pageRenderer;

  public function _before() {
    parent::_before();
    $container = $this->diContainer;
    $this->pageRenderer = new PageRenderer(
      $container->get(WPFunctions::class),
      $container->get(CaptchaFormRenderer::class),
      $container->get(AssetsController::class)
    );
  }

  public function testWindowTitleCanBeCalledWithSingleArgument(): void {
    $result = $this->pageRenderer->setWindowTitle('MailPoet Page');

    $this->assertIsString($result);
    $this->assertNotSame('', $result);
  }

  public function testWindowTitleStillWorksWithThreeArguments(): void {
    $result = $this->pageRenderer->setWindowTitle('MailPoet Page | Example', '|', 'right');

    $this->assertIsString($result);
    $this->assertStringContainsString('Confirm', $result);
  }
}
