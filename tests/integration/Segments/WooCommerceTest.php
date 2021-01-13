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

require_once('WPUserWithExtraProps.php');

class WooCommerceTest extends \MailPoetTest {
  public $customerRoleAdded;

  private $userEmails = [];

  /** @var WooCommerce */
  private $woocommerceSegment;

  /** @var SettingsController */
  private $settings;

  public function _before() {
    $this->woocommerceSegment = ContainerWrapper::getInstance()->get(WooCommerceSegment::class);
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
      'email' => $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'wp_user_id' => $user->ID,
    ]);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->source = Source::WORDPRESS_USER;
    $subscriber->save();
    $subscriber->trash();
    $hook = 'woocommerce_new_customer';
    $this->woocommerceSegment->synchronizeRegisteredCustomer($user->ID, $hook);
    $subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('wp_user_id', $user->ID)
      ->findOne();
    expect($subscriber)->notEmpty();
    expect($subscriber->email)->equals($user->user_email); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($subscriber->isWoocommerceUser)->equals(1);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
    expect($subscriber->deletedAt)->equals(null);
  }

  public function testItSynchronizesUpdatedRegisteredCustomer() {
    $user = $this->insertRegisteredCustomer();
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'wp_user_id' => $user->ID,
    ]);
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->source = Source::WORDPRESS_USER;
    $subscriber->save();
    $hook = 'woocommerce_update_customer';
    $this->woocommerceSegment->synchronizeRegisteredCustomer($user->ID, $hook);
    $subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('wp_user_id', $user->ID)
      ->findOne();
    expect($subscriber)->notEmpty();
    expect($subscriber->email)->equals($user->user_email); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($subscriber->isWoocommerceUser)->equals(1);
    expect($subscriber->source)->equals(Source::WORDPRESS_USER); // no overriding
    expect($subscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED); // no overriding
  }

  public function testItSynchronizesDeletedRegisteredCustomer() {
    $wcSegment = Segment::getWooCommerceSegment();
    $user = $this->insertRegisteredCustomer();
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'wp_user_id' => $user->ID,
    ]);
    $subscriber->status = Subscriber::STATUS_UNSUBSCRIBED;
    $subscriber->source = Source::WORDPRESS_USER;
    $subscriber->save();
    $association = SubscriberSegment::create();
    $association->subscriberId = $subscriber->id;
    $association->segmentId = $wcSegment->id;
    $association->save();
    expect(SubscriberSegment::findOne($association->id))->notEmpty();
    $hook = 'woocommerce_delete_customer';
    $this->woocommerceSegment->synchronizeRegisteredCustomer($user->ID, $hook);
    expect(SubscriberSegment::findOne($association->id))->isEmpty();
  }

  public function testItSynchronizesNewGuestCustomer() {
    $this->settings->set('signup_confirmation', ['enabled' => true]);
    $guest = $this->insertGuestCustomer();
    $hook = 'woocommerce_checkout_update_order_meta';
    $this->woocommerceSegment->synchronizeGuestCustomer($guest['order_id'], $hook);
    $subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('email', $guest['email'])
      ->findOne();
    expect($subscriber)->notEmpty();
    expect($subscriber->isWoocommerceUser)->equals(1);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItSynchronizesNewGuestCustomerWithDoubleOptinDisabled() {
    $this->settings->set('signup_confirmation', ['enabled' => false]);
    $this->settings->resetCache();
    $guest = $this->insertGuestCustomer();
    $hook = 'woocommerce_checkout_update_order_meta';
    $this->woocommerceSegment->synchronizeGuestCustomer($guest['order_id'], $hook);
    $subscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('email', $guest['email'])
      ->findOne();
    expect($subscriber)->notEmpty();
    expect($subscriber->isWoocommerceUser)->equals(1);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItSynchronizesCustomers() {
    $this->settings->set('signup_confirmation', ['enabled' => true]);
    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1', 'enabled' => '1']);
    $user = $this->insertRegisteredCustomer();
    $guest = $this->insertGuestCustomer();
    $this->woocommerceSegment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);

    $subscriber = Subscriber::where('email', $user->user_email)->findOne(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect($subscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
    $subscriber = Subscriber::where('email', $guest['email'])->findOne();
    expect($subscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber->source)->equals(Source::WOOCOMMERCE_USER);
  }

  public function testItSynchronizesNewCustomers() {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->woocommerceSegment->synchronizeCustomers();
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->woocommerceSegment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(4);
  }

  public function testItSynchronizesPresubscribedRegisteredCustomers() {
    $randomNumber = 12345;
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $user = $this->insertRegisteredCustomer($randomNumber);
    $this->woocommerceSegment->synchronizeCustomers();
    $wpSubscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('is_woocommerce_user', 1)
      ->where('wp_user_id', $user->ID)
      ->findOne();
    expect($wpSubscriber)->notEmpty();
    expect($wpSubscriber->id)->equals($subscriber->id);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItSynchronizesPresubscribedGuestCustomers() {
    $randomNumber = 12345;
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $guest = $this->insertGuestCustomer($randomNumber);
    $this->woocommerceSegment->synchronizeCustomers();
    $wpSubscriber = Segment::getWooCommerceSegment()->subscribers()
      ->where('is_woocommerce_user', 1)
      ->where('email', $guest['email'])
      ->findOne();
    expect($wpSubscriber)->notEmpty();
    expect($wpSubscriber->email)->equals($subscriber->email);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesNotSynchronizeEmptyEmailsForNewUsers() {
    $guest = $this->insertGuestCustomer();
    update_post_meta($guest['order_id'], '_billing_email', '');
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', '')->findOne();
    expect($subscriber)->isEmpty();
    $this->deleteOrder($guest['order_id']);
  }

  public function testItDoesNotSynchronizeInvalidEmailsForNewUsers() {
    $guest = $this->insertGuestCustomer();
    $invalidEmail = 'ivalid.@email.com';
    update_post_meta($guest['order_id'], '_billing_email', $invalidEmail);
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', $invalidEmail)->findOne();
    expect($subscriber)->isEmpty();
    $this->deleteOrder($guest['order_id']);
  }

  public function testItSynchronizesFirstNamesForRegisteredCustomers() {
    $user = $this->insertRegisteredCustomerWithOrder(null, ['first_name' => '']);
    $this->woocommerceSegment->synchronizeCustomers();
    update_post_meta($user->orderId, '_billing_first_name', 'First name');
    $this->createOrder(['email' => $user->user_email, 'first_name' => 'First name (newer)']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where('wp_user_id', $user->ID)->findOne();
    expect($subscriber->firstName)->equals('First name (newer)');
  }

  public function testItSynchronizesLastNamesForRegisteredCustomers() {
    $user = $this->insertRegisteredCustomerWithOrder(null, ['last_name' => '']);
    $this->woocommerceSegment->synchronizeCustomers();
    update_post_meta($user->orderId, '_billing_last_name', 'Last name');
    $this->createOrder(['email' => $user->user_email, 'last_name' => 'Last name (newer)']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where('wp_user_id', $user->ID)->findOne();
    expect($subscriber->lastName)->equals('Last name (newer)');
  }

  public function testItSynchronizesFirstNamesForGuestCustomers() {
    $guest = $this->insertGuestCustomer(null, ['first_name' => '']);
    $this->woocommerceSegment->synchronizeCustomers();
    update_post_meta($guest['order_id'], '_billing_first_name', 'First name');
    $this->createOrder(['email' => $guest['email'], 'first_name' => 'First name (newer)']);
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', $guest['email'])->findOne();
    expect($subscriber->firstName)->equals('First name (newer)');
  }

  public function testItSynchronizesLastNamesForGuestCustomers() {
    $guest = $this->insertGuestCustomer(null, ['last_name' => '']);
    $this->woocommerceSegment->synchronizeCustomers();
    update_post_meta($guest['order_id'], '_billing_last_name', 'Last name');
    $this->createOrder(['email' => $guest['email'], 'last_name' => 'Last name (newer)']);
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where('email', $guest['email'])->findOne();
    expect($subscriber->lastName)->equals('Last name (newer)');
  }

  public function testItSynchronizesSegment() {
    $this->insertRegisteredCustomer();
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->insertGuestCustomer();
    $this->woocommerceSegment->synchronizeCustomers();
    $subscribers = Segment::getWooCommerceSegment()->subscribers()
      ->where('is_woocommerce_user', 1)
      ->whereIn('email', $this->userEmails);
    expect($subscribers->count())->equals(4);
  }

  public function testItDoesntRemoveRegisteredCustomersFromTrash() {
    $user = $this->insertRegisteredCustomer();
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $user->user_email) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ->where('is_woocommerce_user', 1)
      ->findOne();
    $subscriber->deletedAt = Carbon::now();
    $subscriber->save();
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $user->user_email) // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ->where('is_woocommerce_user', 1)
      ->findOne();
    expect($subscriber->deletedAt)->notNull();
  }

  public function testItDoesntRemoveGuestCustomersFromTrash() {
    $guest = $this->insertGuestCustomer();
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $guest['email'])
      ->where('is_woocommerce_user', 1)
      ->findOne();
    $subscriber->deletedAt = Carbon::now();
    $subscriber->save();
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriber = Subscriber::where("email", $guest['email'])
      ->where('is_woocommerce_user', 1)
      ->findOne();
    expect($subscriber->deletedAt)->notNull();
  }

  public function testItRemovesOrphanedSubscribers() {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $user = $this->insertRegisteredCustomerWithOrder();
    $guest = $this->insertGuestCustomer();
    $this->woocommerceSegment->synchronizeCustomers();
    $user->remove_role('customer');
    $this->deleteOrder($user->orderId);
    $this->deleteOrder($guest['order_id']);
    $this->woocommerceSegment->synchronizeCustomers();
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
      'email' => $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
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
    $this->woocommerceSegment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(4);
    $dbSubscriber = Subscriber::findOne($subscriber3->id);
    expect($dbSubscriber)->notEmpty();
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
    $wcSegment = Segment::getWooCommerceSegment();
    $association = SubscriberSegment::create();
    $association->subscriberId = $subscriber->id;
    $association->segmentId = $wcSegment->id;
    $association->save();
    expect(SubscriberSegment::findOne($association->id))->notEmpty();
    $this->woocommerceSegment->synchronizeCustomers();
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
    $wcSegment = Segment::getWooCommerceSegment();
    $association = SubscriberSegment::create();
    $association->subscriberId = $subscriber->id;
    $association->segmentId = $wcSegment->id;
    $association->save();
    expect(SubscriberSegment::findOne($association->id))->notEmpty();
    $this->woocommerceSegment->synchronizeCustomers();
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
    $wcSegment = Segment::getWooCommerceSegment();
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => $wcSegment->id,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriberAfterUpdate = Subscriber::where('email', $subscriber->email)->findOne();
    expect($subscriberAfterUpdate->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
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
    $wcSegment = Segment::getWooCommerceSegment();
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => $wcSegment->id,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    SubscriberSegment::createOrUpdate([
      'subscriber_id' => $subscriber->id,
      'segment_id' => 5,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $this->woocommerceSegment->synchronizeCustomers();
    $subscriberAfterUpdate = Subscriber::where('email', $subscriber->email)->findOne();
    expect($subscriberAfterUpdate->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItSubscribesSubscribersToWCListWhenSettingIsEnabled() {
    $wcSegment = Segment::getWooCommerceSegment();
    $user1 = $this->insertRegisteredCustomer();
    $user2 = $this->insertRegisteredCustomer();

    $subscriber1 = Subscriber::createOrUpdate([
      'email' => $user1->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
    ]);
    $association1 = SubscriberSegment::create();
    $association1->subscriberId = $subscriber1->id;
    $association1->segmentId = $wcSegment->id;
    $association1->status = Subscriber::STATUS_UNSUBSCRIBED;
    $association1->save();

    $subscriber2 = Subscriber::createOrUpdate([
      'email' => $user2->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_UNSUBSCRIBED,
      'confirmed_ip' => '123',
    ]);
    $association2 = SubscriberSegment::create();
    $association2->subscriberId = $subscriber2->id;
    $association2->segmentId = $wcSegment->id;
    $association2->status = Subscriber::STATUS_UNSUBSCRIBED;
    $association2->save();

    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1', 'enabled' => '1']);
    $this->woocommerceSegment->synchronizeCustomers();

    $subscriber1AfterUpdate = Subscriber::where('email', $subscriber1->email)->findOne();
    $subscriber2AfterUpdate = Subscriber::where('email', $subscriber2->email)->findOne();
    expect($subscriber1AfterUpdate->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    expect($subscriber2AfterUpdate->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);

    $association1AfterUpdate = SubscriberSegment::findOne($association1->id);
    $association2AfterUpdate = SubscriberSegment::findOne($association2->id);
    assert($association1AfterUpdate instanceof SubscriberSegment);
    assert($association2AfterUpdate instanceof SubscriberSegment);
    expect($association1AfterUpdate->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($association2AfterUpdate->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
  }

  public function testItUnsubscribesSubscribersFromWCListWhenSettingIsDisabled() {
    $wcSegment = Segment::getWooCommerceSegment();
    $user1 = $this->insertRegisteredCustomer();
    $user2 = $this->insertRegisteredCustomer();

    $subscriber1 = Subscriber::createOrUpdate([
      'email' => $user1->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $association1 = SubscriberSegment::create();
    $association1->subscriberId = $subscriber1->id;
    $association1->segmentId = $wcSegment->id;
    $association1->status = Subscriber::STATUS_SUBSCRIBED;
    $association1->save();

    $subscriber2 = Subscriber::createOrUpdate([
      'email' => $user2->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      'is_woocommerce_user' => 1,
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'confirmed_ip' => '123',
    ]);
    $association2 = SubscriberSegment::create();
    $association2->subscriberId = $subscriber2->id;
    $association2->segmentId = $wcSegment->id;
    $association2->status = Subscriber::STATUS_SUBSCRIBED;
    $association2->save();

    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1']);
    $this->woocommerceSegment->synchronizeCustomers();

    $subscriber1AfterUpdate = Subscriber::where('email', $subscriber1->email)->findOne();
    $subscriber2AfterUpdate = Subscriber::where('email', $subscriber2->email)->findOne();
    expect($subscriber1AfterUpdate->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscriber2AfterUpdate->status)->equals(Subscriber::STATUS_SUBSCRIBED);

    $association1AfterUpdate = SubscriberSegment::findOne($association1->id);
    $association2AfterUpdate = SubscriberSegment::findOne($association2->id);
    assert($association1AfterUpdate instanceof SubscriberSegment);
    assert($association2AfterUpdate instanceof SubscriberSegment);
    expect($association1AfterUpdate->status)->equals(Subscriber::STATUS_UNSUBSCRIBED);
    expect($association2AfterUpdate->status)->equals(Subscriber::STATUS_SUBSCRIBED);
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
   * @return WPUserWithExtraProps
   */
  private function insertRegisteredCustomer($number = null) {
    global $wpdb;
    $db = ORM::getDb();
    $numberSql = !is_null($number) ? (int)$number : 'rand()';
    // add user
    $db->exec(sprintf('
         INSERT INTO
           %s (user_login, user_email, user_registered)
           VALUES
           (
             CONCAT("user-sync-test", ' . $numberSql . '),
             CONCAT("user-sync-test", ' . $numberSql . ', "@example.com"),
             "2017-01-02 12:31:12"
           )', $wpdb->users));
    $id = $db->lastInsertId();
    if (!is_string($id)) {
      throw new \RuntimeException('Unexpected error when creating WP user.');
    }
    // add customer role
    $user = new WPUserWithExtraProps($id);
    $user->add_role('customer');
    $this->userEmails[] = $user->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
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
    $data['email'] = $user->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $data['user_id'] = $user->ID;
    $user->orderId = $this->createOrder($data);
    return $user;
  }

  private function createOrder($data) {
    $orderData = [
      'post_type' => 'shop_order',
      'meta_input' => [
        '_billing_email' => isset($data['email']) ? $data['email'] : '',
        '_billing_first_name' => isset($data['first_name']) ? $data['first_name'] : '',
        '_billing_last_name' => isset($data['last_name']) ? $data['last_name'] : '',
      ],
    ];
    if (!empty($data['user_id'])) {
      $orderData['meta_input']['_customer_user'] = (int)$data['user_id'];
    }
    $id = wp_insert_post($orderData);
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
