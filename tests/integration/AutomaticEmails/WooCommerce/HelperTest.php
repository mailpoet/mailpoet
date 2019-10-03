<?php

namespace MailPoet\AutomaticEmails\WooCommerce;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Codeception\Util\Fixtures;
use MailPoet\AutomaticEmails\WooCommerce\Helper;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

require_once(ABSPATH . 'wp-admin/includes/user.php');

class HelperTest extends \MailPoetTest {
  function _before() {
    $this->helper = new Helper;
    $this->wc_segment = Segment::getWooCommerceSegment();
    $this->customer_email = 'helper_test@example.com';
  }

  function testItReturnsFalseForNonExistentSubscriber() {
    $result = $this->helper->getWooCommerceSegmentSubscriber($this->customer_email);
    expect($result)->equals(false);
  }

  function testItReturnsFalseForGloballyUnsubscribedSubscriber() {
    $subscriber = $this->createWooCommerceSegmentSubscriber($this->customer_email);
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->save();

    $result = $this->helper->getWooCommerceSegmentSubscriber($this->customer_email);
    expect($result)->equals(false);
  }

  function testItReturnsFalseForSubscriberUnsubscribedFromWCList() {
    $subscriber = $this->createWooCommerceSegmentSubscriber($this->customer_email);
    SubscriberSegment::unsubscribeFromSegments($subscriber, [$this->wc_segment->id]);

    $result = $this->helper->getWooCommerceSegmentSubscriber($this->customer_email);
    expect($result)->equals(false);
  }

  function testItReturnsWooCommerceSegmentSubscriber() {
    $subscriber = $this->createWooCommerceSegmentSubscriber($this->customer_email);

    $result = $this->helper->getWooCommerceSegmentSubscriber($this->customer_email);
    expect($result)->isInstanceOf(Subscriber::class);
    expect($result->id)->equals($subscriber->id);
    expect($result->email)->equals($subscriber->email);
  }

  function testItUsesAHelperToCountRegisteredCustomerOrders() {
    $user_id = wp_create_user('WP User', 'pass', $this->customer_email);
    $wc_helper = Stub::make(new WCHelper, [
      'wcGetCustomerOrderCount' => Expected::once(1),
    ], $this);
    $helper = new Helper(null, $wc_helper);
    $result = $helper->getCustomerOrderCount($this->customer_email);
    wp_delete_user($user_id);
    expect($result)->equals(1);
  }

  function testItCountsGuestCustomerOrders() {
    $result = $this->helper->getCustomerOrderCount($this->customer_email);
    expect($result)->equals(0);
    $post_id = wp_insert_post([
      'post_type' => 'shop_order',
      'meta_input' => [
        '_billing_email' => $this->customer_email,
      ],
    ]);
    $result = $this->helper->getCustomerOrderCount($this->customer_email);
    expect($result)->equals(1);
    $this->deleteOrder($post_id);
  }

  private function createWooCommerceSegmentSubscriber($customer_email) {
    $subscriber = Subscriber::createOrUpdate(Fixtures::get('subscriber_template'));
    $subscriber->email = $customer_email;
    $subscriber->is_woocommerce_user = 1;
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();

    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => $this->wc_segment->id,
    ]);

    return $subscriber;
  }

  private function deleteOrder($id) {
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         id = %s
    ', $wpdb->posts, $id));
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         post_id = %s
    ', $wpdb->postmeta, $id));
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
  }
}
