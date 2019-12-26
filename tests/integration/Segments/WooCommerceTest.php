<?php

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Segments\WooCommerce;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WooCommerceTest extends \MailPoetTest  {

  private $userEmails = [];

  /** @var WooCommerce */
  private $woocommerce_segment;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    $this->woocommerce_segment = ContainerWrapper::getInstance()->get(WooCommerceSegment::class);
    $this->settings = ContainerWrapper::getInstance()->get(SettingsController::class);
    $this->cleanData();
    $this->addCustomerRole();
  }

  public function testItSynchronizesNewRegisteredCustomer() {
    $user = $this->insertRegisteredCustomer();
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => $user->user_email,
      'wp_user_id' => $user->ID,
    ]);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->source = Source::WORDPRESS_USER;
    $subscriber->save();
    $subscriber->trash();
    $hook = 'woocommerce_new_customer';
    $this->woocommerce_segment->synchronizeRegisteredCustomer($user->ID, $hook);
    $subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('wp_user_id', $user->ID)
      ->findOne();
    expect($subscriber)->notEmpty();
    expect($subscriber->email)->equals($user->user_email);
    expect($subscriber->is_woocommerce_user)->equals(1);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
    expect($subscriber->deleted_at)->equals(null);
  }

  public function testItSynchronizesUpdatedRegisteredCustomer() {
    $user = $this->insertRegisteredCustomer();
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => $user->user_email,
      'wp_user_id' => $user->ID,
    ]);
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->source = Source::WORDPRESS_USER;
    $subscriber->save();
    $hook = 'woocommerce_update_customer';
    $this->woocommerce_segment->synchronizeRegisteredCustomer($user->ID, $hook);
    $subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('wp_user_id', $user->ID)
      ->findOne();
    expect($subscriber)->notEmpty();
    expect($subscriber->email)->equals($user->user_email);
    expect($subscriber->is_woocommerce_user)->equals(1);
    expect($subscriber->source)->equals(Source::WORDPRESS_USER); // no overriding
    expect($subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED); // no overriding
  }

  public function testItSynchronizesDeletedRegisteredCustomer() {
    $wc_segment = Segment::getWooCommerceSegment();
    $user = $this->insertRegisteredCustomer();
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => $user->user_email,
      'wp_user_id' => $user->ID,
    ]);
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->source = Source::WORDPRESS_USER;
    $subscriber->save();
    $association = SubscriberSegment::create();
    $association->subscriber_id = $subscriber->id;
    $association->segment_id = $wc_segment->id;
    $association->save();
    expect(SubscriberSegment::findOne($association->id))->notEmpty();
    $hook = 'woocommerce_delete_customer';
    $this->woocommerce_segment->synchronizeRegisteredCustomer($user->ID, $hook);
    expect(SubscriberSegment::findOne($association->id))->isEmpty();
  }

  public function testItSynchronizesNewGuestCustomer() {
    $guest = $this->insertGuestCustomer();
    $hook = 'woocommerce_checkout_update_order_meta';
    $this->woocommerce_segment->synchronizeGuestCustomer($guest['order_id'], $hook);
    $subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('email', $guest['email'])
      ->findOne();
    expect($subscriber)->notEmpty();
    expect($subscriber->is_woocommerce_user)->equals(1);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
  }

  public function testItSynchronizesCustomers() {
    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1', 'enabled' => '1']);
    $user = $this->insertRegisteredCustomer();
    $guest = $this->insertGuestCustomer();
    $this->woocommerce_segment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
    $subscriber = Subscriber::where('email', $user->user_email)->findOne();
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
    $subscriber = Subscriber::where('email', $guest['email'])->findOne();
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
  }

  public function testItSynchronizesNewCustomers() {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->woocommerce_segment->synchronizeCustomers();
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->woocommerce_segment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(4);
  }

  public function testItSynchronizesPresubscribedRegisteredCustomers() {
    $random_number = 12345;
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $random_number . '@example.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $user = $this->insertRegisteredCustomer($random_number);
    $this->woocommerce_segment->synchronizeCustomers();
    $wp_subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('is_woocommerce_user', 1)
      ->where('wp_user_id', $user->ID)
      ->findOne();
    expect($wp_subscriber)->notEmpty();
    expect($wp_subscriber->id)->equals($subscriber->id);
    expect($wp_subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItSynchronizesPresubscribedGuestCustomers() {
    $random_number = 12345;
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $random_number . '@example.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $guest = $this->insertGuestCustomer($random_number);
    $this->woocommerce_segment->synchronizeCustomers();
    $wp_subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('is_woocommerce_user', 1)
      ->where('email', $guest['email'])
      ->findOne();
    expect($wp_subscriber)->notEmpty();
    expect($wp_subscriber->email)->equals($subscriber->email);
    expect($wp_subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesNotSynchronizeEmptyEmailsForNewUsers() {
    $guest = $this->insertGuestCustomer();
    update_post_meta($guest['order_id'], '_billing_email', '');
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', '')->findOne();
    expect($subscriber)->isEmpty();
    $this->deleteOrder($guest['order_id']);
  }

  public function testItDoesNotSynchronizeInvalidEmailsForNewUsers() {
    $guest = $this->insertGuestCustomer();
    $invalid_email = 'ivalid.@email.com';
    update_post_meta($guest['order_id'], '_billing_email', $invalid_email);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', $invalid_email)->findOne();
    expect($subscriber)->isEmpty();
    $this->deleteOrder($guest['order_id']);
  }

  public function testItSynchronizesFirstNamesForRegisteredCustomers() {
    $user = $this->insertRegisteredCustomerWithOrder(null, ['first_name' => '']);
    $this->woocommerce_segment->synchronizeCustomers();
    update_post_meta($user->order_id, '_billing_first_name', 'First name');
    $this->createOrder(['email' => $user->user_email, 'first_name' => 'First name (newer)']);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where('wp_user_id', $user->ID)->findOne();
    expect($subscriber->first_name)->equals('First name (newer)');
  }

  public function testItSynchronizesLastNamesForRegisteredCustomers() {
    $user = $this->insertRegisteredCustomerWithOrder(null, ['last_name' => '']);
    $this->woocommerce_segment->synchronizeCustomers();
    update_post_meta($user->order_id, '_billing_last_name', 'Last name');
    $this->createOrder(['email' => $user->user_email, 'last_name' => 'Last name (newer)']);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where('wp_user_id', $user->ID)->findOne();
    expect($subscriber->last_name)->equals('Last name (newer)');
  }

  public function testItSynchronizesFirstNamesForGuestCustomers() {
    $guest = $this->insertGuestCustomer(null, ['first_name' => '']);
    $this->woocommerce_segment->synchronizeCustomers();
    update_post_meta($guest['order_id'], '_billing_first_name', 'First name');
    $this->createOrder(['email' => $guest['email'], 'first_name' => 'First name (newer)']);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', $guest['email'])->findOne();
    expect($subscriber->first_name)->equals('First name (newer)');
  }

  public function testItSynchronizesLastNamesForGuestCustomers() {
    $guest = $this->insertGuestCustomer(null, ['last_name' => '']);
    $this->woocommerce_segment->synchronizeCustomers();
    update_post_meta($guest['order_id'], '_billing_last_name', 'Last name');
    $this->createOrder(['email' => $guest['email'], 'last_name' => 'Last name (newer)']);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', $guest['email'])->findOne();
    expect($subscriber->last_name)->equals('Last name (newer)');
  }

  public function testItSynchronizesSegment() {
    $this->insertRegisteredCustomer();
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->insertGuestCustomer();
    $this->woocommerce_segment->synchronizeCustomers();
    $subscribers = Segment::getWooCommerceSegment()->subscribers()
      ->where('is_woocommerce_user', 1)
      ->whereIn('email', $this->userEmails);
    expect($subscribers->count())->equals(4);
  }

  public function testItRemovesRegisteredCustomersFromTrash() {
    $user = $this->insertRegisteredCustomer();
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $user->user_email)
      ->where('is_woocommerce_user', 1)
      ->findOne();
    $subscriber->deleted_at = Carbon::now();
    $subscriber->save();
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $user->user_email)
      ->where('is_woocommerce_user', 1)
      ->findOne();
    expect($subscriber->deleted_at)->null();
  }

  public function testItRemovesGuestCustomersFromTrash() {
    $guest = $this->insertGuestCustomer();
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $guest['email'])
      ->where('is_woocommerce_user', 1)
      ->findOne();
    $subscriber->deleted_at = Carbon::now();
    $subscriber->save();
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $guest['email'])
      ->where('is_woocommerce_user', 1)
      ->findOne();
    expect($subscriber->deleted_at)->null();
  }

  public function testItRemovesOrphanedSubscribers() {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $user = $this->insertRegisteredCustomerWithOrder();
    $guest = $this->insertGuestCustomer();
    $this->woocommerce_segment->synchronizeCustomers();
    $user->remove_role('customer');
    $this->deleteOrder($user->order_id);
    $this->deleteOrder($guest['order_id']);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscribers = Segment::getWooCommerceSegment()->subscribers()
      ->where('is_woocommerce_user', 1)
      ->whereIn('email', $this->userEmails);
    expect($subscribers->count())->equals(2);
  }

  public function testItDoesntDeleteNonWCData() {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    // WP user
    $user = $this->insertRegisteredCustomer();
    $user->remove_role('customer');
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => $user->user_email,
      'wp_user_id' => $user->ID,
    ]);
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->save();
    // Regular subscriber
    $subscriber2 = Subscriber::create();
    $subscriber2->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test2' . rand() . '@example.com',
    ]);
    $subscriber2->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber2->save();
    // email is empty
    $subscriber3 = Subscriber::create();
    $subscriber3->hydrate([
      'first_name' => 'Dave',
      'last_name' => 'Dave',
      'email' => 'user-sync-test3' . rand() . '@example.com', // need to pass validation
    ]);
    $subscriber3->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber3->save();
    $this->clearEmail($subscriber3);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(4);
    $db_subscriber = Subscriber::findOne($subscriber3->id);
    expect($db_subscriber)->notEmpty();
    $subscriber3->delete();
  }

  public function testItUnsubscribesSubscribersWithoutWCFlagFromWCSegment() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test' . rand() . '@example.com',
      'is_woocommerce_user' => 0,
    ]);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $wc_segment = Segment::getWooCommerceSegment();
    $association = SubscriberSegment::create();
    $association->subscriber_id = $subscriber->id;
    $association->segment_id = $wc_segment->id;
    $association->save();
    expect(SubscriberSegment::findOne($association->id))->notEmpty();
    $this->woocommerce_segment->synchronizeCustomers();
    expect(SubscriberSegment::findOne($association->id))->isEmpty();
  }

  public function testItUnsubscribesSubscribersWithoutEmailFromWCSegment() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test' . rand() . '@example.com', // need to pass validation
      'is_woocommerce_user' => 1,
    ]);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $this->clearEmail($subscriber);
    $wc_segment = Segment::getWooCommerceSegment();
    $association = SubscriberSegment::create();
    $association->subscriber_id = $subscriber->id;
    $association->segment_id = $wc_segment->id;
    $association->save();
    expect(SubscriberSegment::findOne($association->id))->notEmpty();
    $this->woocommerce_segment->synchronizeCustomers();
    expect(SubscriberSegment::findOne($association->id))->isEmpty();
  }

  public function testItSetGlobalStatusUnsubscribedForUsersUnsyncedFromWooCommerceSegment() {
    $guest = $this->insertGuestCustomer();
    $subscriber = Subscriber::createOrUpdate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => $guest['email'],
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'confirmed_ip' => '123',
    ]);
    $wc_segment = Segment::getWooCommerceSegment();
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => $wc_segment->id,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber_after_update = Subscriber::where('email', $subscriber->email)->findOne();
    expect($subscriber_after_update->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesntSetGlobalStatusUnsubscribedIfUserHasMoreLists() {
    $guest = $this->insertGuestCustomer();
    $subscriber = Subscriber::createOrUpdate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => $guest['email'],
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'confirmed_ip' => '123',
    ]);
    $wc_segment = Segment::getWooCommerceSegment();
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => $wc_segment->id,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => 5,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $this->woocommerce_segment->synchronizeCustomers();
    $subscriber_after_update = Subscriber::where('email', $subscriber->email)->findOne();
    expect($subscriber_after_update->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItSubscribesSubscribersToWCListWhenSettingIsEnabled() {
    $wc_segment = Segment::getWooCommerceSegment();
    $user1 = $this->insertRegisteredCustomer();
    $user2 = $this->insertRegisteredCustomer();

    $subscriber1 = Subscriber::createOrUpdate([
      'email' => $user1->user_email,
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $association1 = SubscriberSegment::create();
    $association1->subscriber_id = $subscriber1->id;
    $association1->segment_id = $wc_segment->id;
    $association1->status = Subscriber::STATUS_UNSUBSCRIBED;
    $association1->save();

    $subscriber2 = Subscriber::createOrUpdate([
      'email' => $user2->user_email,
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
      'confirmed_ip' => '123',
    ]);
    $association2 = SubscriberSegment::create();
    $association2->subscriber_id = $subscriber2->id;
    $association2->segment_id = $wc_segment->id;
    $association2->status = Subscriber::STATUS_UNSUBSCRIBED;
    $association2->save();

    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1', 'enabled' => '1']);
    $this->woocommerce_segment->synchronizeCustomers();

    $subscriber1_after_update = Subscriber::where('email', $subscriber1->email)->findOne();
    $subscriber2_after_update = Subscriber::where('email', $subscriber2->email)->findOne();
    expect($subscriber1_after_update->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    expect($subscriber2_after_update->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);

    $association1_after_update = SubscriberSegment::findOne($association1->id);
    $association2_after_update = SubscriberSegment::findOne($association2->id);
    expect($association1_after_update->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($association2_after_update->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItUnsubscribesSubscribersFromWCListWhenSettingIsDisabled() {
    $wc_segment = Segment::getWooCommerceSegment();
    $user1 = $this->insertRegisteredCustomer();
    $user2 = $this->insertRegisteredCustomer();

    $subscriber1 = Subscriber::createOrUpdate([
      'email' => $user1->user_email,
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $association1 = SubscriberSegment::create();
    $association1->subscriber_id = $subscriber1->id;
    $association1->segment_id = $wc_segment->id;
    $association1->status = Subscriber::STATUS_SUBSCRIBED;
    $association1->save();

    $subscriber2 = Subscriber::createOrUpdate([
      'email' => $user2->user_email,
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'confirmed_ip' => '123',
    ]);
    $association2 = SubscriberSegment::create();
    $association2->subscriber_id = $subscriber2->id;
    $association2->segment_id = $wc_segment->id;
    $association2->status = Subscriber::STATUS_SUBSCRIBED;
    $association2->save();

    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1']);
    $this->woocommerce_segment->synchronizeCustomers();

    $subscriber1_after_update = Subscriber::where('email', $subscriber1->email)->findOne();
    $subscriber2_after_update = Subscriber::where('email', $subscriber2->email)->findOne();
    expect($subscriber1_after_update->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber2_after_update->status)->equals(Subscriber::STATUS_SUBSCRIBED);

    $association1_after_update = SubscriberSegment::findOne($association1->id);
    $association2_after_update = SubscriberSegment::findOne($association2->id);
    expect($association1_after_update->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    expect($association2_after_update->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function _after() {
    $this->cleanData();
    $this->removeCustomerRole();
  }

  private function addCustomerRole() {
    if (!get_role('customer')) {
      add_role('customer', 'Customer');
      $this->customerRoleAdded = true;
    }
  }

  private function removeCustomerRole() {
    if (!empty($this->customerRoleAdded)) {
      remove_role('customer');
    }
  }

  private function cleanData() {
    ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         subscriber_id IN (select id from %s WHERE email LIKE "user-sync-test%%")
    ', SubscriberSegment::$_table, Subscriber::$_table));
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         user_id IN (select id from %s WHERE user_email LIKE "user-sync-test%%")
    ', $wpdb->usermeta, $wpdb->users));
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         user_email LIKE "user-sync-test%%"
         OR user_login LIKE "user-sync-test%%"
    ', $wpdb->users));
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         email LIKE "user-sync-test%%"
    ', Subscriber::$_table));
    // delete orders
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         id IN (SELECT DISTINCT post_id FROM %s WHERE meta_value LIKE "user-sync-test%%")
    ', $wpdb->posts, $wpdb->postmeta));
    // delete order meta
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         post_id IN (
          SELECT post_id FROM (
            SELECT DISTINCT post_id FROM %s WHERE meta_value LIKE "user-sync-test%%"
          ) AS t
        )
    ', $wpdb->postmeta, $wpdb->postmeta));
  }

  private function getSubscribersCount($a = null) {
    return Subscriber::whereLike("email", "user-sync-test%")->count();
  }

  /**
   * Insert a user without invoking wp hooks.
   * Those tests are testing user synchronisation, so we need data in wp_users table which has not been synchronised to
   * mailpoet database yet. We cannot use wp_insert_user functions because they would do the sync on insert.
   *
   * @return \WP_User
   */
  private function insertRegisteredCustomer($number = null) {
    global $wpdb;
    $db = ORM::getDb();
    $number_sql = !is_null($number) ? (int)$number : 'rand()';
    // add user
    $db->exec(sprintf('
         INSERT INTO
           %s (user_login, user_email, user_registered)
           VALUES
           (
             CONCAT("user-sync-test", ' . $number_sql . '),
             CONCAT("user-sync-test", ' . $number_sql . ', "@example.com"),
             "2017-01-02 12:31:12"
           )', $wpdb->users));
    $id = $db->lastInsertId();
    // add customer role
    $user = new \WP_User($id);
    $user->add_role('customer');
    $this->userEmails[] = $user->user_email;
    return $user;
  }

  /**
   * A guest customer is whose data is only contained in an order
   */
  private function insertGuestCustomer($number = null, array $data = null) {
    $number = !is_null($number) ? (int)$number : mt_rand();
    // add order
    $guest = [
      'email' => isset($data['email']) ? $data['email'] : 'user-sync-test' . $number . '@example.com',
      'first_name' => isset($data['first_name']) ? $data['first_name'] : 'user-sync-test' . $number . ' first',
      'last_name' => isset($data['last_name']) ? $data['last_name'] : 'user-sync-test' . $number . ' last',
    ];
    $guest['order_id'] = $this->createOrder($guest);
    $this->userEmails[] = $guest['email'];
    return $guest;
  }

  private function insertRegisteredCustomerWithOrder($number = null, array $data = null) {
    $number = !is_null($number) ? (int)$number : mt_rand();
    $user = $this->insertRegisteredCustomer($number);
    $data = is_array($data) ? $data : [];
    $data['email'] = $user->user_email;
    $data['user_id'] = $user->ID;
    $user->order_id = $this->createOrder($data);
    return $user;
  }

  private function createOrder($data) {
    $order_data = [
      'post_type' => 'shop_order',
      'meta_input' => [
        '_billing_email' => isset($data['email']) ? $data['email'] : '',
        '_billing_first_name' => isset($data['first_name']) ? $data['first_name'] : '',
        '_billing_last_name' => isset($data['last_name']) ? $data['last_name'] : '',
      ],
    ];
    if (!empty($data['user_id'])) {
      $order_data['meta_input']['_customer_user'] = (int)$data['user_id'];
    }
    $id = wp_insert_post($order_data);
    return $id;
  }

  private function deleteOrder($id) {
    global $wpdb;
    $db = ORM::getDb();
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

  private function clearEmail($subscriber) {
    ORM::raw_execute('
      UPDATE ' . MP_SUBSCRIBERS_TABLE . '
      SET `email` = "" WHERE `id` = ' . $subscriber->id
    );
  }

}
