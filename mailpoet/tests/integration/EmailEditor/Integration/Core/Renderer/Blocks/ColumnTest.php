<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;

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

  public function _before() {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->columnRenderer = new Column();
  }

  public function testItRendersColumnContent() {
    $rendered = $this->columnRenderer->render('', $this->parsedColumn);
    verify($rendered)->stringContainsString('Column content');
    verify($rendered)->stringContainsString('width:300px;');
    verify($rendered)->stringContainsString('max-width:300px;');
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
    $rendered = $this->columnRenderer->render('', $parsedColumn);
    verify($rendered)->stringContainsString('background:#abcdef;');
    verify($rendered)->stringContainsString('background-color:#abcdef;');
    verify($rendered)->stringContainsString('padding-bottom:5px;');
    verify($rendered)->stringContainsString('padding-left:15px;');
    verify($rendered)->stringContainsString('padding-right:20px;');
    verify($rendered)->stringContainsString('padding-top:10px;');
  }
}
