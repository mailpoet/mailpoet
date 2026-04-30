<?php declare(strict_types = 1);

namespace MailPoet\Test\CustomFields;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\DynamicSegment as DynamicSegmentFactory;
use MailPoet\Test\DataFactories\Form as FormFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class CustomFieldsRepositoryTest extends \MailPoetTest {
  /** @var CustomFieldsRepository */
  private $repository;

  /** @var EntityManager */
  private $orm;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(CustomFieldsRepository::class);
    $this->orm = $this->diContainer->get(EntityManager::class);
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

  public function testListWithCountsSupportsTrashGroup(): void {
    $active = (new CustomFieldFactory())->withName('Active')->create();
    $trashed = (new CustomFieldFactory())->withName('Trashed')->create();

    $this->repository->bulkTrash([(int)$trashed->getId()]);

    $activeResult = $this->repository->listWithCounts();
    $this->assertSame(1, $activeResult['total']);
    $this->assertSame('Active', $activeResult['items'][0]['name']);
    $this->assertSame(1, $this->getGroupCount($activeResult['groups'], 'all'));
    $this->assertSame(1, $this->getGroupCount($activeResult['groups'], 'trash'));

    $trashResult = $this->repository->listWithCounts(['group' => 'trash']);
    $this->assertSame(1, $trashResult['total']);
    $this->assertSame('Trashed', $trashResult['items'][0]['name']);
    $this->assertNotNull($trashResult['items'][0]['deleted_at']);

    $activeIds = array_map('intval', array_column($this->repository->findAllAsArray(), 'id'));
    $this->assertContains((int)$active->getId(), $activeIds);
    $this->assertNotContains((int)$trashed->getId(), $activeIds);
  }

  public function testBulkTrashAndRestoreCustomFields(): void {
    $customField = (new CustomFieldFactory())->withName('Restorable')->create();

    $this->assertSame(1, $this->repository->bulkTrash([(int)$customField->getId()]));
    $trashedField = $this->repository->findOneById($customField->getId());
    $this->assertNotNull($trashedField);
    $this->assertNotNull($trashedField->getDeletedAt());

    $this->assertSame(1, $this->repository->bulkRestore([(int)$customField->getId()]));
    $restoredField = $this->repository->findOneById($customField->getId());
    $this->assertNotNull($restoredField);
    $this->assertNull($restoredField->getDeletedAt());
  }

  public function testBulkDeleteRemovesTrashedCustomFieldsAndUsageReferences(): void {
    $subscriber = (new SubscriberFactory())->withEmail('subscriber@example.com')->create();
    $alpha = (new CustomFieldFactory())
      ->withName('Alpha')
      ->withSubscriber($subscriber->getId(), 'value')
      ->create();
    $beta = (new CustomFieldFactory())
      ->withName('Beta')
      ->withSubscriber($subscriber->getId(), 'value')
      ->create();
    $gamma = (new CustomFieldFactory())->withName('Gamma')->create();

    $form = (new FormFactory())
      ->withName('Form with custom fields')
      ->withCustomField($alpha)
      ->withCustomField($beta)
      ->create();
    (new DynamicSegmentFactory())->withCustomFieldFilter($alpha)->create();
    (new DynamicSegmentFactory())->withCustomFieldFilter($beta)->create();

    $this->repository->bulkTrash([(int)$alpha->getId(), (int)$beta->getId()]);
    $deleted = $this->repository->bulkDelete([(int)$alpha->getId(), (int)$beta->getId(), (int)$gamma->getId()]);

    $this->assertSame(2, $deleted);
    $this->assertNull($this->repository->findOneById($alpha->getId()));
    $this->assertNull($this->repository->findOneById($beta->getId()));
    $this->assertNotNull($this->repository->findOneById($gamma->getId()));
    $this->assertSame(0, $this->countSubscriberCustomFieldValues([(int)$alpha->getId(), (int)$beta->getId()]));
    $this->assertSame(0, $this->countDynamicSegmentFilters([(int)$alpha->getId(), (int)$beta->getId()]));

    /** @var FormsRepository $formsRepository */
    $formsRepository = $this->diContainer->get(FormsRepository::class);
    $refreshedForm = $formsRepository->findOneById($form->getId());
    $this->assertInstanceOf(FormEntity::class, $refreshedForm);
    $formCustomFieldIds = array_map('intval', array_column($refreshedForm->getBlocksByTypes(FormEntity::FORM_FIELD_TYPES), 'id'));
    $this->assertNotContains((int)$alpha->getId(), $formCustomFieldIds);
    $this->assertNotContains((int)$beta->getId(), $formCustomFieldIds);
  }

  public function testEmptyTrashPermanentlyDeletesTrashedCustomFields(): void {
    $trashed = (new CustomFieldFactory())->withName('Trashed')->create();
    $active = (new CustomFieldFactory())->withName('Active')->create();
    $this->repository->bulkTrash([(int)$trashed->getId()]);

    $this->assertSame(1, $this->repository->emptyTrash());

    $this->assertNull($this->repository->findOneById($trashed->getId()));
    $this->assertNotNull($this->repository->findOneById($active->getId()));
  }

  /**
   * @param array<int, array{name: string}> $items
   * @return array<string, array>
   */
  private function indexByName(array $items): array {
    return array_column($items, null, 'name');
  }

  /**
   * @param array<int, array{name: string, count: int}> $groups
   */
  private function getGroupCount(array $groups, string $name): int {
    foreach ($groups as $group) {
      if ($group['name'] === $name) {
        return $group['count'];
      }
    }
    return 0;
  }

  /**
   * @param int[] $customFieldIds
   */
  private function countSubscriberCustomFieldValues(array $customFieldIds): int {
    return count($this->orm->getRepository(SubscriberCustomFieldEntity::class)->findBy(['customField' => $customFieldIds]));
  }

  /**
   * @param int[] $customFieldIds
   */
  private function countDynamicSegmentFilters(array $customFieldIds): int {
    $filters = $this->orm->getRepository(DynamicSegmentFilterEntity::class)->findAll();
    $count = 0;
    foreach ($filters as $filter) {
      $customFieldId = $filter->getFilterData()->getParam('custom_field_id');
      if ((is_int($customFieldId) || is_string($customFieldId)) && in_array((int)$customFieldId, $customFieldIds, true)) {
        $count++;
      }
    }
    return $count;
  }
}
