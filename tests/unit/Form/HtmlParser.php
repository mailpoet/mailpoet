<?php

namespace MailPoet\Test\Form;

class HtmlParser extends \MailPoetUnitTest {
  public function findByXpath(string $html, string $xpath): \DOMNodeList {
    $dom = new \DOMDocument();
    $dom->loadHTML($html);
    $value = (new \DOMXPath($dom))->query($xpath);
    return $value ?: new \DOMNodeList();
  }

  public function getElementByXpath(string $html, string $xpath, int $index = 0): \DOMElement {
    $value = $this->findByXpath($html, $xpath);
    $element = $value->item($index);
    assert($element instanceof \DOMElement);
    return $element;
  }

  public function getChildElement(\DOMElement $element, string $tagName, int $index = 0): \DOMElement {
    $result = $element->getElementsByTagName($tagName)->item($index);
    assert($result instanceof \DOMElement);
    return $result;
  }

  public function getAttribute(\DOMElement $element, string $attrNam): \DOMAttr {
    $attr = $element->attributes->getNamedItem($attrNam);
    assert($attr instanceof \DOMAttr);
    return $attr;
  }
}
