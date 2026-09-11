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
    verify($block2['text'])->equals('<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a><br />Add your postal address here!</p>');
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
    $body = $this->bodyWithText($text);

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

  public function testItSanitizesTextOfBlockThatAlsoHasChildBlocks() {
    $body = [
      'content' => [
        'blocks' => [
          [
            'type' => 'text',
            'text' => '<p>Hello</p><img src=x onerror=alert(1)>',
            'blocks' => [
              ['type' => 'text', 'text' => ['<img src=x onerror=alert(2)>']],
            ],
          ],
        ],
      ],
    ];

    $result = $this->sanitizer->sanitizeBody($body);

    $block = $result['content']['blocks'][0];
    $this->assertSame('<p>Hello</p>', $block['text']);
    $this->assertSame('', $block['blocks'][0]['text']);
  }

  public function testItSanitizesChildBlocksOfBlockWithoutType() {
    $body = [
      'content' => [
        'blocks' => [
          [
            'blocks' => [
              ['type' => 'text', 'text' => '<p>Hello</p><img src=x onerror=alert(1)>'],
            ],
          ],
        ],
      ],
    ];

    $result = $this->sanitizer->sanitizeBody($body);

    $this->assertSame('<p>Hello</p>', $result['content']['blocks'][0]['blocks'][0]['text']);
  }

  public function testItSkipsPropertySanitizationForBlockWithNonStringType() {
    $body = [
      'content' => [
        'blocks' => [
          ['type' => ['text'], 'text' => '<p>Hello</p>'],
        ],
      ],
    ];

    $result = $this->sanitizer->sanitizeBody($body);

    verify($result)->equals($body);
  }

  public function testItSanitizesBlockDefaults() {
    $body = [
      'content' => ['blocks' => []],
      'blockDefaults' => [
        'header' => ['text' => '<p>Hello</p><img src=x onerror=alert(1)>', 'link' => ''],
        'footer' => ['text' => ['<img src=x onerror=alert(1)>']],
        'text' => ['text' => '<p>Hello</p><img src=x onerror=alert(1)>'],
        'image' => ['src' => 'http://example.com/image.png'],
        'divider' => 'not-an-array',
      ],
    ];

    $result = $this->sanitizer->sanitizeBody($body);

    $defaults = $result['blockDefaults'];
    $this->assertSame('<p>Hello</p>', $defaults['header']['text']);
    $this->assertSame('', $defaults['header']['link']);
    $this->assertSame('', $defaults['footer']['text']);
    $this->assertSame('<p>Hello</p>', $defaults['text']['text']);
    $this->assertSame($body['blockDefaults']['image'], $defaults['image']);
    $this->assertSame('not-an-array', $defaults['divider']);
  }

  public function testItLeavesNullAndMissingTextUntouched() {
    $body = [
      'content' => [
        'blocks' => [
          ['type' => 'text', 'text' => null],
          ['type' => 'text'],
        ],
      ],
    ];

    $result = $this->sanitizer->sanitizeBody($body);

    $this->assertNull($result['content']['blocks'][0]['text']);
    $this->assertArrayNotHasKey('text', $result['content']['blocks'][1]);
  }

  /**
   * @param mixed $text
   */
  private function bodyWithText($text): array {
    return [
      'content' => [
        'blocks' => [
          ['type' => 'text', 'text' => $text],
        ],
      ],
    ];
  }
}
