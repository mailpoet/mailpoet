<?php

namespace MailPoet\Newsletter\Editor;

class Transformer {

  private $transformer;

  public function __construct($args) {
    $title_list_only = $args['displayType'] === 'titleOnly' && $args['titleFormat'] === 'ul';

    if ($title_list_only) $transformer = new TitleListTransformer($args);
    else $transformer = new PostListTransformer($args);
    $this->transformer = $transformer;
  }

  public function transform($posts) {
    return $this->transformer->transform($posts);
  }
}
