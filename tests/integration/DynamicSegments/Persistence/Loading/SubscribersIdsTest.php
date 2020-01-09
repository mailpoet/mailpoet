<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\DynamicSegments\RequirementsChecker;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Subscriber;

class SubscribersIdsTest extends \MailPoetTest {

  private $editors_wp_ids = [];

  /** @var RequirementsChecker|\PHPUnit_Framework_MockObject_MockObject */
  private $requirement_checker;

  public function _before() {
    $this->cleanData();
    $this->editorsWpIds[] = wp_insert_user([
      'user_login' => 'user-role-test1',
      'user_email' => 'user-role-test1@example.com',
      'role' => 'editor',
      'user_pass' => '12123154',
    ]);
    wp_insert_user([
      'user_login' => 'user-role-test2',
      'user_email' => 'user-role-test2@example.com',
      'role' => 'administrator',
      'user_pass' => '12123154',
    ]);
    $this->editorsWpIds[] = wp_insert_user([
      'user_login' => 'user-role-test3',
      'user_email' => 'user-role-test3@example.com',
      'role' => 'editor',
      'user_pass' => '12123154',
    ]);
    $this->requirementChecker = $this
      ->getMockBuilder(RequirementsChecker::class)
      ->setMethods(['shouldSkipSegment'])
      ->getMock();
  }

  public function testItConstructsSubscribersIdQueryForAnyDynamicSegment() {
    $this->requirementChecker->method('shouldSkipSegment')->willReturn(false);
    $userRole = DynamicSegment::create();
    $userRole->hydrate([
      'name' => 'segment',
      'description' => 'description',
    ]);
    $userRole->setFilters([new UserRole('editor', 'and')]);
    $loader = new SubscribersIds($this->requirementChecker);
    $result = $loader->load($userRole);
    $wpIds = [
      Subscriber::findOne($result[0]->id)->wp_user_id,
      Subscriber::findOne($result[1]->id)->wp_user_id,
    ];
    $this->assertEquals($wpIds, $this->editorsWpIds, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = true);
  }

  public function testItSkipsConstructingSubscribersIdQueryForAnyDynamicSegmentIfRequirementsNotMet() {
    $this->requirementChecker->method('shouldSkipSegment')->willReturn(true);
    $userRole = DynamicSegment::create();
    $userRole->hydrate([
      'name' => 'segment',
      'description' => 'description',
    ]);
    $userRole->setFilters([new UserRole('editor', 'and')]);
    $loader = new SubscribersIds($this->requirementChecker);
    $result = $loader->load($userRole);
    expect($result)->isEmpty();
  }

  public function _after() {
    $this->cleanData();
  }

  private function cleanData() {
    $emails = ['user-role-test1@example.com', 'user-role-test2@example.com', 'user-role-test3@example.com'];
    foreach ($emails as $email) {
      $user = get_user_by('email', $email);
      if (!$user) {
        continue;
      }

      if (is_multisite()) {
        wpmu_delete_user($user->ID);
      } else {
        wp_delete_user($user->ID);
      }
    }
  }
}
