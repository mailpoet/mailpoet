<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

class MailPoetCssInlinerTest extends \MailPoetUnitTest {
  public function testItInlinesCssIntoMatchingElements(): void {
    $html = (new MailPoetCssInliner())
      ->from_html('<html><body><p class="intro">Hello</p><a href="#">Link</a></body></html>')
      ->inline_css('.intro { color: #123456; font-size: 16px; } body > a { color: red; }')
      ->render();

    verify($html)->stringContainsString('<p class="intro" style="color: #123456; font-size: 16px;">Hello</p>');
    verify($html)->stringContainsString('<a href="#" style="color: red;">Link</a>');
  }

  public function testItRejectsEmptyHtml(): void {
    $this->expectException(\InvalidArgumentException::class);
    (new MailPoetCssInliner())->from_html('');
  }

  public function testItRequiresHtmlBeforeInliningCss(): void {
    $this->expectException(\LogicException::class);
    (new MailPoetCssInliner())->inline_css('p { color: red; }');
  }
}
