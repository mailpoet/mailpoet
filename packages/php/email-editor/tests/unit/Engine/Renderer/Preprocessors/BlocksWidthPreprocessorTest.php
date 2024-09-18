<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\BlocksWidthPreprocessor;

class BlocksWidthPreprocessorTest extends \MailPoetUnitTest {

  /** @var BlocksWidthPreprocessor */
  private $preprocessor;

  /** @var array{contentSize: string} */
  private array $layout;

  /** @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles */
  private array $styles;

  public function _before() {
    parent::_before();
    $this->preprocessor = new BlocksWidthPreprocessor();
    $this->layout = ['contentSize' => '660px'];
    $this->styles = ['spacing' => ['padding' => ['left' => '10px', 'right' => '10px', 'top' => '10px', 'bottom' => '10px'], 'blockGap' => '10px']];
  }

  public function testItCalculatesWidthWithoutPadding(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '50%',
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '25%',
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '100px',
          ],
          'innerBlocks' => [],
        ],
      ],
    ]];
    $styles = $this->styles;
    $styles['spacing']['padding'] = ['left' => '0px', 'right' => '0px', 'top' => '0px', 'bottom' => '0px'];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $styles);
    $result = $result[0];
    $this->assertEquals('660px', $result['email_attrs']['width']);
    $this->assertCount(3, $result['innerBlocks']);
    $this->assertEquals('330px', $result['innerBlocks'][0]['email_attrs']['width']); // 660 * 0.5
    $this->assertEquals('165px', $result['innerBlocks'][1]['email_attrs']['width']); // 660 * 0.25
    $this->assertEquals('100px', $result['innerBlocks'][2]['email_attrs']['width']);
  }

  public function testItCalculatesWidthWithLayoutPadding(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '33%',
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '100px',
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '20%',
          ],
          'innerBlocks' => [],
        ],
      ],
    ]];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $result = $result[0];
    $this->assertCount(3, $result['innerBlocks']);
    $this->assertEquals('211px', $result['innerBlocks'][0]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
    $this->assertEquals('100px', $result['innerBlocks'][1]['email_attrs']['width']);
    $this->assertEquals('128px', $result['innerBlocks'][2]['email_attrs']['width']); // (660 - 10 - 10) * 0.2
  }

  public function testItCalculatesWidthOfBlockInColumn(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '40%',
            'style' => [
              'spacing' => [
                'padding' => [
                  'left' => '10px',
                  'right' => '10px',
                ],
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
          'attrs' => [
            'width' => '60%',
            'style' => [
              'spacing' => [
                'padding' => [
                  'left' => '25px',
                  'right' => '15px',
                ],
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
    ]];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $innerBlocks = $result[0]['innerBlocks'];

    $this->assertCount(2, $innerBlocks);
    $this->assertEquals('256px', $innerBlocks[0]['email_attrs']['width']); // (660 - 10 - 10) * 0.4
    $this->assertEquals('236px', $innerBlocks[0]['innerBlocks'][0]['email_attrs']['width']); // 256 - 10 - 10
    $this->assertEquals('384px', $innerBlocks[1]['email_attrs']['width']); // (660 - 10 - 10) * 0.6
    $this->assertEquals('344px', $innerBlocks[1]['innerBlocks'][0]['email_attrs']['width']); // 384 - 25 - 15
  }

  public function testItAddsMissingColumnWidth(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [],
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
          'attrs' => [],
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
          'attrs' => [],
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
    $result = $this->preprocessor->preprocess($blocks, ['contentSize' => '620px'], $this->styles);
    $innerBlocks = $result[0]['innerBlocks'];

    $this->assertCount(3, $innerBlocks);
    $this->assertEquals('200px', $innerBlocks[0]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
    $this->assertEquals('200px', $innerBlocks[0]['innerBlocks'][0]['email_attrs']['width']);
    $this->assertEquals('200px', $innerBlocks[1]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
    $this->assertEquals('200px', $innerBlocks[1]['innerBlocks'][0]['email_attrs']['width']);
    $this->assertEquals('200px', $innerBlocks[2]['email_attrs']['width']); // (660 - 10 - 10) * 0.33
    $this->assertEquals('200px', $innerBlocks[2]['innerBlocks'][0]['email_attrs']['width']);
  }

  public function testItCalculatesMissingColumnWidth(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [
        'style' => [
          'spacing' => [
            'padding' => [
              'left' => '25px',
              'right' => '15px',
            ],
          ],
        ],
      ],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '33.33%',
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '200px',
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [],
          'innerBlocks' => [],
        ],
      ],
    ]];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $innerBlocks = $result[0]['innerBlocks'];

    $this->assertCount(3, $innerBlocks);
    $this->assertEquals('200px', $innerBlocks[0]['email_attrs']['width']); // (620 - 10 - 10) * 0.3333
    $this->assertEquals('200px', $innerBlocks[1]['email_attrs']['width']); // already defined
    $this->assertEquals('200px', $innerBlocks[2]['email_attrs']['width']); // 600 -200 - 200
  }

  public function testItDoesNotSubtractPaddingForFullWidthBlocks(): void {
    $blocks = [
      [
        'blockName' => 'core/columns',
        'attrs' => [
          'align' => 'full',
        ],
        'innerBlocks' => [],
      ],
      [
        'blockName' => 'core/columns',
        'attrs' => [],
        'innerBlocks' => [],
      ],
    ];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);

    $this->assertCount(2, $result);
    $this->assertEquals('660px', $result[0]['email_attrs']['width']); // full width
    $this->assertEquals('640px', $result[1]['email_attrs']['width']); // 660 - 10 - 10
  }

  public function testItCalculatesWidthForColumnWithoutDefinition(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [
        'style' => [
          'spacing' => [
            'padding' => [
              'left' => '25px',
              'right' => '15px',
            ],
          ],
        ],
      ],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '140px',
            'style' => [
              'spacing' => [
                'padding' => [
                  'left' => '25px',
                  'right' => '15px',
                ],
              ],
            ],
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'style' => [
              'spacing' => [
                'padding' => [
                  'left' => '10px',
                  'right' => '10px',
                ],
              ],
            ],
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'style' => [
              'spacing' => [
                'padding' => [
                  'left' => '20px',
                  'right' => '20px',
                ],
              ],
            ],
          ],
          'innerBlocks' => [],
        ],
      ],
    ]];

    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $this->assertCount(3, $result[0]['innerBlocks']);
    $this->assertEquals('140px', $result[0]['innerBlocks'][0]['email_attrs']['width']);
    $this->assertEquals('220px', $result[0]['innerBlocks'][1]['email_attrs']['width']);
    $this->assertEquals('240px', $result[0]['innerBlocks'][2]['email_attrs']['width']);

    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '140px',
            'style' => [
              'spacing' => [
                'padding' => [
                  'left' => '25px',
                  'right' => '15px',
                ],
              ],
            ],
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [],
          'innerBlocks' => [],
        ],
      ],
    ]];

    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $this->assertCount(2, $result[0]['innerBlocks']);
    $this->assertEquals('140px', $result[0]['innerBlocks'][0]['email_attrs']['width']);
    $this->assertEquals('500px', $result[0]['innerBlocks'][1]['email_attrs']['width']);
  }

  public function testItCalculatesWidthForColumnWithBorder(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [
        'style' => [
          'border' => [
            'width' => '10px',
          ],
          'spacing' => [
            'padding' => [
              'left' => '25px',
              'right' => '15px',
            ],
          ],
        ],
      ],
      'innerBlocks' => [
        [
          'blockName' => 'core/column',
          'attrs' => [
            'width' => '140px',
            'style' => [
              'border' => [
                'left' => [
                  'width' => '5px',
                ],
                'right' => [
                  'width' => '5px',
                ],
              ],
              'spacing' => [
                'padding' => [
                  'left' => '25px',
                  'right' => '15px',
                ],
              ],
            ],
          ],
          'innerBlocks' => [],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [],
          'innerBlocks' => [
            [
              'blockName' => 'core/image',
              'attrs' => [],
              'innerBlocks' => [],
            ],
          ],
        ],
        [
          'blockName' => 'core/column',
          'attrs' => [
            'style' => [
              'border' => [
                'width' => '15px',
              ],
              'spacing' => [
                'padding' => [
                  'left' => '20px',
                  'right' => '20px',
                ],
              ],
            ],
          ],
          'innerBlocks' => [
            [
              'blockName' => 'core/image',
              'attrs' => [],
              'innerBlocks' => [],
            ],
          ],
        ],
      ],
    ]];

    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $this->assertCount(3, $result[0]['innerBlocks']);
    $this->assertEquals('140px', $result[0]['innerBlocks'][0]['email_attrs']['width']);
    $this->assertEquals('185px', $result[0]['innerBlocks'][1]['email_attrs']['width']);
    $this->assertEquals('255px', $result[0]['innerBlocks'][2]['email_attrs']['width']);
    $imageBlock = $result[0]['innerBlocks'][1]['innerBlocks'][0];
    $this->assertEquals('185px', $imageBlock['email_attrs']['width']);
    $imageBlock = $result[0]['innerBlocks'][2]['innerBlocks'][0];
    $this->assertEquals('215px', $imageBlock['email_attrs']['width']);
  }
}
