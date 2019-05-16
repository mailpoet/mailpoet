<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

class HeaderTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'header',
    'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open in browser</a>',
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

  function testItRendersCorrectly() {
    $output = Header::render($this->block);
    $expected_result = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_header"  style="line-height: 19.2px;color: #222222;font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;font-size: 12px;text-align: left;">
          Display problems? <a href="[link:newsletter_view_in_browser_url]" style="color:#6cb7d4;text-decoration:underline">Open in browser</a>
        </td>
      </tr>';
    expect($output)->equals($expected_result);
  }

  function testItRendersBackgroundColorCorrectly() {
    $this->block['styles']['block']['backgroundColor'] = '#f0f0f0';
    $output = Header::render($this->block);
    $expected_result = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_header" bgcolor="#f0f0f0" style="line-height: 19.2px;background-color: #f0f0f0;color: #222222;font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;font-size: 12px;text-align: left;">
          Display problems? <a href="[link:newsletter_view_in_browser_url]" style="color:#6cb7d4;text-decoration:underline">Open in browser</a>
        </td>
      </tr>';
    expect($output)->equals($expected_result);
  }

  function testItPrefersInlinedCssForLinks() {
    $this->block['text'] = '<p>Header text. <a href="http://example.com" style="color:#aaaaaa;">link</a></p>';
    $output = Footer::render($this->block);
    expect($output)->contains('<a href="http://example.com" style="color:#aaaaaa;text-decoration:underline">link</a>');
  }
}
