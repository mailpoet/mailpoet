<?php declare(strict_types = 1);

namespace unit\EmailEditor\Integrations\MailPoet\Coupons;

use MailPoet\EmailEditor\Integrations\MailPoet\Coupons\CouponBlockGenerationFailureCollector;

class CouponBlockGenerationFailureCollectorTest extends \MailPoetUnitTest {
  public function testItRecordsFailuresInOrder(): void {
    $collector = new CouponBlockGenerationFailureCollector();

    $collector->record('first', 'First failure.', ['amount' => '101'], ['newsletter_id' => 1]);
    $collector->record('second', 'Second failure.', ['amount' => '10'], ['newsletter_id' => 2]);

    verify($collector->hasFailures())->true();
    verify($collector->getFailures())->equals([
      [
        'code' => 'first',
        'message' => 'First failure.',
        'attrs' => ['amount' => '101'],
        'context' => ['newsletter_id' => 1],
      ],
      [
        'code' => 'second',
        'message' => 'Second failure.',
        'attrs' => ['amount' => '10'],
        'context' => ['newsletter_id' => 2],
      ],
    ]);
  }

  public function testItClearsFailures(): void {
    $collector = new CouponBlockGenerationFailureCollector();

    $collector->record('failure', 'Failure.', [], []);
    $collector->clear();

    verify($collector->hasFailures())->false();
    verify($collector->getFailures())->equals([]);
  }
}
