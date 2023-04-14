<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Filters;

use DateTimeImmutable;
use DateTimeZone;
use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Integrations\Core\Filters\DateTimeFilter;
use MailPoet\InvalidStateException;
use MailPoetUnitTest;
use stdClass;

class DateTimeFilterTest extends MailPoetUnitTest {
  /** @var DateTimeZone */
  private $timezone;

  public function _before() {
    // let's test with a timezone far from UTC
    $this->timezone = new DateTimeZone('America/Los_Angeles');
  }

  public function testItReturnsCorrectConfiguration(): void {
    $filter = new DateTimeFilter($this->timezone);
    $this->assertSame('datetime', $filter->getFieldType());
    $this->assertSame([
      'before' => 'before',
      'after' => 'after',
      'on' => 'on',
      'not-on' => 'not on',
      'in-the-last' => 'in the last',
      'not-in-the-last' => 'not in the last',
      'is-set' => 'is set',
      'is-not-set' => 'is not set',
      'on-the-days-of-the-week' => 'on the day(s) of the week',
    ], $filter->getConditions());

    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'value' => [
          'oneOf' => [
            [
              'type' => 'string',
              'pattern' => '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$',
            ],
            [
              'type' => 'string',
              'pattern' => '^\d{4}-\d{2}-\d{2}$',
            ],
            [
              'type' => 'array',
              'items' => [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 6,
              ],
              'minItems' => 1,
            ],
            [
              'type' => 'object',
              'properties' => [
                'number' => [
                  'type' => 'integer',
                  'minimum' => 1,
                  'required' => true,
                ],
                'unit' => [
                  'type' => 'string',
                  'pattern' => '^days|weeks|months$',
                  'required' => true,
                ],
              ],
            ],
          ],
        ],
      ],
    ], $filter->getArgsSchema()->toArray());
  }

  public function testInvalidValues(): void {
    $this->assertNotMatches('before', '2023-04-26T16:42', null);
    $this->assertNotMatches('before', '2023-04-26T16:42', true);
    $this->assertNotMatches('before', '2023-04-26T16:42', 123);
    $this->assertNotMatches('before', '2023-04-26T16:42', 'abc');
    $this->assertNotMatches('before', '2023-04-26T16:42', []);
    $this->assertNotMatches('before', '2023-04-26T16:42', [1, 2, 3, 'a', 'b', 'c']);
    $this->assertNotMatches('before', '2023-04-26T16:42', new stdClass());

    $this->assertNotMatches('before', null, '2023-04-26T16:42+02:00');
    $this->assertNotMatches('before', true, '2023-04-26T16:42+02:00');
    $this->assertNotMatches('before', 123, '2023-04-26T16:42+02:00');
    $this->assertNotMatches('before', 'abc', '2023-04-26T16:42+02:00');
    $this->assertNotMatches('before', [], '2023-04-26T16:42+02:00');
    $this->assertNotMatches('before', [1, 2, 3, 'a', 'b', 'c'], '2023-04-26T16:42+02:00');
    $this->assertNotMatches('before', new stdClass(), '2023-04-26T16:42+02:00');
  }

  public function testBeforeCondition(): void {
    $filterValue = '2023-04-26T16:42:01';

    $this->assertMatches('before', $filterValue, $this->getDateTime('1900-01-01'));
    $this->assertMatches('before', $filterValue, $this->getDateTime('2023-04-25'));
    $this->assertMatches('before', $filterValue, $this->getDateTime('2023-04-26', '16:42:00', '+02:00'));

    $this->assertNotMatches('before', $filterValue, $this->getDateTime('2100-01-01'));
    $this->assertNotMatches('before', $filterValue, $this->getDateTime('2023-04-27'));
    $this->assertNotMatches('before', $filterValue, $this->getDateTime('2023-04-27', '16:42:00', '+01:00'));
  }

  public function testAfterCondition(): void {
    $filterValue = '2023-04-26T16:42:01';

    $this->assertMatches('after', $filterValue, $this->getDateTime('2100-01-01'));
    $this->assertMatches('after', $filterValue, $this->getDateTime('2023-04-27'));
    $this->assertMatches('after', $filterValue, $this->getDateTime('2023-04-27', '16:42:00', '+01:00'));

    $this->assertNotMatches('after', $filterValue, $this->getDateTime('1900-01-01'));
    $this->assertNotMatches('after', $filterValue, $this->getDateTime('2023-04-25'));
    $this->assertNotMatches('after', $filterValue, $this->getDateTime('2023-04-26', '16:42:00', '+02:00'));
  }

  public function testOnCondition(): void {
    $filterValue = '2023-04-26';

    $this->assertMatches('on', $filterValue, $this->getDateTime('2023-04-26'));
    $this->assertMatches('on', $filterValue, $this->getDateTime('2023-04-26', '00:00:00'));
    $this->assertMatches('on', $filterValue, $this->getDateTime('2023-04-26', '23:59:59'));
    $this->assertMatches('on', $filterValue, $this->getDateTime('2023-04-26', '16:42:01', '+02:00'));
    $this->assertMatches('on', $filterValue, $this->getDateTime('2023-04-26', '00:00:00', '-05:00'));
    $this->assertMatches('on', $filterValue, $this->getDateTime('2023-04-26', '23:59:59', '+05:00'));

    $this->assertNotMatches('on', $filterValue, $this->getDateTime('1900-01-01'));
    $this->assertNotMatches('on', $filterValue, $this->getDateTime('2023-04-25'));
    $this->assertNotMatches('on', $filterValue, $this->getDateTime('2023-04-27'));
    $this->assertNotMatches('on', $filterValue, $this->getDateTime('2100-01-01'));
    $this->assertNotMatches('on', $filterValue, $this->getDateTime('2023-04-26', '00:00:00', '+05:00'));
    $this->assertNotMatches('on', $filterValue, $this->getDateTime('2023-04-26', '23:59:59', '-05:00'));
  }

  public function testNotOnCondition(): void {
    $filterValue = '2023-04-26';

    $this->assertMatches('not-on', $filterValue, $this->getDateTime('1900-01-01'));
    $this->assertMatches('not-on', $filterValue, $this->getDateTime('2023-04-25'));
    $this->assertMatches('not-on', $filterValue, $this->getDateTime('2023-04-27'));
    $this->assertMatches('not-on', $filterValue, $this->getDateTime('2100-01-01'));
    $this->assertMatches('not-on', $filterValue, $this->getDateTime('2023-04-26', '00:00:00', '+05:00'));
    $this->assertMatches('not-on', $filterValue, $this->getDateTime('2023-04-26', '23:59:59', '-05:00'));

    $this->assertNotMatches('not-on', $filterValue, $this->getDateTime('2023-04-26'));
    $this->assertNotMatches('not-on', $filterValue, $this->getDateTime('2023-04-26', '00:00:00'));
    $this->assertNotMatches('not-on', $filterValue, $this->getDateTime('2023-04-26', '23:59:59'));
    $this->assertNotMatches('not-on', $filterValue, $this->getDateTime('2023-04-26', '16:42:01', '+02:00'));
    $this->assertNotMatches('not-on', $filterValue, $this->getDateTime('2023-04-26', '00:00:00', '-05:00'));
    $this->assertNotMatches('not-on', $filterValue, $this->getDateTime('2023-04-26', '23:59:59', '+05:00'));
  }

  public function testInTheLast(): void {
    $filterDays = ['unit' => 'days', 'number' => 3];
    $filterWeeks = ['unit' => 'weeks', 'number' => 3];
    $filterMonths = ['unit' => 'months', 'number' => 3];

    $now = new DateTimeImmutable('now', $this->timezone);

    $this->assertMatches('in-the-last', $filterDays, $now);
    $this->assertMatches('in-the-last', $filterDays, $now->modify('-1 day'));
    $this->assertMatches('in-the-last', $filterWeeks, $now->modify('-1 day'));
    $this->assertMatches('in-the-last', $filterMonths, $now->modify('-1 day'));
    $this->assertMatches('in-the-last', $filterWeeks, $now->modify('-1 week'));
    $this->assertMatches('in-the-last', $filterMonths, $now->modify('-1 week'));
    $this->assertMatches('in-the-last', $filterMonths, $now->modify('-1 month'));

    $this->assertNotMatches('in-the-last', $filterDays, $now->modify('+1 day'));
    $this->assertNotMatches('in-the-last', $filterWeeks, $now->modify('+1 day'));
    $this->assertNotMatches('in-the-last', $filterMonths, $now->modify('+1 day'));
    $this->assertNotMatches('in-the-last', $filterDays, $now->modify('-4 days'));
    $this->assertNotMatches('in-the-last', $filterDays, $now->modify('-1 week'));
    $this->assertNotMatches('in-the-last', $filterWeeks, $now->modify('-1 month'));
    $this->assertNotMatches('in-the-last', $filterMonths, $now->modify('-4 months'));
  }

  public function testNotInTheLast(): void {
    $filterDays = ['unit' => 'days', 'number' => 3];
    $filterWeeks = ['unit' => 'weeks', 'number' => 3];
    $filterMonths = ['unit' => 'months', 'number' => 3];

    $now = new DateTimeImmutable('now', $this->timezone);

    $this->assertMatches('not-in-the-last', $filterDays, $now->modify('+1 day'));
    $this->assertMatches('not-in-the-last', $filterWeeks, $now->modify('+1 day'));
    $this->assertMatches('not-in-the-last', $filterMonths, $now->modify('+1 day'));
    $this->assertMatches('not-in-the-last', $filterDays, $now->modify('-4 days'));
    $this->assertMatches('not-in-the-last', $filterDays, $now->modify('-1 week'));
    $this->assertMatches('not-in-the-last', $filterWeeks, $now->modify('-1 month'));
    $this->assertMatches('not-in-the-last', $filterMonths, $now->modify('-4 months'));

    $this->assertNotMatches('not-in-the-last', $filterDays, $now);
    $this->assertNotMatches('not-in-the-last', $filterDays, $now->modify('-1 day'));
    $this->assertNotMatches('not-in-the-last', $filterWeeks, $now->modify('-1 day'));
    $this->assertNotMatches('not-in-the-last', $filterMonths, $now->modify('-1 day'));
    $this->assertNotMatches('not-in-the-last', $filterWeeks, $now->modify('-1 week'));
    $this->assertNotMatches('not-in-the-last', $filterMonths, $now->modify('-1 week'));
    $this->assertNotMatches('not-in-the-last', $filterMonths, $now->modify('-1 month'));
  }

  public function testIsSet(): void {
    $now = new DateTimeImmutable('now', $this->timezone);

    $this->assertMatches('is-set', null, $now);
    $this->assertNotMatches('is-set', null, null);
  }

  public function testIsNotSet(): void {
    $now = new DateTimeImmutable('now', $this->timezone);

    $this->assertMatches('is-not-set', null, null);
    $this->assertNotMatches('is-not-set', null, $now);
  }

  public function testOnTheDaysOfTheWeek(): void {
    $filterValue = [1, 3, 5]; // Monday, Wednesday, Friday

    $this->assertMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-24')); // Monday
    $this->assertMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-26')); // Wednesday
    $this->assertMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-28')); // Friday
    $this->assertMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-24', '00:00:00', '-05:00'));
    $this->assertMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-24', '23:59:59', '+05:00'));

    $this->assertNotMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-23')); // Sunday
    $this->assertNotMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-25')); // Tuesday
    $this->assertNotMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-27')); // Thursday
    $this->assertNotMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-29')); // Saturday
    $this->assertNotMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-30')); // Sunday
    $this->assertNotMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-24', '00:00:00', '+05:00'));
    $this->assertNotMatches('on-the-days-of-the-week', $filterValue, $this->getDateTime('2023-04-24', '23:59:59', '-05:00'));
  }

  public function testUnknownCondition(): void {
    $value = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, '2023-04-26T16:42+02:00');
    $this->assertNotMatches('unknown', '2023-04-26T16:42', $value);
    $this->assertNotMatches('unknown', ['2023-04-26T16:42', '2023-04-26T17:42'], $value);
  }

  private function getDateTime(string $date, string $time = '12:00:00', string $tzOffset = '+00:00'): DateTimeImmutable {
    $datetime = DateTimeImmutable::createFromFormat(DateTimeImmutable::W3C, "{$date}T{$time}{$tzOffset}");
    if ($datetime === false) {
      throw new InvalidStateException('Invalid date format');
    }
    return $datetime;
  }

  private function assertMatches(string $condition, $filterValue, $value): void {
    $this->assertTrue($this->matchesFilter($condition, $filterValue, $value));
  }

  private function assertNotMatches(string $condition, $filterValue, $value): void {
    $this->assertFalse($this->matchesFilter($condition, $filterValue, $value));
  }

  private function matchesFilter(string $condition, $filterValue, $value): bool {
    $filter = new DateTimeFilter($this->timezone);
    return $filter->matches(new Filter('f1', 'datetime', '', $condition, ['value' => $filterValue]), $value);
  }
}
