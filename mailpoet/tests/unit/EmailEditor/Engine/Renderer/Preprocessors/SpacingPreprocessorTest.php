<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\SpacingPreprocessor;
use MailPoet\EmailEditor\Engine\SettingsController;

class SpacingPreprocessorTest extends \MailPoetUnitTest {

  /** @var SpacingPreprocessor */
  private $preprocessor;

  public function _before() {
    parent::_before();
    $this->preprocessor = new SpacingPreprocessor();
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

    $expectedEmailAttrs = ['margin-top' => SettingsController::FLEX_GAP];
    $result = $this->preprocessor->preprocess($blocks, []);
    verify($result)->arrayCount(2);
    $firstColumns = $result[0];
    $secondColumns = $result[1];
    verify($firstColumns)->arrayHasNotKey('email_attrs');
    verify($secondColumns['email_attrs'])->equals($expectedEmailAttrs);
    verify($firstColumns['innerBlocks'][0])->arrayHasNotKey('email_attrs');
    verify($secondColumns['innerBlocks'][0])->arrayHasNotKey('email_attrs');
    // Verify margins for the first columns blocks
    $firstColumn = $firstColumns['innerBlocks'][0];
    verify($firstColumn['innerBlocks'][0])->arrayHasNotKey('email_attrs');
    verify($firstColumn['innerBlocks'][1]['email_attrs'])->equals($expectedEmailAttrs);
    $secondColumn = $firstColumns['innerBlocks'][1];
    verify($secondColumn['innerBlocks'][0])->arrayHasNotKey('email_attrs');
    verify($secondColumn['innerBlocks'][1]['email_attrs'])->equals($expectedEmailAttrs);
  }
}
