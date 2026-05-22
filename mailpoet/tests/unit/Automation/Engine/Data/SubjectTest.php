<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Engine\Data;

use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;

class SubjectTest extends \MailPoetUnitTest {
  public function testSubscriberSubjectHashMatchesManualStartSqlFormat(): void {
    $subscriberId = 123;
    $subject = new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriberId]);

    $this->assertSame(
      md5(SubscriberSubject::KEY . 'a:1:{s:13:"subscriber_id";i:123;}'),
      $subject->getHash()
    );
  }
}
