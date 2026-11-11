<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\AccessControl;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class LandingpageTest extends \MailPoetUnitTest {
  public function testItTellsTheFrontendWhenTheUserCanManageSettings() {
    verify($this->renderPageData(true)['can_manage_settings'])->true();
  }

  public function testItTellsTheFrontendWhenTheUserCannotManageSettings() {
    verify($this->renderPageData(false)['can_manage_settings'])->false();
  }

  private function renderPageData(bool $canManageSettings): array {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('adminUrl')->willReturnArgument(0);
    $wp->method('currentUserCan')->willReturnMap([
      [AccessControl::PERMISSION_MANAGE_SETTINGS, $canManageSettings],
    ]);

    $data = [];
    $pageRenderer = $this->createMock(PageRenderer::class);
    $pageRenderer->expects($this->once())
      ->method('displayPage')
      ->willReturnCallback(function (string $template, array $pageData) use (&$data) {
        verify($template)->equals('landingpage.html');
        $data = $pageData;
      });

    $page = new Landingpage($pageRenderer, $wp, $this->createMock(SettingsController::class));
    $page->render();
    return $data;
  }
}
