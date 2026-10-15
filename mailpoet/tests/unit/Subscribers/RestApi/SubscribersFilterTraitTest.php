<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscribers\RestApi;

use MailPoet\API\REST\ApiException;
use MailPoet\API\REST\ListingRequestValidationTrait;
use MailPoet\Subscribers\RestApi\SubscribersFilterTrait;

class SubscribersFilterTraitTest extends \MailPoetUnitTest {
  /** @var \Closure(mixed): void */
  private $check;

  public function _before() {
    parent::_before();
    $harness = new class {
      use ListingRequestValidationTrait;
      use SubscribersFilterTrait;

      private const MAX_PER_PAGE = 100;
      private const MAX_PAGE = 100000;

      protected function getListingValidationErrorPrefix(): string {
        return 'subscribers';
      }

      /** @param mixed $filters */
      public function check($filters): void {
        $this->validateSubscribersFilter($filters);
      }
    };
    $this->check = \Closure::fromCallable([$harness, 'check']);
  }

  /**
   * @dataProvider validFilterProvider
   * @param mixed $filters
   */
  public function testItAcceptsValidFilters($filters): void {
    ($this->check)($filters);
    $this->addToAssertionCount(1);
  }

  /** @return array<string, array{0: mixed}> */
  public function validFilterProvider(): array {
    return [
      'null' => [null],
      'empty array' => [[]],
      'empty values are absent' => [[
        'segment' => '', 'tag' => '', 'segment_in' => [], 'tag_not_in' => '', 'status_in' => null,
        'score_gt' => '', 'created_from' => '', 'subscribed_to' => null, 'statusInclude' => '',
        'engagementScoreExclude' => [], 'createdAtFrom' => '', 'source_in' => [],
      ]],
      'legacy segment id' => [['segment' => '12']],
      'legacy segment int' => [['segment' => 12]],
      'legacy segment without list' => [['segment' => 'without-list']],
      'legacy tag' => [['tag' => '3']],
      'segment sets' => [['segment_in' => ['1', 2, 'without-list'], 'segment_not_in' => ['5']]],
      'tag sets' => [['tag_in' => ['1', '2'], 'tag_not_in' => [3]]],
      'status sets' => [['status_in' => ['subscribed', 'unconfirmed', 'unsubscribed', 'inactive', 'bounced'], 'status_not_in' => ['bounced']]],
      'source sets' => [['source_in' => ['form', 'imported', 'administrator', 'api', 'wordpress_user', 'wordpress_user_deleted', 'woocommerce_user', 'woocommerce_checkout', 'unknown'], 'source_not_in' => ['form']]],
      'scores' => [['score_gt' => '10', 'score_lt' => 90, 'score_min' => '0', 'score_max' => '50.5']],
      'negative score' => [['score_gt' => '-1']],
      'equal score min and max' => [['score_min' => '5', 'score_max' => '5']],
      'dates' => [['created_from' => '2026-01-01', 'created_to' => '2026-01-01', 'subscribed_from' => '2026-02-01', 'subscribed_to' => '2026-03-01']],
      'legacy status' => [['statusInclude' => 'subscribed', 'statusExclude' => ['bounced', 'inactive']]],
      'legacy engagement score' => [['engagementScoreInclude' => 'good', 'engagementScoreExclude' => ['unknown', 'dormant', 'low', 'excellent']]],
      'legacy created at' => [['createdAtFrom' => '2026-01-01 10:00:00', 'createdAtTo' => '2026-12-31']],
      'exactly 100 entries' => [['tag_in' => range(1, 100)]],
    ];
  }

  /**
   * @dataProvider invalidFilterProvider
   * @param mixed $filters
   */
  public function testItRejectsInvalidFilters($filters, string $code): void {
    try {
      ($this->check)($filters);
      $this->fail('Expected an ApiException.');
    } catch (ApiException $e) {
      verify($e->getStatusCode())->same(400);
      verify($e->getErrorCode())->same($code);
    }
  }

  /** @return array<string, array{0: mixed, 1: string}> */
  public function invalidFilterProvider(): array {
    return [
      'non-array' => ['foo', 'mailpoet_subscribers_invalid_filter'],
      'unknown key' => [['foo' => 'bar'], 'mailpoet_subscribers_invalid_filter'],
      'minUpdatedAt is not allowed' => [['minUpdatedAt' => '2026-01-01'], 'mailpoet_subscribers_invalid_filter'],
      'unknown key next to valid one' => [['tag' => '1', 'bogus' => '1'], 'mailpoet_subscribers_invalid_filter'],
      'segment not numeric' => [['segment' => 'abc'], 'mailpoet_subscribers_invalid_segment'],
      'segment zero' => [['segment' => '0'], 'mailpoet_subscribers_invalid_segment'],
      'segment array' => [['segment' => [1]], 'mailpoet_subscribers_invalid_segment'],
      'tag not numeric' => [['tag' => 'without-list'], 'mailpoet_subscribers_invalid_tag'],
      'tag negative' => [['tag' => '-1'], 'mailpoet_subscribers_invalid_tag'],
      'segment_in string' => [['segment_in' => '1,2'], 'mailpoet_subscribers_invalid_segment_in'],
      'segment_in bad value' => [['segment_in' => ['1', 'x']], 'mailpoet_subscribers_invalid_segment_in'],
      'segment_not_in zero' => [['segment_not_in' => [0]], 'mailpoet_subscribers_invalid_segment_not_in'],
      'tag_in without-list' => [['tag_in' => ['without-list']], 'mailpoet_subscribers_invalid_tag_in'],
      'tag_not_in nested' => [['tag_not_in' => [[1]]], 'mailpoet_subscribers_invalid_tag_not_in'],
      'status_in unknown' => [['status_in' => ['subscribed', 'nope']], 'mailpoet_subscribers_invalid_status_in'],
      'status_not_in string' => [['status_not_in' => 'bounced'], 'mailpoet_subscribers_invalid_status_not_in'],
      'source_in unknown' => [['source_in' => ['nope']], 'mailpoet_subscribers_invalid_source_in'],
      'source_not_in string' => [['source_not_in' => 'form'], 'mailpoet_subscribers_invalid_source_not_in'],
      '101 entries' => [['tag_in' => range(1, 101)], 'mailpoet_subscribers_invalid_tag_in'],
      '101 statuses' => [['status_in' => array_fill(0, 101, 'subscribed')], 'mailpoet_subscribers_invalid_status_in'],
      'score_gt not numeric' => [['score_gt' => 'abc'], 'mailpoet_subscribers_invalid_score_gt'],
      'score_lt array' => [['score_lt' => [1]], 'mailpoet_subscribers_invalid_score_lt'],
      'score_min not numeric' => [['score_min' => 'x'], 'mailpoet_subscribers_invalid_score_min'],
      'score_max not numeric' => [['score_max' => 'x'], 'mailpoet_subscribers_invalid_score_max'],
      'score_min above score_max' => [['score_min' => '60', 'score_max' => '10'], 'mailpoet_subscribers_invalid_score_range'],
      'created_from bad format' => [['created_from' => '01/02/2026'], 'mailpoet_subscribers_invalid_created_from'],
      'created_to with time' => [['created_to' => '2026-01-01 10:00:00'], 'mailpoet_subscribers_invalid_created_to'],
      'subscribed_from impossible date' => [['subscribed_from' => '2026-02-30'], 'mailpoet_subscribers_invalid_subscribed_from'],
      'subscribed_to array' => [['subscribed_to' => ['2026-01-01']], 'mailpoet_subscribers_invalid_subscribed_to'],
      'created range reversed' => [['created_from' => '2026-02-01', 'created_to' => '2026-01-01'], 'mailpoet_subscribers_invalid_date_range'],
      'subscribed range reversed' => [['subscribed_from' => '2026-02-01', 'subscribed_to' => '2026-01-01'], 'mailpoet_subscribers_invalid_date_range'],
      'statusInclude unknown' => [['statusInclude' => 'nope'], 'mailpoet_subscribers_invalid_statusInclude'],
      'statusExclude unknown in array' => [['statusExclude' => ['bounced', 'nope']], 'mailpoet_subscribers_invalid_statusExclude'],
      'engagementScoreInclude unknown' => [['engagementScoreInclude' => 'great'], 'mailpoet_subscribers_invalid_engagementScoreInclude'],
      'engagementScoreExclude unknown' => [['engagementScoreExclude' => ['good', 'x']], 'mailpoet_subscribers_invalid_engagementScoreExclude'],
      'createdAtFrom garbage' => [['createdAtFrom' => 'not a date'], 'mailpoet_subscribers_invalid_createdAtFrom'],
      'createdAtTo array' => [['createdAtTo' => ['2026-01-01']], 'mailpoet_subscribers_invalid_createdAtTo'],
      'single status key' => [['status' => 'subscribed'], 'mailpoet_subscribers_invalid_filter'],
      'single source key' => [['source' => 'form'], 'mailpoet_subscribers_invalid_filter'],
    ];
  }
}
