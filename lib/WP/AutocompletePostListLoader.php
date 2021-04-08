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

  public function getPosts() {
    global $wpdb;
    $optionList = $wpdb->get_results('SELECT ID, post_title FROM ' . $wpdb->posts . " WHERE post_type='post' ORDER BY `post_title` ASC;");
    return $this->formatPosts($optionList);
  }

  public function getPages() {
    global $wpdb;
    $optionList = $wpdb->get_results('SELECT ID, post_title FROM ' . $wpdb->posts . " WHERE post_type='page' ORDER BY `post_title` ASC;");
    return $this->formatPosts($optionList);
  }

  public function getWooCommerceTags() {
    return $this->formatTerms($this->wp->getTerms('product_tag'));
  }

  public function getCategories() {
    return $this->formatTerms($this->wp->getCategories());
  }

  public function getTags() {
    return $this->formatTerms($this->wp->getTags());
  }

  private function formatPosts($posts) {
    if (empty($posts)) return [];
    $result = [];
    foreach ($posts as $post) {
      $result[] = [
        'id' => (string)$post->ID,
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
        'id' => (string)$term->term_id,// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
        'name' => $term->name,
      ];
    }
    return $result;
  }
}
