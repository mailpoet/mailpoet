<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\PreprocessManager;
use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;

class PreprocessManagerTest extends \MailPoetUnitTest {
  public function testItCallsPreprocessorsProperly(): void {
    $topLevel = $this->createMock(TopLevelPreprocessor::class);
    $topLevel->expects($this->once())->method('preprocess')->willReturn([]);

    $secondPreprocessor = $this->createMock(TopLevelPreprocessor::class);
    $secondPreprocessor->expects($this->once())->method('preprocess')->willReturn([]);

    $preprocessManager = new PreprocessManager($topLevel);
    $preprocessManager->registerPreprocessor($secondPreprocessor);
    expect($preprocessManager->preprocess([]))->equals([]);
  }
}
