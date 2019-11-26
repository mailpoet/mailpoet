<?php

namespace MailPoet\Util\pQuery;

// extend pQuery class to use UTF-8 encoding when getting elements' inner/outer text
// phpcs:ignore Squiz.Classes.ValidClassName
class pQuery extends \pQuery {
  public static function parseStr($html) {
    $parser = new Html5Parser($html);

    if (!$parser->root instanceof \pQuery\DomNode) {
      // this condition shouldn't happen it is here only for PHPStan
      throw new \Exception('Renderer is not configured correctly');
    }

    return $parser->root;
  }
}
