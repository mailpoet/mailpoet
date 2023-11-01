<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;

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
    $this->columnsRenderer = new Columns();
  }

  public function testItRendersInnerColumn() {
    $rendered = $this->columnsRenderer->render('', $this->parsedColumns);
    verify($rendered)->stringContainsString('Column 1');
    verify($rendered)->stringContainsString('width:784px;');
    verify($rendered)->stringContainsString('max-width:784px;');
  }

  public function testItContainsColumnsStyles(): void {
    $parsedColumns = $this->parsedColumns;
    $parsedColumns['attrs'] = [
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
    $rendered = $this->columnsRenderer->render('', $parsedColumns);
    verify($rendered)->stringContainsString('background:#abcdef;');
    verify($rendered)->stringContainsString('background-color:#abcdef;');
    verify($rendered)->stringContainsString('padding-bottom:5px;');
    verify($rendered)->stringContainsString('padding-left:15px;');
    verify($rendered)->stringContainsString('padding-right:20px;');
    verify($rendered)->stringContainsString('padding-top:10px;');
  }
}
