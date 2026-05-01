<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Forms;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\Form as FormFactory;

require_once __DIR__ . '/../Test.php';

class FormsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/forms';

  /** @var FormsRepository */
  private $repository;

  /** @var int */
  private $editorUserId;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(FormsRepository::class);
    wp_set_current_user(1);
    $userId = wp_create_user('forms_editor_' . uniqid(), 'password', 'forms-editor-' . uniqid() . '@localhost.test');
    $this->assertIsNumeric($userId);
    $user = new \WP_User($userId);
    $user->add_role('editor');
    $this->editorUserId = $userId;
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
    is_multisite() ? wpmu_delete_user($this->editorUserId) : wp_delete_user($this->editorUserId);
  }

  public function testGetReturnsActiveForms(): void {
    $suffix = uniqid();
    (new FormFactory())->withName("Newsletter_{$suffix}")->create();
    (new FormFactory())->withName("Welcome_{$suffix}")->create();
    (new FormFactory())->withName("Trashed_{$suffix}")->withDeleted()->create();

    // Use a large per_page so paging never separates the items we just
    // created from meta.count.
    $data = $this->get(self::BASE_PATH, ['query' => ['per_page' => 100]]);
    $this->assertIsArray($data);
    $payload = $data['data'];
    $this->assertIsArray($payload);
    $items = $payload['items'];
    $this->assertIsArray($items);
    $names = array_column($items, 'name');
    $this->assertContains("Newsletter_{$suffix}", $names);
    $this->assertContains("Welcome_{$suffix}", $names);
    $this->assertNotContains("Trashed_{$suffix}", $names);
  }

  public function testGetExposesTrashGroup(): void {
    (new FormFactory())->withName('Trashed_' . uniqid())->withDeleted()->create();

    $data = $this->get(self::BASE_PATH, ['query' => ['group' => 'trash']]);
    $items = $data['data']['items'];
    $this->assertNotEmpty($items);
    foreach ($items as $item) {
      $this->assertNotNull($item['deleted_at']);
    }

    $groups = array_column($data['data']['groups'], 'count', 'name');
    $this->assertArrayHasKey('all', $groups);
    $this->assertArrayHasKey('trash', $groups);
    $this->assertGreaterThanOrEqual(1, $groups['trash']);
  }

  public function testGetSupportsPaginationAndSort(): void {
    $suffix = uniqid();
    (new FormFactory())->withName("zzz_b_form_{$suffix}")->create();
    (new FormFactory())->withName("zzz_a_form_{$suffix}")->create();
    (new FormFactory())->withName("zzz_c_form_{$suffix}")->create();

    $response = $this->get(self::BASE_PATH, ['query' => [
      'per_page' => 100,
      'page' => 1,
      'orderby' => 'name',
      'order' => 'asc',
    ]]);
    $names = array_values(array_filter(
      array_column($response['data']['items'], 'name'),
      static function (string $name) use ($suffix): bool {
        return strpos($name, $suffix) !== false;
      }
    ));
    $this->assertSame(
      ["zzz_a_form_{$suffix}", "zzz_b_form_{$suffix}", "zzz_c_form_{$suffix}"],
      $names
    );
  }

  public function testGetReturnsMetaConsistentWithItems(): void {
    $perPage = 7;
    $data = $this->get(self::BASE_PATH, ['query' => ['per_page' => $perPage]]);
    $expectedPages = $data['data']['meta']['count'] === 0
      ? 0
      : (int)ceil($data['data']['meta']['count'] / $perPage);
    $this->assertSame($expectedPages, $data['data']['meta']['pages']);
  }

  public function testGetRejectsUsersWithoutPermission(): void {
    wp_set_current_user($this->editorUserId);

    $data = $this->get(self::BASE_PATH);
    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testBulkActionTrashesForms(): void {
    $a = (new FormFactory())->withName('A')->create();
    $b = (new FormFactory())->withName('B')->create();
    $c = (new FormFactory())->withName('C')->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$a->getId(), (int)$b->getId()]],
    ]);
    $this->assertSame('trash', $data['data']['action']);
    $this->assertSame(2, $data['data']['count']);

    $trashedA = $this->repository->findOneById($a->getId());
    $this->assertInstanceOf(FormEntity::class, $trashedA);
    $this->assertNotNull($trashedA->getDeletedAt());

    $trashedB = $this->repository->findOneById($b->getId());
    $this->assertInstanceOf(FormEntity::class, $trashedB);
    $this->assertNotNull($trashedB->getDeletedAt());

    $untouched = $this->repository->findOneById($c->getId());
    $this->assertInstanceOf(FormEntity::class, $untouched);
    $this->assertNull($untouched->getDeletedAt());
  }

  public function testBulkActionRestoresForms(): void {
    $form = (new FormFactory())->withName('Restore me')->withDeleted()->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'restore', 'ids' => [(int)$form->getId()]],
    ]);
    $this->assertSame('restore', $data['data']['action']);
    $this->assertSame(1, $data['data']['count']);

    $restored = $this->repository->findOneById($form->getId());
    $this->assertInstanceOf(FormEntity::class, $restored);
    $this->assertNull($restored->getDeletedAt());
  }

  public function testBulkActionDeletesForms(): void {
    $form = (new FormFactory())->withName('Gone')->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'delete', 'ids' => [(int)$form->getId()]],
    ]);
    $this->assertSame('delete', $data['data']['action']);
    $this->assertSame(1, $data['data']['count']);

    $this->assertNull($this->repository->findOneById($form->getId()));
  }

  public function testBulkActionRejectsUnknownAction(): void {
    $form = (new FormFactory())->withName('Keep')->create();

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'publish', 'ids' => [(int)$form->getId()]],
    ]);
    $this->assertSame('mailpoet_forms_invalid_bulk_action', $data['code']);
    $this->assertSame(400, $data['data']['status']);
  }

  public function testBulkActionRejectsEmptyIds(): void {
    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => []],
    ]);
    $this->assertSame('mailpoet_forms_ids_required', $data['code']);
    $this->assertSame(400, $data['data']['status']);
  }

  public function testBulkActionRejectsGuest(): void {
    $form = (new FormFactory())->withName('Keep')->create();
    wp_set_current_user(0);

    $data = $this->post(self::BASE_PATH . '/bulk-action', [
      'json' => ['action' => 'trash', 'ids' => [(int)$form->getId()]],
    ]);
    $this->assertSame('rest_forbidden', $data['code']);

    $kept = $this->repository->findOneById($form->getId());
    $this->assertInstanceOf(FormEntity::class, $kept);
    $this->assertNull($kept->getDeletedAt());
  }
}
