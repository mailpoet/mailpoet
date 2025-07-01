<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Columns;

class RendererTest extends \MailPoetUnitTest {
  /** @var Renderer */
  private $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = new Renderer();
  }

  public function testItReturnsSkippedMessageWhenContentBlockHasNoBlocks() {
    $contentBlock = [
      'styles' => ['block' => []],
    ];

    $result = $this->renderer->render($contentBlock, []);
    verify($result)->equals('<!-- Skipped unsupported block -->');
  }

  public function testItReturnsSkippedMessageWhenContentBlockHasTypeButNoBlocks() {
    $contentBlock = [
      'type' => 'container',
      'styles' => ['block' => []],
    ];

    $result = $this->renderer->render($contentBlock, []);
    verify($result)->equals('<!-- Skipped unsupported block type: container -->');
  }

  public function testItReturnsSkippedMessageWhenBlocksIsNotCountable() {
    $contentBlock = [
      'type' => 'container',
      'blocks' => 'not countable',
      'styles' => ['block' => []],
    ];

    $result = $this->renderer->render($contentBlock, []);
    verify($result)->equals('<!-- Skipped unsupported block type: container -->');
  }

  public function testItRendersOneColumn() {
    $contentBlock = [
      'blocks' => [1], // Single block
      'styles' => [
        'block' => [
          'backgroundColor' => '#ffffff',
        ],
      ],
    ];

    $columnsData = ['<div>Test content</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('mailpoet_content');
    verify($result)->stringContainsString('cols-one');
    verify($result)->stringContainsString('Test content');
    verify($result)->stringContainsString('background-color:#ffffff!important;');
  }

  public function testItRendersOneColumnWithImage() {
    $contentBlock = [
      'blocks' => [1], // Single block
      'image' => [
        'src' => 'test-image.jpg',
        'display' => 'scale',
      ],
      'styles' => [
        'block' => [
          'backgroundColor' => '#f0f0f0',
        ],
      ],
    ];

    $columnsData = ['<div>Test content with image</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('mailpoet_content');
    verify($result)->stringContainsString('cols-one');
    verify($result)->stringContainsString('Test content with image');
    verify($result)->stringContainsString('background-image: url(test-image.jpg)');
    verify($result)->stringContainsString('background-size: cover');
  }

  public function testItRendersMultipleColumns() {
    $contentBlock = [
      'blocks' => [1, 2], // Two blocks
      'styles' => [
        'block' => [
          'backgroundColor' => '#e0e0e0',
        ],
      ],
    ];

    $columnsData = [
      '<div>First column</div>',
      '<div>Second column</div>',
    ];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('mailpoet_content-cols-two');
    verify($result)->stringContainsString('First column');
    verify($result)->stringContainsString('Second column');
    verify($result)->stringContainsString('width="330"');
    verify($result)->stringContainsString('align="left"');
  }

  public function testItRendersMultipleColumnsWithCustomLayout() {
    $contentBlock = [
      'blocks' => [1, 2], // Two blocks
      'columnLayout' => '1_2',
      'styles' => [
        'block' => [
          'backgroundColor' => '#d0d0d0',
        ],
      ],
    ];

    $columnsData = [
      '<div>Narrow column</div>',
      '<div>Wide column</div>',
    ];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('mailpoet_content-cols-two');
    verify($result)->stringContainsString('Narrow column');
    verify($result)->stringContainsString('Wide column');
    verify($result)->stringContainsString('width="220"');
    verify($result)->stringContainsString('width="440"');
  }

  public function testItRendersThreeColumns() {
    $contentBlock = [
      'blocks' => [1, 2, 3], // Three blocks
      'styles' => [
        'block' => [
          'backgroundColor' => '#c0c0c0',
        ],
      ],
    ];

    $columnsData = [
      '<div>First column</div>',
      '<div>Second column</div>',
      '<div>Third column</div>',
    ];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('mailpoet_content-cols-three');
    verify($result)->stringContainsString('First column');
    verify($result)->stringContainsString('Second column');
    verify($result)->stringContainsString('Third column');
    verify($result)->stringContainsString('width="220"');
    verify($result)->stringContainsString('align="right"');
  }

  public function testItHandlesTransparentBackground() {
    $contentBlock = [
      'blocks' => [1],
      'styles' => [
        'block' => [
          'backgroundColor' => 'transparent',
        ],
      ],
    ];

    $columnsData = ['<div>Transparent background</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('Transparent background');
    verify($result)->stringNotContainsString('background-color:transparent!important;');
    verify($result)->stringNotContainsString('bgcolor=');
  }

  public function testItHandlesMissingBackgroundColor() {
    $contentBlock = [
      'blocks' => [1],
      'styles' => [
        'block' => [],
      ],
    ];

    $columnsData = ['<div>No background</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('No background');
    verify($result)->stringNotContainsString('background-color');
    verify($result)->stringNotContainsString('bgcolor=');
  }

  public function testItHandlesImageWithTileDisplay() {
    $contentBlock = [
      'blocks' => [1],
      'image' => [
        'src' => 'tile-image.jpg',
        'display' => 'tile',
      ],
      'styles' => [
        'block' => [
          'backgroundColor' => '#ffffff',
        ],
      ],
    ];

    $columnsData = ['<div>Tiled image</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('Tiled image');
    verify($result)->stringContainsString('background-repeat: repeat');
    verify($result)->stringContainsString('background-size: contain');
  }

  public function testItHandlesImageWithContainDisplay() {
    $contentBlock = [
      'blocks' => [1],
      'image' => [
        'src' => 'contain-image.jpg',
        'display' => 'contain',
      ],
      'styles' => [
        'block' => [
          'backgroundColor' => '#ffffff',
        ],
      ],
    ];

    $columnsData = ['<div>Contained image</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('Contained image');
    verify($result)->stringContainsString('background-repeat: no-repeat');
    verify($result)->stringContainsString('background-size: contain');
  }

  public function testItHandlesImageWithNullSrc() {
    $contentBlock = [
      'blocks' => [1],
      'image' => [
        'src' => null,
        'display' => 'scale',
      ],
      'styles' => [
        'block' => [
          'backgroundColor' => '#ffffff',
        ],
      ],
    ];

    $columnsData = ['<div>No image src</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('No image src');
    verify($result)->stringContainsString('background-color:#ffffff!important;');
    verify($result)->stringContainsString('bgcolor="#ffffff"');
  }

  public function testItHandlesNullImage() {
    $contentBlock = [
      'blocks' => [1],
      'image' => null,
      'styles' => [
        'block' => [
          'backgroundColor' => '#ffffff',
        ],
      ],
    ];

    $columnsData = ['<div>No image</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('No image');
    verify($result)->stringContainsString('background-color:#ffffff!important;');
    verify($result)->stringContainsString('bgcolor="#ffffff"');
  }

  public function testGetOneColumnTemplate() {
    $styles = [
      'backgroundColor' => '#ff0000',
    ];

    $template = $this->renderer->getOneColumnTemplate($styles, null);
    verify($template)->arrayHasKey('content_start');
    verify($template)->arrayHasKey('content_end');
    verify($template['content_start'])->stringContainsString('mailpoet_content');
    verify($template['content_start'])->stringContainsString('cols-one');
    verify($template['content_start'])->stringContainsString('background-color:#ff0000!important;');
  }

  public function testGetOneColumnTemplateWithImage() {
    $styles = [
      'backgroundColor' => '#00ff00',
    ];

    $image = [
      'src' => 'test.jpg',
      'display' => 'scale',
    ];

    $template = $this->renderer->getOneColumnTemplate($styles, $image);
    verify($template)->arrayHasKey('content_start');
    verify($template)->arrayHasKey('content_end');
    verify($template['content_start'])->stringContainsString('mailpoet_content');
    verify($template['content_start'])->stringContainsString('cols-one');
    verify($template['content_start'])->stringContainsString('background-image: url(test.jpg)');
  }

  public function testGetMultipleColumnsContentStart() {
    $width = 330;
    $alignment = 'left';
    $class = 'cols-two';

    $result = $this->renderer->getMultipleColumnsContentStart($width, $alignment, $class);
    verify($result)->stringContainsString('width="330"');
    verify($result)->stringContainsString('align="left"');
    verify($result)->stringContainsString('mailpoet_cols-two');
    verify($result)->stringContainsString('max-width:330px');
  }

  public function testItEscapesHtmlInBackgroundStyles() {
    $contentBlock = [
      'blocks' => [1],
      'styles' => [
        'block' => [
          'backgroundColor' => '#ffffff',
        ],
      ],
    ];

    $columnsData = ['<div>Test</div>'];

    $result = $this->renderer->render($contentBlock, $columnsData);
    verify($result)->stringContainsString('background-color:#ffffff!important;');
  }
}
