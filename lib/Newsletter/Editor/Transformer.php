<?php
namespace MailPoet\Newsletter\Editor;


if (!defined('ABSPATH')) exit;

class Transformer {

  private $transformer;

  function __construct($args) {
    $title_list_only = $args['displayType'] === 'titleOnly' && $args['titleFormat'] === 'ul';

    if ($title_list_only) $transformer = new TitleListTransformer($args);
    else $transformer = new PostListTransformer($args);
    $this->transformer = $transformer;
  }

  function transform($posts) {
    return $this->transformer->transform($posts);
  }
}
