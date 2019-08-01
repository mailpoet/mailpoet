<?php
namespace MailPoet\Newsletter\Editor;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class MetaInformationManager {

  function appendMetaInformation($content, $post, $args) {
    // Append author and categories above and below contents
    foreach (['above', 'below'] as $position) {
      $position_field = $position . 'Text';
      $text = [];

      if (isset($args['showAuthor']) && $args['showAuthor'] === $position_field) {
        $text[] = self::getPostAuthor(
          $post->post_author,
          $args['authorPrecededBy']
        );
      }

      if (isset($args['showCategories']) && $args['showCategories'] === $position_field) {
        $text[] = self::getPostCategories(
          $post->ID,
          $post->post_type,
          $args['categoriesPrecededBy']
        );
      }

      if (!empty($text)) {
        $text = '<p>' . implode('<br />', $text) . '</p>';
        if ($position === 'above') $content = $text . $content;
        else if ($position === 'below') $content .= $text;
      }
    }

    return $content;
  }


  private static function getPostCategories($post_id, $post_type, $preceded_by) {
    $preceded_by = trim($preceded_by);

    // Get categories
    $categories = WPFunctions::get()->wpGetPostTerms(
      $post_id,
      ['category'],
      ['fields' => 'names']
    );
    if (!empty($categories)) {
      // check if the user specified a label to be displayed before the author's name
      if (strlen($preceded_by) > 0) {
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
    $author_name = WPFunctions::get()->getTheAuthorMeta('display_name', (int)$author_id);

    $preceded_by = trim($preceded_by);
    if (strlen($preceded_by) > 0) {
      $author_name = stripslashes($preceded_by) . ' ' . $author_name;
    }

    return $author_name;
  }
}
