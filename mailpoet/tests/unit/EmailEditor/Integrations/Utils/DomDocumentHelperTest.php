<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\Utils;

use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;

class DomDocumentHelperTest extends \MailPoetUnitTest {
  public function testItFindsElement(): void {
    $html = '<div><p>Some text</p></div>';
    $domDocumentHelper = new DomDocumentHelper($html);
    $element = $domDocumentHelper->findElement('p');
    $empty = $domDocumentHelper->findElement('span');
    $this->assertInstanceOf(\DOMElement::class, $element);
    $this->assertEquals('p', $element->tagName);
    $this->assertNull($empty);
  }

  public function testItGetsAttributeValue(): void {
    $html = '<div><p class="some-class">Some text</p></div>';
    $domDocumentHelper = new DomDocumentHelper($html);
    $element = $domDocumentHelper->findElement('p');
    $this->assertInstanceOf(\DOMElement::class, $element);
    $this->assertEquals('some-class', $domDocumentHelper->getAttributeValue($element, 'class'));
  }

  public function testItGetsOuterHtml(): void {
    $html = '<div><span>Some <strong>text</strong></span></div>';
    $domDocumentHelper = new DomDocumentHelper($html);
    $element = $domDocumentHelper->findElement('span');
    $this->assertInstanceOf(\DOMElement::class, $element);
    $this->assertEquals('<span>Some <strong>text</strong></span>', $domDocumentHelper->getOuterHtml($element));

    // testings encoding of special characters
    $html = '<div><img src="https://test.com/DALL·E-A®∑oecasƒ-803x1024.jpg"></div>';
    $domDocumentHelper = new DomDocumentHelper($html);
    $element = $domDocumentHelper->findElement('img');
    $this->assertInstanceOf(\DOMElement::class, $element);
    $this->assertEquals('<img src="https://test.com/DALL%C2%B7E-A%C2%AE%E2%88%91oecas%C6%92-803x1024.jpg">', $domDocumentHelper->getOuterHtml($element));
  }

  public function testItGetsAttributeValueByTagName(): void {
    $html = '<div><p class="some-class">Some text</p><p class="second-paragraph"></p></div>';
    $domDocumentHelper = new DomDocumentHelper($html);
    $this->assertEquals('some-class', $domDocumentHelper->getAttributeValueByTagName('p', 'class'));
    $this->assertNull($domDocumentHelper->getAttributeValueByTagName('span', 'class'));
  }
}
