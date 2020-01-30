<?php

namespace MailPoet\Test\Form;

class HtmlParser extends \MailPoetUnitTest {
  public function findByXpath(string $html, string $xpath): \DOMNodeList {
    $dom = new \DOMDocument();
    $dom->loadHTML($html);
    $value = (new \DOMXPath($dom))->query($xpath);
    return $value ?: new \DOMNodeList();
  }
}
