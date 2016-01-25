<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

class AutomatedLatestContent {
  static function render($element, $column_count) {
    $ALC = new \MailPoet\Newsletter\AutomatedLatestContent();
    $posts = $ALC->getPosts($element);
    $transformed_posts = array('blocks' => $ALC->transformPosts($element, $posts));
    $renderer = new Renderer();
    return $renderer->render($transformed_posts, $column_count);
  }
}