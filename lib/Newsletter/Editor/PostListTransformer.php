<?php
namespace MailPoet\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostTransformer;

if(!defined('ABSPATH')) exit;

class PostListTransformer {

  function __construct($args) {
    $this->args = $args;
    $this->transformer = new PostTransformer($args);
  }

  function transform($posts) {
    $results = array();
    $use_divider = filter_var($this->args['showDivider'], FILTER_VALIDATE_BOOLEAN);

    foreach($posts as $index => $post) {
      if($use_divider && $index > 0) {
        $results[] = $this->args['divider'];
      }

      $results = array_merge($results, $this->transformer->transform($post));
    }

    return $results;
  }
}
