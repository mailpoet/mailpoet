<?php declare(strict_types = 1);

namespace MailPoet\Test\Automation\Integrations\Core\Filters;

use MailPoet\Automation\Engine\Data\Filter;
use MailPoet\Automation\Integrations\Core\Filters\StringFilter;
use MailPoetUnitTest;
use stdClass;

class StringFilterTest extends MailPoetUnitTest {
  public function testItReturnsCorrectConfiguration(): void {
    $filter = new StringFilter();
    $this->assertSame('string', $filter->getFieldType());

    $this->assertSame([
      'is' => 'is',
      'is-not' => 'is not',
      'contains' => 'contains',
      'does-not-contain' => 'does not contain',
      'starts-with' => 'starts with',
      'ends-with' => 'ends with',
      'is-blank' => 'is blank',
      'is-not-blank' => 'is not blank',
      'matches-regex' => 'matches regex',
    ], $filter->getConditions());

    $this->assertSame([
      'type' => 'object',
      'properties' => [
        'value' => [
          'type' => 'string',
          'required' => true,
        ],
      ],
    ], $filter->getArgsSchema()->toArray());
  }

  public function testInvalidValues(): void {
    $this->assertNotMatches('is', '', null);
    $this->assertNotMatches('is', '', 123);
    $this->assertNotMatches('is', '', []);
    $this->assertNotMatches('is', '', [1, 2, 3, 'a', 'b', 'c']);
    $this->assertNotMatches('is', '', new stdClass());
    $this->assertNotMatches('is', '', true);
    $this->assertNotMatches('is', '', false);

    $this->assertNotMatches('is', null, '');
    $this->assertNotMatches('is', 123, '');
    $this->assertNotMatches('is', [], '');
    $this->assertNotMatches('is', [1, 2, 3, 'a', 'b', 'c'], '');
    $this->assertNotMatches('is', new stdClass(), '');
    $this->assertNotMatches('is', true, '');
    $this->assertNotMatches('is', false, '');
  }

  public function testIsCondition(): void {
    $this->assertMatches('is', '', '');
    $this->assertMatches('is', 'abc', 'abc');
    $this->assertNotMatches('is', 'abc', 'xyz');
    $this->assertNotMatches('is', 'abc', 'abcd');
  }

  public function testIsNotCondition(): void {
    $this->assertMatches('is-not', 'abc', 'xyz');
    $this->assertMatches('is-not', 'abc', 'abcd');
    $this->assertNotMatches('is-not', '', '');
    $this->assertNotMatches('is-not', 'abc', 'abc');
  }

  public function testContainsCondition(): void {
    $this->assertMatches('contains', '', '');
    $this->assertMatches('contains', '', 'abc');
    $this->assertMatches('contains', 'b', 'abc');
    $this->assertMatches('contains', 'bc', 'abc');
    $this->assertMatches('contains', 'abc', 'abc');
    $this->assertNotMatches('contains', 'abc', '');
    $this->assertNotMatches('contains', 'abc', 'b');
    $this->assertNotMatches('contains', 'abx', 'abc');
    $this->assertNotMatches('contains', 'abcd', 'abc');
  }

  public function testNotContainsCondition(): void {
    $this->assertMatches('does-not-contain', 'abc', '');
    $this->assertMatches('does-not-contain', 'abc', 'b');
    $this->assertMatches('does-not-contain', 'abx', 'abc');
    $this->assertMatches('does-not-contain', 'abcd', 'abc');
    $this->assertNotMatches('does-not-contain', '', '');
    $this->assertNotMatches('does-not-contain', '', 'abc');
    $this->assertNotMatches('does-not-contain', 'b', 'abc');
    $this->assertNotMatches('does-not-contain', 'bc', 'abc');
    $this->assertNotMatches('does-not-contain', 'abc', 'abc');
  }

  public function testStartsWithCondition(): void {
    $this->assertMatches('starts-with', '', 'abc');
    $this->assertMatches('starts-with', 'a', 'abc');
    $this->assertMatches('starts-with', 'ab', 'abc');
    $this->assertMatches('starts-with', 'abc', 'abc');
    $this->assertNotMatches('starts-with', 'abc', '');
    $this->assertNotMatches('starts-with', 'abc', 'a');
    $this->assertNotMatches('starts-with', 'abc', 'ab');
    $this->assertNotMatches('starts-with', 'abc', 'abx');
  }

  public function testEndsWithCondition(): void {
    $this->assertMatches('ends-with', '', 'abc');
    $this->assertMatches('ends-with', 'c', 'abc');
    $this->assertMatches('ends-with', 'bc', 'abc');
    $this->assertMatches('ends-with', 'abc', 'abc');
    $this->assertNotMatches('ends-with', 'abc', '');
    $this->assertNotMatches('ends-with', 'abc', 'c');
    $this->assertNotMatches('ends-with', 'abc', 'bc');
    $this->assertNotMatches('ends-with', 'abc', 'abx');
  }

  public function testIsBlankCondition(): void {
    $this->assertMatches('is-blank', '', '');
    $this->assertNotMatches('is-blank', '', ' ');
    $this->assertNotMatches('is-blank', '', 'a');
    $this->assertNotMatches('is-blank', '', 'abc');
  }

  public function testIsNotBlankCondition(): void {
    $this->assertMatches('is-not-blank', '', ' ');
    $this->assertMatches('is-not-blank', '', 'a');
    $this->assertMatches('is-not-blank', '', 'abc');
    $this->assertNotMatches('is-not-blank', '', '');
  }

  public function testMatchesRegex(): void {
    $this->assertMatches('matches-regex', '', '');
    $this->assertMatches('matches-regex', 'a', 'abc');
    $this->assertMatches('matches-regex', 'b', 'abc');
    $this->assertMatches('matches-regex', 'abc', 'abc');
    $this->assertMatches('matches-regex', '/a/', 'abc');
    $this->assertMatches('matches-regex', '/b/', 'abc');
    $this->assertMatches('matches-regex', '/abc/', 'abc');
    $this->assertMatches('matches-regex', '/^ab/', 'abc');
    $this->assertMatches('matches-regex', '/bc$/', 'abc');
    $this->assertMatches('matches-regex', '/^abc$/', 'abc');
    $this->assertMatches('matches-regex', '/^abc$/i', 'ABC');

    $this->assertNotMatches('matches-regex', 'a', '');
    $this->assertNotMatches('matches-regex', 'a', 'x');
    $this->assertNotMatches('matches-regex', 'abc', 'ab');
    $this->assertNotMatches('matches-regex', '/a/', 'x');
    $this->assertNotMatches('matches-regex', '/abc/', 'ab');
    $this->assertNotMatches('matches-regex', '/^ab/', 'bc');
    $this->assertNotMatches('matches-regex', '/bc$/', 'ab');
    $this->assertNotMatches('matches-regex', '/^abc$/', 'ab');
    $this->assertNotMatches('matches-regex', '/^abc$/', 'ABC');
  }

  public function testUnknownCondition(): void {
    $this->assertNotMatches('unknown', '', '');
    $this->assertNotMatches('unknown', 'abc', 'abc');
  }

  private function assertMatches(string $condition, $filterValue, $value): void {
    $this->assertTrue($this->matchesFilter($condition, $filterValue, $value));
  }

  private function assertNotMatches(string $condition, $filterValue, $value): void {
    $this->assertFalse($this->matchesFilter($condition, $filterValue, $value));
  }

  private function matchesFilter(string $condition, $filterValue, $value): bool {
    $filter = new StringFilter();
    return $filter->matches(new Filter('f1', 'string', '', $condition, ['value' => $filterValue]), $value);
  }
}
