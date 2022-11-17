<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

class SocialTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'social',
    'iconSet' => 'full-symbol-black',
    'styles' => [
      'block' => [
        'textAlign' => 'center',
      ],
    ],
    'icons' => [
      0 => [
        'type' => 'socialIcon',
        'iconType' => 'facebook',
        'link' => 'http://www.facebook.com',
        'image' => 'http://mailpoet.localhost/Facebook.png',
        'height' => '32px',
        'width' => '32px',
        'text' => 'Facebook',
      ],
      1 => [
        'type' => 'socialIcon',
        'iconType' => 'twitter',
        'link' => null,
        'image' => 'http://mailpoet.localhost/Twitter.png',
        'height' => '36px',
        'width' => '36px',
        'text' => 'Twitter',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $output = (new Social)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_padded_side mailpoet_padded_vertical" valign="top" align="center">
          <a href="http://www.facebook.com" style="text-decoration:none!important;"
        ><img
          src="http://mailpoet.localhost/Facebook.png"
          width="32"
          height="32"
          style="width:32px;height:32px;-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;"
          alt="facebook"
        ></a>&nbsp;<a href="" style="text-decoration:none!important;"
        ><img
          src="http://mailpoet.localhost/Twitter.png"
          width="36"
          height="36"
          style="width:36px;height:36px;-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;"
          alt="twitter"
        ></a>&nbsp;
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }
}
