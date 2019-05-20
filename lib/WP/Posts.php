<?php
namespace MailPoet\WP;

use MailPoet\WP\Functions as WPFunctions;

class Posts {

  static function getTerms($args) {
    // Since WordPress 4.5.0 signature of get_terms changed to require
    // one argument array, where taxonomy is key of that array
    if (version_compare(WPFunctions::get()->getBloginfo('version'), '4.5.0', '>=')) {
      return WPFunctions::get()->getTerms($args);
    } else {
      $taxonomy = $args['taxonomy'];
      unset($args['taxonomy']);
      return WPFunctions::get()->getTerms($taxonomy, $args);
    }
  }

  static function getTypes($args = [], $output = 'names', $operator = 'and') {
    $defaults = [
      'exclude_from_search' => false,
    ];
    $args = array_merge($defaults, $args);
    return WPFunctions::get()->getPostTypes($args, $output, $operator);
  }

}
