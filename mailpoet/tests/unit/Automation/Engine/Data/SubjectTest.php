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

  public function testSubscriberSubjectSqlHashExpressionUsesSubjectHashHelper(): void {
    $subscriberId = 123;
    $subject = new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriberId]);
    $serializedArgs = sprintf('a:1:{s:13:"subscriber_id";i:%d;}', $subscriberId);

    $this->assertSame(
      $subject->getHash(),
      md5(SubscriberSubject::KEY . $serializedArgs)
    );
    $this->assertSame(
      Subject::getHashSqlExpression(':subjectKey', 'serialized_args'),
      'MD5(CONCAT(:subjectKey, serialized_args))'
    );
    $this->assertSame(
      Subject::getHashSqlExpression(
        ':subjectKey',
        "CONCAT('a:1:{s:13:\"subscriber_id\";i:', subscribers.id, ';}')"
      ),
      SubscriberSubject::getHashSqlExpression('subscribers.id', ':subjectKey')
    );
  }
}
