<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TypographyPreprocessor;
use MailPoet\EmailEditor\Engine\SettingsController;

class TypographyPreprocessorTest extends \MailPoetUnitTest {

  /** @var TypographyPreprocessor */
  private $preprocessor;

  public function _before() {
    parent::_before();
    $settingsMock = $this->createMock(SettingsController::class);
    $themeMock = $this->createMock(\WP_Theme_JSON::class);
    $themeMock->method('get_data')->willReturn([
      'styles' => [
        'color' => [
          'text' => '#000000',
        ],
        'typography' => [
          'fontSize' => '13px',
          'fontFamily' => 'Arial',
        ],
      ],
      'settings' => [
        'typography' => [
          'fontFamilies' => [
            [
              'slug' => 'arial-slug',
              'name' => 'Arial Name',
              'fontFamily' => 'Arial',
            ],
            [
              'slug' => 'georgia-slug',
              'name' => 'Georgia Name',
              'fontFamily' => 'Georgia',
            ],
          ],
        ],
      ],
    ]);
    $settingsMock->method('getTheme')->willReturn($themeMock);
    $this->preprocessor = new TypographyPreprocessor($settingsMock);
  }

  public function testItCopiesColumnsTypography(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [
        'fontFamily' => 'arial-slug',
        'style' => [
          'color' => [
            'text' => '#aa00dd',
          ],
          'typography' => [
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
    verify($result['email_attrs'])->equals(['width' => '640px', 'color' => '#000000', 'font-size' => '13px']);
    $defaultFontStyles = ['color' => '#000000', 'font-size' => '13px'];
    verify($result['innerBlocks'][0]['email_attrs'])->equals($defaultFontStyles);
    verify($result['innerBlocks'][1]['email_attrs'])->equals($defaultFontStyles);
    verify($result['innerBlocks'][1]['innerBlocks'][0]['email_attrs'])->equals($defaultFontStyles);
  }

  public function testItOverridesColumnsTypography(): void {
    $blocks = [
      [
        'blockName' => 'core/columns',
        'attrs' => [
          'fontFamily' => 'arial-slug',
          'style' => [
            'color' => [
              'text' => '#aa00dd',
            ],
            'typography' => [
              'fontSize' => '12px',
            ],
          ],
        ],
        'innerBlocks' => [
          [
            'blockName' => 'core/column',
            'attrs' => [
              'fontFamily' => 'georgia-slug',
              'style' => [
                'color' => [
                  'text' => '#cc22aa',
                ],
                'typography' => [
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
              'fontFamily' => 'georgia-slug',
              'style' => [
                'color' => [
                  'text' => '#cc22aa',
                ],
                'typography' => [
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
      'font-size' => '12px',
    ];
    $expectedEmailAttrs2 = [
      'color' => '#cc22aa',
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
    verify($child2['email_attrs'])->equals(['color' => '#000000', 'font-size' => '13px']);
    verify($child2['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
    verify($child2['innerBlocks'][0]['innerBlocks'][0]['email_attrs'])->equals($expectedEmailAttrs2);
  }
}
