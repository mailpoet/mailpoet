<?php declare(strict_types = 1);

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use Codeception\Stub;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Subscription\Registration;
use MailPoet\WooCommerce\Helper;
use MailPoet\WooCommerce\Subscription;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WPTest extends \MailPoetTest {
  /** @var array<int> */
  private $userIds = [];

  /** @var SettingsController */
  private $settings;

  /** @var WP */
  private $wpSegment;

  public function _before(): void {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->wpSegment = $this->diContainer->get(WP::class);
    $currentTime = Carbon::now();
    Carbon::setTestNow($currentTime);
    $this->cleanData();
  }

  public function testSynchronizeUserKeepsStatusOfOldUser(): void {
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'wp_user_id' => $id,
    ]);
    $this->wpSegment->synchronizeUser($id);
    $dbSubscriber = Subscriber::findOne($subscriber->id);
    expect($dbSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUserKeepsStatusOfOldSubscriber(): void {
    $randomNumber = rand();
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'wp_user_id' => null,
    ]);
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $dbSubscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($dbSubscriber->status)->equals($subscriber->status);
  }

  public function testSynchronizeUserStatusIsSubscribedForNewUserWithSignUpConfirmationDisabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '0']);
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUserStatusIsUnconfirmedForNewUserWithSignUpConfirmationEnabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '1']);
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testSynchronizeUsersStatusIsSubscribedForNewUsersWithSignUpConfirmationDisabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '0']);
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = Subscriber::whereLike("email", "user-sync-test%")->findMany();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscribers[1]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUsersStatusIsUnconfirmedForNewUsersWithSignUpConfirmationEnabled(): void {
    $this->settings->set('signup_confirmation', ['enabled' => '1']);
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = Subscriber::whereLike("email", "user-sync-test%")->findMany();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscribers[1]->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItSendsConfirmationEmailWhenSignupConfirmationAndSubscribeOnRegisterEnabled(): void {
    $registration = $this->diContainer->get(Registration::class);
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);

    // signup confirmation enabled, subscribe on-register enabled, checkbox in form is checked
    $_POST = ['mailpoet' => ['subscribe_on_register_active' => '1', 'subscribe_on_register' => '1']];
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $user = $this->getUser((int)$id);
    $registration->onRegister([], $user['user_login'], $user['user_email']);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(1);
    expect($wpSubscriber->status)->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    unset($_POST['mailpoet']);

    // signup confirmation enabled, subscribe on-register enabled, checkbox in form is unchecked
    $_POST = ['mailpoet' => ['subscribe_on_register_active' => '1']];
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $user = $this->getUser((int)$id);
    $registration->onRegister([], $user['user_login'], $user['user_email']);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(0);
    expect($wpSubscriber->status)->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
    unset($_POST['mailpoet']);

    // signup confirmation disabled, subscribe on-register enabled
    $this->settings->set('signup_confirmation.enabled', '0');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $user = $this->getUser((int)$id);
    $registration->onRegister([], $user['user_login'], $user['user_email']);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(0);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);

    // signup confirmation enabled, subscribe on-register disabled
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '0');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(0);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItSynchronizeNewUsers(): void {
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(3);
  }

  public function testItSynchronizesPresubscribedUsers(): void {
    $randomNumber = 12345;
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUsers();
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber)->notEmpty();
    expect($wpSubscriber->id)->equals($subscriber->id);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testItSynchronizeEmails(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, 'user-sync-test-xx@email.com');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->email)->equals('user-sync-test-xx@email.com');
  }

  public function testRemovesUsersWithEmptyEmailsFromSunscribersDuringSynchronization(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, '');
    $this->wpSegment->synchronizeUsers();
    expect(Subscriber::where('wp_user_id', $id)->count())->equals(0);
    $this->deleteWPUser($id);
  }

  public function testRemovesUsersWithInvalidEmailsFromSunscribersDuringSynchronization(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, 'ivalid.@email.com');
    $this->wpSegment->synchronizeUsers();
    expect(Subscriber::where('wp_user_id', $id)->count())->equals(0);
    $this->deleteWPUser($id);
  }

  public function testItDoesNotSynchronizeEmptyEmailsForNewUsers(): void {
    $id = $this->insertUser();
    $this->updateWPUserEmail($id, '');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber)->isEmpty();
    $this->deleteWPUser($id);
  }

  public function testItSynchronizeFirstNames(): void {
    $firstName = 'Very long name over 255 characters lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum';
    $trucantedFirstName = substr($firstName, 0, 255);

    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    update_user_meta((int)$id, 'first_name', $firstName);
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->firstName)->equals($trucantedFirstName);
  }

  public function testItSynchronizeLastNames(): void {
    $lastName = 'Very long name over 255 characters lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum';
    $trucantedLastName = substr($lastName, 0, 255);

    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    update_user_meta((int)$id, 'last_name', $lastName);
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->lastName)->equals($trucantedLastName);
  }

  public function testItSynchronizeFirstNamesUsingDisplayName(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserDisplayName($id, 'First name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->firstName)->equals('First name');
  }

  public function testItSynchronizeFirstNamesFromMetaNotDisplayName(): void {
    $id = $this->insertUser();
    update_user_meta((int)$id, 'first_name', 'First name');
    $this->updateWPUserDisplayName($id, 'display_name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->firstName)->equals('First name');
  }

  public function testItSynchronizeSegment(): void {
    $this->insertUser();
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = Segment::getWPSegment()->subscribers()->whereIn('wp_user_id', $this->userIds);
    expect($subscribers->count())->equals(3);
  }

  public function testItDoesntRemoveUsersFromTrash(): void {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    $subscriber->deletedAt = Carbon::now();
    $subscriber->save();
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber->deletedAt)->notNull();
  }

  public function testItSynchronizesDeletedWPUsersUsingHooks(): void {
    $id = $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
    wp_delete_user((int)$id);
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(1);
  }

  public function testItSynchronizesNewUsersToDisabledWPSegmentAsUnconfirmedAndTrashed(): void {
    $this->disableWpSegment();
    $this->settings->set('signup_confirmation.enabled', '1');
    $id = $this->insertUser();
    $id2 = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
    $subscriber1 = Subscriber::where("wp_user_id", $id)->findOne();
    $deletedAt1 = Carbon::createFromFormat('Y-m-d H:i:s', $subscriber1->deletedAt);
    $this->assertInstanceOf(Carbon::class, $deletedAt1);
    expect($subscriber1->status)->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($deletedAt1->timestamp)->equals(Carbon::now()->timestamp, 1);
    $subscriber2 = Subscriber::where("wp_user_id", $id2)->findOne();
    $deletedAt2 = Carbon::createFromFormat('Y-m-d H:i:s', $subscriber2->deletedAt);
    $this->assertInstanceOf(Carbon::class, $deletedAt2);
    expect($subscriber2->status)->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($deletedAt2->timestamp)->equals(Carbon::now()->timestamp, 1);
  }

  public function testItRemovesOrphanedSubscribers(): void {
    $this->insertUser();
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->deleteWPUser($id);
    $this->wpSegment->synchronizeUsers();
    $subscribers = Segment::getWPSegment()->subscribers()->whereIn('wp_user_id', $this->userIds);
    expect($subscribers->count())->equals(1);
  }

  public function testItDoesntDeleteNonWPData(): void {
    $this->insertUser();
    // wp_user_id is null
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'user-sync-test' . rand() . '@example.com',
    ]);
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->save();
    // wp_user_id is zero
    $subscriber2 = Subscriber::create();
    $subscriber2->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test2' . rand() . '@example.com',
      'wp_user_id' => 0,
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
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(3);
    $dbSubscriber = Subscriber::findOne($subscriber3->id);
    expect($dbSubscriber)->notEmpty();
    $subscriber3->delete();
  }

  public function testItRemovesSubscribersInWPSegmentWithoutWPId(): void {
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test' . rand() . '@example.com',
      'wp_user_id' => null,
    ]);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $wpSegment = Segment::getWPSegment();
    $association = SubscriberSegment::create();
    $association->subscriberId = $subscriber->id;
    $association->segmentId = $wpSegment->id;
    $association->save();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(1);
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(0);
  }

  public function testItRemovesSubscribersInWPSegmentWithoutEmail(): void {
    $id = $this->insertUser();
    $this->updateWPUserEmail($id, '');
    $subscriber = Subscriber::create();
    $subscriber->hydrate([
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test' . rand() . '@example.com', // need to pass validation
      'wp_user_id' => $id,
    ]);
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $this->clearEmail($subscriber);
    $wpSegment = Segment::getWPSegment();
    $association = SubscriberSegment::create();
    $association->subscriberId = $subscriber->id;
    $association->segmentId = $wpSegment->id;
    $association->save();
    $dbSubscriber = Subscriber::findOne($subscriber->id);
    expect($dbSubscriber)->notEmpty();
    $this->wpSegment->synchronizeUsers();
    $dbSubscriber = Subscriber::findOne($subscriber->id);
    expect($dbSubscriber)->isEmpty();
  }

  public function testItAddsNewUserToDisabledWpSegmentAsUnconfirmedAndTrashed(): void {
    $this->disableWpSegment();
    $id = $this->insertUser();
    $wp = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $wpSegment->synchronizeUser($id);
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    $deletedAt = Carbon::createFromFormat('Y-m-d H:i:s', $subscriber->deletedAt);
    $this->assertInstanceOf(Carbon::class, $deletedAt);
    expect($subscriber->status)->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($deletedAt->timestamp)->equals(Carbon::now()->timestamp, 1);
  }

  public function testItAddsNewUserWhoUncheckedOptInOnCheckoutPageAsUnsubscribed(): void {
    $id = $this->insertUser();
    $wp = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $_POST[Subscription::CHECKOUT_OPTIN_PRESENCE_CHECK_INPUT_NAME] = 1;
    $wpSegment->synchronizeUser($id);
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber->status)->equals(SubscriberEntity::STATUS_UNSUBSCRIBED);
  }

  public function testItDoesNotSendConfirmationEmailForNewUserWhenWPSegmentIsDisabledOnRegisterEnabled(): void {
    $this->disableWpSegment();
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);

    // signup confirmation enabled, subscribe on-register enabled
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $id = $this->insertUser();
    $wp = $worker = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(0);
  }

  public function testItDecodesHtmlEntitesInFirstAndLastName(): void {
    $args = [
      'user_login' => 'html-entities',
      'user_email' => 'user-sync-test-html-entities@example.com',
      'first_name' => 'Family & friends',
      'last_name' => 'Family & friends lastname',
      'role' => 'subscriber',
      'user_pass' => 'password',
    ];
    $userId = wp_insert_user($args);
    $this->assertIsNumeric($userId);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'user-sync-test-html-entities@example.com']);
    /**
     * @var SubscriberEntity $subscriber
     */
    $firstName = $subscriber->getFirstName();
    $this->assertEquals($args['first_name'], $subscriber->getFirstName());
    $this->assertEquals($args['last_name'], $subscriber->getLastName());
    wp_delete_user($userId);
  }

  public function testItDecodesHtmlEntitesInDisplayName(): void {
    $args = [
      'user_login' => 'entities-display-name',
      'user_email' => 'user-sync-test-html-entities-display-name@example.com',
      'first_name' => '',
      'last_name' => '',
      'display_name' => 'Family & Frieds',
      'role' => 'subscriber',
      'user_pass' => 'password',
    ];

    $userId = wp_insert_user($args);
    $this->assertIsNumeric($userId);
    $subscriberRepository = $this->diContainer->get(SubscribersRepository::class);
    $subscriber = $subscriberRepository->findOneBy(['email' => 'user-sync-test-html-entities-display-name@example.com']);
    /**
     * @var SubscriberEntity $subscriber
     */
    $this->assertEquals($args['display_name'], $subscriber->getFirstName());
    wp_delete_user($userId);
  }

  public function testItDoesNotTrashNewUsersWhoHaveSomeSegmentsToDisabledWPSegment(): void {
    $this->disableWpSegment();
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'wp_user_id' => null,
    ]);
    $segment = Segment::createOrUpdate(['name' => 'Test Segment', 'description' => '']);
    $subscriberSegment = SubscriberSegment::create();
    $subscriberSegment->subscriberId = $subscriber->id;
    $subscriberSegment->segmentId = $segment->id;
    $subscriberSegment->save();

    $wp = $worker = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, ['wp' => $wp]);
    $wpSegment->synchronizeUser($id);
    $subscriber1 = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber1->status)->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscriber1->deletedAt)->null();
  }

  public function testItDoesNotTrashNewUsersWhoIsWooCustomerToDisabledWPSegment(): void {
    $this->disableWpSegment();
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    add_role('customer', 'customer', []);
    $wpUser = get_user_by('id', $id);
    $this->assertInstanceOf(\WP_User::class, $wpUser);
    $wpUser->add_role('customer');

    $wp = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wooHelper = Stub::make(
      $this->diContainer->get(Helper::class),
      [
        'isWooCommerceActive' => true,
      ],
      $this
    );
    $wpSegment = $this->getServiceWithOverrides(WP::class, [
      'wp' => $wp,
      'wooHelper' => $wooHelper,
    ]);
    $wpSegment->synchronizeUser($id);
    $subscriber1 = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber1->status)->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($subscriber1->deletedAt)->null();
    remove_role('customer');
  }

  public function _after(): void {
    parent::_after();
    $this->cleanData();
    Carbon::setTestNow();
  }

  private function cleanData(): void {
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
  }

  private function getSubscribersCount(): int {
    return Subscriber::whereLike("email", "user-sync-test%")->count();
  }

  /**
   * Insert a user without invoking wp hooks.
   * Those tests are testing user synchronisation, so we need data in wp_users table which has not been synchronised to
   * mailpoet database yet. We cannot use wp_insert_user functions because they would do the sync on insert.
   *
   * @param int|null $number
   * @return int
   */
  private function insertUser(?int $number = null): int {
    global $wpdb;
    $db = ORM::getDb();
    $numberSql = !is_null($number) ? (int)$number : 'rand()';
    $db->exec(sprintf('
         INSERT INTO
           %s (user_login, user_nicename, user_email, user_registered)
           VALUES
           (
             CONCAT("user-sync-test", ' . $numberSql . '),
             CONCAT("user-sync-test", ' . $numberSql . '),
             CONCAT("user-sync-test", ' . $numberSql . ', "@example.com"),
             "2017-01-02 12:31:12"
           )', $wpdb->users));
    $id = $db->lastInsertId();
    if (!is_string($id)) {
      throw new \RuntimeException('Unexpected error when creating WP user.');
    }
    $this->userIds[] = (int)$id;
    return (int)$id;
  }

  private function getUser(int $id): array {
    global $wpdb;
    $user = $this->entityManager->getConnection()->executeQuery('
      SELECT user_login, user_email, user_registered
      FROM ' . $wpdb->users . '
      WHERE id = :id
    ', ['id' => $id])->fetchAssociative();
    $this->assertIsArray($user);
    return $user;
  }

  private function updateWPUserEmail(int $id, string $email): void {
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       UPDATE
         %s
       SET user_email = "%s"
       WHERE
         id = %s
    ', $wpdb->users, $email, $id));
  }

  private function updateWPUserDisplayName(int $id, string $name): void {
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       UPDATE
         %s
       SET display_name = "%s"
       WHERE
         id = %s
    ', $wpdb->users, $name, $id));
  }

  private function deleteWPUser(int $id): void {
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         id = %s
    ', $wpdb->users, $id));
  }

  private function clearEmail(Subscriber $subscriber): void {
    ORM::raw_execute('
      UPDATE ' . MP_SUBSCRIBERS_TABLE . '
      SET `email` = "" WHERE `id` = ' . $subscriber->id
    );
  }

  private function disableWpSegment(): void {
    $segment = Segment::getWPSegment();
    $segment->deletedAt = Carbon::now();
    $segment->save();
  }
}
