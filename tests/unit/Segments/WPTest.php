<?php

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use Carbon\Carbon;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Segments\WP;

class WPTest extends \MailPoetTest  {

  private $userIds = array();

  function testItSynchronizeUsers() {
    $this->insertUser();
    $this->insertUser();
    WP::synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
  }

  function testItSynchronizeNewUsers() {
    $this->insertUser();
    $this->insertUser();
    WP::synchronizeUsers();
    $this->insertUser();
    WP::synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(3);
  }

  function testItSynchronizesPresubscribedUsers() {
    $random_number = 12345;
    $subscriber = Subscriber::createOrUpdate(array(
      'email' => 'user-sync-test' . $random_number . '@example.com',
      'status' => Subscriber::STATUS_SUBSCRIBED
    ));
    $id = $this->insertUser($random_number);
    WP::synchronizeUsers();
    $wp_subscriber = Segment::getWPSegment()->subscribers()->where('wp_user_id', $id)->findOne();
    expect($wp_subscriber)->notEmpty();
    expect($wp_subscriber->id)->equals($subscriber->id);
  }

  function testItSynchronizeEmails() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $this->updateWPUserEmail($id, 'user-sync-test-xx@email.com');
    WP::synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->email)->equals('user-sync-test-xx@email.com');
  }

  function testRemovesUsersWithEmptyEmailsFromSunscribersDuringSynchronization() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $this->updateWPUserEmail($id, '');
    WP::synchronizeUsers();
    expect(Subscriber::where('wp_user_id', $id)->count())->equals(0);
    $this->deleteWPUser($id);
  }

  function testRemovesUsersWithInvalidEmailsFromSunscribersDuringSynchronization() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $this->updateWPUserEmail($id, 'ivalid.@email.com');
    WP::synchronizeUsers();
    expect(Subscriber::where('wp_user_id', $id)->count())->equals(0);
    $this->deleteWPUser($id);
  }

  function testItDoesNotSynchronizeEmptyEmailsForNewUsers() {
    $id = $this->insertUser();
    $this->updateWPUserEmail($id, '');
    WP::synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber)->isEmpty();
    $this->deleteWPUser($id);
  }

  function testItSynchronizeFirstNames() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    update_user_meta($id, 'first_name', 'First name');
    WP::synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->first_name)->equals('First name');
  }

  function testItSynchronizeLastNames() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    update_user_meta($id, 'last_name', 'Last name');
    WP::synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->last_name)->equals('Last name');
  }

  function testItSynchronizeFirstNamesUsingDisplayName() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $this->updateWPUserDisplayName($id, 'First name');
    WP::synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->first_name)->equals('First name');
  }

  function testItSynchronizeFirstNamesFromMetaNotDisplayName() {
    $id = $this->insertUser();
    update_user_meta($id, 'first_name', 'First name');
    $this->updateWPUserDisplayName($id, 'display_name');
    WP::synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->first_name)->equals('First name');
  }

  function testItSynchronizeSegment() {
    $this->insertUser();
    $this->insertUser();
    $this->insertUser();
    WP::synchronizeUsers();
    $subscribers = Segment::getWPSegment()->subscribers()->whereIn('wp_user_id', $this->userIds);
    expect($subscribers->count())->equals(3);
  }

  function testItRemovesUsersFromTrash() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    $subscriber->deleted_at = Carbon::now();
    $subscriber->save();
    WP::synchronizeUsers();
    $subscriber = Subscriber::where("wp_user_id", $id)->findOne();
    expect($subscriber->deleted_at)->null();
  }

  function testItSynchronizesDeletedWPUsersUsingHooks() {
    $id = $this->insertUser();
    $this->insertUser();
    WP::synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
    wp_delete_user($id);
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(1);
  }

  function testItRemovesOrphanedSubscribers() {
    $this->insertUser();
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $this->deleteWPUser($id);
    WP::synchronizeUsers();
    $subscribers = Segment::getWPSegment()->subscribers()->whereIn('wp_user_id', $this->userIds);
    expect($subscribers->count())->equals(1);
  }

  function testItDoesntDeleteNonWPData() {
    $this->insertUser();
    // wp_user_id is null
    $subscriber = Subscriber::create();
    $subscriber->hydrate(array(
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'user-sync-test' . rand() . '@example.com',
    ));
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->save();
    // wp_user_id is zero
    $subscriber2 = Subscriber::create();
    $subscriber2->hydrate(array(
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test2' . rand() . '@example.com',
      'wp_user_id' => 0,
    ));
    $subscriber2->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber2->save();
    // email is empty
    $subscriber3 = Subscriber::create();
    $subscriber3->hydrate(array(
      'first_name' => 'Dave',
      'last_name' => 'Dave',
      'email' => 'user-sync-test3' . rand() . '@example.com', // need to pass validation
    ));
    $subscriber3->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber3->save();
    $this->clearEmail($subscriber3);
    WP::synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(3);
    $db_subscriber = Subscriber::findOne($subscriber3->id);
    expect($db_subscriber)->notEmpty();
    $subscriber3->delete();
  }

  function testItRemovesSubscribersInWPSegmentWithoutWPId() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(array(
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test' . rand() . '@example.com',
      'wp_user_id' => null,
    ));
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $wp_segment = Segment::getWPSegment();
    $association = SubscriberSegment::create();
    $association->subscriber_id = $subscriber->id;
    $association->segment_id = $wp_segment->id;
    $association->save();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(1);
    WP::synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount(0);
    expect($subscribersCount)->equals(0);
  }

  function testItRemovesSubscribersInWPSegmentWithoutEmail() {
    $id = $this->insertUser();
    $this->updateWPUserEmail($id, '');
    $subscriber = Subscriber::create();
    $subscriber->hydrate(array(
      'first_name' => 'Mike',
      'last_name' => 'Mike',
      'email' => 'user-sync-test' . rand() . '@example.com', // need to pass validation
      'wp_user_id' => $id,
    ));
    $subscriber->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber->save();
    $this->clearEmail($subscriber);
    $wp_segment = Segment::getWPSegment();
    $association = SubscriberSegment::create();
    $association->subscriber_id = $subscriber->id;
    $association->segment_id = $wp_segment->id;
    $association->save();
    $db_subscriber = Subscriber::findOne($subscriber->id);
    expect($db_subscriber)->notEmpty();
    WP::synchronizeUsers();
    $db_subscriber = Subscriber::findOne($subscriber->id);
    expect($db_subscriber)->isEmpty();
  }

  function _before() {
    $this->cleanData();
  }

  function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    \ORM::raw_execute('TRUNCATE ' . SubscriberSegment::$_table);
    global $wpdb;
    $db = \ORM::getDb();
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
    $db = \ORM::getDb();
    $number_sql = !is_null($number) ? (int)$number : 'rand()';
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
    $this->userIds[] = $id;
    return $id;
  }

  private function updateWPUserEmail($id, $email) {
    global $wpdb;
    $db = \ORM::getDb();
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
    $db = \ORM::getDb();
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
    $db = \ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         id = %s
    ', $wpdb->users, $id));
  }

  private function clearEmail($subscriber) {
    \ORM::raw_execute('
      UPDATE ' . MP_SUBSCRIBERS_TABLE . '
      SET `email` = "" WHERE `id` = ' . $subscriber->id
    );
  }

}