<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Editor;

use MailPoet\WP\Functions as WPFunctions;

class MetaInformationManager {
  public function appendMetaInformation($content, $post, $args) {
    if ($this->isWcProduct($post)) {
      $postId = $post->get_id();
      $postAuthor = null; // Don't display author for WC products
      $postType = 'product';
    } else {
      $postId = $post->ID;
      $postAuthor = $post->post_author; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $postType = $post->post_type; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }

    // Append author and categories above and below contents
    foreach (['above', 'below'] as $position) {
      $positionField = $position . 'Text';
      $text = [];

      if (isset($args['showAuthor']) && $args['showAuthor'] === $positionField) {
        $text[] = self::applyMetaFilter(
          'mailpoet_newsletter_post_author',
          self::getPostAuthor($postAuthor, $args['authorPrecededBy']),
          [$postId, $postAuthor]
        );
      }

      if (isset($args['showCategories']) && $args['showCategories'] === $positionField) {
        $text[] = self::applyMetaFilter(
          'mailpoet_newsletter_post_categories',
          self::getPostCategories($postId, $postType, $args['categoriesPrecededBy']),
          [$postId, $postType]
        );
      }

      if (!empty($text)) {
        $text = '<p>' . implode('<br />', $text) . '</p>';
        if ($position === 'above') $content = $text . $content;
        else $content .= $text;
      }
    }

    return $content;
  }

  /**
   * Applies a meta information filter and keeps the unfiltered value when a
   * callback returns something that cannot be rendered as text.
   *
   * @param string $filterName
   * @param string $value
   * @param array $args
   * @return string
   */
  private static function applyMetaFilter($filterName, $value, array $args) {
    $filtered = WPFunctions::get()->applyFilters($filterName, $value, ...$args);

    if (is_string($filtered)) {
      return $filtered;
    }

    if (is_int($filtered) || is_float($filtered)) {
      return (string)$filtered;
    }

    // An array or object here would fatal in the implode() that joins these lines.
    return $value;
  }

  private static function getPostCategories($postId, $postType, $precededBy) {
    $precededBy = trim($precededBy);

    // Get categories
    $categories = WPFunctions::get()->wpGetPostTerms(
      $postId,
      ['category'],
      ['fields' => 'names']
    );
    if (!empty($categories)) {
      // check if the user specified a label to be displayed before the author's name
      if (strlen($precededBy) > 0) {
        $content = stripslashes($precededBy) . ' ';
      } else {
        $content = '';
      }

      return $content . join(', ', $categories);
    } else {
      return '';
    }
  }

  private static function getPostAuthor($authorId, $precededBy) {
    $authorName = WPFunctions::get()->getTheAuthorMeta('display_name', (int)$authorId);

    $precededBy = trim($precededBy);
    if (strlen($precededBy) > 0) {
      $authorName = stripslashes($precededBy) . ' ' . $authorName;
    }

    return $authorName;
  }

  private function isWcProduct($post) {
    return class_exists('\WC_Product') && $post instanceof \WC_Product;
  }
}
