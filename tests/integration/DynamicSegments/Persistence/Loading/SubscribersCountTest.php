<?php

namespace MailPoet\DynamicSegments\Persistence\Loading;

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
    $this->tester->createWordPressUser('user-role-test1@example.com', 'editor');
    $this->tester->createWordPressUser('user-role-test2@example.com', 'administrator');
    $this->tester->createWordPressUser('user-role-test3@example.com', 'editor');
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
      $this->tester->deleteWordPressUser($email);
    }
  }
}
