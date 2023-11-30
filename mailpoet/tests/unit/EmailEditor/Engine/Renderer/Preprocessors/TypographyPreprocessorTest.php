<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TypographyPreprocessor;

class TypographyPreprocessorTest extends \MailPoetUnitTest {

  /** @var TypographyPreprocessor */
  private $preprocessor;

  public function _before() {
    parent::_before();
    $this->preprocessor = new TypographyPreprocessor();
  }

  public function testItCopiesColumnsTypography(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [
        'style' => [
          'color' => [
            'text' => '#aa00dd',
          ],
          'typography' => [
            'fontFamily' => 'Arial',
            'fontSize' => '12px',
            'textDecoration' => 'underline',
          ],
        ],
      ],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'innerBlocks' => [
            [
              'blockName' => 'core/paragraph',
              'attrs' => [],
              'innerBlocks' => [],
            ],
          ],
        ],
      ],
    ]];
    $expectedEmailAttrs = [
      'color' => '#aa00dd',
      'font-family' => 'Arial',
      'font-size' => '12px',
      'text-decoration' => 'underline',
    ];
    $result = $this->preprocessor->preprocess($blocks, []);
    $result = $result[0];
    verify($result['innerBlocks'])->arrayCount(2);
    verify($result['email_attrs'])->equals($expectedEmailAttrs);
    verify($result['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs);
    verify($result['innerBlocks'][1]['email_attrs'])->equals($expectedEmailAttrs);
    verify($result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs);
  }

  public function testItDoesNotCopyColumnsWidth(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [],
      'email_attrs' => [
        'width' => '640px',
      ],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'innerBlocks' => [
            [
              'blockName' => 'core/paragraph',
              'attrs' => [],
              'innerBlocks' => [],
            ],
          ],
        ],
      ],
    ]];
    $result = $this->preprocessor->preprocess($blocks, []);
    $result = $result[0];
    verify($result['innerBlocks'])->arrayCount(2);
    verify($result['email_attrs'])->equals(['width' => '640px']);
    verify($result['innerBlocks'][0]['email_attrs'])->equals([]);
    verify($result['innerBlocks'][1]['email_attrs'])->equals([]);
    verify($result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'])->equals([]);
  }

  public function testItOverridesColumnsTypography(): void {
    $blocks = [
      [
        'blockName' => 'core/columns',
        'attrs' => [
          'style' => [
            'color' => [
              'text' => '#aa00dd',
            ],
            'typography' => [
              'fontFamily' => 'Arial',
              'fontSize' => '12px',
            ],
          ],
        ],
        'innerBlocks' => [
          [
            'blockName' => 'core/column',
            'attrs' => [
              'style' => [
                'color' => [
                  'text' => '#cc22aa',
                ],
                'typography' => [
                  'fontFamily' => 'Georgia',
                  'fontSize' => '18px',
                ],
              ],
            ],
            'innerBlocks' => [
              [
                'blockName' => 'core/paragraph',
                'attrs' => [],
                'innerBlocks' => [],
              ],
            ],
          ],
          [
            'blockName' => 'core/column',
            'innerBlocks' => [
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
            'attrs' => [
              'style' => [
                'color' => [
                  'text' => '#cc22aa',
                ],
                'typography' => [
                  'fontFamily' => 'Georgia',
                  'fontSize' => '18px',
                ],
              ],
            ],
            'innerBlocks' => [
              [
                'blockName' => 'core/paragraph',
                'attrs' => [],
                'innerBlocks' => [],
              ],
            ],
          ],
        ],
      ],
    ];
    $expectedEmailAttrs1 = [
      'color' => '#aa00dd',
      'font-family' => 'Arial',
      'font-size' => '12px',
    ];
    $expectedEmailAttrs2 = [
      'color' => '#cc22aa',
      'font-family' => 'Georgia',
      'font-size' => '18px',
    ];
    $result = $this->preprocessor->preprocess($blocks, []);
    $child1 = $result[0];
    $child2 = $result[1];
    verify($child1['innerBlocks'])->arrayCount(2);
    verify($child1['email_attrs'])->equals($expectedEmailAttrs1);
    verify($child1['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
    verify($child1['innerBlocks'][0]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
    verify($child1['innerBlocks'][1]['email_attrs'])->equals($expectedEmailAttrs1);
    verify($child1['innerBlocks'][1]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs1);
    verify($child2['innerBlocks'])->arrayCount(1);
    verify($child2['email_attrs'])->equals([]);
    verify($child2['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
    verify($child2['innerBlocks'][0]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
  }
}
