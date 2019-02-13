<?php
namespace MailPoet\WP;

class Posts {

  static function getTerms($args) {
    // Since WordPress 4.5.0 signature of get_terms changed to require
    // one argument array, where taxonomy is key of that array
    if (version_compare(get_bloginfo('version'), '4.5.0', '>=')) {
      return get_terms($args);
    } else {
      $taxonomy = $args['taxonomy'];
      unset($args['taxonomy']);
      return get_terms($taxonomy, $args);
    }
  }

  static function getTypes($args = array(), $output = 'names', $operator = 'and') {
    $defaults = array(
      'exclude_from_search' => false
    );
    $args = array_merge($defaults, $args);
    return get_post_types($args, $output, $operator);
  }

}
