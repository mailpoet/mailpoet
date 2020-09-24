<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

require_once(ABSPATH . 'wp-admin/includes/user.php');

use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\DynamicSegments\RequirementsChecker;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;

class SubscribersCountTest extends \MailPoetTest {

  /** @var RequirementsChecker|MockObject */
  private $requirementChecker;

  public function _before() {
    $this->cleanData();
    wp_insert_user([
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
    wp_insert_user([
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

  public function testItConstructsQuery() {
    $this->requirementChecker->method('shouldSkipSegment')->willReturn(false);
    $userRole = DynamicSegment::create();
    $subscriber1 = Subscriber::findOne('user-role-test1@example.com');
    $subscriber1->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber1->save();
    $subscriber3 = Subscriber::findOne('user-role-test3@example.com');
    $subscriber3->status = Subscriber::STATUS_SUBSCRIBED;
    $subscriber3->save();
    $userRole->hydrate([
      'name' => 'segment',
      'description' => 'description',
    ]);
    $userRole->setFilters([new UserRole('editor', 'and')]);

    $loader = new SubscribersCount($this->requirementChecker);
    $count = $loader->getSubscribersCount($userRole);
    expect($count)->equals(2);
  }

  public function testItSkipsIfRequirementNotMet() {
    $this->requirementChecker->method('shouldSkipSegment')->willReturn(true);
    $userRole = DynamicSegment::create();
    $userRole->hydrate([
      'name' => 'segment',
      'description' => 'description',
    ]);
    $userRole->setFilters([new UserRole('editor', 'and')]);

    $loader = new SubscribersCount($this->requirementChecker);
    $count = $loader->getSubscribersCount($userRole);
    expect($count)->equals(0);
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
