<?php

namespace MailPoet\Util\pQuery;

use MailPoetVendor\pQuery\HtmlParser;

class Html5Parser extends HtmlParser {
  /** @var string|DomNode */
  public $root = DomNode::class;
}
