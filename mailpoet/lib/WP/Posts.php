<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\WP;

use MailPoet\WP\Functions as WPFunctions;

class Posts {
  public static function getTypes($args = [], $output = 'names', $operator = 'and') {
    $defaults = [
      'exclude_from_search' => false,
    ];
    $args = array_merge($defaults, $args);
    return WPFunctions::get()->getPostTypes($args, $output, $operator);
  }
}
