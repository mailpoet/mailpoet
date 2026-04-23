<?php declare(strict_types = 1);

namespace MailPoet\Test\Tags;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Tags\TagRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;

class TagRepositoryTest extends \MailPoetTest {
  /** @var TagRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(TagRepository::class);
  }

  public function testListWithCountsReturnsTagsWithSubscriberCounts(): void {
    $tagA = (new TagFactory())->withName('Alpha')->create();
    $tagB = (new TagFactory())->withName('Beta')->create();
    (new TagFactory())->withName('Gamma')->create();

    (new SubscriberFactory())->withEmail('a@example.com')->withTags([$tagA])->create();
    (new SubscriberFactory())->withEmail('b@example.com')->withTags([$tagA, $tagB])->create();

    $result = $this->repository->listWithCounts();
    $this->assertSame(3, $result['total']);
    $items = $this->indexByName($result['items']);

    $this->assertSame(2, $items['Alpha']['subscribers_count']);
    $this->assertSame(1, $items['Beta']['subscribers_count']);
    $this->assertSame(0, $items['Gamma']['subscribers_count']);
  }

  public function testListWithCountsIgnoresSoftDeletedSubscribers(): void {
    $tag = (new TagFactory())->withName('Tag1')->create();

    (new SubscriberFactory())->withEmail('active@example.com')->withTags([$tag])->create();
    (new SubscriberFactory())
      ->withEmail('deleted@example.com')
      ->withTags([$tag])
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();

    $result = $this->repository->listWithCounts();
    $items = $this->indexByName($result['items']);
    $this->assertSame(1, $items['Tag1']['subscribers_count']);
  }

  public function testListWithCountsSupportsSearch(): void {
    (new TagFactory())->withName('Customers')->create();
    (new TagFactory())->withName('Prospects')->withDescription('customer prospects')->create();
    (new TagFactory())->withName('VIP')->create();

    $result = $this->repository->listWithCounts(['search' => 'custom']);
    $this->assertSame(2, $result['total']);
    $names = array_column($result['items'], 'name');
    $this->assertContains('Customers', $names);
    $this->assertContains('Prospects', $names);
  }

  public function testListWithCountsSupportsSortByName(): void {
    (new TagFactory())->withName('Zulu')->create();
    (new TagFactory())->withName('Alpha')->create();
    (new TagFactory())->withName('Mike')->create();

    $asc = $this->repository->listWithCounts(['orderby' => 'name', 'order' => 'asc']);
    $this->assertSame(['Alpha', 'Mike', 'Zulu'], array_column($asc['items'], 'name'));

    $desc = $this->repository->listWithCounts(['orderby' => 'name', 'order' => 'desc']);
    $this->assertSame(['Zulu', 'Mike', 'Alpha'], array_column($desc['items'], 'name'));
  }

  public function testListWithCountsSupportsSortBySubscribersCount(): void {
    $empty = (new TagFactory())->withName('Empty')->create();
    $one = (new TagFactory())->withName('One')->create();
    $two = (new TagFactory())->withName('Two')->create();
    unset($empty);

    (new SubscriberFactory())->withEmail('s1@example.com')->withTags([$one])->create();
    (new SubscriberFactory())->withEmail('s2@example.com')->withTags([$two])->create();
    (new SubscriberFactory())->withEmail('s3@example.com')->withTags([$two])->create();

    $result = $this->repository->listWithCounts(['orderby' => 'subscribers_count', 'order' => 'desc']);
    $this->assertSame(['Two', 'One', 'Empty'], array_column($result['items'], 'name'));
  }

  public function testListWithCountsPaginates(): void {
    for ($i = 1; $i <= 5; $i++) {
      (new TagFactory())->withName(sprintf('Tag-%02d', $i))->create();
    }

    $page1 = $this->repository->listWithCounts(['per_page' => 2, 'page' => 1, 'orderby' => 'name', 'order' => 'asc']);
    $this->assertSame(5, $page1['total']);
    $this->assertCount(2, $page1['items']);
    $this->assertSame(['Tag-01', 'Tag-02'], array_column($page1['items'], 'name'));

    $page3 = $this->repository->listWithCounts(['per_page' => 2, 'page' => 3, 'orderby' => 'name', 'order' => 'asc']);
    $this->assertCount(1, $page3['items']);
    $this->assertSame(['Tag-05'], array_column($page3['items'], 'name'));
  }

  public function testBulkDeleteRemovesTagsAndSubscriberLinks(): void {
    $tagA = (new TagFactory())->withName('A')->create();
    $tagB = (new TagFactory())->withName('B')->create();
    $tagC = (new TagFactory())->withName('C')->create();

    $subscriber = (new SubscriberFactory())->withEmail('link@example.com')->withTags([$tagA, $tagB, $tagC])->create();

    $deleted = $this->repository->bulkDelete([(int)$tagA->getId(), (int)$tagB->getId()]);
    $this->assertSame(2, $deleted);

    $this->entityManager->clear();
    $this->assertNull($this->repository->findOneById($tagA->getId()));
    $this->assertNull($this->repository->findOneById($tagB->getId()));
    $this->assertNotNull($this->repository->findOneById($tagC->getId()));

    $refreshed = $this->entityManager->find(SubscriberEntity::class, $subscriber->getId());
    $this->assertInstanceOf(SubscriberEntity::class, $refreshed);
    $remainingTagIds = [];
    foreach ($refreshed->getSubscriberTags() as $subscriberTag) {
      $tag = $subscriberTag->getTag();
      if ($tag instanceof TagEntity) {
        $remainingTagIds[] = (int)$tag->getId();
      }
    }
    $this->assertSame([(int)$tagC->getId()], $remainingTagIds);
  }

  public function testBulkDeleteReturnsZeroForEmptyInput(): void {
    $this->assertSame(0, $this->repository->bulkDelete([]));
    $this->assertSame(0, $this->repository->bulkDelete([0, null]));
  }

  public function testGetSubscribersCountCountsOnlyNonDeleted(): void {
    $tag = (new TagFactory())->withName('Counted')->create();

    (new SubscriberFactory())->withEmail('alive1@example.com')->withTags([$tag])->create();
    (new SubscriberFactory())->withEmail('alive2@example.com')->withTags([$tag])->create();
    (new SubscriberFactory())
      ->withEmail('removed@example.com')
      ->withTags([$tag])
      ->withDeletedAt(new \DateTimeImmutable())
      ->create();

    $this->assertSame(2, $this->repository->getSubscribersCount((int)$tag->getId()));
  }

  /**
   * @param array<int, array{name: string, subscribers_count: int}> $items
   * @return array<string, array{name: string, subscribers_count: int}>
   */
  private function indexByName(array $items): array {
    $indexed = [];
    foreach ($items as $item) {
      $indexed[$item['name']] = $item;
    }
    return $indexed;
  }
}
