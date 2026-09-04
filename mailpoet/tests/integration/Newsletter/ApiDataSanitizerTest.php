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

  private function bodyWithBlocks(array $blocks): array {
    return ['content' => ['blocks' => $blocks]];
  }
}
