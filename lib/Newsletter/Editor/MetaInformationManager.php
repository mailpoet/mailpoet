<?php
namespace MailPoet\Newsletter\Editor;

if(!defined('ABSPATH')) exit;

class MetaInformationManager {

  function appendMetaInformation($content, $post, $args) {
    // Append author and categories above and below contents
    foreach(array('above', 'below') as $position) {
      $position_field = $position . 'Text';
      $text = array();

      if($args['showAuthor'] === $position_field) {
        $text[] = self::getPostAuthor(
          $post->post_author,
          $args['authorPrecededBy']
        );
      }

      if($args['showCategories'] === $position_field) {
        $text[] = self::getPostCategories(
          $post->ID,
          $post->post_type,
          $args['categoriesPrecededBy']
        );
      }

      if(!empty($text)) {
        $text = '<p>' . implode('<br />', $text) . '</p>';
        if($position === 'above') $content = $text . $content;
        else if($position === 'below') $content .= $text;
      }
    }

    return $content;
  }


  private static function getPostCategories($post_id, $post_type, $preceded_by) {
    $preceded_by = trim($preceded_by);

    // Get categories
    $categories = wp_get_post_terms(
      $post_id,
      array('post_tag', 'category'),
      array('fields' => 'names')
    );
    if(!empty($categories)) {
      // check if the user specified a label to be displayed before the author's name
      if(strlen($preceded_by) > 0) {
        $content = stripslashes($preceded_by) . ' ';
      } else {
        $content = '';
      }

      return $content . join(', ', $categories);
    } else {
      return '';
    }
  }

  private static function getPostAuthor($author_id, $preceded_by) {
    $author_name = get_the_author_meta('display_name', (int)$author_id);

    $preceded_by = trim($preceded_by);
    if(strlen($preceded_by) > 0) {
      $author_name = stripslashes($preceded_by) . ' ' . $author_name;
    }

    return $author_name;
  }
}
