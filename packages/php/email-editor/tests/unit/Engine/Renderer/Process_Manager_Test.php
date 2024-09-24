<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Highlighting_Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Variables_Postprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Blocks_Width_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Cleanup_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Typography_Preprocessor;
use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Process_Manager;

class Process_Manager_Test extends \MailPoetUnitTest {
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

    $cleanup = $this->createMock(Cleanup_Preprocessor::class);
    $cleanup->expects($this->once())->method('preprocess')->willReturn([]);

    $blocksWidth = $this->createMock(Blocks_Width_Preprocessor::class);
    $blocksWidth->expects($this->once())->method('preprocess')->willReturn([]);

    $typography = $this->createMock(Typography_Preprocessor::class);
    $typography->expects($this->once())->method('preprocess')->willReturn([]);

    $spacing = $this->createMock(Spacing_Preprocessor::class);
    $spacing->expects($this->once())->method('preprocess')->willReturn([]);

    $highlighting = $this->createMock(Highlighting_Postprocessor::class);
    $highlighting->expects($this->once())->method('postprocess')->willReturn('');

    $variables = $this->createMock(Variables_Postprocessor::class);
    $variables->expects($this->once())->method('postprocess')->willReturn('');

    $processManager = new Process_Manager($cleanup, $blocksWidth, $typography, $spacing, $highlighting, $variables);
    $this->assertEquals([], $processManager->preprocess([], $layout, $styles));
    $this->assertEmpty($processManager->postprocess(''));
  }
}
