<?php declare(strict_types = 1);

namespace MailPoet\Test\CustomFields;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\DynamicSegment as DynamicSegmentFactory;
use MailPoet\Test\DataFactories\Form as FormFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

class CustomFieldsRepositoryTest extends \MailPoetTest {
  /** @var CustomFieldsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(CustomFieldsRepository::class);
  }

  public function testListWithCountsReturnsCustomFieldsWithUsageCounts(): void {
    $subscriberA = (new SubscriberFactory())->withEmail('a@example.com')->create();
    $subscriberB = (new SubscriberFactory())->withEmail('b@example.com')->create();
    $deletedSubscriber = (new SubscriberFactory())
      ->withEmail('deleted@example.com')
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();

    $alpha = (new CustomFieldFactory())
      ->withName('Alpha')
      ->withParams(['label' => 'Alpha label'])
      ->withSubscriber($subscriberA->getId(), 'value')
      ->withSubscriber($subscriberB->getId(), 'value')
      ->withSubscriber($deletedSubscriber->getId(), 'deleted value')
      ->create();
    $beta = (new CustomFieldFactory())
      ->withName('Beta')
      ->withSubscriber($subscriberB->getId(), 'value')
      ->create();
    (new CustomFieldFactory())->withName('Gamma')->create();

    (new FormFactory())->withName('Alpha form')->withCustomField($alpha)->create();
    (new FormFactory())->withName('Both fields form')->withCustomField($alpha)->withCustomField($beta)->create();
    (new FormFactory())->withName('Deleted form')->withCustomField($alpha)->withDeleted()->create();

    (new DynamicSegmentFactory())->withCustomFieldFilter($alpha)->create();
    (new DynamicSegmentFactory())->withCustomFieldFilter($alpha)->create();
    (new DynamicSegmentFactory())->withCustomFieldFilter($beta)->withDeleted()->create();

    $result = $this->repository->listWithCounts();
    $this->assertSame(3, $result['total']);
    $items = $this->indexByName($result['items']);

    $this->assertSame('Alpha label', $items['Alpha']['label']);
    $this->assertSame(2, $items['Alpha']['subscribers_count']);
    $this->assertSame(2, $items['Alpha']['forms_count']);
    $this->assertSame(2, $items['Alpha']['dynamic_segments_count']);
    $this->assertSame(1, $items['Beta']['subscribers_count']);
    $this->assertSame(1, $items['Beta']['forms_count']);
    $this->assertSame(0, $items['Beta']['dynamic_segments_count']);
    $this->assertSame(0, $items['Gamma']['subscribers_count']);
  }

  public function testListWithCountsSupportsSearchSortAndPagination(): void {
    (new CustomFieldFactory())->withName('Customers')->create();
    (new CustomFieldFactory())->withName('Prospects')->create();
    (new CustomFieldFactory())->withName('VIP')->create();

    $result = $this->repository->listWithCounts(['search' => 'Pro']);
    $this->assertSame(1, $result['total']);
    $this->assertSame('Prospects', $result['items'][0]['name']);

    $desc = $this->repository->listWithCounts(['orderby' => 'name', 'order' => 'desc']);
    $this->assertSame(['VIP', 'Prospects', 'Customers'], array_column($desc['items'], 'name'));

    $page1 = $this->repository->listWithCounts(['per_page' => 2, 'page' => 1, 'orderby' => 'name', 'order' => 'asc']);
    $this->assertSame(3, $page1['total']);
    $this->assertCount(2, $page1['items']);
    $this->assertSame(['Customers', 'Prospects'], array_column($page1['items'], 'name'));
  }

  /**
   * @param array<int, array{name: string}> $items
   * @return array<string, array>
   */
  private function indexByName(array $items): array {
    return array_column($items, null, 'name');
  }
}
