<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\PreprocessManager;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\BlocksWidthPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\CleanupPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\SpacingPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TypographyPreprocessor;

class PreprocessManagerTest extends \MailPoetUnitTest {
  public function testItCallsPreprocessorsProperly(): void {
    $layoutStyles = [
      'width' => '600px',
      'background' => '#ffffff',
      'padding' => [
        'bottom' => '0px',
        'left' => '0px',
        'right' => '0px',
        'top' => '0px',
      ],
    ];
    $topLevel = $this->createMock(TopLevelPreprocessor::class);
    $topLevel->expects($this->once())->method('preprocess')->willReturn([]);

    $cleanup = $this->createMock(CleanupPreprocessor::class);
    $cleanup->expects($this->once())->method('preprocess')->willReturn([]);

    $blocksWidth = $this->createMock(BlocksWidthPreprocessor::class);
    $blocksWidth->expects($this->once())->method('preprocess')->willReturn([]);

    $typography = $this->createMock(TypographyPreprocessor::class);
    $typography->expects($this->once())->method('preprocess')->willReturn([]);

    $spacing = $this->createMock(SpacingPreprocessor::class);
    $spacing->expects($this->once())->method('preprocess')->willReturn([]);

    $secondPreprocessor = $this->createMock(TopLevelPreprocessor::class);
    $secondPreprocessor->expects($this->once())->method('preprocess')->willReturn([]);

    $preprocessManager = new PreprocessManager($cleanup, $topLevel, $blocksWidth, $typography, $spacing);
    $preprocessManager->registerPreprocessor($secondPreprocessor);
    verify($preprocessManager->preprocess([], $layoutStyles))->equals([]);
  }
}
