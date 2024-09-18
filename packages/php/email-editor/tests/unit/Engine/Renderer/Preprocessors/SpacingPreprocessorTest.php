<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\SpacingPreprocessor;

class SpacingPreprocessorTest extends \MailPoetUnitTest {

  /** @var SpacingPreprocessor */
  private $preprocessor;

  /** @var array{contentSize: string} */
  private array $layout;

  /** @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles */
  private array $styles;

  public function _before() {
    parent::_before();
    $this->preprocessor = new SpacingPreprocessor();
    $this->layout = ['contentSize' => '660px'];
    $this->styles = ['spacing' => ['padding' => ['left' => '10px', 'right' => '10px', 'top' => '10px', 'bottom' => '10px'], 'blockGap' => '10px']];
  }

  public function testItAddsDefaultVerticalSpacing(): void {
    $blocks = [
      [
        'blockName' => 'core/columns',
        'attrs' => [],
        'innerBlocks' => [
          [
            'blockName' => 'core/column',
            'attrs' => [],
            'innerBlocks' => [
              [
                'blockName' => 'core/list',
                'attrs' => [],
                'innerBlocks' => [],
              ],
              [
                'blockName' => 'core/img',
                'attrs' => [],
                'innerBlocks' => [],
              ],
            ],
          ],
          [
            'blockName' => 'core/column',
            'attrs' => [],
            'innerBlocks' => [
              [
                'blockName' => 'core/heading',
                'attrs' => [],
                'innerBlocks' => [],
              ],
              [
                'blockName' => 'core/paragraph',
                'attrs' => [],
                'innerBlocks' => [],
              ],
            ],
          ],
        ],
      ],
      [
        'blockName' => 'core/columns',
        'attrs' => [],
        'innerBlocks' => [
          [
            'blockName' => 'core/column',
            'attrs' => [],
            'innerBlocks' => [],
          ],
        ],
      ],
    ];

    $expectedEmailAttrs = ['margin-top' => '10px'];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $this->assertCount(2, $result);
    $firstColumns = $result[0];
    $secondColumns = $result[1];

    // First elements should not have margin-top, but others should.
    $this->assertArrayNotHasKey('margin-top', $firstColumns['email_attrs']);
    $this->arrayHasKey('margin-top', $secondColumns['email_attrs']);
    $this->assertEquals('10px', $secondColumns['email_attrs']['margin-top']);

    // First element children should have margin-top unless first child.
    $this->assertArrayNotHasKey('margin-top', $firstColumns['innerBlocks'][0]['email_attrs']);
    $this->assertArrayHasKey('margin-top', $firstColumns['innerBlocks'][1]['email_attrs']);
    $this->assertEquals('10px', $firstColumns['innerBlocks'][1]['email_attrs']['margin-top']);
  }
}
