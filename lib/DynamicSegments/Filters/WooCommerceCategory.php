<?php

namespace MailPoet\Premium\DynamicSegments\Filters;

use MailPoet\Models\Subscriber;
use MailPoet\WP\Functions as WPFunctions;

class WooCommerceCategory implements Filter {

  const SEGMENT_TYPE = 'woocommerce';

  const ACTION_CATEGORY = 'purchasedCategory';

  /** @var int */
  private $category_id;

  /** @var string */
  private $connect;

  /**
   * @param int $category_id
   * @param string $connect
   */
  public function __construct($category_id, $connect = null) {
    $this->category_id = (int)$category_id;
    $this->connect = $connect;
  }

  function toSql(\ORM $orm) {
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
    $orm->join($wpdb->term_relationships, ['itemmeta.meta_value', '=', 'term_relationships.object_id'], 'term_relationships');
    $orm->rawJoin(
      'INNER JOIN ' . $wpdb->term_taxonomy,
      '
         term_taxonomy.term_taxonomy_id=term_relationships.term_taxonomy_id 
         AND 
         term_taxonomy.term_id IN (' . join(',', $this->getAllCategoryIds()) . ')',
      'term_taxonomy'
    );
    $orm->where('status', Subscriber::STATUS_SUBSCRIBED);
    return $orm;
  }

  private function getAllCategoryIds() {
    $subcategories = WPFunctions::get()->getTerms('product_cat', ['child_of' => $this->category_id]);
    if (!is_array($subcategories)) return [];
    $ids = array_map(function($category) {
      return $category->term_id;
    }, $subcategories);
    $ids[] = $this->category_id;
    return $ids;
  }

  function toArray() {
    return [
      'action' => WooCommerceCategory::ACTION_CATEGORY,
      'category_id' => $this->category_id,
      'connect' => $this->connect,
      'segmentType' => WooCommerceCategory::SEGMENT_TYPE,
    ];
  }
}
