<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\WP;

use MailPoet\WooCommerce\Helper;

/**
 * Class AutocompletePostListLoader is used to load data for the frontend autocomplete
 */
class AutocompletePostListLoader {
  /** @var Functions */
  private $wp;

  private $wc;

  public function __construct(
    Functions $wp,
    Helper $wc
  ) {
    $this->wp = $wp;
    $this->wc = $wc;
  }

  public function getProductsExcludingTypes(array $types): array {
    if (!$this->wc->isWooCommerceActive()) {
      return [];
    }
    /** @var \WC_Product_Data_Store_Interface $dataStore */
    $dataStore = (new \WC_Product())->get_data_store();
    return array_values(array_filter(
      $this->getProducts(),
      function($product) use ($types, $dataStore) {
        return !in_array($dataStore->get_product_type($product['id']), $types);
      }
    ));
  }

  public function getProducts(): array {
    global $wpdb;

    $products = $wpdb->get_results($wpdb->prepare(
      "SELECT `ID`, `post_title` FROM {$wpdb->posts} WHERE `post_type` = %s ORDER BY `post_title` ASC;",
      'product'
    ));
    return $this->formatPosts($products);
  }

  public function getMembershipPlans() {
    global $wpdb;
    $products = $wpdb->get_results($wpdb->prepare(
      "SELECT `ID`, `post_title` FROM {$wpdb->posts} WHERE `post_type` = %s AND `post_status` = 'publish' ORDER BY `post_title` ASC;",
      'wc_membership_plan'
    ));
    return $this->formatPosts($products);
  }

  public function getSubscriptionProducts() {
    global $wpdb;
    $products = $wpdb->get_results($wpdb->prepare(
      "SELECT `ID`, `post_title` FROM {$wpdb->posts} AS p
        INNER JOIN {$wpdb->term_relationships} AS trel ON trel.object_id = p.id
        INNER JOIN {$wpdb->term_taxonomy} AS ttax ON ttax.term_taxonomy_id = trel.term_taxonomy_id
        INNER JOIN {$wpdb->terms} AS t ON ttax.term_id = t.term_id AND t.slug IN ('subscription', 'variable-subscription')
        WHERE `p`.`post_type` = %s ORDER BY `post_title` ASC;",
      'product'
    ));
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
        'name' => $post->post_title,// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
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
        'id' => (string)$term->term_id,// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        'name' => $term->name,
      ];
    }
    return $result;
  }
}
