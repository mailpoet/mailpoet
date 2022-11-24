<?php

namespace MailPoet\Test\Form;

use PHPUnit\Framework\Assert;

class HtmlParser {

  private $allowedHtml5Tags = ['<figure', '<figcaption'];

  /**
   * @return \DOMNodeList<\DOMNode>
   */
  public function findByXpath(string $html, string $xpath): \DOMNodeList {
    $isHtml5 = str_replace($this->allowedHtml5Tags, '', $html) !== $html;
    $dom = new \DOMDocument();
    if ($isHtml5) {
      // HTML 5 tags like figure, nav are not supported so we need to turn off errors
      libxml_use_internal_errors(true);
      $dom->loadHTML($html);
      libxml_clear_errors();
    } else {
      $dom->loadHTML($html);
    }
    $value = (new \DOMXPath($dom))->query($xpath);
    return $value ?: new \DOMNodeList();
  }

  public function getElementByXpath(string $html, string $xpath, int $index = 0): \DOMElement {
    $value = $this->findByXpath($html, $xpath);
    $element = $value->item($index);
    Assert::assertInstanceOf(\DOMElement::class, $element);
    return $element;
  }

  public function getChildElement(\DOMElement $element, string $tagName, int $index = 0): \DOMElement {
    $result = $element->getElementsByTagName($tagName)->item($index);
    Assert::assertInstanceOf(\DOMElement::class, $result);
    return $result;
  }

  public function getAttribute(\DOMElement $element, string $attrNam): \DOMAttr {
    Assert::assertInstanceOf(\DOMNamedNodeMap::class, $element->attributes);
    $attr = $element->attributes->getNamedItem($attrNam);
    Assert::assertInstanceOf(\DOMAttr::class, $attr);
    return $attr;
  }
}
