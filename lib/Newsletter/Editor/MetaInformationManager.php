<?php
namespace MailPoet\Newsletter\Editor;

if(!defined('ABSPATH')) exit;

class MetaInformationManager {

  function appendMetaInformation($content, $post, $args) {
    // Append author and categories above and below contents
    foreach (array('above', 'below') as $position) {
      $position_field = $position . 'Text';
      $text = '';

      if ($args['showAuthor'] === $position_field) {
        $text .= self::getPostAuthor(
          $args['authorPrecededBy'],
          $post->post_author
        );
      }

      if ($args['showCategories'] === $position_field) {
        if (!empty($text)) $text .= '<br />';
        $text .= self::getPostCategories(
          $args['categoriesPrecededBy'],
          $post
        );
      }

      if (!empty($text)) $text = '<p>' . $text . '</p>';
      if ($position === 'above') $content = $text . $content;
      else if ($position === 'below') $content .= $text;
    }

    return $content;
  }


  private static function getPostCategories($preceded_by, $post) {
    $preceded_by = trim($preceded_by);
    $content = '';

    // Get categories
    $categories = wp_get_post_terms(
      $post->ID,
      get_object_taxonomies($post->post_type),
      array('fields' => 'names')
    );
    if(!empty($categories)) {
      // check if the user specified a label to be displayed before the author's name
      if(strlen($preceded_by) > 0) {
        $content = stripslashes($preceded_by) . ' ';
      }

      $content .= join(', ', $categories);
    }

    return $content;
  }

  private static function getPostAuthor($preceded_by, $author_id) {
    $author_name = get_the_author_meta('display_name', (int)$author_id);

    $preceded_by = trim($preceded_by);
    if(strlen($preceded_by) > 0) {
      $author_name = stripslashes($preceded_by) . ' ' . $author_name;
    }

    return $author_name;
  }
}
