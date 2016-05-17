<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\StylesHelper;

class Renderer {
  public $newsletter;
  public $posts;

  function __construct(array $newsletter, $posts = false) {
    $this->newsletter = $newsletter;
    $this->posts = array();
    $this->ALC = new \MailPoet\Newsletter\AutomatedLatestContent($this->newsletter['id']);
  }

  function render($data, $column_count) {
    $block_content = '';
    array_map(function($block) use (&$block_content, &$columns, $column_count) {
      $block_content .= $this->createElementFromBlockType($block, $column_count);
      if(isset($block['blocks'])) {
        $block_content = $this->render($block, $column_count);
      }
      // vertical orientation denotes column container
      if($block['type'] === 'container' && $block['orientation'] === 'vertical') {
        $columns[] = $block_content;
      }
    }, $data['blocks']);
    return (isset($columns)) ? $columns : $block_content;
  }

  function createElementFromBlockType($block, $column_count) {
    if ($block['type'] === 'automatedLatestContent') {
      $content = $this->processAutomatedLatestContent($block, $column_count);
      return $content;
    }
    $block = StylesHelper::applyTextAlignment($block);
    $block_class = __NAMESPACE__ . '\\' . ucfirst($block['type']);
    if (!class_exists($block_class)) {
      return '';
    }
    return $block_class::render($block, $column_count);
  }

  function processAutomatedLatestContent($args, $column_count) {
    $posts_to_exclude = $this->getPosts();
    $ALCPosts = $this->ALC->getPosts($args, $posts_to_exclude);
    foreach($ALCPosts as $post) {
      $posts_to_exclude[] = $post->ID;
    }
    $transformed_posts = array(
      'blocks' => $this->ALC->transformPosts($args, $ALCPosts)
    );
    $this->setPosts($posts_to_exclude);
    $rendered_posts = $this->render($transformed_posts, $column_count);
    return $rendered_posts;
  }

  function getPosts() {
    return $this->posts;
  }

  function setPosts($posts) {
    return $this->posts = $posts;
  }
}
