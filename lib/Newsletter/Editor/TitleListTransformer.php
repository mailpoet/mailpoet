<?php
namespace MailPoet\Newsletter\Editor;

if(!defined('ABSPATH')) exit;

class TitleListTransformer {

  function __construct($args) {
    $this->args = $args;
  }

  function transform($posts) {
    $results = array_map(array($this, 'getPostTitle'), $posts);

    return array(
      $this->wrap(array(
        'type' => 'text',
        'text' => '<ul>' . implode('', $results) . '</ul>',
      )));
  }

  private function wrap($block) {
    return LayoutHelper::row(array(
      LayoutHelper::col(array($block))
    ));
  }

  private function getPostTitle($post) {
    $title = $post->post_title;
    $alignment = $this->args['titleAlignment'];
    $alignment = (in_array($alignment, array('left', 'right', 'center'))) ? $alignment : 'left';

    if($this->args['titleIsLink']) {
      $title = '<a data-post-id="' . $post->ID . '" href="' . get_permalink($post->ID) . '">' . $title . '</a>';
    }

    return '<li style="text-align: ' . $alignment . ';">' . $title . '</li>';
  }
}
