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
    verify($this->sanitizer->sanitize(''))->equals('');
    verify($this->sanitizer->sanitize('<span style="font-family: BioRhyme">Style</span>'))->equals('<span style="font-family: BioRhyme">Style</span>');
    verify($this->sanitizer->sanitize('<span class="my-class">Class</span>'))->equals('<span class="my-class">Class</span>');
    verify($this->sanitizer->sanitize('<h1>Heading one</h1><p>Some text</p>'))->equals('<h1>Heading one</h1><p>Some text</p>');
    verify($this->sanitizer->sanitize('Text <span>👋</span> around'))->equals('Text <span>👋</span> around');
    verify($this->sanitizer->sanitize('<strong>Strong</strong><em>Em</em><br />'))->equals('<strong>Strong</strong><em>Em</em><br />');
    verify($this->sanitizer->sanitize('<ul><li>list 1</li><li>list 2</li></ul>'))->equals('<ul><li>list 1</li><li>list 2</li></ul>');
    verify($this->sanitizer->sanitize('<table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table>'))->equals('<table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table>');
    verify($this->sanitizer->sanitize('<a href="http://example.com/" target="_blank" class="some-class">link</a>'))->equals('<a href="http://example.com/" target="_blank" class="some-class">link</a>');
    verify($this->sanitizer->sanitize('<a href="[link:subscribe]" target="_blank" style="color: blue;font-size: 12px">Subscribe</a>'))->equals('<a href="[link:subscribe]" target="_blank" style="color: blue;font-size: 12px">Subscribe</a>');
    verify($this->sanitizer->sanitize('<span style="color:rgb(0,0,0)">Does not sanitize RGB</span>'))->equals('<span style="color:rgb(0,0,0)">Does not sanitize RGB</span>');
  }

  public function testItRemovesUnwantedHtml() {
    verify($this->sanitizer->sanitize('<script>'))->equals('');
    verify($this->sanitizer->sanitize('<span>Hello<img src="http://nonsense" onerror="alert(1)"/></span>'))->equals('<span>Hello</span>');
    verify($this->sanitizer->sanitize('<a href="#" onclick="alert(1)">click me</a>'))->equals('<a href="#">click me</a>');
    verify($this->sanitizer->sanitize('<a href="javascript:alert(1)">click me</a>'))->equals('<a href="alert(1)">click me</a>');
    verify($this->sanitizer->sanitize('<p>Thanks<img src=x onerror=alert(4)> See you soon!</p>'))->equals('<p>Thanks See you soon!</p>');
    verify($this->sanitizer->sanitize('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">'))->equals('');
  }
}
