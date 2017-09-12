<?php

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use Carbon\Carbon;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Segments\WP;
use Codeception\Util\Fixtures;

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

  function testItSynchronizeEmails() {
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $this->updateWPUserEmail($id, 'user-sync-test-xx@email.com');
    WP::synchronizeUsers();
    $subscriber = Subscriber::where('wp_user_id', $id)->findOne();
    expect($subscriber->email)->equals('user-sync-test-xx@email.com');
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
    $subscriber = Subscriber::create();
    $subscriber->hydrate(array(
      'first_name' => 'John',
      'last_name' => 'John',
      'email' => 'user-sync-test' . rand() . '@example.com',
    ));
    $subscriber->status = Subscriber::STATUS_UNCONFIRMED;
    $subscriber->save();
    WP::synchronizeUsers();
    $subscribersCount = $this->getSubscribersCount();
    expect($subscribersCount)->equals(2);
  }

  function _before() {
    $this->cleanData();
  }

  function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %susers
       WHERE
         user_email LIKE "user-sync-test%%"
    ', $wpdb->prefix));
    $db->exec(sprintf('
       DELETE FROM
         %s
       WHERE
         email LIKE "user-sync-test%%"
    ', Subscriber::$_table));
  }

  private function getSubscribersCount() {
    return Subscriber::whereLike("email", "user-sync-test%")->count();
  }

  /**
   * Insert a user without invoking wp hooks.
   * Those tests are testing user synchronisation, so we need data in wp_users table which has not been synchronised to
   * mailpoet database yet. We cannot use wp_insert_user functions because they would do the sync on insert.
   *
   * @return string
   */
  private function insertUser() {
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
         INSERT INTO 
           %susers(user_login, user_email, user_registered) 
           VALUES 
           (
             CONCAT("user-sync-test", rand()), 
             CONCAT("user-sync-test", rand(), "@example.com"),
             "2017-01-02 12:31:12"
           )', $wpdb->prefix));
    $id = $db->lastInsertId();
    $this->userIds[] = $id;
    return $id;
  }

  private function updateWPUserEmail($id, $email) {
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
       UPDATE
         %susers
       SET user_email = "%s"
       WHERE
         id = %s
    ', $wpdb->prefix, $email, $id));
  }

  private function updateWPUserDisplayName($id, $name) {
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
       UPDATE
         %susers
       SET display_name = "%s"
       WHERE
         id = %s
    ', $wpdb->prefix, $name, $id));
  }

  private function deleteWPUser($id) {
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %susers       
       WHERE
         id = %s
    ', $wpdb->prefix, $id));
  }

}