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
    $result = $this->preprocessor->preprocess($blocks, ['width' => '660px', 'padding' => ['left' => '0px', 'right' => '0px']]);
    $result = $result[0];
    expect($result['email_attrs']['width'])->equals('660px');
    expect($result['innerBlocks'])->count(3);
    expect($result['innerBlocks'][0]['email_attrs']['width'])->equals('330px'); // 660 * 0.5
    expect($result['innerBlocks'][1]['email_attrs']['width'])->equals('165px'); // 660 * 0.25
    expect($result['innerBlocks'][2]['email_attrs']['width'])->equals('100px');
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
    $result = $this->preprocessor->preprocess($blocks, ['width' => '600px', 'padding' => ['left' => '20px', 'right' => '20px']]);
    $result = $result[0];
    expect($result['innerBlocks'])->count(3);
    expect($result['innerBlocks'][0]['email_attrs']['width'])->equals('185px'); // (600 - 20 - 20) * 0.33
    expect($result['innerBlocks'][1]['email_attrs']['width'])->equals('100px');
    expect($result['innerBlocks'][2]['email_attrs']['width'])->equals('112px'); // (600 - 20 - 20) * 0.2
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
    $result = $this->preprocessor->preprocess($blocks, ['width' => '660px', 'padding' => ['left' => '15px', 'right' => '15px']]);
    $innerBlocks = $result[0]['innerBlocks'];

    expect($innerBlocks)->count(2);
    expect($innerBlocks[0]['email_attrs']['width'])->equals('252px'); // (660 - 15 - 15) * 0.4
    expect($innerBlocks[0]['innerBlocks'][0])->hasNotKey('email_attrs'); // paragraph block should not have width
    expect($innerBlocks[1]['email_attrs']['width'])->equals('378px'); // (660 - 15 - 15) * 0.6
    expect($innerBlocks[1]['innerBlocks'][0])->hasNotKey('email_attrs'); // paragraph block should not have width
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
    $result = $this->preprocessor->preprocess($blocks, ['width' => '660px', 'padding' => ['left' => '30px', 'right' => '30px']]);
    $innerBlocks = $result[0]['innerBlocks'];

    expect($innerBlocks)->count(3);
    expect($innerBlocks[0]['email_attrs']['width'])->equals('200px'); // (660 - 30 - 30) * 0.33
    expect($innerBlocks[0]['innerBlocks'][0])->hasNotKey('email_attrs'); // paragraph block should not have width
    expect($innerBlocks[1]['email_attrs']['width'])->equals('200px'); // (660 - 30 - 30) * 0.33
    expect($innerBlocks[1]['innerBlocks'][0])->hasNotKey('email_attrs'); // paragraph block should not have width
    expect($innerBlocks[2]['email_attrs']['width'])->equals('200px'); // (660 - 30 - 30) * 0.33
    expect($innerBlocks[2]['innerBlocks'][0])->hasNotKey('email_attrs'); // paragraph block should not have width
  }
}
