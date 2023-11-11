<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\SettingsController;

class ColumnTest extends \MailPoetTest {
  /** @var Column */
  private $columnRenderer;

  /** @var array */
  private $parsedColumn = [
    'blockName' => 'core/column',
    'email_attrs' => [
      'width' => '300px',
    ],
    'attrs' => [],
      'innerBlocks' => [
        0 => [
          'blockName' => 'core/paragraph',
          'attrs' => [],
          'innerBlocks' => [],
          'innerHTML' => '<p>Column content</p>',
          'innerContent' => [
            0 => '<p>Column content</p>',
           ],
         ],
      ],
      'innerHTML' => '<div class="wp-block-column"></div>',
      'innerContent' => [
        0 => '<div class="wp-block-column">',
        1 => null,
        2 => '</div>',
     ],
  ];

  /** @var SettingsController */
  private $settingsController;

  public function _before() {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->columnRenderer = new Column();
    $this->settingsController = $this->diContainer->get(SettingsController::class);
  }

  public function testItRendersColumnContent() {
    $rendered = $this->columnRenderer->render('', $this->parsedColumn, $this->settingsController);
    $this->checkValidHTML($rendered);
    $this->assertStringContainsString('Column content', $rendered);
    $this->assertStringContainsString('width:300px;', $rendered);
    $this->assertStringContainsString('max-width:300px;', $rendered);
  }

  public function testItContainsColumnsStyles(): void {
    $parsedColumn = $this->parsedColumn;
    $parsedColumn['attrs'] = [
      'style' => [
        'color' => [
          'background' => '#abcdef',
        ],
        'spacing' => [
          'padding' => [
            'bottom' => '5px',
            'left' => '15px',
            'right' => '20px',
            'top' => '10px',
          ],
        ],
      ],
    ];
    $rendered = $this->columnRenderer->render('', $parsedColumn, $this->settingsController);
    $this->checkValidHTML($rendered);
    $this->assertStringContainsString('background:#abcdef;', $rendered);
    $this->assertStringContainsString('background-color:#abcdef;', $rendered);
    $this->assertStringContainsString('padding-bottom:5px;', $rendered);
    $this->assertStringContainsString('padding-left:15px;', $rendered);
    $this->assertStringContainsString('padding-right:20px;', $rendered);
    $this->assertStringContainsString('padding-top:10px;', $rendered);
    $this->assertStringContainsString('vertical-align:top;', $rendered); // Check for the default value of vertical alignment
  }

  public function testItContainsExpectedVerticalAlignment(): void {
    $parsedColumn = $this->parsedColumn;
    $parsedColumn['attrs']['verticalAlignment'] = 'bottom';
    $rendered = $this->columnRenderer->render('', $parsedColumn, $this->settingsController);
    $this->checkValidHTML($rendered);
    $this->assertStringContainsString('vertical-align:bottom;', $rendered);
  }
}
