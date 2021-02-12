<?php

namespace MailPoet\Form;

class ApiDataSanitizerTest extends \MailPoetTest {

  /** @var ApiDataSanitizer */
  private $sanitizer;

  private $body = [
    [
      'type' => 'paragraph',
      'params' => [
        'content' => '<script>alert(1);</script>Paragraph',
        'align' => 'left',
        'font_size' => '',
      ],
    ],
    [
      'type' => 'column',
      'body' => [
        [
          'type' => 'heading',
          'params' => [
            'content' => '<script>alert(2);</script>Heading',
            'align' => 'right',
            'font_size' => '',
          ],
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
    $paragraph = $result[0];
    $nestedHeading = $result[1]['body'][0];
    expect($paragraph['params']['content'])->equals('alert(1);Paragraph');
    expect($paragraph['params']['align'])->equals('left');
    expect($nestedHeading['params']['content'])->equals('alert(2);Heading');
    expect($nestedHeading['params']['align'])->equals('right');
  }
}
