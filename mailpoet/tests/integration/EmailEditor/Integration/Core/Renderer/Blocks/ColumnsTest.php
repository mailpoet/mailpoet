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
  }
}
