<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Utils;

class Dom_Document_Helper_Test extends \MailPoetUnitTest {
	public function testItFindsElement(): void {
		$html              = '<div><p>Some text</p></div>';
		$domDocumentHelper = new Dom_Document_Helper( $html );
		$element           = $domDocumentHelper->find_element( 'p' );
		$empty             = $domDocumentHelper->find_element( 'span' );
		$this->assertInstanceOf( \DOMElement::class, $element );
		$this->assertEquals( 'p', $element->tagName );
		$this->assertNull( $empty );
	}

	public function testItGetsAttributeValue(): void {
		$html              = '<div><p class="some-class">Some text</p></div>';
		$domDocumentHelper = new Dom_Document_Helper( $html );
		$element           = $domDocumentHelper->find_element( 'p' );
		$this->assertInstanceOf( \DOMElement::class, $element );
		$this->assertEquals( 'some-class', $domDocumentHelper->get_attribute_value( $element, 'class' ) );
	}

	public function testItGetsOuterHtml(): void {
		$html              = '<div><span>Some <strong>text</strong></span></div>';
		$domDocumentHelper = new Dom_Document_Helper( $html );
		$element           = $domDocumentHelper->find_element( 'span' );
		$this->assertInstanceOf( \DOMElement::class, $element );
		$this->assertEquals( '<span>Some <strong>text</strong></span>', $domDocumentHelper->get_outer_html( $element ) );

		// testings encoding of special characters
		$html              = '<div><img src="https://test.com/DALL·E-A®∑oecasƒ-803x1024.jpg"></div>';
		$domDocumentHelper = new Dom_Document_Helper( $html );
		$element           = $domDocumentHelper->find_element( 'img' );
		$this->assertInstanceOf( \DOMElement::class, $element );
		$this->assertEquals( '<img src="https://test.com/DALL%C2%B7E-A%C2%AE%E2%88%91oecas%C6%92-803x1024.jpg">', $domDocumentHelper->get_outer_html( $element ) );
	}

	public function testItGetsAttributeValueByTagName(): void {
		$html              = '<div><p class="some-class">Some text</p><p class="second-paragraph"></p></div>';
		$domDocumentHelper = new Dom_Document_Helper( $html );
		$this->assertEquals( 'some-class', $domDocumentHelper->get_attribute_value_by_tag_name( 'p', 'class' ) );
		$this->assertNull( $domDocumentHelper->get_attribute_value_by_tag_name( 'span', 'class' ) );
	}
}
