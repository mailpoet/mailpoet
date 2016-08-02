<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Renderer {
  public $newsletter;
  public $posts;
  public $ALC;

  function __construct(array $newsletter, $preview) {
    $this->newsletter = $newsletter;
    $this->posts = array();
    if($newsletter['type'] === Newsletter::TYPE_NOTIFICATION_HISTORY) {
      $newsletter_id = $newsletter['parent_id'];
    } else if($preview) {
      $newsletter_id = false;
    } else {
      $newsletter_id = $newsletter['id'];
    }
    $this->ALC = new \MailPoet\Newsletter\AutomatedLatestContent(
      $newsletter_id,
      $newsletter['created_at']
    );
  }

  function render($data, $column_count) {
    $block_content = '';
    array_map(function($block) use (&$block_content, &$column_content, $column_count) {
      $rendered_block_element = $this->createElementFromBlockType($block, $column_count);
      if(isset($block['blocks'])) {
        $rendered_block_element = $this->render($block, $column_count);
      }
      // vertical orientation denotes column container
      if($block['type'] === 'container' && $block['orientation'] === 'vertical') {
        $column_content[] = $rendered_block_element;
      } else {
        $block_content .= $rendered_block_element;
      }
    }, $data['blocks']);
    return (isset($column_content)) ? $column_content : $block_content;
  }

  function createElementFromBlockType($block, $column_count) {
    if($block['type'] === 'automatedLatestContent') {
      $content = $this->processAutomatedLatestContent($block, $column_count);
      return $content;
    }
    $block = StylesHelper::applyTextAlignment($block);
    $block_class = __NAMESPACE__ . '\\' . ucfirst($block['type']);
    if(!class_exists($block_class)) {
      return '';
    }
    return $block_class::render($block, $column_count);
  }

  function processAutomatedLatestContent($args, $column_count) {
    $posts_to_exclude = $this->getPosts();
    $ALC_posts = $this->ALC->getPosts($args, $posts_to_exclude);
    foreach($ALC_posts as $post) {
      $posts_to_exclude[] = $post->ID;
    }
    $transformed_posts = array(
      'blocks' => $this->ALC->transformPosts($args, $ALC_posts)
    );
    $this->setPosts($posts_to_exclude);
    $transformed_posts = StylesHelper::applyTextAlignment($transformed_posts);
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
