<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\NewsletterHtmlSanitizer;
use MailPoet\WP\Functions as WPFunctions;

class FooterTest extends \MailPoetUnitTest {
  private Footer $renderer;
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

  public function _before() {
    parent::_before();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->willReturnCallback(function($attr) {
      return $attr;
    });
    $sanitizerMock = $this->createMock(NewsletterHtmlSanitizer::class);
    $sanitizerMock->method('sanitize')->willReturnCallback(function($html) {
      return $html;
    });
    $this->renderer = new Footer($sanitizerMock, $wpMock);
  }

  public function testItRendersCorrectly() {
    $output = $this->renderer->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_footer"  style="line-height: 19.2px;color: #222222;font-family: roboto, \'helvetica neue\', helvetica, arial, sans-serif;font-size: 12px;text-align: center;">
          Footer text. <a href="http://example.com" style="color:#689f2c;text-decoration:none">link</a>
        </td>
      </tr>';
    verify($output)->equals($expectedResult);
  }

  public function testItRendersWithBackgroundColor() {
    $this->block['styles']['block']['backgroundColor'] = '#f0f0f0';
    $output = $this->renderer->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_header_footer_padded mailpoet_footer" bgcolor="#f0f0f0" style="line-height: 19.2px;background-color: #f0f0f0;color: #222222;font-family: roboto, \'helvetica neue\', helvetica, arial, sans-serif;font-size: 12px;text-align: center;">
          Footer text. <a href="http://example.com" style="color:#689f2c;text-decoration:none">link</a>
        </td>
      </tr>';
    verify($output)->equals($expectedResult);
  }

  public function testItPrefersInlinedCssForLinks() {
    $this->block['text'] = '<p>Footer text. <a href="http://example.com" style="color:#aaaaaa;">link</a></p>';
    $output = $this->renderer->render($this->block);
    verify($output)->stringContainsString('<a href="http://example.com" style="color:#aaaaaa;text-decoration:none">link</a>');
  }

  public function testItRaisesExceptionIfTextIsNotString() {
    $this->block['text'] = ['some', 'array'];
    $this->expectException('RuntimeException');
    $this->renderer->render($this->block);
  }
}
