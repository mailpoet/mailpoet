<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Editor\Transformer;
use MailPoet\Router\Wordpress;

class AutomatedLatestContent {
  static function render($element, $column_count) {
    $wordpress = new Wordpress();
    $transformer = new Transformer($element);
    $posts = $wordpress->fetchWordPressPosts($element);
    $transformed_posts = array('blocks' => $transformer->transform($posts));
    $renderer = new Renderer();
    return $renderer->render($transformed_posts, $column_count);
  }
}