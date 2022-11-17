<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

class ImageTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'image',
    'link' => 'http://example.com',
    'src' => 'http://mailpoet.localhost/image.jpg',
    'alt' => 'Alt text',
    'fullWidth' => false,
    'width' => '310.015625px',
    'height' => '390px',
    'styles' => [
      'block' => [
        'textAlign' => 'center',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $output = (new Image)->render($this->block, 200);
    $expectedResult = '
      <tr>
        <td class="mailpoet_image mailpoet_padded_vertical mailpoet_padded_side" align="center" valign="top">
          <a href="http://example.com"><img src="http://mailpoet.localhost/image.jpg" width="160" alt="Alt text"/></a>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testItRendersWithoutLink() {
    $this->block['link'] = null;
    $output = (new Image)->render($this->block, 200);
    $expectedResult = '
      <tr>
        <td class="mailpoet_image mailpoet_padded_vertical mailpoet_padded_side" align="center" valign="top">
          <img src="http://mailpoet.localhost/image.jpg" width="160" alt="Alt text"/>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }
}
