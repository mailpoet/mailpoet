<?php

namespace MailPoet\AutomaticEmails\WooCommerce;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

class Helper {
  /**
   * @var WPFunctions
   */
  private $wp;

  /**
   * @var WCHelper
   */
  private $helper;

  function __construct(WPFunctions $wp = null, WCHelper $helper = null) {
    if ($wp === null) {
      $wp = new WPFunctions;
    }
    if ($helper === null) {
      $helper = new WCHelper;
    }
    $this->wp = $wp;
    $this->helper = $helper;
  }

  /**
   * @param string $customer_email
   * @return bool|Subscriber
   */
  function getWooCommerceSegmentSubscriber($customer_email) {
    $wc_segment = Segment::getWooCommerceSegment();
    return Subscriber::tableAlias('subscribers')
      ->select('subscribers.*')
      ->where('subscribers.email', $customer_email)
      ->join(
          MP_SUBSCRIBER_SEGMENT_TABLE,
          'relation.subscriber_id = subscribers.id',
          'relation'
        )
      ->where('relation.segment_id', $wc_segment->id)
      ->where('relation.status', Subscriber::STATUS_SUBSCRIBED)
      ->where('subscribers.status', Subscriber::STATUS_SUBSCRIBED)
      ->where('subscribers.is_woocommerce_user', 1)
      ->findOne();
  }

  function getCustomerOrderCount($customer_email) {
    // registered user
    $user = $this->wp->getUserBy('email', $customer_email);
    if ($user) {
      return $this->helper->wcGetCustomerOrderCount($user->ID);
    }
    // guest user
    return $this->getGuestCustomerOrderCountByEmail($customer_email);
  }

  private function getGuestCustomerOrderCountByEmail($customer_email) {
    global $wpdb;
    $count = $wpdb->get_var( "SELECT COUNT(*)
        FROM $wpdb->posts as posts
        LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
        WHERE   meta.meta_key = '_billing_email'
        AND     posts.post_type = 'shop_order'
        AND     meta_value = '" . $this->wp->escSql($customer_email) . "'
    " );
    return (int)$count;
  }
}
