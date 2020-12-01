<?php

namespace MailPoet\WP;

/**
 * Class AutocompletePostListLoader is used to load data for the frontend autocomplete
 */
class AutocompletePostListLoader {
  /** @var Functions */
  private $wp;

  public function __construct(Functions $wp) {
    $this->wp = $wp;
  }

  public function getProducts() {
    $products = $this->wp->getResultsFromWpDb(
      "SELECT `ID`, `post_title` FROM {$this->wp->getWPTableName('posts')} WHERE `post_type` = %s ORDER BY `post_title` ASC;",
      'product'
    );
    return $this->formatPosts($products);
  }

  public function getWooCommerceCategories() {
    return $this->formatTerms($this->wp->getCategories(['taxonomy' => 'product_cat', 'orderby' => 'name']));
  }

  private function formatPosts($posts) {
    if (empty($posts)) return [];
    $result = [];
    foreach ($posts as $post) {
      $result[] = [
        'id' => $post->ID,
        'name' => $post->post_title,// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ];
    }
    return $result;
  }

  private function formatTerms($terms) {
    if (empty($terms)) return [];
    if (!is_array($terms)) return []; // there can be instance of WP_Error instead of list of terms if woo commerce is not active
    $result = [];
    foreach ($terms as $term) {
      $result[] = [
        'id' => $term->term_id,// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        'name' => $term->name,
      ];
    }
    return $result;
  }
}
