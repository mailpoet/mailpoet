<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterPost;
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

      $last_post = NewsletterPost::getNewestNewsletterPost($newsletter_id);
      if($last_post) {
        $newer_than_timestamp = $last_post->created_at;
      } else {
        $parent = Newsletter::findOne($newsletter_id);
        $newer_than_timestamp = $parent->created_at;
      }
    } else if($preview) {
      $newsletter_id = false;
      $newer_than_timestamp = false;
    } else {
      $newsletter_id = $newsletter['id'];
      $newer_than_timestamp = false;
    }
    $this->ALC = new \MailPoet\Newsletter\AutomatedLatestContent(
      $newsletter_id,
      $newer_than_timestamp
    );
  }

  function render($data, $column_count) {
    $block_content = '';
    $_this = $this;
    array_map(function($block) use (&$block_content, &$column_content, $column_count, $_this) {
      $rendered_block_element = $_this->createElementFromBlockType($block, $column_count);
      if(isset($block['blocks'])) {
        $rendered_block_element = $_this->render($block, $column_count);
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
