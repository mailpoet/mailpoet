<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

class NewsletterHtmlSanitizerTest extends \MailPoetTest {

  /** @var NewsletterHtmlSanitizer */
  private $sanitizer;

  public function _before() {
    parent::_before();
    $this->sanitizer = $this->diContainer->get(NewsletterHtmlSanitizer::class);
  }

  public function testItKeepsAllowedTags() {
    $this->assertSameHtml('', $this->sanitizer->sanitize(''));
    $this->assertSameHtml('<span style="font-family: BioRhyme">Style</span>', $this->sanitizer->sanitize('<span style="font-family: BioRhyme">Style</span>'));
    $this->assertSameHtml('<span class="my-class">Class</span>', $this->sanitizer->sanitize('<span class="my-class">Class</span>'));
    $this->assertSameHtml('<h1>Heading one</h1><p>Some text</p>', $this->sanitizer->sanitize('<h1>Heading one</h1><p>Some text</p>'));
    $this->assertSameHtml('Text <span>👋</span> around', $this->sanitizer->sanitize('Text <span>👋</span> around'));
    $this->assertSameHtml('<strong>Strong</strong><em>Em</em><br />', $this->sanitizer->sanitize('<strong>Strong</strong><em>Em</em><br />'));
    $this->assertSameHtml('<ul><li>list 1</li><li>list 2</li></ul>', $this->sanitizer->sanitize('<ul><li>list 1</li><li>list 2</li></ul>'));
    $this->assertSameHtml('<table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table>', $this->sanitizer->sanitize('<table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table>'));
    $this->assertSameHtml('<a href="http://example.com/" target="_blank" class="some-class">link</a>', $this->sanitizer->sanitize('<a href="http://example.com/" target="_blank" class="some-class">link</a>'));
    $this->assertSameHtml('<a href="[link:subscribe]" target="_blank" style="color: blue;font-size: 12px">Subscribe</a>', $this->sanitizer->sanitize('<a href="[link:subscribe]" target="_blank" style="color: blue;font-size: 12px">Subscribe</a>'));
    $this->assertSameHtml('<span style="color:rgb(0,0,0)">Does not sanitize RGB</span>', $this->sanitizer->sanitize('<span style="color:rgb(0,0,0)">Does not sanitize RGB</span>'));
  }

  public function testItKeepsRgbColorsInStyles() {
    $this->assertSameHtml('<span style="color:rgb(10, 20, 30)">x</span>', $this->sanitizer->sanitize('<span style="color:rgb(10, 20, 30)">x</span>'));
    $this->assertSameHtml('<span style="color:rgb(0 208 132 / 50%)">x</span>', $this->sanitizer->sanitize('<span style="color:rgb(0 208 132 / 50%)">x</span>'));
  }

  public function testItRemovesStylesWithOtherContentInsideRgb() {
    $this->assertSameHtml('<span>x</span>', $this->sanitizer->sanitize('<span style="color:rgb(expression(1))">x</span>'));
    $this->assertSameHtml('<span>x</span>', $this->sanitizer->sanitize('<span style="color:rgb(0 /* x */ 0 0)">x</span>'));
    $this->assertSameHtml('<span>x</span>', $this->sanitizer->sanitize('<span style="color:rgb(\\30 , 0, 0)">x</span>'));
  }

  public function testItRemovesUnwantedHtml() {
    $this->assertSameHtml('', $this->sanitizer->sanitize('<script>'));
    $this->assertSameHtml('<span>Hello</span>', $this->sanitizer->sanitize('<span>Hello<img src="http://nonsense" onerror="alert(1)"/></span>'));
    $this->assertSameHtml('<a href="#">click me</a>', $this->sanitizer->sanitize('<a href="#" onclick="alert(1)">click me</a>'));
    $this->assertSameHtml('<a href="alert(1)">click me</a>', $this->sanitizer->sanitize('<a href="javascript:alert(1)">click me</a>'));
    $this->assertSameHtml('<p>Thanks See you soon!</p>', $this->sanitizer->sanitize('<p>Thanks<img src=x onerror=alert(4)> See you soon!</p>'));
    $this->assertSameHtml('', $this->sanitizer->sanitize('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">'));
  }
}
