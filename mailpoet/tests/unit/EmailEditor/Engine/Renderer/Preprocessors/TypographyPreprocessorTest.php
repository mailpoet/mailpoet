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
    ];
    $result = $this->preprocessor->preprocess($blocks, []);
    $result = $result[0];
    expect($result['innerBlocks'])->count(2);
    expect($result['email_attrs'])->equals($expectedEmailAttrs);
    expect($result['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs);
    expect($result['innerBlocks'][1]['email_attrs'])->equals($expectedEmailAttrs);
    expect($result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs);
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
    expect($result['innerBlocks'])->count(2);
    expect($result['email_attrs'])->equals(['width' => '640px']);
    expect($result['innerBlocks'][0]['email_attrs'])->equals([]);
    expect($result['innerBlocks'][1]['email_attrs'])->equals([]);
    expect($result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'])->equals([]);
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
    expect($child1['innerBlocks'])->count(2);
    expect($child1['email_attrs'])->equals($expectedEmailAttrs1);
    expect($child1['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
    expect($child1['innerBlocks'][0]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
    expect($child1['innerBlocks'][1]['email_attrs'])->equals($expectedEmailAttrs1);
    expect($child1['innerBlocks'][1]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs1);
    expect($child2['innerBlocks'])->count(1);
    expect($child2['email_attrs'])->equals([]);
    expect($child2['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
    expect($child2['innerBlocks'][0]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
  }
}
