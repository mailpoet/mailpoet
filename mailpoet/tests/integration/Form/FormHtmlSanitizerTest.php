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
    $this->assertSameHtml('', $this->sanitizer->sanitize(''));
    $this->assertSameHtml('<span style="font-family: BioRhyme">Style</span>', $this->sanitizer->sanitize('<span style="font-family: BioRhyme">Style</span>'));
    $this->assertSameHtml('<span data-font="BioRhyme">DataFont</span>', $this->sanitizer->sanitize('<span data-font="BioRhyme">DataFont</span>'));
    $this->assertSameHtml('<span class="my-class">Class</span>', $this->sanitizer->sanitize('<span class="my-class">Class</span>'));
    $this->assertSameHtml('Text <span>👋</span> around', $this->sanitizer->sanitize('Text <span>👋</span> around'));
    $this->assertSameHtml('<strong>Strong</strong><em>Em</em><br />', $this->sanitizer->sanitize('<strong>Strong</strong><em>Em</em><br />'));
    $this->assertSameHtml('<sub>Strong</sub><sup>Em</sup><s>s</s><kbd>kbd</kbd>', $this->sanitizer->sanitize('<sub>Strong</sub><sup>Em</sup><s>s</s><kbd>kbd</kbd>'));
    $this->assertSameHtml('<code>Code</code>', $this->sanitizer->sanitize('<code>Code</code>'));
    $this->assertSameHtml('<a href="http://example.com/" data-type="post" data-id="1" target="_blank" rel="noreferrer">link</a>', $this->sanitizer->sanitize('<a href="http://example.com/" data-type="post" data-id="1" target="_blank" rel="noreferrer">link</a>'));
    $this->assertSameHtml('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">', $this->sanitizer->sanitize('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">'));
  }

  public function testItKeepsMathFormatting() {
    $input = implode('', [
      '<span contenteditable="false" data-rich-text-bogus="true" data-rich-text-format-boundary="true">',
      '<math data-latex="x^2">',
      '<semantics><msup><mi>x</mi><mn>2</mn></msup><annotation encoding="application/x-tex">x^2</annotation></semantics>',
      '</math>',
      '</span>',
    ]);
    $expected = implode('', [
      '<span>',
      '<math data-latex="x^2">',
      '<semantics><msup><mi>x</mi><mn>2</mn></msup><annotation encoding="application/x-tex">x^2</annotation></semantics>',
      '</math>',
      '</span>',
    ]);

    $this->assertSameHtml($expected, $this->sanitizer->sanitize($input));
  }

  public function testItRemovesUnwantedHtml() {
    $this->assertSameHtml('', $this->sanitizer->sanitize('<script>'));
    $this->assertSameHtml('<span>Hello<img src="http://nonsense" /></span>', $this->sanitizer->sanitize('<span>Hello<img src="http://nonsense" onerror="alert(1)"/></span>'));
    $this->assertSameHtml('<a href="#">click me</a>', $this->sanitizer->sanitize('<a href="#" onclick="alert(1)">click me</a>'));
    $this->assertSameHtml('<a href="alert(1)">click me</a>', $this->sanitizer->sanitize('<a href="javascript:alert(1)">click me</a>'));
  }
}
