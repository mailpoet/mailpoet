<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

class FooterTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'footer',
    'text' => '<p>Footer text. <a href="http://example.com">link</a></p>',
    'styles' => [
      'block' => [
        'backgroundColor' => 'transparent',
      ],
      'text' => [
        'fontColor' => '#222222',
        'fontFamily' => 'Roboto',
        'fontSize' => '12px',
        'textAlign' => 'center',
      ],
      'link' => [
        'fontColor' => '#689f2c',
        'textDecoration' => 'none',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $output = (new Footer)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_footer"  style="line-height: 19.2px;color: #222222;font-family: roboto, \'helvetica neue\', helvetica, arial, sans-serif;font-size: 12px;text-align: center;">
          Footer text. <a href="http://example.com" style="color:#689f2c;text-decoration:none">link</a>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testItRendersWithBackgroundColor() {
    $this->block['styles']['block']['backgroundColor'] = '#f0f0f0';
    $output = (new Footer)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_footer" bgcolor="#f0f0f0" style="line-height: 19.2px;background-color: #f0f0f0;color: #222222;font-family: roboto, \'helvetica neue\', helvetica, arial, sans-serif;font-size: 12px;text-align: center;">
          Footer text. <a href="http://example.com" style="color:#689f2c;text-decoration:none">link</a>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testItPrefersInlinedCssForLinks() {
    $this->block['text'] = '<p>Footer text. <a href="http://example.com" style="color:#aaaaaa;">link</a></p>';
    $output = (new Footer)->render($this->block);
    expect($output)->stringContainsString('<a href="http://example.com" style="color:#aaaaaa;text-decoration:none">link</a>');
  }

  public function testItRaisesExceptionIfTextIsNotString() {
    $this->block['text'] = ['some', 'array'];
    $this->expectException('RuntimeException');
    (new Footer)->render($this->block);
  }
}
