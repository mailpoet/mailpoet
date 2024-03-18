<?php declare(strict_types = 1);

namespace integration\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberDateField;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\CarbonImmutable;

class SubscriberDateFieldTest extends \MailPoetTest {

  /** @var SubscriberDateField */
  private $filter;

  public function _before() {
    parent::_before();
    $this->filter = $this->diContainer->get(SubscriberDateField::class);
  }

  public function testItWorksForBefore(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastEngagementAt(new CarbonImmutable('2023-07-11'))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastEngagementAt(new CarbonImmutable('2023-07-12'))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastEngagementAt(new CarbonImmutable('2023-07-13'))
      ->create();
    (new Subscriber())
      ->withEmail('4@example.com')
      ->withLastEngagementAt(new CarbonImmutable('2023-07-14'))
      ->create();
    (new Subscriber())
      ->withEmail('5@example.com')
      ->withLastEngagementAt(new CarbonImmutable('2023-07-15'))
      ->create();
    $this->assertFilterReturnsEmails('lastEngagementDate', 'before', '2023-07-13', ['1@example.com', '2@example.com']);
  }

  public function testItWorksForAfter(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastPurchaseAt(new CarbonImmutable('2023-07-11'))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastPurchaseAt(new CarbonImmutable('2023-07-12'))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastPurchaseAt(new CarbonImmutable('2023-07-13'))
      ->create();
    (new Subscriber())
      ->withEmail('4@example.com')
      ->withLastPurchaseAt(new CarbonImmutable('2023-07-14'))
      ->create();
    (new Subscriber())
      ->withEmail('5@example.com')
      ->withLastPurchaseAt(new CarbonImmutable('2023-07-15'))
      ->create();
    $this->assertFilterReturnsEmails('lastPurchaseDate', 'after', '2023-07-13', ['4@example.com', '5@example.com']);
  }

  public function testItWorksForOn(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastClickAt(new CarbonImmutable('2023-07-11'))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastClickAt(new CarbonImmutable('2023-07-12'))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastClickAt(new CarbonImmutable('2023-07-13'))
      ->create();
    $this->assertFilterReturnsEmails('lastClickDate', 'on', '2023-07-12', ['2@example.com']);
  }

  public function testItWorksForOnOrBefore(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastOpenAt(new CarbonImmutable('2023-07-11'))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastOpenAt(new CarbonImmutable('2023-07-12'))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastOpenAt(new CarbonImmutable('2023-07-13'))
      ->create();
    (new Subscriber())
      ->withEmail('4@example.com')
      ->withLastOpenAt(new CarbonImmutable('2023-07-14'))
      ->create();
    (new Subscriber())
      ->withEmail('5@example.com')
      ->withLastOpenAt(new CarbonImmutable('2023-07-15'))
      ->create();
    $this->assertFilterReturnsEmails('lastOpenDate', 'onOrBefore', '2023-07-13', ['1@example.com', '2@example.com', '3@example.com']);
  }

  public function testItWorksForOnOrAfter(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastPageViewAt(new CarbonImmutable('2023-07-11'))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastPageViewAt(new CarbonImmutable('2023-07-12'))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastPageViewAt(new CarbonImmutable('2023-07-13'))
      ->create();
    (new Subscriber())
      ->withEmail('4@example.com')
      ->withLastPageViewAt(new CarbonImmutable('2023-07-14'))
      ->create();
    (new Subscriber())
      ->withEmail('5@example.com')
      ->withLastPageViewAt(new CarbonImmutable('2023-07-15'))
      ->create();
    $this->assertFilterReturnsEmails('lastPageViewDate', 'onOrAfter', '2023-07-13', ['3@example.com', '4@example.com', '5@example.com']);
  }

  public function testItWorksForNotOn(): void {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastClickAt(new CarbonImmutable('2023-07-11'))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastClickAt(new CarbonImmutable('2023-07-12'))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastClickAt(new CarbonImmutable('2023-07-13'))
      ->create();
    $this->assertFilterReturnsEmails('lastClickDate', 'notOn', '2023-07-12', ['1@example.com', '3@example.com']);
  }

  public function testItWorksForInTheLast() {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastSendingAt((new CarbonImmutable())->subDays(4))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastSendingAt((new CarbonImmutable())->subDays(3))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastSendingAt((new CarbonImmutable())->subDays(2))
      ->create();
    (new Subscriber())
      ->withEmail('4@example.com')
      ->withLastSendingAt((new CarbonImmutable())->subDay())
      ->create();
    (new Subscriber())
      ->withEmail('5@example.com')
      ->withLastSendingAt((new CarbonImmutable()))
      ->create();
    $this->assertFilterReturnsEmails('lastSendingDate', 'inTheLast', '3', ['3@example.com', '4@example.com', '5@example.com']);
  }

  public function testItWorksForNotInTheLast() {
    (new Subscriber())
      ->withEmail('1@example.com')
      ->withLastSubscribedAt((new CarbonImmutable())->subDays(4))
      ->create();
    (new Subscriber())
      ->withEmail('2@example.com')
      ->withLastSubscribedAt((new CarbonImmutable())->subDays(3))
      ->create();
    (new Subscriber())
      ->withEmail('3@example.com')
      ->withLastSubscribedAt((new CarbonImmutable())->subDays(2))
      ->create();
    (new Subscriber())
      ->withEmail('4@example.com')
      ->withLastSubscribedAt((new CarbonImmutable())->subDay())
      ->create();
    (new Subscriber())
      ->withEmail('5@example.com')
      ->withLastSubscribedAt((new CarbonImmutable()))
      ->create();
    $this->assertFilterReturnsEmails('subscribedDate', 'notInTheLast', '3', ['1@example.com', '2@example.com']);
  }

  private function assertFilterReturnsEmails(string $action, string $operator, string $value, array $expectedEmails): void {
    $filterData = new DynamicSegmentFilterData(DynamicSegmentFilterData::TYPE_USER_ROLE, $action, [
      'operator' => $operator,
      'value' => $value,
    ]);
    $emails = $this->tester->getSubscriberEmailsMatchingDynamicFilter($filterData, $this->filter);
    $this->assertEqualsCanonicalizing($expectedEmails, $emails);
  }
}
