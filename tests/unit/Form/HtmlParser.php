<?php

namespace MailPoet\Test\Form;

class HtmlParser {

  private $allowedHtml5Tags = ['<figure', '<figcaption'];

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
    assert($element instanceof \DOMElement);
    return $element;
  }

  public function getChildElement(\DOMElement $element, string $tagName, int $index = 0): \DOMElement {
    $result = $element->getElementsByTagName($tagName)->item($index);
    assert($result instanceof \DOMElement);
    return $result;
  }

  public function getAttribute(\DOMElement $element, string $attrNam): \DOMAttr {
    assert($element->attributes instanceof \DOMNamedNodeMap);
    $attr = $element->attributes->getNamedItem($attrNam);
    assert($attr instanceof \DOMAttr);
    return $attr;
  }
}
