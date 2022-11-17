<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

class HeaderTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'header',
    'text' => '<a href="[link:newsletter_view_in_browser_url]">View this in your browser.</a>',
    'styles' => [
      'block' => [
        'backgroundColor' => 'transparent',
      ],
      'text' => [
        'fontColor' => '#222222',
        'fontFamily' => 'Arial',
        'fontSize' => '12px',
        'textAlign' => 'left',
      ],
      'link' => [
        'fontColor' => '#6cb7d4',
        'textDecoration' => 'underline',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $output = (new Header)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_header"  style="line-height: 19.2px;color: #222222;font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;font-size: 12px;text-align: left;">
          <a href="[link:newsletter_view_in_browser_url]" style="color:#6cb7d4;text-decoration:underline">View this in your browser.</a>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testItRendersBackgroundColorCorrectly() {
    $this->block['styles']['block']['backgroundColor'] = '#f0f0f0';
    $output = (new Header)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_header" bgcolor="#f0f0f0" style="line-height: 19.2px;background-color: #f0f0f0;color: #222222;font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;font-size: 12px;text-align: left;">
          <a href="[link:newsletter_view_in_browser_url]" style="color:#6cb7d4;text-decoration:underline">View this in your browser.</a>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testItPrefersInlinedCssForLinks() {
    $this->block['text'] = '<p>Header text. <a href="http://example.com" style="color:#aaaaaa;">link</a></p>';
    $output = (new Footer)->render($this->block);
    expect($output)->stringContainsString('<a href="http://example.com" style="color:#aaaaaa;text-decoration:underline">link</a>');
  }

  public function testItRaisesExceptionIfTextIsNotString() {
    $this->block['text'] = ['some', 'array'];
    $this->expectException('RuntimeException');
    (new Header)->render($this->block);
  }
}
