<?php declare(strict_types = 1);

namespace MailPoet\Services\Bridge;

class ReviewStatusTest extends \MailPoetUnitTest {
  /**
   * @dataProvider validReviews
   */
  public function testItReadsValidReviews(array $review, string $status, ?string $reason, ?string $since): void {
    $result = ReviewStatus::fromArray($review);
    $this->assertInstanceOf(ReviewStatus::class, $result);
    $this->assertSame($status, $result->getStatus());
    $this->assertSame($reason, $result->getReason());
    $this->assertSame($since, $result->getSince() ? $result->getSince()->format('Y-m-d H:i:s') : null);
    // What is stored reads back the same.
    $this->assertEquals($result, ReviewStatus::fromArray($result->toArray()));
  }

  public static function validReviews(): array {
    return [
      'fix with reason' => [
        ['status' => 'fix', 'reason' => 'http_error', 'since' => '2026-11-20T10:00:00.000Z'],
        'fix',
        'http_error',
        '2026-11-20 10:00:00',
      ],
      'reviewing without a date' => [
        ['status' => 'reviewing', 'reason' => null, 'since' => null],
        'reviewing',
        null,
        null,
      ],
      'reason only kept with fix' => [
        ['status' => 'replied', 'reason' => 'http_error', 'since' => '2026-11-21T08:30:00Z'],
        'replied',
        null,
        '2026-11-21 08:30:00',
      ],
    ];
  }

  /**
   * @dataProvider invalidReviews
   * @param mixed $review
   */
  public function testItIgnoresAnswersItCannotShow($review): void {
    $this->assertNull(ReviewStatus::fromArray($review));
  }

  public static function invalidReviews(): array {
    return [
      'no review' => [null],
      'unknown status' => [['status' => 'rejected', 'reason' => null, 'since' => '2026-11-20T10:00:00.000Z']],
      'fix with unknown reason' => [['status' => 'fix', 'reason' => 'spam', 'since' => '2026-11-20T10:00:00.000Z']],
      'question without a date' => [['status' => 'question', 'reason' => null, 'since' => 'not a date']],
    ];
  }
}
