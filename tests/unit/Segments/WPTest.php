<?php
namespace MailPoet\Test\Segments;

use MailPoet\Models\Segment;
use MailPoet\Segments\WP;

require_once(ABSPATH . 'wp-admin/includes/user.php');

class WPTest extends \MailPoetTest {
  function _before() {
    $this->wp_user_1 = $this->createWPUser('phoenix_test_user');
    $this->wp_user_2 = $this->createWPUser('phoenix_test_user2');
    $this->wp_segment = Segment::getWPSegment();
  }

  function testItSynchronizesDeletedWPUsersUsingHooks() {
    expect($this->getWPSegmentSubscribers()->count())->equals(2);
    wp_delete_user($this->wp_user_1->ID);
    expect($this->getWPSegmentSubscribers()->count())->equals(1);
  }

  function testItForciblySynchronizesDeletedWPUsers() {
    global $wpdb;
    expect($this->getWPSegmentSubscribers()->count())->equals(2);
    // Remove a WP user directly from the database
    \ORM::for_table($wpdb->prefix . 'users')
      ->where('id', $this->wp_user_2->ID)
      ->deleteMany();
    WP::synchronizeUsers();
    expect($this->getWPSegmentSubscribers()->count())->equals(1);
  }

  private function getWPSegmentSubscribers() {
    return $this->wp_segment->subscribers()
      ->whereIn(
        'wp_user_id',
        array(
          $this->wp_user_1->ID,
          $this->wp_user_2->ID
        )
      );
  }

  private function createWPUser($login) {
    $WP_user = wp_create_user($login, 'pass', $login . '@example.com');
    $WP_user = get_user_by('login', $login);
    return $WP_user;
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Segment::$_table);
    wp_delete_user($this->wp_user_1->ID);
    wp_delete_user($this->wp_user_2->ID);
  }
}
