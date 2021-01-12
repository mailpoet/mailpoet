<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Idiorm\ORM;

class WooCommerceCategory implements Filter {

  const SEGMENT_TYPE = 'woocommerce';

  const ACTION_CATEGORY = 'purchasedCategory';

  /** @var int */
  private $categoryId;

  /** @var string|null */
  private $connect;

  /**
   * @param int $categoryId
   * @param string|null $connect
   */
  public function __construct($categoryId, $connect = null) {
    $this->categoryId = (int)$categoryId;
    $this->connect = $connect;
  }

  public function toSql(ORM $orm) {
    global $wpdb;
    $orm->distinct();
    $orm->rawJoin(
      'INNER JOIN ' . $wpdb->postmeta,
      "postmeta.meta_key = '_customer_user' AND " . Subscriber::$_table . '.wp_user_id=postmeta.meta_value',
      'postmeta'
    );
    $orm->join($wpdb->prefix . 'woocommerce_order_items', ['postmeta.post_id', '=', 'items.order_id'], 'items');
    $orm->rawJoin(
      'INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta',
      "itemmeta.order_item_id = items.order_item_id AND itemmeta.meta_key = '_product_id'",
      'itemmeta'
    );
    $orm->join($wpdb->term_relationships, ['itemmeta.meta_value', '=', 'term_relationships.object_id'], 'term_relationships'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $orm->rawJoin(
      'INNER JOIN ' . $wpdb->term_taxonomy, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      '
         term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id
         AND
         term_taxonomy.term_id IN (' . join(',', $this->getAllCategoryIds()) . ')',
      'term_taxonomy'
    );
    $orm->where('status', Subscriber::STATUS_SUBSCRIBED);
    $orm->whereRaw(
      'postmeta.post_id NOT IN (
               SELECT id FROM ' . $wpdb->posts . ' as p WHERE p.post_status IN ("wc-cancelled", "wc-failed")
      )'
    );
    return $orm;
  }

  private function getAllCategoryIds() {
    $subcategories = WPFunctions::get()->getTerms('product_cat', ['child_of' => $this->categoryId]);
    if (!is_array($subcategories)) return [];
    $ids = array_map(function($category) {
      return $category->term_id; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    }, $subcategories);
    $ids[] = $this->categoryId;
    return $ids;
  }

  public function toArray() {
    return [
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'category_id' => $this->categoryId,
      'connect' => $this->connect,
      'segmentType' => WooCommerceCategory::SEGMENT_TYPE,
    ];
  }
}
