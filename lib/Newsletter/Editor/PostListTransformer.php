<?php
namespace MailPoet\Newsletter\Editor;

use \MailPoet\Newsletter\Editor\PostTransformer;

if(!defined('ABSPATH')) exit;

class PostListTransformer {

  function __construct($args) {
    $this->args = $args || array();
    $this->transformer = new PostTransformer($args);
  }

  function transform($posts) {
    $total_posts = count($posts);
    $results = array();
    $use_divider = (bool)$this->args['showDivider'];

    foreach ($posts as $index => $post) {
      $results = array_merge($this->transformer->transform($post), $results);

      if ($use_divider && $index + 1 < $total_posts) {
        $results[] = $args['divider'];
      }
    }

    return $results;
  }
}
