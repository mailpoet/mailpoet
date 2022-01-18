<?php

namespace MailPoet\Util;

use MailPoetVendor\pQuery\DomNode;

class DOM {
  /**
   * Splits a DOM tree around the cut element, bringing it up to bound
   * ancestor and splitting left and right siblings into subtrees along
   * the way, retaining order and nesting level.
   */
  public static function splitOn(DomNode $bound, DomNode $cutElement) {
    $ignoreTextAndCommentNodes = false;
    $grandparent = $cutElement->parent;
    for ($parent = $cutElement->parent; $bound != $parent; $parent = $grandparent) {
      // Clone parent node without children, but with attributes
      $parent->after($parent->toString());
      $right = $parent->getNextSibling($ignoreTextAndCommentNodes);
      $right->clear();

      while ($sibling = $cutElement->getNextSibling($ignoreTextAndCommentNodes)) {
        $sibling->move($right);
      }

      // Reattach cut_element and right siblings to grandparent
      /* @phpstan-ignore-next-line Because there is a wrong annotation in the library tburry/pquery */
      $grandparent = $parent->parent;
      $indexAfterParent = $parent->index() + 1;
      $right->move($grandparent, $indexAfterParent);
      $indexAfterParent = $parent->index() + 1;
      $cutElement->move($grandparent, $indexAfterParent);
    }
  }

  public static function findTopAncestor(DomNode $item) {
    while ($item->parent->parent !== null) {
      $item = $item->parent;
    }
    return $item;
  }
}
