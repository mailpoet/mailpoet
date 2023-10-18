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
}
