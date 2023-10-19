<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\Renderer\BlocksRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class ColumnsTest extends \MailPoetTest {
  /** @var BlocksRenderer */
  private $blocksRenderer;

  /** @var Columns */
  private $columnsRenderer;

  /** @var array */
  private $parsedColumns = [
    'blockName' => 'core/columns',
    'attrs' => [],
    'innerBlocks' => [
      0 => [
        'blockName' => 'core/column',
        'attrs' => [],
          'innerBlocks' => [
            0 => [
              'blockName' => 'core/paragraph',
              'attrs' => [],
              'innerBlocks' => [],
              'innerHTML' => '<p>Column 1</p>',
              'innerContent' => [
                0 => '<p>Column 1</p>',
               ],
             ],
          ],
          'innerHTML' => '<div class="wp-block-column"></div>',
          'innerContent' => [
            0 => '<div class="wp-block-column">',
            1 => null,
            2 => '</div>',
         ],
      ],
    ],
  ];

  public function _before() {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->blocksRenderer = $this->diContainer->get(BlocksRenderer::class);
    $this->columnsRenderer = new Columns();
  }

  public function testItRendersInnerColumn() {
    $settingsController = $this->diContainer->get(SettingsController::class);
    $rendered = $this->columnsRenderer->render($this->parsedColumns, $this->blocksRenderer, $settingsController);
    verify($rendered)->stringContainsString('Column 1');
  }

  public function testItRendersWidthForOneColumn() {
    $settingsController = $this->createMock(SettingsController::class);
    $settingsController->method('getEmailLayoutStyles')
      ->willReturn(['width' => 800]);
    $rendered = $this->columnsRenderer->render($this->parsedColumns, $this->blocksRenderer, $settingsController);
    verify($rendered)->stringContainsString('width:784px;');
  }

  public function testItRendersWidthForTwoColumns() {
    $settingsController = $this->createMock(SettingsController::class);
    $settingsController->method('getEmailLayoutStyles')
      ->willReturn(['width' => 800]);
    $parsedColumns = $this->parsedColumns;
    $parsedColumns['innerBlocks'][] = $parsedColumns['innerBlocks'][0]; // Insert another column
    $rendered = $this->columnsRenderer->render($parsedColumns, $this->blocksRenderer, $settingsController);
    verify($rendered)->stringContainsString('width:392px;');
  }
}
