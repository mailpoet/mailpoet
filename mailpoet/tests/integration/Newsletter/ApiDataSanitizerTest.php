<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

class ApiDataSanitizerTest extends \MailPoetTest {

  /** @var ApiDataSanitizer */
  private $sanitizer;

  private $body = [
    'content' => [
      'blocks' => [
        [
          'type' => 'container',
          'columnLayout' => false,
          'orientation' => 'vertical',
          'blocks' => [
            [
              'type' => 'text',
              'text' => '<p>Thanks for reading.<img src=x onerror=alert(4)> See you soon!</p>',
            ],
            [
              'type' => 'footer',
              'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a><br />Add your postal address here!</p>',
            ],
          ],
        ],
        [
          'type' => 'header',
          'link' => '',
          'text' => 'http://some.url/wp-c\'"><img src=x onerror=alert(2)>ontent/fake-logo.png',
        ],
      ],
    ],
  ];

  public function _before() {
    parent::_before();
    $this->sanitizer = $this->diContainer->get(ApiDataSanitizer::class);
  }

  public function testItSanitizesBody() {
    $result = $this->sanitizer->sanitizeBody($this->body);
    $container = $result['content']['blocks'][0];
    $block1 = $container['blocks'][0];
    $block2 = $container['blocks'][1];
    verify($container['columnLayout'])->equals(false);
    verify($block1['type'])->equals('text');
    verify($block1['text'])->equals('<p>Thanks for reading. See you soon!</p>');
    verify($block2['type'])->equals('footer');
    $this->assertSameHtml('<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a><br />Add your postal address here!</p>', $block2['text']);
    $image = $result['content']['blocks'][1];
    verify($image['type'])->equals('header');
    verify($image['link'])->equals('');
    verify($image['text'])->equals('http://some.url/wp-c\'"&gt;ontent/fake-logo.png');
  }

  /**
   * @dataProvider textValueProvider
   * @param mixed $text
   */
  public function testItStoresTextAsSanitizedStringForAnyValueType($text, string $expected) {
    $body = $this->bodyWithBlocks([['type' => 'text', 'text' => $text]]);

    $result = $this->sanitizer->sanitizeBody($body);

    $this->assertSame($expected, $result['content']['blocks'][0]['text']);
  }

  public function textValueProvider(): array {
    return [
      'list' => [['<img src=x onerror=alert(1)>'], ''],
      'map' => [['text' => '<img src=x onerror=alert(1)>'], ''],
      'integer' => [123, '123'],
      'float' => [1.5, '1.5'],
      'true' => [true, '1'],
      'false' => [false, ''],
    ];
  }

  public function testItLeavesNullAndMissingTextUntouched() {
    $body = $this->bodyWithBlocks([
      ['type' => 'text', 'text' => null],
      ['type' => 'text'],
    ]);

    $result = $this->sanitizer->sanitizeBody($body);

    $this->assertNull($result['content']['blocks'][0]['text']);
    $this->assertArrayNotHasKey('text', $result['content']['blocks'][1]);
  }

  public function testItSanitizesBlockTextWhenBlockHasNestedBlocks() {
    $body = $this->bodyWithBlocks([
      [
        'type' => 'text',
        'text' => '<p>Hello<img src=x onerror=alert(1)> there</p>',
        'blocks' => [
          [
            'type' => 'text',
            'text' => '<p>Nested<img src=x onerror=alert(2)> text</p>',
          ],
        ],
      ],
    ]);
    $block = $this->sanitizer->sanitizeBody($body)['content']['blocks'][0];
    verify($block['text'])->equals('<p>Hello there</p>');
    verify($block['blocks'][0]['text'])->equals('<p>Nested text</p>');
  }

  public function testItSanitizesBlockTextWhenNestedBlocksAreEmpty() {
    $body = $this->bodyWithBlocks([
      [
        'type' => 'text',
        'text' => '<p>Empty<img src=x onerror=alert(3)> children</p>',
        'blocks' => [],
      ],
    ]);
    $block = $this->sanitizer->sanitizeBody($body)['content']['blocks'][0];
    verify($block['text'])->equals('<p>Empty children</p>');
    verify($block['blocks'])->equals([]);
  }

  public function testItSanitizesNestedBlocksOfBlockWithoutType() {
    $body = $this->bodyWithBlocks([
      [
        'blocks' => [
          [
            'type' => 'text',
            'text' => '<p>Nested<img src=x onerror=alert(4)> text</p>',
          ],
        ],
      ],
    ]);
    $block = $this->sanitizer->sanitizeBody($body)['content']['blocks'][0];
    verify($block)->arrayHasNotKey('type');
    verify($block['blocks'][0]['text'])->equals('<p>Nested text</p>');
  }

  public function testItLeavesBlockWithNonStringTypeUntouched() {
    $body = $this->bodyWithBlocks([
      [
        'type' => ['text'],
        'text' => '<p>Some text</p>',
      ],
    ]);
    $block = $this->sanitizer->sanitizeBody($body)['content']['blocks'][0];
    verify($block['type'])->equals(['text']);
    verify($block['text'])->equals('<p>Some text</p>');
  }

  public function testItSanitizesBlockDefaults() {
    $body = [
      'content' => ['blocks' => []],
      'blockDefaults' => [
        'text' => ['text' => '<p>Default<img src=x onerror=alert(1)> text</p>', 'styles' => []],
        'footer' => ['text' => '<p>Footer<img src=x onerror=alert(2)> text</p>'],
        'button' => ['text' => 'Read <b>more</b>'],
      ],
    ];
    $result = $this->sanitizer->sanitizeBody($body);
    verify($result['blockDefaults']['text'])->equals(['text' => '<p>Default text</p>', 'styles' => []]);
    verify($result['blockDefaults']['footer']['text'])->equals('<p>Footer text</p>');
    verify($result['blockDefaults']['button']['text'])->equals('Read <b>more</b>');
  }

  public function testItEmptiesBlockStyleValuesThatCouldEndTheDeclaration() {
    $body = $this->bodyWithBlocks([
      [
        'type' => 'button',
        'styles' => [
          'block' => [
            'backgroundColor' => '#252525;position:fixed',
            'fontColor' => '#ffffff',
            'fontFamily' => "Arial, 'Helvetica Neue', sans-serif",
            'width' => '180px',
          ],
        ],
      ],
    ]);

    $styles = $this->sanitizer->sanitizeBody($body)['content']['blocks'][0]['styles']['block'];

    verify($styles)->equals([
      'backgroundColor' => '',
      'fontColor' => '#ffffff',
      'fontFamily' => "Arial, 'Helvetica Neue', sans-serif",
      'width' => '180px',
    ]);
  }

  public function testItEmptiesBlockStyleValuesWithEntitiesOrNonScalarValues() {
    $body = $this->bodyWithBlocks([
      [
        'type' => 'button',
        'styles' => [
          'block' => [
            'backgroundColor' => 'red&#59;position:fixed',
            'borderColor' => ['red;position:fixed'],
            'fontColor' => '#ffffff',
          ],
        ],
      ],
    ]);

    $styles = $this->sanitizer->sanitizeBody($body)['content']['blocks'][0]['styles']['block'];

    verify($styles)->equals(['backgroundColor' => '', 'borderColor' => '', 'fontColor' => '#ffffff']);
  }

  public function testItRemovesStylePropertiesWithInvalidNames() {
    $body = $this->bodyWithBlocks([
      [
        'type' => 'button',
        'styles' => [
          'block' => [
            'background-color:red;display' => 'none',
            'fontColor' => '#ffffff',
            'border-radius' => '5px',
          ],
        ],
      ],
    ]);
    $body['globalStyles'] = ['text' => ['color:red;display' => 'none', 'fontSize' => '16px']];

    $result = $this->sanitizer->sanitizeBody($body);

    verify($result['content']['blocks'][0]['styles']['block'])->equals(['fontColor' => '#ffffff', 'border-radius' => '5px']);
    verify($result['globalStyles']['text'])->equals(['fontSize' => '16px']);
  }

  public function testItEmptiesInvalidStyleValuesInNestedBlocksAndBlockDefaults() {
    $body = $this->bodyWithBlocks([
      [
        'type' => 'container',
        'styles' => ['block' => ['backgroundColor' => '#fff}body{display:none']],
        'blocks' => [
          [
            'type' => 'automatedLatestContentLayout',
            'readMoreButton' => ['styles' => ['block' => ['fontColor' => '<b>', 'fontSize' => '14px']]],
            'divider' => ['styles' => ['block' => ['borderColor' => '@import x', 'borderWidth' => '1px']]],
          ],
        ],
      ],
    ]);
    $body['blockDefaults'] = ['divider' => ['styles' => ['block' => ['padding' => '13px;color:red']]]];

    $result = $this->sanitizer->sanitizeBody($body);

    $container = $result['content']['blocks'][0];
    verify($container['styles']['block']['backgroundColor'])->equals('');
    verify($container['blocks'][0]['readMoreButton']['styles']['block'])->equals(['fontColor' => '', 'fontSize' => '14px']);
    verify($container['blocks'][0]['divider']['styles']['block'])->equals(['borderColor' => '', 'borderWidth' => '1px']);
    verify($result['blockDefaults']['divider']['styles']['block']['padding'])->equals('');
  }

  public function testItKeepsContainerBackgroundImageUrlInsideUrlFunction() {
    $body = $this->bodyWithBlocks([
      [
        'type' => 'container',
        'image' => ['src' => "https://example.com/bg.png);x-src:url('a b')", 'display' => 'scale'],
        'blocks' => [
          [
            'type' => 'container',
            'image' => ['src' => 'https://example.com/uploads/bg@2x.png?ver=1&size=large', 'display' => 'tile'],
          ],
        ],
      ],
    ]);

    $container = $this->sanitizer->sanitizeBody($body)['content']['blocks'][0];

    verify($container['image']['src'])->equals('https://example.com/bg.png%29%3Bx-src:url%28%27a%20b%27%29');
    verify($container['blocks'][0]['image']['src'])->equals('https://example.com/uploads/bg@2x.png?ver=1&size=large');
  }

  public function testItRemovesGlobalStyleValuesThatCouldEndTheRule() {
    $body = [
      'content' => ['blocks' => []],
      'globalStyles' => [
        'text' => ['fontColor' => '#000000}body{display:none}x{', 'fontFamily' => 'Arial', 'fontSize' => '16px'],
        'wrapper' => ['backgroundColor' => '#ffffff'],
        'body' => ['backgroundColor' => '<style>'],
      ],
    ];

    $globalStyles = $this->sanitizer->sanitizeBody($body)['globalStyles'];

    verify($globalStyles)->equals([
      'text' => ['fontFamily' => 'Arial', 'fontSize' => '16px'],
      'wrapper' => ['backgroundColor' => '#ffffff'],
      'body' => [],
    ]);
  }

  private function bodyWithBlocks(array $blocks): array {
    return ['content' => ['blocks' => $blocks]];
  }
}
