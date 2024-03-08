<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\BlocksWidthPreprocessor;

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
    verify($result['email_attrs']['width'])->equals('660px');
    verify($result['innerBlocks'])->arrayCount(3);
    verify($result['innerBlocks'][0]['email_attrs']['width'])->equals('330px'); // 660 * 0.5
    verify($result['innerBlocks'][1]['email_attrs']['width'])->equals('165px'); // 660 * 0.25
    verify($result['innerBlocks'][2]['email_attrs']['width'])->equals('100px');
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
    verify($result['innerBlocks'])->arrayCount(3);
    verify($result['innerBlocks'][0]['email_attrs']['width'])->equals('211px'); // (660 - 10 - 10) * 0.33
    verify($result['innerBlocks'][1]['email_attrs']['width'])->equals('100px');
    verify($result['innerBlocks'][2]['email_attrs']['width'])->equals('128px'); // (660 - 10 - 10) * 0.2
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

    verify($innerBlocks)->arrayCount(2);
    verify($innerBlocks[0]['email_attrs']['width'])->equals('256px'); // (660 - 10 - 10) * 0.4
    verify($innerBlocks[0]['innerBlocks'][0]['email_attrs']['width'])->equals('236px'); // 256 - 10 - 10
    verify($innerBlocks[1]['email_attrs']['width'])->equals('384px'); // (660 - 10 - 10) * 0.6
    verify($innerBlocks[1]['innerBlocks'][0]['email_attrs']['width'])->equals('344px'); // 384 - 25 - 15
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

    verify($innerBlocks)->arrayCount(3);
    verify($innerBlocks[0]['email_attrs']['width'])->equals('200px'); // (660 - 10 - 10) * 0.33
    verify($innerBlocks[0]['innerBlocks'][0]['email_attrs']['width'])->equals('200px');
    verify($innerBlocks[1]['email_attrs']['width'])->equals('200px'); // (660 - 10 - 10) * 0.33
    verify($innerBlocks[1]['innerBlocks'][0]['email_attrs']['width'])->equals('200px');
    verify($innerBlocks[2]['email_attrs']['width'])->equals('200px'); // (660 - 10 - 10) * 0.33
    verify($innerBlocks[2]['innerBlocks'][0]['email_attrs']['width'])->equals('200px');
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

    verify($innerBlocks)->arrayCount(3);
    verify($innerBlocks[0]['email_attrs']['width'])->equals('200px'); // (620 - 10 - 10) * 0.3333
    verify($innerBlocks[1]['email_attrs']['width'])->equals('200px'); // already defined
    verify($innerBlocks[2]['email_attrs']['width'])->equals('200px'); // 600 -200 - 200
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

    verify($result)->arrayCount(2);
    verify($result[0]['email_attrs']['width'])->equals('660px'); // full width
    verify($result[1]['email_attrs']['width'])->equals('640px'); // 660 - 10 - 10
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
    verify($result[0]['innerBlocks'])->arrayCount(3);
    verify($result[0]['innerBlocks'][0]['email_attrs']['width'])->equals('140px');
    verify($result[0]['innerBlocks'][1]['email_attrs']['width'])->equals('220px');
    verify($result[0]['innerBlocks'][2]['email_attrs']['width'])->equals('240px');

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
    verify($result[0]['innerBlocks'])->arrayCount(2);
    verify($result[0]['innerBlocks'][0]['email_attrs']['width'])->equals('140px');
    verify($result[0]['innerBlocks'][1]['email_attrs']['width'])->equals('500px');
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
    verify($result[0]['innerBlocks'])->arrayCount(3);
    verify($result[0]['innerBlocks'][0]['email_attrs']['width'])->equals('140px');
    verify($result[0]['innerBlocks'][1]['email_attrs']['width'])->equals('185px');
    verify($result[0]['innerBlocks'][2]['email_attrs']['width'])->equals('255px');
    $imageBlock = $result[0]['innerBlocks'][1]['innerBlocks'][0];
    verify($imageBlock['email_attrs']['width'])->equals('185px');
    $imageBlock = $result[0]['innerBlocks'][2]['innerBlocks'][0];
    verify($imageBlock['email_attrs']['width'])->equals('215px');
  }
}
