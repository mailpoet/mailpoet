<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Postprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\VariablesPostprocessor;
use MailPoet\EmailEditor\Engine\ThemeController;
use PHPUnit\Framework\MockObject\MockObject;

class VariablesPostprocessorTest extends \MailPoetUnitTest {
  private VariablesPostprocessor $postprocessor;

  /** @var ThemeController & MockObject */
  private $themeControllerMock;

  public function _before() {
    parent::_before();
    $this->themeControllerMock = $this->createMock(ThemeController::class);
    $this->postprocessor = new VariablesPostprocessor($this->themeControllerMock);
  }

  public function testItReplacesVariablesInStyleAttributes(): void {
    $variablesMap = [
      '--wp--preset--spacing--10' => '10px',
      '--wp--preset--spacing--20' => '20px',
      '--wp--preset--spacing--30' => '30px',
    ];
    $this->themeControllerMock->method('getVariablesValuesMap')->willReturn($variablesMap);
    $html = '<div style="padding:var(--wp--preset--spacing--10);margin:var(--wp--preset--spacing--20)"><p style="color:white;padding-left:var(--wp--preset--spacing--10);">Helloo I have padding var(--wp--preset--spacing--10); </p></div>';
    $result = $this->postprocessor->postprocess($html);
    verify($result)->equals('<div style="padding:10px;margin:20px"><p style="color:white;padding-left:10px;">Helloo I have padding var(--wp--preset--spacing--10); </p></div>');
  }
}
