<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util\pQuery;

use MailPoetVendor\pQuery\Html5Parser as pQueryHtml5Parser;

class Html5Parser extends pQueryHtml5Parser {
  /**
   * Override the default root class so the parent's auto-create-root
   * pattern instantiates our DomNode subclass. Before construction this
   * holds a class-name string; after construction the parent constructor
   * replaces it with an instance — see HtmlParser::__construct.
   *
   * @var class-string<DomNode>|DomNode
   * @phpstan-ignore property.phpDocType
   */
  public $root = DomNode::class;
}
