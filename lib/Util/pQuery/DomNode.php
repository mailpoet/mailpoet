<?php

namespace MailPoet\Util\pQuery;

class DomNode extends \pQuery\DomNode {
  public $childClass = 'MailPoet\Util\pQuery\DomNode';

  function getInnerText() {
    return html_entity_decode($this->toString(true, true, 1), ENT_NOQUOTES, 'UTF-8');
  }

  function getOuterText() {
    return html_entity_decode($this->toString(), ENT_NOQUOTES, 'UTF-8');
  }
}
