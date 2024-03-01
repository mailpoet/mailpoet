<?php declare(strict_types = 1);

namespace MailPoet\Util\pQuery;

use MailPoetVendor\pQuery\Html5Parser as pQueryHtml5Parser;

class Html5Parser extends pQueryHtml5Parser {
  /** @var string|DomNode */
  public $root = DomNode::class;
}
