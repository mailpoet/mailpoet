<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\CustomFields;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

require_once __DIR__ . '/../Test.php';

class CustomFieldsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/custom-fields';

  /** @var CustomFieldsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(CustomFieldsRepository::class);
    wp_set_current_user(1);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testGetReturnsCustomFieldsWithCounts(): void {
    $subscriber = (new SubscriberFactory())->withEmail('x@example.com')->create();
    (new CustomFieldFactory())
      ->withName('Favorite color')
      ->withParams(['label' => 'Color'])
      ->withSubscriber($subscriber->getId(), 'blue')
      ->create();
    (new CustomFieldFactory())->withName('Birthday')->create();

    $payload = $this->getListingPayload($this->get(self::BASE_PATH));
    $items = $payload['items'];
    $this->assertCount(2, $items);
    $this->assertSame(2, $payload['meta']['count']);
    $this->assertSame(1, $payload['meta']['pages']);

    $byName = array_column($items, null, 'name');
    $favoriteColor = $byName['Favorite color'] ?? null;
    $birthday = $byName['Birthday'] ?? null;
    $this->assertIsArray($favoriteColor);
    $this->assertIsArray($birthday);
    $this->assertSame('Color', $favoriteColor['label']);
    $this->assertSame(1, $favoriteColor['subscribers_count']);
    $this->assertSame(0, $birthday['subscribers_count']);
    $this->assertSame(2, $this->getGroupCount($payload['groups'], 'all'));
    $this->assertSame(0, $this->getGroupCount($payload['groups'], 'trash'));
  }

  public function testGetSupportsSearchAndPagination(): void {
    (new CustomFieldFactory())->withName('Customers')->create();
    (new CustomFieldFactory())->withName('Prospective')->create();
    (new CustomFieldFactory())->withName('VIP')->create();

    $data = $this->getListingPayload($this->get(self::BASE_PATH, ['query' => ['search' => 'Pro']]));
    $this->assertSame(1, $data['meta']['count']);
    $this->assertSame('Prospective', $data['items'][0]['name']);

    $page1 = $this->getListingPayload($this->get(self::BASE_PATH, ['query' => ['per_page' => 2, 'page' => 1, 'orderby' => 'name', 'order' => 'asc']]));
    $this->assertCount(2, $page1['items']);
    $this->assertSame(2, $page1['meta']['pages']);
  }

  public function testGetRejectsGuest(): void {
    wp_set_current_user(0);
    $data = $this->get(self::BASE_PATH);
    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testPostCreatesCustomField(): void {
    $data = $this->post(self::BASE_PATH, [
      'json' => [
        'name' => 'Favorite trail',
        'type' => 'select',
        'params' => [
          'label' => 'Favorite trail label',
          'required' => '1',
          'values' => [
            ['value' => 'Alpine Loop'],
            ['value' => 'River Walk', 'is_checked' => '1'],
          ],
        ],
      ],
    ]);

    $customField = $data['data'];
    $this->assertSame('Favorite trail', $customField['name']);
    $this->assertSame('select', $customField['type']);
    $this->assertSame('Favorite trail label', $customField['label']);
    $this->assertSame('1', $customField['params']['required']);
    $this->assertSame('River Walk', $customField['params']['values'][1]['value']);

    $entity = $this->repository->findOneById($customField['id']);
    $this->assertNotNull($entity);
    $this->assertSame('Favorite trail', $entity->getName());
  }

  public function testPostCreatesDateCustomField(): void {
    $data = $this->post(self::BASE_PATH, [
      'json' => [
        'name' => 'Anniversary',
        'type' => 'date',
        'params' => [
          'date_type' => 'year_month',
          'date_format' => 'MM/YYYY',
          'is_default_today' => '1',
        ],
      ],
    ]);

    $customField = $data['data'];
    $this->assertSame('date', $customField['type']);
    $this->assertSame('year_month', $customField['params']['date_type']);
    $this->assertSame('MM/YYYY', $customField['params']['date_format']);
    $this->assertSame('1', $customField['params']['is_default_today']);
  }

  public function testPostRejectsDuplicateCustomFieldName(): void {
    (new CustomFieldFactory())->withName('Duplicate')->create();

    $data = $this->post(self::BASE_PATH, [
      'json' => [
        'name' => 'Duplicate',
        'type' => 'text',
        'params' => [
          'label' => 'Duplicate',
        ],
      ],
    ]);

    $this->assertSame('mailpoet_custom_fields_duplicate', $data['code']);
    $this->assertSame(409, $data['data']['status']);
  }

  public function testPostRejectsInvalidCustomFieldData(): void {
    $data = $this->post(self::BASE_PATH, [
      'json' => [
        'name' => 'Bad field',
        'type' => 'select',
        'params' => [
          'values' => [],
        ],
      ],
    ]);

    $this->assertSame('mailpoet_custom_fields_invalid_data', $data['code']);
    $this->assertSame(400, $data['data']['status']);
  }

  public function testPostRejectsGuest(): void {
    wp_set_current_user(0);

    $data = $this->post(self::BASE_PATH, [
      'json' => [
        'name' => 'Guest field',
        'type' => 'text',
      ],
    ]);

    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testPutUpdatesCustomField(): void {
    $field = (new CustomFieldFactory())
      ->withName('Favorite trail')
      ->withType('select')
      ->withParams([
        'label' => 'Favorite trail',
        'values' => [
          ['value' => 'Alpine Loop'],
          ['value' => 'River Walk'],
        ],
      ])
      ->create();

    $data = $this->put(self::BASE_PATH . '/' . $field->getId(), [
      'json' => [
        'name' => 'Preferred trail',
        'type' => 'radio',
        'params' => [
          'label' => 'Preferred trail label',
          'required' => '1',
          'values' => [
            ['value' => 'Forest Path', 'is_checked' => '1'],
            ['value' => 'Mountain Pass'],
          ],
        ],
      ],
    ]);

    $customField = $data['data'];
    $this->assertSame((int)$field->getId(), $customField['id']);
    $this->assertSame('Preferred trail', $customField['name']);
    $this->assertSame('radio', $customField['type']);
    $this->assertSame('Preferred trail label', $customField['label']);
    $this->assertSame('Forest Path', $customField['params']['values'][0]['value']);

    $entity = $this->repository->findOneById($field->getId());
    $this->assertNotNull($entity);
    $this->assertSame('Preferred trail', $entity->getName());
    $this->assertSame('radio', $entity->getType());
  }

  public function testPutRejectsDuplicateCustomFieldName(): void {
    (new CustomFieldFactory())->withName('Existing')->create();
    $field = (new CustomFieldFactory())->withName('Editable')->create();

    $data = $this->put(self::BASE_PATH . '/' . $field->getId(), [
      'json' => [
        'name' => 'Existing',
        'type' => 'text',
        'params' => [
          'label' => 'Existing',
        ],
      ],
    ]);

    $this->assertSame('mailpoet_custom_fields_duplicate', $data['code']);
    $this->assertSame(409, $data['data']['status']);
  }

  public function testPutRejectsMissingCustomField(): void {
    $data = $this->put(self::BASE_PATH . '/999999', [
      'json' => [
        'name' => 'Missing',
        'type' => 'text',
        'params' => [
          'label' => 'Missing',
        ],
      ],
    ]);

    $this->assertSame('mailpoet_custom_fields_not_found', $data['code']);
    $this->assertSame(404, $data['data']['status']);
  }

  public function testPutRejectsGuest(): void {
    $field = (new CustomFieldFactory())->withName('Keep')->create();
    wp_set_current_user(0);

    $data = $this->put(self::BASE_PATH . '/' . $field->getId(), [
      'json' => [
        'name' => 'Guest update',
        'type' => 'text',
      ],
    ]);

    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testGetSupportsTrashGroup(): void {
    $active = (new CustomFieldFactory())->withName('Active')->create();
    $trashed = (new CustomFieldFactory())->withName('Trashed')->create();
    $this->repository->bulkTrash([(int)$trashed->getId()]);

    $activePayload = $this->getListingPayload($this->get(self::BASE_PATH));
    $this->assertSame(1, $activePayload['meta']['count']);
    $this->assertSame((int)$active->getId(), $activePayload['items'][0]['id']);

    $trashPayload = $this->getListingPayload($this->get(self::BASE_PATH, ['query' => ['group' => 'trash']]));
    $this->assertSame(1, $trashPayload['meta']['count']);
    $this->assertSame((int)$trashed->getId(), $trashPayload['items'][0]['id']);
    $this->assertNotNull($trashPayload['items'][0]['deleted_at']);
  }

  public function testBulkActionTrashesRestoresAndDeletesCustomFields(): void {
    $a = (new CustomFieldFactory())->withName('A')->create();
    $b = (new CustomFieldFactory())->withName('B')->create();
    $c = (new CustomFieldFactory())->withName('C')->create();

    $trash = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$a->getId(), (int)$b->getId()]],
    ]);
    $this->assertSame('trash', $trash['data']['action']);
    $this->assertSame(2, $trash['data']['count']);
    $trashedA = $this->repository->findOneById($a->getId());
    $trashedB = $this->repository->findOneById($b->getId());
    $this->assertNotNull($trashedA);
    $this->assertNotNull($trashedB);
    $this->assertNotNull($trashedA->getDeletedAt());
    $this->assertNotNull($trashedB->getDeletedAt());

    $restore = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'restore', 'ids' => [(int)$b->getId()]],
    ]);
    $this->assertSame('restore', $restore['data']['action']);
    $this->assertSame(1, $restore['data']['count']);
    $restoredB = $this->repository->findOneById($b->getId());
    $this->assertNotNull($restoredB);
    $this->assertNull($restoredB->getDeletedAt());

    $delete = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'delete', 'ids' => [(int)$a->getId(), (int)$b->getId()]],
    ]);
    $this->assertSame('delete', $delete['data']['action']);
    $this->assertSame(1, $delete['data']['count']);

    $this->assertNull($this->repository->findOneById($a->getId()));
    $this->assertNotNull($this->repository->findOneById($b->getId()));
    $this->assertNotNull($this->repository->findOneById($c->getId()));
  }

  public function testEmptyTrashDeletesTrashedCustomFields(): void {
    $a = (new CustomFieldFactory())->withName('A')->create();
    $b = (new CustomFieldFactory())->withName('B')->create();
    $c = (new CustomFieldFactory())->withName('C')->create();
    $this->repository->bulkTrash([(int)$a->getId(), (int)$b->getId()]);

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'empty_trash'],
    ]);
    $this->assertSame('empty_trash', $data['data']['action']);
    $this->assertSame(2, $data['data']['count']);

    $this->assertNull($this->repository->findOneById($a->getId()));
    $this->assertNull($this->repository->findOneById($b->getId()));
    $this->assertNotNull($this->repository->findOneById($c->getId()));
  }

  public function testBulkActionRejectsInvalidPayload(): void {
    $invalidAction = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'unknown', 'ids' => [1]],
    ]);
    $this->assertSame('mailpoet_custom_fields_invalid_bulk_action', $invalidAction['code']);

    $missingIds = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash'],
    ]);
    $this->assertSame('mailpoet_custom_fields_ids_required', $missingIds['code']);
  }

  public function testBulkActionRejectsGuest(): void {
    $customField = (new CustomFieldFactory())->withName('Keep')->create();
    wp_set_current_user(0);

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$customField->getId()]],
    ]);
    $this->assertSame('rest_forbidden', $data['code']);

    $field = $this->repository->findOneById($customField->getId());
    $this->assertNotNull($field);
    $this->assertNull($field->getDeletedAt());
  }

  /**
   * @param mixed $response
   * @return array{items: array<int, array<int|string, mixed>>, meta: array<int|string, mixed>, groups: array<int, array<int|string, mixed>>}
   */
  private function getListingPayload($response): array {
    $this->assertIsArray($response);
    $data = $response['data'] ?? null;
    $this->assertIsArray($data);
    $items = $data['items'] ?? null;
    $meta = $data['meta'] ?? null;
    $groups = $data['groups'] ?? null;
    $this->assertIsArray($items);
    $this->assertIsArray($meta);
    $this->assertIsArray($groups);

    $normalizedItems = [];
    foreach ($items as $item) {
      $this->assertIsArray($item);
      $normalizedItems[] = $item;
    }
    $normalizedGroups = [];
    foreach ($groups as $group) {
      $this->assertIsArray($group);
      $normalizedGroups[] = $group;
    }

    return [
      'items' => $normalizedItems,
      'meta' => $meta,
      'groups' => $normalizedGroups,
    ];
  }

  /**
   * @param array<int, array<int|string, mixed>> $groups
   */
  private function getGroupCount(array $groups, string $name): int {
    foreach ($groups as $group) {
      if (($group['name'] ?? null) === $name) {
        $count = $group['count'] ?? null;
        return is_int($count) || is_string($count) ? (int)$count : 0;
      }
    }
    return 0;
  }
}
