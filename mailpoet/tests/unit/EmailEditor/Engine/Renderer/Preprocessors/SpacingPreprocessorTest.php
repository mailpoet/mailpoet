<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

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
    verify($result)->arrayCount(2);
    $firstColumns = $result[0];
    $secondColumns = $result[1];

    // First elements should not have margin-top, but others should.
    verify($firstColumns['email_attrs'])->arrayHasNotKey('margin-top');
    verify($secondColumns['email_attrs'])->arrayHasKey('margin-top');
    verify($secondColumns['email_attrs']['margin-top'])->equals('10px');

    // First element children should have margin-top unless first child.
    verify($firstColumns['innerBlocks'][0]['email_attrs'])->arrayHasNotKey('margin-top');
    verify($firstColumns['innerBlocks'][1]['email_attrs'])->arrayHasKey('margin-top');
    verify($firstColumns['innerBlocks'][1]['email_attrs']['margin-top'])->equals('10px');
  }
}
