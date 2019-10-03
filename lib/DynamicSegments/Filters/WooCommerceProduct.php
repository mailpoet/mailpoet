<?php

namespace MailPoet\DynamicSegments\Filters;

use MailPoet\Models\Subscriber;

class WooCommerceProduct implements Filter {

  const SEGMENT_TYPE = 'woocommerce';

  const ACTION_PRODUCT = 'purchasedProduct';

  /** @var int */
  private $product_id;

  /** @var string */
  private $connect;

  /**
   * @param int $product_id
   * @param string $connect
   */
  public function __construct($product_id, $connect = null) {
    $this->product_id = (int)$product_id;
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
      "itemmeta.order_item_id=items.order_item_id 
       AND itemmeta.meta_key='_product_id' 
       AND itemmeta.meta_value=" . $this->product_id,
      'itemmeta'
    );
    $orm->where('status', Subscriber::STATUS_SUBSCRIBED);
    return $orm;
  }

  function toArray() {
    return [
      'action' => WooCommerceProduct::ACTION_PRODUCT,
      'product_id' => $this->product_id,
      'connect' => $this->connect,
      'segmentType' => WooCommerceProduct::SEGMENT_TYPE,
    ];
  }
}
