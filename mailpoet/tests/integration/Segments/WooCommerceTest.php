<?php declare(strict_types = 1);

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\WooCommerce as WooCommerceSegment;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Carbon\Carbon;

require_once('WPTestUser.php');

/**
 * @group woo
 */
class WooCommerceTest extends \MailPoetTest {
  /** @var bool */
  public $customerRoleAdded = false;

  /** @var string[] */
  private $userEmails = [];

  /** @var WooCommerceSegment */
  private $wooCommerceSegment;

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentsRepository;

  public function _before(): void {
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscriberSegmentsRepository = $this->diContainer->get(SubscriberSegmentRepository::class);
    $this->wooCommerceSegment = $this->diContainer->get(WooCommerceSegment::class);
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->cleanData();
    $this->addCustomerRole();
  }

  public function testItSynchronizesNewRegisteredCustomer(): void {
    $firstName = 'Test First';
    $lastName = 'Test Last';
    $user = $this->insertRegisteredCustomerWithOrder(null, ['first_name' => $firstName, 'last_name' => $lastName]);
    $this->createSubscriber(
      'Mike',
      'Mike',
      $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_SUBSCRIBED,
      $user->ID,
      Source::WORDPRESS_USER
    );
    $hook = 'woocommerce_new_customer';
    $this->wooCommerceSegment->synchronizeRegisteredCustomer($user->ID, $hook);
    $subscriber = $this->findWCSubscriberByWpUserId($user->ID);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber)->notEmpty();
    expect($subscriber->getEmail())->equals($user->user_email); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect($subscriber->getFirstName())->equals($firstName);
    expect($subscriber->getLastName())->equals($lastName);
    expect($subscriber->getIsWoocommerceUser())->equals(true);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_USER);
    expect($subscriber->getDeletedAt())->equals(null);
  }

  public function testItSynchronizesUpdatedRegisteredCustomer(): void {
    $firstName = 'Test First';
    $lastName = 'Test Last';
    $user = $this->insertRegisteredCustomerWithOrder(null, ['first_name' => $firstName, 'last_name' => $lastName]);
    $this->createSubscriber(
      'Mike',
      'Mike',
      $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_UNSUBSCRIBED,
      $user->ID,
      Source::WORDPRESS_USER
    );
    $hook = 'woocommerce_update_customer';
    $this->wooCommerceSegment->synchronizeRegisteredCustomer($user->ID, $hook);
    $subscriber = $this->findWCSubscriberByWpUserId($user->ID);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber)->notEmpty();
    expect($subscriber->getEmail())->equals($user->user_email); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect($subscriber->getFirstName())->equals($firstName);
    expect($subscriber->getLastName())->equals($lastName);
    expect($subscriber->getIsWoocommerceUser())->equals(true);
    expect($subscriber->getSource())->equals(Source::WORDPRESS_USER); // no overriding
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED); // no overriding
  }

  public function testItSynchronizesDeletedRegisteredCustomer(): void {
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    $user = $this->insertRegisteredCustomer();
    $subscriber = $this->createSubscriber(
      'Mike',
      'Mike',
      $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_UNSUBSCRIBED,
      $user->ID,
      Source::WORDPRESS_USER
    );
    $association = $this->createSubscriberSegment($subscriber, $wooCommerceSegment);
    expect($this->subscriberSegmentsRepository->findOneById($association->getId()))->notEmpty();
    $hook = 'woocommerce_delete_customer';
    $this->wooCommerceSegment->synchronizeRegisteredCustomer($user->ID, $hook);
    expect($this->subscriberSegmentsRepository->findOneById($association->getId()))->notEmpty();
  }

  public function testItSynchronizesNewGuestCustomer(): void {
    $this->settings->set('signup_confirmation', ['enabled' => true]);
    $this->settings->set('woocommerce.optin_on_checkout', ['enabled' => false]);
    $guest = $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeGuestCustomer($guest['order_id']);
    $subscribers = $this->getWCSubscribersByEmails([$guest['email']]);
    expect($subscribers)->isEmpty();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $guest['email']]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getFirstName())->equals($guest['first_name']);
    expect($subscriber->getLastName())->equals($guest['last_name']);
    expect($subscriber->getIsWoocommerceUser())->equals(true);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_USER);
  }

  public function testItSynchronizesNewGuestCustomerWithDoubleOptinDisabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => false]);
    $this->settings->set('woocommerce.optin_on_checkout', ['enabled' => false]);
    $this->settings->resetCache();
    $guest = $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeGuestCustomer($guest['order_id']);
    $subscribers = $this->getWCSubscribersByEmails([$guest['email']]);
    expect($subscribers)->isEmpty();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $guest['email']]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getFirstName())->equals($guest['first_name']);
    expect($subscriber->getLastName())->equals($guest['last_name']);
    expect($subscriber->getIsWoocommerceUser())->equals(true);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_USER);
  }

  public function testItSynchronizesNewGuestCustomerWithOptinCheckoutEnabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => false]);
    $this->settings->set('woocommerce.optin_on_checkout', ['enabled' => true]);
    $this->settings->resetCache();
    $guest = $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeGuestCustomer($guest['order_id']);
    $subscribers = $this->getWCSubscribersByEmails([$guest['email']]);
    expect($subscribers)->isEmpty();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $guest['email']]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getFirstName())->equals($guest['first_name']);
    expect($subscriber->getLastName())->equals($guest['last_name']);
    expect($subscriber->getIsWoocommerceUser())->equals(true);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_USER);
  }

  public function testItSynchronizesCustomers(): void {
    $this->settings->set('signup_confirmation', ['enabled' => true]);
    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1', 'enabled' => '1']);
    $user = $this->insertRegisteredCustomer();
    $guest = $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $user->user_email]); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_USER);
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $guest['email']]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscriber->getSource())->equals(Source::WOOCOMMERCE_USER);
  }

  public function testItSynchronizesCustomersInBatches(): void {
    $user1 = $this->insertGuestCustomer();
    $user2 = $this->insertGuestCustomer();
    $user3 = $this->insertGuestCustomer();
    $orderIds = [
      $user1['order_id'],
      $user2['order_id'],
      $user3['order_id'],
    ];
    $lowestOrderId = min($orderIds);
    $highestOrderId = max($orderIds);
    // Check if empty batch run returns the highest order ID to an avoid infinite loop
    $lastOrderId = $this->wooCommerceSegment->synchronizeCustomers($lowestOrderId - 2, $highestOrderId, 1);
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(0);
    // Check regular subscriber sync
    $lastOrderId = $this->wooCommerceSegment->synchronizeCustomers($lastOrderId, $highestOrderId, 1);
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(1);
    $lastOrderId = $this->wooCommerceSegment->synchronizeCustomers($lastOrderId, $highestOrderId, 1);
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
    $this->wooCommerceSegment->synchronizeCustomers($lastOrderId, $highestOrderId, 1);
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(3);
  }

  public function testItSynchronizesNewCustomers(): void {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeCustomers();
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(4);
  }

  public function testItSynchronizesPresubscribedRegisteredCustomers(): void {
    $randomNumber = 12345;
    $subscriber = $this->createSubscriber(
      'Mike',
      'Mike',
      'user-sync-test' . $randomNumber . '@example.com',
      SubscriberEntity::STATUS_UNSUBSCRIBED,
      null
    );
    $user = $this->insertRegisteredCustomer($randomNumber);
    $this->wooCommerceSegment->synchronizeCustomers();
    $wpSubscriber = $this->subscribersRepository->findOneBy([
      'wpUserId' => $user->ID,
      'isWoocommerceUser' => true,
    ]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    expect($wpSubscriber)->notEmpty();
    expect($wpSubscriber->getId())->equals($subscriber->getId());
    expect($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItSynchronizesPresubscribedGuestCustomers(): void {
    $randomNumber = 12345;
    $subscriber = $this->createSubscriber(
      'Mike',
      'Mike',
      'user-sync-test' . $randomNumber . '@example.com',
      SubscriberEntity::STATUS_UNSUBSCRIBED,
      null
    );
    $guest = $this->insertGuestCustomer($randomNumber);
    $this->wooCommerceSegment->synchronizeCustomers();
    $wpSubscriber = $this->subscribersRepository->findOneBy([
      'email' => $guest['email'],
      'isWoocommerceUser' => true,
    ]);
    $this->assertInstanceOf(SubscriberEntity::class, $wpSubscriber);
    expect($wpSubscriber)->notEmpty();
    expect($wpSubscriber->getEmail())->equals($subscriber->getEmail());
    expect($wpSubscriber->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesNotSynchronizeEmptyEmailsForNewUsers(): void {
    $guest = $this->insertGuestCustomer();
    update_post_meta($guest['order_id'], '_billing_email', '');
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => '']);
    expect($subscriber)->isEmpty();
    $this->tester->deleteTestWooOrders();
  }

  public function testItDoesNotSynchronizeInvalidEmailsForNewUsers(): void {
    $guest = $this->insertGuestCustomer();
    $invalidEmail = 'ivalid.@email.com';
    update_post_meta($guest['order_id'], '_billing_email', $invalidEmail);
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $invalidEmail]);
    expect($subscriber)->isEmpty();
    $this->tester->deleteTestWooOrders();
  }

  public function testItSynchronizesFirstNamesForRegisteredCustomers(): void {
    $user = $this->insertRegisteredCustomerWithOrder(null, ['first_name' => '']);
    $this->wooCommerceSegment->synchronizeCustomers();
    update_post_meta($user->orderId, '_billing_first_name', 'First name');
    $this->createOrder(['email' => $user->user_email, 'first_name' => 'First name (newer)']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $user->ID]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getFirstName())->equals('First name (newer)');
  }

  public function testItSynchronizesLastNamesForRegisteredCustomers(): void {
    $user = $this->insertRegisteredCustomerWithOrder(null, ['last_name' => '']);
    $this->wooCommerceSegment->synchronizeCustomers();
    update_post_meta($user->orderId, '_billing_last_name', 'Last name');
    $this->createOrder(['email' => $user->user_email, 'last_name' => 'Last name (newer)']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $user->ID]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getLastName())->equals('Last name (newer)');
  }

  public function testItSynchronizesFirstNamesForGuestCustomers(): void {
    $guest = $this->insertGuestCustomer(null, ['first_name' => '']);
    $this->wooCommerceSegment->synchronizeCustomers();
    update_post_meta($guest['order_id'], '_billing_first_name', 'First name');
    $this->createOrder(['email' => $guest['email'], 'first_name' => 'First name (newer)']);
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $guest['email']]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getFirstName())->equals('First name (newer)');
  }

  public function testItSynchronizesLastNamesForGuestCustomers(): void {
    $guest = $this->insertGuestCustomer(null, ['last_name' => '']);
    $this->wooCommerceSegment->synchronizeCustomers();
    update_post_meta($guest['order_id'], '_billing_last_name', 'Last name');
    $this->createOrder(['email' => $guest['email'], 'last_name' => 'Last name (newer)']);
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $guest['email']]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getLastName())->equals('Last name (newer)');
  }

  public function testItSynchronizesSegment(): void {
    $this->insertRegisteredCustomer();
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscribers = $this->getWCSubscribersByEmails($this->userEmails);
    expect($subscribers)->count(4);
  }

  public function testItDoesntRemoveRegisteredCustomersFromTrash(): void {
    $user = $this->insertRegisteredCustomer();
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy([
      'email' => $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'isWoocommerceUser' => true,
    ]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriber->setDeletedAt(Carbon::now());
    $this->subscribersRepository->flush();
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy([
      'email' => $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'isWoocommerceUser' => true,
    ]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getDeletedAt())->notNull();
  }

  public function testItDoesntRemoveGuestCustomersFromTrash(): void {
    $guest = $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy([
      'email' => $guest['email'],
      'isWoocommerceUser' => true,
    ]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $subscriber->setDeletedAt(Carbon::now());
    $this->entityManager->flush();
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscriber = $this->subscribersRepository->findOneBy([
      'email' => $guest['email'],
      'isWoocommerceUser' => true,
    ]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber->getDeletedAt())->notNull();
  }

  public function testItRemovesOrphanedSubscribers(): void {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    $user = $this->insertRegisteredCustomerWithOrder();
    $guest = $this->insertGuestCustomer();
    $this->wooCommerceSegment->synchronizeCustomers();
    $user->remove_role('customer');
    $this->tester->deleteTestWooOrder((int)$user->orderId);
    $this->tester->deleteTestWooOrder((int)$guest['order_id']);
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscribers = $this->getWCSubscribersByEmails($this->userEmails);
    expect($subscribers)->count(2);
  }

  public function testItDoesntDeleteNonWCData(): void {
    $this->insertRegisteredCustomer();
    $this->insertGuestCustomer();
    // WP user
    $user = $this->insertRegisteredCustomer();
    $user->remove_role('customer');
    $this->createSubscriber(
      'John',
      'John',
      $user->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_UNCONFIRMED,
      $user->ID
    );
    // Regular subscriber
    $this->createSubscriber(
      'Mike',
      'Mike',
      'user-sync-test2' . rand() . '@example.com',
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    // email is empty
    $subscriber3 = $this->createSubscriber(
      'Dave',
      'Dave',
      'user-sync-test3' . rand() . '@example.com',
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $this->clearEmail($subscriber3);
    $this->wooCommerceSegment->synchronizeCustomers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(4);
    $this->entityManager->clear();
    $dbSubscriber = $this->subscribersRepository->findOneById($subscriber3->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $dbSubscriber);
    expect($dbSubscriber)->notEmpty();
    $this->entityManager->remove($dbSubscriber);
    $this->entityManager->flush();
  }

  public function testItUnsubscribesSubscribersWithoutWCFlagFromWCSegment(): void {
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    $subscriber = $this->createSubscriber(
      'Mike',
      'Mike',
      'user-sync-test' . rand() . '@example.com',
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $association = $this->createSubscriberSegment($subscriber, $wooCommerceSegment);
    expect($this->subscriberSegmentsRepository->findOneById($association->getId()))->notEmpty();
    $this->wooCommerceSegment->synchronizeCustomers();
    $this->entityManager->clear();
    expect($this->subscriberSegmentsRepository->findOneById($association->getId()))->isEmpty();
  }

  public function testItUnsubscribesSubscribersWithoutEmailFromWCSegment(): void {
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    $subscriber = $this->createSubscriber(
      'Mike',
      'Mike',
      'user-sync-test' . rand() . '@example.com', // need to pass validation
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $subscriber->setIsWoocommerceUser(true);
    $this->subscribersRepository->flush();
    $this->clearEmail($subscriber);
    $association = $this->createSubscriberSegment($subscriber, $wooCommerceSegment);
    expect($this->subscriberSegmentsRepository->findOneById($association->getId()))->notEmpty();
    $this->entityManager->clear();
    $this->wooCommerceSegment->synchronizeCustomers();
    expect($this->subscriberSegmentsRepository->findOneById($association->getId()))->isEmpty();
  }

  public function testItSetGlobalStatusUnsubscribedForUsersUnsyncedFromWooCommerceSegment(): void {
    $guest = $this->insertGuestCustomer();
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    $subscriber = $this->createSubscriber(
      'Mike',
      'Mike',
      $guest['email'],
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $subscriber->setIsWoocommerceUser(true);
    $subscriber->setConfirmedIp('123');
    $this->subscribersRepository->flush();
    $this->createSubscriberSegment($subscriber, $wooCommerceSegment, SubscriberEntity::STATUS_UNSUBSCRIBED);
    $this->wooCommerceSegment->synchronizeCustomers();
    $this->entityManager->clear();
    $subscriberAfterUpdate = $this->subscribersRepository->findOneBy(['email' => $subscriber->getEmail()]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberAfterUpdate);
    expect($subscriberAfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesntSetGlobalStatusUnsubscribedIfUserHasMoreLists(): void {
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    $guest = $this->insertGuestCustomer();
    $subscriber = $this->createSubscriber(
      'Mike',
      'Mike',
      $guest['email'],
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $subscriber->setIsWoocommerceUser(true);
    $subscriber->setConfirmedIp('123');
    $this->subscribersRepository->flush();
    $this->createSubscriberSegment(
      $subscriber,
      $wooCommerceSegment,
      SubscriberEntity::STATUS_UNSUBSCRIBED
    );
    $segment = $this->createSegment();
    $this->createSubscriberSegment(
      $subscriber,
      $segment
    );
    $this->wooCommerceSegment->synchronizeCustomers();
    $this->entityManager->clear();
    $subscriberAfterUpdate = $this->subscribersRepository->findOneBy(['email' => $subscriber->getEmail()]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriberAfterUpdate);
    expect($subscriberAfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItSubscribesSubscribersToWCListWhenSettingIsEnabled(): void {
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    $user1 = $this->insertRegisteredCustomer();
    $user2 = $this->insertRegisteredCustomer();

    $subscriber1 = $this->createSubscriber(
      'Mike',
      'Mike',
      $user1->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_UNSUBSCRIBED
    );
    $subscriber1->setIsWoocommerceUser(true);
    $this->subscribersRepository->flush();
    $association1 = $this->createSubscriberSegment($subscriber1, $wooCommerceSegment, SubscriberEntity::STATUS_UNSUBSCRIBED);

    $subscriber2 = $this->createSubscriber(
      'Mike',
      'Mike',
      $user2->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_UNSUBSCRIBED
    );
    $subscriber2->setIsWoocommerceUser(true);
    $subscriber2->setConfirmedIp('123');
    $this->subscribersRepository->flush();

    $association2 = $this->createSubscriberSegment($subscriber2, $wooCommerceSegment, SubscriberEntity::STATUS_UNSUBSCRIBED);

    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1', 'enabled' => '1']);
    $this->wooCommerceSegment->synchronizeCustomers();

    $this->entityManager->clear();
    $subscriber1AfterUpdate = $this->subscribersRepository->findOneBy(['email' => $subscriber1->getEmail()]);
    $subscriber2AfterUpdate = $this->subscribersRepository->findOneBy(['email' => $subscriber2->getEmail()]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1AfterUpdate);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2AfterUpdate);
    expect($subscriber1AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    expect($subscriber2AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);

    $association1AfterUpdate = $this->subscriberSegmentsRepository->findOneById($association1->getId());
    $association2AfterUpdate = $this->subscriberSegmentsRepository->findOneById($association2->getId());
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $association1AfterUpdate);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $association2AfterUpdate);
    expect($association1AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($association2AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItUnsubscribesSubscribersFromWCListWhenSettingIsDisabled(): void {
    $wcSegment = $this->segmentsRepository->getWooCommerceSegment();
    $user1 = $this->insertRegisteredCustomer();
    $user2 = $this->insertRegisteredCustomer();

    $subscriber1 = $this->createSubscriber(
      'Mike',
      'Mike',
      $user1->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $subscriber1->setIsWoocommerceUser(true);
    $this->subscribersRepository->flush();
    $association1 = $this->createSubscriberSegment($subscriber1, $wcSegment);

    $subscriber2 = $this->createSubscriber(
      'Mike',
      'Mike',
      $user2->user_email, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      SubscriberEntity::STATUS_SUBSCRIBED
    );
    $subscriber2->setIsWoocommerceUser(true);
    $subscriber2->setConfirmedIp('123');
    $this->subscribersRepository->flush();
    $association2 = $this->createSubscriberSegment($subscriber2, $wcSegment);

    $this->settings->set('mailpoet_subscribe_old_woocommerce_customers', ['dummy' => '1']);
    $this->wooCommerceSegment->synchronizeCustomers();
    $this->entityManager->clear();

    $subscriber1AfterUpdate = $this->subscribersRepository->findOneBy(['email' => $subscriber1->getEmail()]);
    $subscriber2AfterUpdate = $this->subscribersRepository->findOneBy(['email' => $subscriber2->getEmail()]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1AfterUpdate);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2AfterUpdate);
    expect($subscriber1AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscriber2AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);

    $association1AfterUpdate = $this->subscriberSegmentsRepository->findOneById($association1->getId());
    $association2AfterUpdate = $this->subscriberSegmentsRepository->findOneById($association2->getId());
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $association1AfterUpdate);
    $this->assertInstanceOf(SubscriberSegmentEntity::class, $association2AfterUpdate);
    expect($association1AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    expect($association2AfterUpdate->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function _after(): void {
    parent::_after();
    $this->cleanData();
    $this->removeCustomerRole();
  }

  private function addCustomerRole(): void {
    if (!get_role('customer')) {
      add_role('customer', 'Customer');
      $this->customerRoleAdded = true;
    }
  }

  private function removeCustomerRole(): void {
    if ($this->customerRoleAdded) {
      remove_role('customer');
    }
  }

  private function cleanData(): void {
    $this->tester->deleteTestWooOrders();
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $segmentsTable = $this->entityManager->getClassMetadata(SegmentEntity::class)->getTableName();
    $subscriberSegmentTable = $this->entityManager->getClassMetadata(SubscriberSegmentEntity::class)->getTableName();
    $connection = $this->entityManager->getConnection();
    $connection->executeQuery('TRUNCATE ' . $segmentsTable);
    $connection->executeQuery('TRUNCATE ' . $subscriberSegmentTable);
    global $wpdb;
    $connection->executeQuery("
      DELETE FROM
        {$subscriberSegmentTable}
      WHERE
        subscriber_id IN (SELECT id FROM {$subscribersTable} WHERE email LIKE 'user-sync-test%')
    ");
    $connection->executeQuery("
      DELETE FROM
        {$wpdb->usermeta}
      WHERE
        user_id IN (select id from {$wpdb->users} WHERE user_email LIKE 'user-sync-test%')
    ");
    $connection->executeQuery("
      DELETE FROM
        {$wpdb->users}
      WHERE
        user_email LIKE 'user-sync-test%'
        OR user_login LIKE 'user-sync-test%'
    ");
    $connection->executeQuery("
      DELETE FROM
        {$subscribersTable}
      WHERE
        email LIKE 'user-sync-test%' OR email = ''
    ");
  }

  private function getSubscribersCount(): int {
    $subscribersCount = $this->entityManager->createQueryBuilder()
      ->select('COUNT(s.id)')
      ->from(SubscriberEntity::class, 's')
      ->where('s.email LIKE :prefix')
      ->setParameter('prefix', 'user-sync-test%')
      ->getQuery()
      ->getSingleScalarResult();

    if (is_string($subscribersCount)) {
      return (int)$subscribersCount;
    }

    return 0;
  }

  /**
   * Insert a user without invoking wp hooks.
   * Those tests are testing user synchronisation, so we need data in wp_users table which has not been synchronised to
   * mailpoet database yet. We cannot use wp_insert_user functions because they would do the sync on insert.
   *
   * @return WPTestUser
   */
  private function insertRegisteredCustomer(?int $number = null, ?string $firstName = null, ?string $lastName = null): WPTestUser {
    global $wpdb;
    $connection = $this->entityManager->getConnection();
    $numberSql = !is_null($number) ? (int)$number : mt_rand();
    // add user
    $connection->executeQuery("
      INSERT INTO {$wpdb->users} (user_login, user_nicename, user_email, user_registered)
      VALUES (
        CONCAT('user-sync-test', :number),
        CONCAT('user-sync-test', :number),
        CONCAT('user-sync-test', :number, '@example.com'),
        '2017-01-02 12:31:12'
      )", ['number' => $numberSql]);
    $id = $connection->lastInsertId();
    if (!is_string($id)) {
      throw new \RuntimeException('Unexpected error when creating WP user.');
    }
    if ($firstName) {
      // add user first name
      $connection->executeQuery("
        INSERT INTO {$wpdb->usermeta} (user_id, meta_key, meta_value)
        VALUES (
          :id,
          'first_name',
          :firstName
        )
      ", ['id' => $id, 'firstName' => $firstName]);
    }
    if ($lastName) {
      // add user first name
      $connection->executeQuery("
        INSERT INTO {$wpdb->usermeta} (user_id, meta_key, meta_value)
        VALUES (
          :id,
          'last_name',
          :lastName
        )
      ", ['id' => $id, 'lastName' => $lastName]);
    }
    // add customer role
    $user = new WPTestUser($id);
    $user->add_role('customer');
    $this->userEmails[] = $user->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    return $user;
  }

  /**
   * A guest customer is whose data is only contained in an order
   */
  private function insertGuestCustomer(?int $number = null, ?array $data = null): array {
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

  private function insertRegisteredCustomerWithOrder(?int $number = null, array $data = null): WPTestUser {
    $number = !is_null($number) ? (int)$number : mt_rand();
    $data = is_array($data) ? $data : [];
    $user = $this->insertRegisteredCustomer($number, $data['first_name'] ?? null, $data['last_name'] ?? null);
    $data['email'] = $user->user_email; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $data['user_id'] = $user->ID;
    $user->orderId = $this->createOrder($data);
    return $user;
  }

  /**
   * @param array $data
   * @return int
   */
  private function createOrder(array $data): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_billing_email($data['email'] ?? '');
    $order->set_billing_first_name($data['first_name'] ?? '');
    $order->set_billing_last_name($data['last_name'] ?? '');
    if (!empty($data['user_id'])) {
      $order->set_customer_id((int)$data['user_id']);
    }
    $order->save();
    return $order->get_id();
  }

  private function clearEmail(SubscriberEntity $subscriber): void {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeQuery('
      UPDATE ' . $subscribersTable . '
      SET `email` = "" WHERE `id` = ' . $subscriber->getId()
    );
  }

  private function createSubscriber(
    string $firstName,
    string $lastName,
    string $email,
    string $status,
    ?int $wpUserId = null,
    ?string $source = null
  ): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setLastName($lastName);
    $subscriber->setFirstName($firstName);
    if ($wpUserId) $subscriber->setWpUserId($wpUserId);
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    if ($source) $subscriber->setSource($source);
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createSegment(): SegmentEntity {
    $segment = new SegmentEntity('Segment', SegmentEntity::TYPE_DEFAULT, '');
    $this->segmentsRepository->persist($segment);
    $this->segmentsRepository->flush();
    return $segment;
  }

  private function createSubscriberSegment(
    SubscriberEntity $subscriber,
    SegmentEntity $segment,
    string $status = SubscriberEntity::STATUS_SUBSCRIBED
  ): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, $status);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }

  private function findWCSubscriberByWpUserId(int $wpUserId): ?SubscriberEntity {
    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $wpUserId]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    foreach ($subscriber->getSegments() as $segment) {
      if ($segment->getId() === $wooCommerceSegment->getId()) {
        return $subscriber;
      }
    }
    return null;
  }

  /**
   * @param string[] $emails
   * @return array
   */
  private function getWCSubscribersByEmails(array $emails): array {
    $subscribers = $this->entityManager->createQueryBuilder()
      ->select('s')
      ->from(SubscriberEntity::class, 's')
      ->where('s.email IN (:emails)')
      ->andWhere('s.isWoocommerceUser = true')
      ->setParameter('emails', $emails)
      ->getQuery()
      ->getResult();
    $result = [];
    $wooCommerceSegment = $this->segmentsRepository->getWooCommerceSegment();
    foreach ($subscribers as $subscriber) {
      foreach ($subscriber->getSegments() as $segment) {
        if ($segment->getId() === $wooCommerceSegment->getId()) {
          $result[] = $subscriber;
        }
      }
    }
    return $result;
  }
}
