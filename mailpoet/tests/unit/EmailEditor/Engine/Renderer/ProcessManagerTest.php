<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\HighlightingPostprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Postprocessors\VariablesPostprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\BlocksWidthPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\CleanupPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\SpacingPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TypographyPreprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ProcessManager;

class ProcessManagerTest extends \MailPoetUnitTest {
  public function testItCallsPreprocessorsProperly(): void {
    $layout = [
      'contentSize' => '600px',
    ];
    $styles = [
      'spacing' => [
        'blockGap' => '0px',
        'padding' => [
          'bottom' => '0px',
          'left' => '0px',
          'right' => '0px',
          'top' => '0px',
        ],
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

    $highlighting = $this->createMock(HighlightingPostprocessor::class);
    $highlighting->expects($this->once())->method('postprocess')->willReturn('');

    $variables = $this->createMock(VariablesPostprocessor::class);
    $variables->expects($this->once())->method('postprocess')->willReturn('');

    $processManager = new ProcessManager($cleanup, $topLevel, $blocksWidth, $typography, $spacing, $highlighting, $variables);
    $processManager->registerPreprocessor($secondPreprocessor);
    verify($processManager->preprocess([], $layout, $styles))->equals([]);
    verify($processManager->postprocess(''))->equals('');
  }
}
