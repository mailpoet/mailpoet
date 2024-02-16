<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\SettingsController;

class ColumnsTest extends \MailPoetTest {
  /** @var Columns */
  private $columnsRenderer;

  /** @var array */
  private $parsedColumns = [
    'blockName' => 'core/columns',
    'attrs' => [],
    'email_attrs' => [
      'width' => '784px',
    ],
    'innerHTML' => '<div class="wp-block-columns"></div>',
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

  /** @var SettingsController */
  private $settingsController;

  public function _before() {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->columnsRenderer = new Columns();
    $this->settingsController = $this->diContainer->get(SettingsController::class);
  }

  public function testItRendersInnerColumn() {
    $rendered = $this->columnsRenderer->render('', $this->parsedColumns, $this->settingsController);
    verify($rendered)->stringContainsString('Column 1');
    verify($rendered)->stringContainsString('width:784px;');
    verify($rendered)->stringContainsString('max-width:784px;');
  }

  public function testItContainsColumnsStyles(): void {
    $parsedColumns = $this->parsedColumns;
    $parsedColumns['attrs'] = [
      'style' => [
        'border' => [
          'color' => '#123456',
          'radius' => '10px',
          'width' => '2px',
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
    $rendered = $this->columnsRenderer->render('', $parsedColumns, $this->settingsController);
    verify($rendered)->stringContainsString('background:#abcdef;');
    verify($rendered)->stringContainsString('background-color:#abcdef;');
    verify($rendered)->stringContainsString('border-bottom:2px solid #123456;');
    verify($rendered)->stringContainsString('border-left:2px solid #123456;');
    verify($rendered)->stringContainsString('border-right:2px solid #123456;');
    verify($rendered)->stringContainsString('border-top:2px solid #123456;');
    verify($rendered)->stringContainsString('border-radius:10px 10px 10px 10px;');
    verify($rendered)->stringContainsString('padding-bottom:5px;');
    verify($rendered)->stringContainsString('padding-left:15px;');
    verify($rendered)->stringContainsString('padding-right:20px;');
    verify($rendered)->stringContainsString('padding-top:10px;');
  }

  public function testItSetsCustomColorAndBackground(): void {
    $parsedColumns = $this->parsedColumns;
    $parsedColumns['attrs']['style']['color']['text'] = '#123456';
    $parsedColumns['attrs']['style']['color']['background'] = '#654321';
    $rendered = $this->columnsRenderer->render('', $parsedColumns, $this->settingsController);
    $this->checkValidHTML($rendered);
    $this->assertStringContainsString('color:#123456;', $rendered);
    $this->assertStringContainsString('background-color:#654321;', $rendered);
    $this->assertStringContainsString('background:#654321;', $rendered);
  }

  public function testItPreservesClassesSetByEditor(): void {
    $parsedColumns = $this->parsedColumns;
    $content = '<div class="wp-block-columns editor-class-1 another-class"></div>';
    $parsedColumns['attrs']['style']['color']['background'] = '#654321';
    $rendered = $this->columnsRenderer->render($content, $parsedColumns, $this->settingsController);
    $this->checkValidHTML($rendered);
    $this->assertStringContainsString('wp-block-columns editor-class-1 another-class', $rendered);
  }
}
