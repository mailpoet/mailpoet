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
  }

  public function testItContainsColumnsStyles(): void {
    $parsedColumn = $this->parsedColumn;
    $parsedColumn['attrs'] = [
      'style' => [
        'border' => [
          'bottom' => [
            'color' => '#111111',
            'width' => '1px',
          ],
          'left' => [
            'color' => '#222222',
            'width' => '2px',
          ],
          'right' => [
            'color' => '#333333',
            'width' => '3px',
          ],
          'top' => [
            'color' => '#444444',
            'width' => '4px',
          ],
          'radius' => [
            'bottomLeft' => '5px',
            'bottomRight' => '10px',
            'topLeft' => '15px',
            'topRight' => '20px',
          ],
        ],
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
    $this->assertStringContainsString('background-color:#abcdef;', $rendered);
    $this->assertStringContainsString('border-bottom-left-radius:5px;', $rendered);
    $this->assertStringContainsString('border-bottom-right-radius:10px;', $rendered);
    $this->assertStringContainsString('border-top-left-radius:15px;', $rendered);
    $this->assertStringContainsString('border-top-right-radius:20px;', $rendered);
    $this->assertStringContainsString('border-top-color:#444444;', $rendered);
    $this->assertStringContainsString('border-top-width:4px;', $rendered);
    $this->assertStringContainsString('border-right-color:#333333;', $rendered);
    $this->assertStringContainsString('border-right-width:3px;', $rendered);
    $this->assertStringContainsString('border-bottom-color:#111111;', $rendered);
    $this->assertStringContainsString('border-bottom-width:1px;', $rendered);
    $this->assertStringContainsString('border-left-color:#222222;', $rendered);
    $this->assertStringContainsString('border-left-width:2px;', $rendered);
    $this->assertStringContainsString('border-style:solid;', $rendered);
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

  public function testItSetsCustomColorAndBackground(): void {
    $parsedColumn = $this->parsedColumn;
    $parsedColumn['attrs']['style']['color']['text'] = '#123456';
    $parsedColumn['attrs']['style']['color']['background'] = '#654321';
    $rendered = $this->columnRenderer->render('', $parsedColumn, $this->settingsController);
    $this->checkValidHTML($rendered);
    $this->assertStringContainsString('color:#123456;', $rendered);
    $this->assertStringContainsString('background-color:#654321;', $rendered);
  }

  public function testItPreservesClassesSetByEditor(): void {
    $parsedColumn = $this->parsedColumn;
    $content = '<div class="wp-block-column editor-class-1 another-class"></div>';
    $parsedColumn['attrs']['style']['color']['background'] = '#654321';
    $rendered = $this->columnRenderer->render($content, $parsedColumn, $this->settingsController);
    $this->checkValidHTML($rendered);
    $this->assertStringContainsString('wp-block-column editor-class-1 another-class', $rendered);
  }
}
