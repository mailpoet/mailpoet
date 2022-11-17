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
    expect($this->sanitizer->sanitize(''))->equals('');
    expect($this->sanitizer->sanitize('<span style="font-family: BioRhyme">Style</span>'))->equals('<span style="font-family: BioRhyme">Style</span>');
    expect($this->sanitizer->sanitize('<span class="my-class">Class</span>'))->equals('<span class="my-class">Class</span>');
    expect($this->sanitizer->sanitize('<h1>Heading one</h1><p>Some text</p>'))->equals('<h1>Heading one</h1><p>Some text</p>');
    expect($this->sanitizer->sanitize('Text <span>👋</span> around'))->equals('Text <span>👋</span> around');
    expect($this->sanitizer->sanitize('<strong>Strong</strong><em>Em</em><br />'))->equals('<strong>Strong</strong><em>Em</em><br />');
    expect($this->sanitizer->sanitize('<ul><li>list 1</li><li>list 2</li></ul>'))->equals('<ul><li>list 1</li><li>list 2</li></ul>');
    expect($this->sanitizer->sanitize('<table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table>'))->equals('<table><tr><th>Head</th></tr><tr><td>Cell</td></tr></table>');
    expect($this->sanitizer->sanitize('<a href="http://example.com/" target="_blank" class="some-class">link</a>'))->equals('<a href="http://example.com/" target="_blank" class="some-class">link</a>');
    expect($this->sanitizer->sanitize('<a href="[link:subscribe]" target="_blank" style="color: blue;font-size: 12px">Subscribe</a>'))->equals('<a href="[link:subscribe]" target="_blank" style="color: blue;font-size: 12px">Subscribe</a>');
  }

  public function testItRemovesUnwantedHtml() {
    expect($this->sanitizer->sanitize('<script>'))->equals('');
    expect($this->sanitizer->sanitize('<span>Hello<img src="http://nonsense" onerror="alert(1)"/></span>'))->equals('<span>Hello</span>');
    expect($this->sanitizer->sanitize('<a href="#" onclick="alert(1)">click me</a>'))->equals('<a href="#">click me</a>');
    expect($this->sanitizer->sanitize('<a href="javascript:alert(1)">click me</a>'))->equals('<a href="alert(1)">click me</a>');
    expect($this->sanitizer->sanitize('<p>Thanks<img src=x onerror=alert(4)> See you soon!</p>'))->equals('<p>Thanks See you soon!</p>');
    expect($this->sanitizer->sanitize('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">'))->equals('');
  }
}
