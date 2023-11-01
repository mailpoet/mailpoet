<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;

class ParagraphTest extends \MailPoetTest {
  /** @var Paragraph */
  private $paragraphRenderer;

  /** @var array */
  private $parsedParagraph = [
    'blockName' => 'core/paragraph',
    'email_attrs' => [
      'font-family' => 'Arial',
      'font-size' => '16px',
    ],
    'innerBlocks' => [],
    'innerHTML' => '<p>Lorem Ipsum</p>',
    'innerContent' => [
      0 => '<p>Lorem Ipsum</p>',
     ],
  ];

  public function _before() {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->paragraphRenderer = new Paragraph();
  }

  public function testItRendersContent(): void {
    $rendered = $this->paragraphRenderer->render('<p>Lorem Ipsum</p>', $this->parsedParagraph);
    verify($rendered)->stringNotContainsString('width:'); // Paragraph should not contain width
    verify($rendered)->stringContainsString('Lorem Ipsum');
    verify($rendered)->stringContainsString('font-size:16px;');
    verify($rendered)->stringContainsString('font-family:Arial;');
  }
}
