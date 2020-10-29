<?php

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use Codeception\Stub;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Idiorm\ORM;

class WPTest extends \MailPoetTest {
  private $userIds = [];

  /** @var SettingsController */
  private $settings;

  /** @var WP */
  private $wpSegment;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->wpSegment = $this->diContainer->get(WP::class);
    $this->cleanData();
  }

  public function testSynchronizeUserKeepsStatusOfOldUser() {
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

  public function testSynchronizeUserKeepsStatusOfOldSubscriber() {
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

  public function testSynchronizeUserStatusIsSubscribedForNewUserWithSignUpConfirmationDisabled() {
    $this->settings->set('signup_confirmation', ['enabled' => '0']);
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUserStatusIsUnconfirmedForNewUserWithSignUpConfirmationEnabled() {
    $this->settings->set('signup_confirmation', ['enabled' => '1']);
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testSynchronizeUsersStatusIsSubscribedForNewUsersWithSignUpConfirmationDisabled() {
    $this->settings->set('signup_confirmation', ['enabled' => '0']);
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = Subscriber::whereLike("email", "user-sync-test%")->findMany();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
    expect($subscribers[1]->status)->equals(Subscriber::STATUS_SUBSCRIBED);
  }

  public function testSynchronizeUsersStatusIsUnconfirmedForNewUsersWithSignUpConfirmationEnabled() {
    $this->settings->set('signup_confirmation', ['enabled' => '1']);
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = Subscriber::whereLike("email", "user-sync-test%")->findMany();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]->status)->equals(Subscriber::STATUS_UNCONFIRMED);
    expect($subscribers[1]->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItSendsConfirmationEmailWhenSignupConfirmationAndSubscribeOnRegisterEnabled() {
    $this->settings->set('sender', [
      'address' => 'sender@mailpoet.com',
      'name' => 'Sender',
    ]);

    // signup confirmation enabled, subscribe on-register enabled
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(1);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);

    // signup confirmation disabled, subscribe on-register enabled
    $this->settings->set('signup_confirmation.enabled', '0');
    $this->settings->set('subscribe.on_register.enabled', '1');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(0);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_SUBSCRIBED);

    // signup confirmation enaled, subscribe on-register disabled
    $this->settings->set('signup_confirmation.enabled', '1');
    $this->settings->set('subscribe.on_register.enabled', '0');
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $this->wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(0);
    expect($wpSubscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItSynchronizeNewUsers() {
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(3);
  }

  public function testItSynchronizesPresubscribedUsers() {
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

  public function testItSynchronizeEmails() {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, 'user-sync-test-xx@email.com');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->email)->equals('user-sync-test-xx@email.com');
  }

  public function testRemovesUsersWithEmptyEmailsFromSunscribersDuringSynchronization() {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, '');
    $this->wpSegment->synchronizeUsers();
    expect(Subscriber::where('wp_user_id', $id)->count())->equals(0);
    $this->deleteWPUser($id);
  }

  public function testRemovesUsersWithInvalidEmailsFromSunscribersDuringSynchronization() {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserEmail($id, 'ivalid.@email.com');
    $this->wpSegment->synchronizeUsers();
    expect(Subscriber::where('wp_user_id', $id)->count())->equals(0);
    $this->deleteWPUser($id);
  }

  public function testItDoesNotSynchronizeEmptyEmailsForNewUsers() {
    $id = $this->insertUser();
    $this->updateWPUserEmail($id, '');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber)->isEmpty();
    $this->deleteWPUser($id);
  }

  public function testItSynchronizeFirstNames() {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    update_user_meta((int)$id, 'first_name', 'First name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->firstName)->equals('First name');
  }

  public function testItSynchronizeLastNames() {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    update_user_meta((int)$id, 'last_name', 'Last name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->lastName)->equals('Last name');
  }

  public function testItSynchronizeFirstNamesUsingDisplayName() {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->updateWPUserDisplayName($id, 'First name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->firstName)->equals('First name');
  }

  public function testItSynchronizeFirstNamesFromMetaNotDisplayName() {
    $id = $this->insertUser();
    update_user_meta((int)$id, 'first_name', 'First name');
    $this->updateWPUserDisplayName($id, 'display_name');
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->firstName)->equals('First name');
  }

  public function testItSynchronizeSegment() {
    $this->insertUser();
    $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribers = Segment::getWPSegment()->subscribers()->whereIn('wp_user_id', $this->userIds);
    expect($subscribers->count())->equals(3);
  }

  public function testItDoesntRemoveUsersFromTrash() {
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    $subscriber->deletedAt = Carbon::now();
    $subscriber->save();
    $this->wpSegment->synchronizeUsers();
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber->deletedAt)->notNull();
  }

  public function testItSynchronizesDeletedWPUsersUsingHooks() {
    $id = $this->insertUser();
    $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
    wp_delete_user((int)$id);
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(1);
  }

  public function testItRemovesOrphanedSubscribers() {
    $this->insertUser();
    $id = $this->insertUser();
    $this->wpSegment->synchronizeUsers();
    $this->deleteWPUser($id);
    $this->wpSegment->synchronizeUsers();
    $subscribers = Segment::getWPSegment()->subscribers()->whereIn('wp_user_id', $this->userIds);
    expect($subscribers->count())->equals(1);
  }

  public function testItDoesntDeleteNonWPData() {
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

  public function testItRemovesSubscribersInWPSegmentWithoutWPId() {
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
    $subscribersCount = $this->getSubscribersCount(0);
    expect($subscribersCount)->equals(0);
  }

  public function testItRemovesSubscribersInWPSegmentWithoutEmail() {
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

  public function testItMarksSpammySubscribersAsUnconfirmed() {
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'wp_user_id' => $id,
    ]);
    update_user_meta((int)$id, 'default_password_nag', '1');
    $this->wpSegment->synchronizeUsers();
    $dbSubscriber = Subscriber::findOne($subscriber->id);
    expect($dbSubscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItMarksSpammySubscribersWithUserStatus2AsUnconfirmed() {
    global $wpdb;
    $columnExists = $wpdb->query(sprintf('SHOW COLUMNS FROM `%s` LIKE "user_status"', $wpdb->users));
    if (!$columnExists) {
      // This column is deprecated in WP, is no longer used by the core
      // and either may not be present, or may be removed in the future.
      return false;
    }
    $randomNumber = rand();
    $id = $this->insertUser($randomNumber);
    $subscriber = Subscriber::createOrUpdate([
      'email' => 'user-sync-test' . $randomNumber . '@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED,
      'wp_user_id' => $id,
    ]);
    wp_update_user(['ID' => $id, 'user_status' => 2]);
    $db = ORM::getDb();
    $db->exec(sprintf('UPDATE %s SET `user_status` = 2 WHERE ID = %s', $wpdb->users, $id));
    $this->wpSegment->synchronizeUsers();
    $dbSubscriber = Subscriber::findOne($subscriber->id);
    expect($dbSubscriber->status)->equals(Subscriber::STATUS_UNCONFIRMED);
  }

  public function testItAddsNewUserToDisabledWpSegmentAsUnconfirmedAndTrashed() {
    $segment = Segment::getWPSegment();
    $segment->deletedAt = Carbon::now();
    $segment->save();
    $id = $this->insertUser();
    $wp = $worker = Stub::make(
      $this->diContainer->get(Functions::class),
      [
        'currentFilter' => 'user_register',
      ],
      $this
    );
    $wpSegment = new WP($wp, $this->diContainer->get(WelcomeScheduler::class));
    $wpSegment->synchronizeUser($id);
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber->status)->equals(SubscriberEntity::STATUS_UNCONFIRMED);
    expect($subscriber->deletedAt)->equals(Carbon::now(), 1);
  }

  public function testItDoesNotSendConfirmationEmailForNewUserWhenWPSegmentIsDisabledOnRegisterEnabled() {
    $segment = Segment::getWPSegment();
    $segment->deletedAt = Carbon::now();
    $segment->save();
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
    $wpSegment = new WP($wp, $this->diContainer->get(WelcomeScheduler::class));
    $wpSegment->synchronizeUser($id);
    $wpSubscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wpSubscriber->countConfirmations)->equals(0);
  }

  public function testItDoesNotTrashNewUsersWhoHaveSomeSegmentsToDisabledWPSegment() {
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
    $wpSegment = new WP($wp, $this->diContainer->get(WelcomeScheduler::class));
    $wpSegment->synchronizeUser($id);
    $subscriber1 = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber1->status)->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    expect($subscriber1->deletedAt)->null();
  }

  public function _after() {
    $this->cleanData();
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
  }

  private function getSubscribersCount($a = null) {
    return Subscriber::whereLike("email", "user-sync-test%")->count();
  }

  /**
   * Insert a user without invoking wp hooks.
   * Those tests are testing user synchronisation, so we need data in wp_users table which has not been synchronised to
   * mailpoet database yet. We cannot use wp_insert_user functions because they would do the sync on insert.
   *
   * @return string
   */
  private function insertUser($number = null) {
    global $wpdb;
    $db = ORM::getDb();
    $numberSql = !is_null($number) ? (int)$number : 'rand()';
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
    $this->userIds[] = $id;
    return $id;
  }

  private function updateWPUserEmail($id, $email) {
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

  private function updateWPUserDisplayName($id, $name) {
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

  private function deleteWPUser($id) {
    global $wpdb;
    $db = ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         id = %s
    ', $wpdb->users, $id));
  }

  private function clearEmail($subscriber) {
    ORM::raw_execute('
      UPDATE ' . MP_SUBSCRIBERS_TABLE . '
      SET `email` = "" WHERE `id` = ' . $subscriber->id
    );
  }
}
