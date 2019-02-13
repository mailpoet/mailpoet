<?php
namespace MailPoet\Util;
use pQuery\DomNode;

class DOM {

  /**
   * Splits a DOM tree around the cut element, bringing it up to bound
   * ancestor and splitting left and right siblings into subtrees along
   * the way, retaining order and nesting level.
   */
  static function splitOn(DomNode $bound, DomNode $cut_element) {
    $ignore_text_and_comment_nodes = false;
    for ($parent = $cut_element->parent; $bound != $parent; $parent = $grandparent) {
      // Clone parent node without children, but with attributes
      $parent->after($parent->toString());
      $right = $parent->getNextSibling($ignore_text_and_comment_nodes);
      $right->clear();

      while ($sibling = $cut_element->getNextSibling($ignore_text_and_comment_nodes)) {
        $sibling->move($right);
      }

      // Reattach cut_element and right siblings to grandparent
      $grandparent = $parent->parent;
      $index_after_parent = $parent->index() + 1;
      $right->move($grandparent, $index_after_parent);
      $index_after_parent = $parent->index() + 1;
      $cut_element->move($grandparent, $index_after_parent);
    }
  }

  static function findTopAncestor(DomNode $item) {
    while ($item->parent->parent !== null) {
      $item = $item->parent;
    }
    return $item;
  }

}
