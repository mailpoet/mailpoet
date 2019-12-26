<?php

namespace MailPoet\Newsletter\Editor;

class PostListTransformer {

  private $args;
  private $transformer;

  public function __construct($args) {
    $this->args = $args;
    $this->transformer = new PostTransformer($args);
  }

  public function transform($posts) {
    $results = [];
    $use_divider = filter_var($this->args['showDivider'], FILTER_VALIDATE_BOOLEAN);

    foreach ($posts as $index => $post) {
      if ($use_divider && $index > 0) {
        $results[] = $this->transformer->getDivider();
      }

      $results = array_merge($results, $this->transformer->transform($post));
    }

    return $results;
  }
}
