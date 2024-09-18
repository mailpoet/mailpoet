<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\SettingsController;

class ParagraphTest extends \MailPoetTest {
  /** @var Text */
  private $paragraphRenderer;

  /** @var array */
  private $parsedParagraph = [
    'blockName' => 'core/paragraph',
    'attrs' => [
      'style' => [
        'typography' => [
          'fontSize' => '16px',
        ],
      ],
    ],
    'innerBlocks' => [],
    'innerHTML' => '<p>Lorem Ipsum</p>',
    'innerContent' => [
      0 => '<p>Lorem Ipsum</p>',
     ],
  ];

  /** @var SettingsController */
  private $settingsController;

  public function _before() {
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->paragraphRenderer = new Text();
    $this->settingsController = $this->diContainer->get(SettingsController::class);
  }

  public function testItRendersContent(): void {
    $rendered = $this->paragraphRenderer->render('<p>Lorem Ipsum</p>', $this->parsedParagraph, $this->settingsController);
    $this->assertStringContainsString('width:100%', $rendered);
    $this->assertStringContainsString('Lorem Ipsum', $rendered);
    $this->assertStringContainsString('font-size:16px;', $rendered);
    $this->assertStringContainsString('text-align:left;', $rendered); // Check the default text-align
    $this->assertStringContainsString('align="left"', $rendered); // Check the default align
  }

  public function testItRendersContentWithPadding(): void {
    $parsedParagraph = $this->parsedParagraph;
    $parsedParagraph['attrs']['style']['spacing']['padding']['top'] = '10px';
    $parsedParagraph['attrs']['style']['spacing']['padding']['right'] = '20px';
    $parsedParagraph['attrs']['style']['spacing']['padding']['bottom'] = '30px';
    $parsedParagraph['attrs']['style']['spacing']['padding']['left'] = '40px';
    $parsedParagraph['attrs']['align'] = 'center';

    $rendered = $this->paragraphRenderer->render('<p>Lorem Ipsum</p>', $parsedParagraph, $this->settingsController);
    $this->assertStringContainsString('padding-top:10px;', $rendered);
    $this->assertStringContainsString('padding-right:20px;', $rendered);
    $this->assertStringContainsString('padding-bottom:30px;', $rendered);
    $this->assertStringContainsString('padding-left:40px;', $rendered);
    $this->assertStringContainsString('text-align:center;', $rendered);
    $this->assertStringContainsString('align="center"', $rendered);
    $this->assertStringContainsString('Lorem Ipsum', $rendered);
  }

  public function testItConvertsBlockTypography(): void {
    $parsedParagraph = $this->parsedParagraph;
    $parsedParagraph['attrs']['style']['typography'] = [
      'textTransform' => 'uppercase',
      'letterSpacing' => '1px',
      'textDecoration' => 'underline',
      'fontStyle' => 'italic',
      'fontWeight' => 'bold',
      'fontSize' => '20px',
    ];

    $rendered = $this->paragraphRenderer->render('<p>Lorem Ipsum</p>', $parsedParagraph, $this->settingsController);
    $this->assertStringContainsString('text-transform:uppercase;', $rendered);
    $this->assertStringContainsString('letter-spacing:1px;', $rendered);
    $this->assertStringContainsString('text-decoration:underline;', $rendered);
    $this->assertStringContainsString('font-style:italic;', $rendered);
    $this->assertStringContainsString('font-weight:bold;', $rendered);
    $this->assertStringContainsString('font-size:20px;', $rendered);
    $this->assertStringContainsString('Lorem Ipsum', $rendered);
  }
}
