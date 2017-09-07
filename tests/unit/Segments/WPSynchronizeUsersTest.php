<?php

namespace MailPoet\Test\Segments;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use Carbon\Carbon;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Segments\WP;

class WPSynchronizeUsersTest extends \MailPoetTest  {

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
    $this->updateWPUserEmail($id,'user-sync-test-xx@email.com');
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

  function testItRemovesOrphanedSubscribers() {
    $this->insertUser();
    $id = $this->insertUser();
    WP::synchronizeUsers();
    $this->deleteWPUser($id);
    WP::synchronizeUsers();
    $subscribers = Segment::getWPSegment()->subscribers()->whereIn('wp_user_id', $this->userIds);
    expect($subscribers->count())->equals(1);
  }

  function _before() {
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
       DELETE FROM
         %susers       
       WHERE
         user_email LIKE "user-sync-test%%"
    ', $wpdb->prefix));
  }

  private function getSubscribersCount() {
    return Subscriber::whereIn("wp_user_id", $this->userIds)->count();
  }

  private function insertUser() {
    global $wpdb;
    $db = \ORM::getDb();
    $db->exec(sprintf('
         INSERT INTO 
           %susers(user_login, user_email) 
           VALUES 
           (CONCAT("user-sync-test", rand()), CONCAT("user", rand(), "@example.com"))', $wpdb->prefix));
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