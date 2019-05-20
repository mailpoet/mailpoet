<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterPost;
use MailPoet\Newsletter\Renderer\Columns\ColumnsHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;

class Renderer {
  public $newsletter;
  public $posts;
  public $ALC;

  function __construct(array $newsletter) {
    $this->newsletter = $newsletter;
    $this->posts = [];
    $newer_than_timestamp = false;
    $newsletter_id = false;
    if ($newsletter['type'] === Newsletter::TYPE_NOTIFICATION_HISTORY) {
      $newsletter_id = $newsletter['parent_id'];

      $last_post = NewsletterPost::getNewestNewsletterPost($newsletter_id);
      if ($last_post) {
        $newer_than_timestamp = $last_post->created_at;
      }
    }
    $this->ALC = new \MailPoet\Newsletter\AutomatedLatestContent(
      $newsletter_id,
      $newer_than_timestamp
    );
  }

  function render($data) {
    $column_count = count($data['blocks']);
    $columns_layout = isset($data['columnLayout']) ? $data['columnLayout'] : null;
    $column_widths = ColumnsHelper::columnWidth($column_count, $columns_layout);
    $column_content = [];

    foreach ($data['blocks'] as $index => $column_blocks) {
      $rendered_block_element = $this->renderBlocksInColumn($column_blocks, $column_widths[$index]);
      $column_content[] = $rendered_block_element;
    }

    return $column_content;
  }

  private function renderBlocksInColumn($block, $column_base_width) {
    $block_content = '';
    $_this = $this;
    array_map(function($block) use (&$block_content, $column_base_width, $_this) {
      $rendered_block_element = $_this->createElementFromBlockType($block, $column_base_width);
      if (isset($block['blocks'])) {
        $rendered_block_element = $_this->renderBlocksInColumn($block, $column_base_width);
        // nested vertical column container is rendered as an array
        if (is_array($rendered_block_element)) {
          $rendered_block_element = implode('', $rendered_block_element);
        }
      }

      $block_content .= $rendered_block_element;
    }, $block['blocks']);
    return $block_content;
  }

  function createElementFromBlockType($block, $column_base_width) {
    if ($block['type'] === 'automatedLatestContent') {
      $content = $this->processAutomatedLatestContent($block, $column_base_width);
      return $content;
    }
    $block = StylesHelper::applyTextAlignment($block);
    $block_class = __NAMESPACE__ . '\\' . ucfirst($block['type']);
    if (!class_exists($block_class)) {
      return '';
    }
    return $block_class::render($block, $column_base_width);
  }

  function automatedLatestContentTransformedPosts($args) {
    $posts_to_exclude = $this->getPosts();
    $ALC_posts = $this->ALC->getPosts($args, $posts_to_exclude);
    foreach ($ALC_posts as $post) {
      $posts_to_exclude[] = $post->ID;
    }
    $this->setPosts($posts_to_exclude);
    return $this->ALC->transformPosts($args, $ALC_posts);
  }

  function processAutomatedLatestContent($args, $column_base_width) {
    $transformed_posts = [
      'blocks' => $this->automatedLatestContentTransformedPosts($args),
    ];
    $transformed_posts = StylesHelper::applyTextAlignment($transformed_posts);
    $rendered_posts = $this->renderBlocksInColumn($transformed_posts, $column_base_width);
    return $rendered_posts;
  }

  function getPosts() {
    return $this->posts;
  }

  function setPosts($posts) {
    return $this->posts = $posts;
  }
}
