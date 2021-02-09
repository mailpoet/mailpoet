<?php

namespace MailPoet\Form;

class FormHtmlSanitiserTest extends \MailPoetTest {

  /** @var FormHtmlSanitiser */
  private $sanitiser;

  public function _before() {
    parent::_before();
    $this->sanitiser = $this->diContainer->get(FormHtmlSanitiser::class);
  }

  public function testItKeepsAllowedTags() {
    expect($this->sanitiser->sanitise(''))->equals('');
    expect($this->sanitiser->sanitise('<span style="font-family: BioRhyme">Style</span>'))->equals('<span style="font-family: BioRhyme">Style</span>');
    expect($this->sanitiser->sanitise('<span data-font="BioRhyme">DataFont</span>'))->equals('<span data-font="BioRhyme">DataFont</span>');
    expect($this->sanitiser->sanitise('<span class="my-class">Class</span>'))->equals('<span class="my-class">Class</span>');
    expect($this->sanitiser->sanitise('Text <span>👋</span> around'))->equals('Text <span>👋</span> around');
    expect($this->sanitiser->sanitise('<strong>Strong</strong><em>Em</em><br />'))->equals('<strong>Strong</strong><em>Em</em><br />');
    expect($this->sanitiser->sanitise('<sub>Strong</sub><sup>Em</sup><s>s</s><kbd>kbd</kbd>'))->equals('<sub>Strong</sub><sup>Em</sup><s>s</s><kbd>kbd</kbd>');
    expect($this->sanitiser->sanitise('<code>Code</code>'))->equals('<code>Code</code>');
    expect($this->sanitiser->sanitise('<a href="http://example.com/" data-type="post" data-id="1" target="_blank" rel="noreferrer">link</a>'))->equals('<a href="http://example.com/" data-type="post" data-id="1" target="_blank" rel="noreferrer">link</a>');
    expect($this->sanitiser->sanitise('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">'))->equals('<img class="wp-image-55" style="width: 150px;height: 1px" src="http://test.com/logo-1.jpg" alt="alt text">');
  }

  public function testItRemovesUnwantedHtml() {
    expect($this->sanitiser->sanitise('<script>'))->equals('');
    expect($this->sanitiser->sanitise('<span>Hello<img src="http://nonsense" onerror="alert(1)"/></span>'))->equals('<span>Hello<img src="http://nonsense" /></span>');
    expect($this->sanitiser->sanitise('<a href="#" onclick="alert(1)">click me</a>'))->equals('<a href="#">click me</a>');
    expect($this->sanitiser->sanitise('<a href="javascript:alert(1)">click me</a>'))->equals('<a href="alert(1)">click me</a>');
  }
}
