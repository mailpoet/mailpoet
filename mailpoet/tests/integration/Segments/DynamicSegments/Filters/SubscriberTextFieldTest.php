<?php declare(strict_types = 1);

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Test\DataFactories\Subscriber;

class SubscriberTextFieldTest extends \MailPoetTest {

  /** @var SubscriberTextField */
  private $filter;

  public function _before(): void {
    $this->filter = $this->diContainer->get(SubscriberTextField::class);
  }

  public function testItWorksForFirstNameEquals(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('testFirst')
      ->withLastName('testLast')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberFirstName', 'is', 'testFirst', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'is', 'test2First', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'is', 'tes', []);
  }

  public function testItWorksForFirstNameNotEquals(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('testFirst')
      ->withLastName('testLast')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberFirstName', 'isNot', 'testFirst', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'isNot', 'test2First', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'isNot', 'tes', ['1@example.com', '2@example.com']);
  }

  public function testItWorksForFirstNameContains(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withFirstName('firstThree')
      ->withLastName('lastThree')
      ->create();
    $this->assertFilterReturnsEmails('subscriberFirstName', 'contains', 'test1F', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'contains', 'est1', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'contains', '1Fir', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'contains', 'test', ['1@example.com', '2@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'contains', 't', ['1@example.com', '2@example.com', '3@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'contains', 'q', []);
  }

  public function testItWorksWithLastNameEquals() {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('testFirst')
      ->withLastName('testLast')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberLastName', 'is', 'testLast', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'is', 'test2Last', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'is', 'tes', []);
    $this->assertFilterReturnsEmails('subscriberLastName', 'is', 'testFirst', []);
  }

  public function testItWorksForLastNameNotEquals(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('testFirst')
      ->withLastName('testLast')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberLastName', 'isNot', 'testLast', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'isNot', 'test2Last', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'isNot', 'test', ['1@example.com', '2@example.com']);
  }

  public function testItWorksForLastNameContains(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withFirstName('firstThree')
      ->withLastName('lastThree')
      ->create();
    $this->assertFilterReturnsEmails('subscriberLastName', 'contains', 'test1L', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'contains', 't1L', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'contains', '1Last', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'contains', 'est', ['1@example.com', '2@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'contains', 'ast', ['1@example.com', '2@example.com', '3@example.com']);
    $this->assertFilterReturnsEmails('subscriberLastName', 'contains', 'q', []);
  }

  public function testItWorksForEmailEquals(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('testFirst')
      ->withLastName('testLast')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberEmail', 'is', '1@example.com', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'is', '2@example.com', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'is', '3@example.com', []);
  }

  public function testItWorksForEmailNotEquals(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('testFirst')
      ->withLastName('testLast')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withFirstName('firstThree')
      ->withLastName('lastThree')
      ->create();
    $this->assertFilterReturnsEmails('subscriberEmail', 'isNot', '1@example.com', ['2@example.com', '3@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'isNot', '2@example.com', ['1@example.com', '3@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'isNot', '3@example.com', ['1@example.com', '2@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'isNot', '4@example.com', ['1@example.com', '2@example.com', '3@example.com']);
  }

  public function testItWorksForEmailContains(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('two@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    (new Subscriber())
      ->withEmail('3@example2.com')
      ->withFirstName('firstThree')
      ->withLastName('lastThree')
      ->create();
    $this->assertFilterReturnsEmails('subscriberEmail', 'contains', 'exam', ['1@example.com', 'two@example.com', '3@example2.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'contains', 'example.c', ['1@example.com', 'two@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'contains', 'wo', ['two@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'contains', 'co', ['1@example.com', 'two@example.com', '3@example2.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'contains', 'q', []);
  }

  public function testDoesNotContain(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberFirstName', 'notContains', '2', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'notContains', '1', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'notContains', 'q', ['1@example.com', '2@example.com']);
  }

  public function testStartsWith() {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberFirstName', 'startsWith', 'test1', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'startsWith', 'test2', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'startsWith', 'test', ['1@example.com', '2@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'startsWith', 'something', []);
  }

  public function testDoesNotStartWith(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberFirstName', 'notStartsWith', 'test2', ['1@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'notStartsWith', 'test1', ['2@example.com']);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'notStartsWith', 'test', []);
    $this->assertFilterReturnsEmails('subscriberFirstName', 'notStartsWith', 'bill', ['1@example.com', '2@example.com']);
  }

  public function testEndsWith(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    (new Subscriber())
      ->withEmail('3@example.co.uk')
      ->withFirstName('test3First')
      ->withLastName('test3Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberEmail', 'endsWith', 'example.com', ['1@example.com', '2@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'endsWith', '.co.uk', ['3@example.co.uk']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'endsWith', 'q', []);
  }

  public function testDoesNotEndWith(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test1First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    (new Subscriber())
      ->withEmail('3@example.co.uk')
      ->withFirstName('test3First')
      ->withLastName('test3Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberEmail', 'notEndsWith', 'example.com', ['3@example.co.uk']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'notEndsWith', '.co.uk', ['1@example.com', '2@example.com']);
    $this->assertFilterReturnsEmails('subscriberEmail', 'notEndsWith', 'q', ['1@example.com', '2@example.com', '3@example.co.uk']);
  }

  public function testContainsDoesNotBreakIfItIncludesPercentSymbol(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withFirstName('test%First')
      ->withLastName('test1Last')
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withFirstName('test2First')
      ->withLastName('test2Last')
      ->create();
    $this->assertFilterReturnsEmails('subscriberFirstName', 'contains', '%', ['1@example.com']);
  }

  private function assertFilterReturnsEmails(string $action, string $operator, string $value, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, $action, [
      'operator' => $operator,
      'value' => $value,
      'action' => $action,
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }
}
