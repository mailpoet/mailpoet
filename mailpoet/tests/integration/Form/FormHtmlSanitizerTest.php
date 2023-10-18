<?php declare(strict_types = 1);

namespace MailPoet\Form;

class FormHtmlSanitizerTest extends \MailPoetTest {

  /** @var FormHtmlSanitizer */
  private $sanitizer;

  public function _before() {
    parent::_before();
    $this->sanitizer = $this->diContainer->get(FormHtmlSanitizer::class);
  }

  public function testItKeepsAllowedTags() {
    verify($this->sanitizer->sanitize(''))->equals('');
    verify($this->sanitizer->sanitize('<span style="font-family: BioRhyme">Style</span>'))->equals('<span style="font-family: BioRhyme">Style</span>');
    verify($this->sanitizer->sanitize('<span data-font="BioRhyme">DataFont</span>'))->equals('<span data-font="BioRhyme">DataFont</span>');
    verify($this->sanitizer->sanitize('<span class="my-class">Class</span>'))->equals('<span class="my-class">Class</span>');
    verify($this->sanitizer->sanitize('Text <span>👋</span> around'))->equals('Text <span>👋</span> around');
    verify($this->sanitizer->sanitize('<strong>Strong</strong><em>Em</em><br />'))->equals('<strong>Strong</strong><em>Em</em><br />');
    verify($this->sanitizer->sanitize('<sub>Strong</sub><sup>Em</sup><s>s</s><kbd>kbd</kbd>'))->equals('<sub>Strong</sub><sup>Em</sup><s>s</s><kbd>kbd</kbd>');
    verify($this->sanitizer->sanitize('<code>Code</code>'))->equals('<code>Code</code>');
    verify($this->sanitizer->sanitize('<a href="http://example.com/" data-type="post" data-id="1" target="_blank" rel="noreferrer">link</a>'))->equals('<a href="http://example.com/" data-type="post" data-id="1" target="_blank" rel="noreferrer">link</a>');
    verify($this->sanitizer->sanitize('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">'))->equals('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">');
  }

  public function testItRemovesUnwantedHtml() {
    verify($this->sanitizer->sanitize('<script>'))->equals('');
    verify($this->sanitizer->sanitize('<span>Hello<img src="http://nonsense" onerror="alert(1)"/></span>'))->equals('<span>Hello<img src="http://nonsense" /></span>');
    verify($this->sanitizer->sanitize('<a href="#" onclick="alert(1)">click me</a>'))->equals('<a href="#">click me</a>');
    verify($this->sanitizer->sanitize('<a href="javascript:alert(1)">click me</a>'))->equals('<a href="alert(1)">click me</a>');
  }
}
