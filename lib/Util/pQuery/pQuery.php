<?php

namespace MailPoet\Util\pQuery;

// extend pQuery class to use UTF-8 encoding when getting elements' inner/outer text
class pQuery extends \pQuery {
  public static function parseStr($html) {
    $parser = new Html5Parser($html);
    return $parser->root;
  }
}

class Html5Parser extends \pQuery\HtmlParser {
  /** @var string|\pQuery\DomNode */
  public $root = 'MailPoet\Util\pQuery\DomNode';
}

class DomNode extends \pQuery\DomNode {
  public $childClass = 'MailPoet\Util\pQuery\DomNode';

  function getInnerText() {
    return html_entity_decode($this->toString(true, true, 1), ENT_NOQUOTES, 'UTF-8');
  }

  function getOuterText() {
    return html_entity_decode($this->toString(), ENT_NOQUOTES, 'UTF-8');
  }
}
