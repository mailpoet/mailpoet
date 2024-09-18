<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\TypographyPreprocessor;
use MailPoet\EmailEditor\Engine\SettingsController;

class TypographyPreprocessorTest extends \MailPoetUnitTest {

  /** @var TypographyPreprocessor */
  private $preprocessor;

  /** @var array{contentSize: string} */
  private array $layout;

  /** @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles */
  private array $styles;

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
    // This slug translate mock expect slugs in format slug-10px and will return 10px
    $settingsMock->method('translateSlugToFontSize')->willReturnCallback(function($slug) {
      return str_replace('slug-', '', $slug);
    });
    $this->preprocessor = new TypographyPreprocessor($settingsMock);
    $this->layout = ['contentSize' => '660px'];
    $this->styles = ['spacing' => ['padding' => ['left' => '10px', 'right' => '10px', 'top' => '10px', 'bottom' => '10px'], 'blockGap' => '10px']];
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
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $result = $result[0];
    $this->assertCount(2, $result['innerBlocks']);
    $this->assertEquals($expectedEmailAttrs, $result['email_attrs']);
    $this->assertEquals($expectedEmailAttrs, $result['innerBlocks'][0]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs, $result['innerBlocks'][1]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
  }

  public function testItReplacesFontSizeSlugsWithValues(): void {
    $blocks = [[
      'blockName' => 'core/columns',
      'attrs' => [
        'fontSize' => 'slug-20px',
        'style' => [],
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
      'color' => '#000000',
      'font-size' => '20px',
    ];
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $result = $result[0];
    $this->assertCount(2, $result['innerBlocks']);
    $this->assertEquals($expectedEmailAttrs, $result['email_attrs']);
    $this->assertEquals($expectedEmailAttrs, $result['innerBlocks'][0]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs, $result['innerBlocks'][1]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
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
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $result = $result[0];
    $this->assertCount(2, $result['innerBlocks']);
    $this->assertEquals(['width' => '640px', 'color' => '#000000', 'font-size' => '13px'], $result['email_attrs']);
    $defaultFontStyles = ['color' => '#000000', 'font-size' => '13px'];
    $this->assertEquals($defaultFontStyles, $result['innerBlocks'][0]['email_attrs']);
    $this->assertEquals($defaultFontStyles, $result['innerBlocks'][1]['email_attrs']);
    $this->assertEquals($defaultFontStyles, $result['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
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
    $result = $this->preprocessor->preprocess($blocks, $this->layout, $this->styles);
    $child1 = $result[0];
    $child2 = $result[1];
    $this->assertCount(2, $child1['innerBlocks']);
    $this->assertEquals($expectedEmailAttrs1, $child1['email_attrs']);
    $this->assertEquals($expectedEmailAttrs2, $child1['innerBlocks'][0]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs2, $child1['innerBlocks'][0]['innerBlocks'][0]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs1, $child1['innerBlocks'][1]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs1, $child1['innerBlocks'][1]['innerBlocks'][0]['email_attrs']);
    $this->assertCount(1, $child2['innerBlocks']);
    $this->assertEquals(['color' => '#000000', 'font-size' => '13px'], $child2['email_attrs']);
    $this->assertEquals($expectedEmailAttrs2, $child2['innerBlocks'][0]['email_attrs']);
    $this->assertEquals($expectedEmailAttrs2, $child2['innerBlocks'][0]['innerBlocks'][0]['email_attrs']);
  }
}
