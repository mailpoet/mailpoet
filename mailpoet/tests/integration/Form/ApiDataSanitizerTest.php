<?php declare(strict_types = 1);

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
        [
          'type' => 'checkbox',
          'params' => [
            'label' => 'Label',
            'values' => [
              ['value' => '<script>alert(3);</script>Checkbox'],
            ],
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
    $nestedCheckbox = $result[1]['body'][1];
    expect($paragraph['params']['content'])->equals('alert(1);Paragraph');
    expect($paragraph['params']['align'])->equals('left');
    expect($nestedHeading['params']['content'])->equals('alert(2);Heading');
    expect($nestedHeading['params']['align'])->equals('right');
    expect($nestedCheckbox['params']['values'][0]['value'])->equals('alert(3);Checkbox');
    expect($nestedCheckbox['params']['label'])->equals('Label');
  }
}
