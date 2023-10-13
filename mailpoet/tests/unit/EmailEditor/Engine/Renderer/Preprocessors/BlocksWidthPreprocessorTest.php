<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\BlocksWidthPreprocessor;

class BlocksWidthPreprocessorTest extends \MailPoetUnitTest {

  /** @var BlocksWidthPreprocessor */
  private $preprocessor;

  public function _before() {
    parent::_before();
    $this->preprocessor = new BlocksWidthPreprocessor();
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
    $result = $this->preprocessor->preprocess($blocks, ['width' => 660, 'padding' => ['left' => 0, 'right' => 0]]);
    $result = $result[0];
    expect($result['email_attrs']['width'])->equals(660);
    expect($result['innerBlocks'])->count(3);
    expect($result['innerBlocks'][0]['email_attrs']['width'])->equals(330); // 660 * 0.5
    expect($result['innerBlocks'][1]['email_attrs']['width'])->equals(165); // 660 * 0.25
    expect($result['innerBlocks'][2]['email_attrs']['width'])->equals(100);
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
    $result = $this->preprocessor->preprocess($blocks, ['width' => 600, 'padding' => ['left' => 20, 'right' => 20]]);
    $result = $result[0];
    expect($result['innerBlocks'])->count(3);
    expect($result['innerBlocks'][0]['email_attrs']['width'])->equals(185); // (600 - 20 - 20) * 0.33
    expect($result['innerBlocks'][1]['email_attrs']['width'])->equals(100);
    expect($result['innerBlocks'][2]['email_attrs']['width'])->equals(112); // (600 - 20 - 20) * 0.2
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
    $result = $this->preprocessor->preprocess($blocks, ['width' => 660, 'padding' => ['left' => 15, 'right' => 15]]);
    $innerBlocks = $result[0]['innerBlocks'];

    expect($innerBlocks)->count(2);
    expect($innerBlocks[0]['email_attrs']['width'])->equals(252); // (660 - 15 - 15) * 0.4
    expect($innerBlocks[0]['innerBlocks'][0]['email_attrs']['width'])->equals(232); // paragraph: 252 - 10 - 10
    expect($innerBlocks[1]['email_attrs']['width'])->equals(378); // (660 - 15 - 15) * 0.6
    expect($innerBlocks[1]['innerBlocks'][0]['email_attrs']['width'])->equals(338); // paragraph: 378 - 25 - 15
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
    $result = $this->preprocessor->preprocess($blocks, ['width' => 660, 'padding' => ['left' => 30, 'right' => 30]]);
    $innerBlocks = $result[0]['innerBlocks'];

    expect($innerBlocks)->count(3);
    expect($innerBlocks[0]['email_attrs']['width'])->equals(200); // (660 - 30 - 30) * 0.33
    expect($innerBlocks[0]['innerBlocks'][0]['email_attrs']['width'])->equals(200); // paragraph: 200
    expect($innerBlocks[1]['email_attrs']['width'])->equals(200); // (660 - 30 - 30) * 0.33
    expect($innerBlocks[1]['innerBlocks'][0]['email_attrs']['width'])->equals(200); // paragraph: 200
    expect($innerBlocks[2]['email_attrs']['width'])->equals(200); // (660 - 30 - 30) * 0.33
    expect($innerBlocks[2]['innerBlocks'][0]['email_attrs']['width'])->equals(200); // paragraph: 200
  }
}
